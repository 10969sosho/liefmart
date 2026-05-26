<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResyncAllInvoices extends Command
{
    protected $signature = 'finance:resync-all-invoices {--force : Force regenerate even if correct}';
    protected $description = 'Resync all invoice numbers to match order dates and fix duplicates';

    public function handle()
    {
        $this->info("Scanning invoices to reset all counters and regenerate sequences (Monthly Reset Mode)...");

        if (!$this->option('force')) {
            $this->error("You must use --force to run this command as it rewrites all invoice numbers.");
            return;
        }

        if (!$this->confirm("WARNING: This will rewrite ALL invoice numbers to start from 1 for EACH MONTH. Do you want to proceed?", true)) {
            return;
        }

        // 0. RESET COUNTERS
        $this->warn("Truncating InvoiceSequence table...");
        InvoiceSequence::truncate();

        // 1. Collect all invoices
        $invoices = collect();
        
        $platforms = [
            'Tiktok' => \App\Models\TiktokFinancialTransaction::class,
            'Shopee' => \App\Models\ShopeeFinancialTransaction::class,
            'Shopee2' => \App\Models\Shopee2FinancialTransaction::class,
            'Offline' => \App\Models\FinanceOffline::class,
        ];

        foreach ($platforms as $name => $modelClass) {
            if (!class_exists($modelClass)) continue;
            
            $this->info("Scanning $name...");
            
            // Load all records to memory (might be heavy, but safer for sorting)
            // Using chunking to populate collection
            $modelClass::chunk(1000, function($records) use ($invoices, $name, $modelClass) {
                foreach ($records as $record) {
                    $invNo = $name === 'Offline' ? $record->invoice_number : $record->no_invoice;
                    if (!$invNo) continue;

                    // Determine Order Date
                    $orderDate = null;
                    if ($name === 'Offline') {
                        // Logic for offline date
                        $bk = $record->barangKeluarItems()->first();
                        if ($bk && $bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                            $orderDate = $bk->offlineSaleItem->offlineSale->sale_date;
                        } else {
                            $orderDate = $record->tanggal_invoice; 
                        }
                    } else {
                        $orderDate = $record->tanggal_order;
                    }
                    
                    if (!$orderDate) continue;

                    $invoices->push([
                        'platform' => $name,
                        'id' => $record->id,
                        'invoice_no' => $invNo,
                        'order_date' => Carbon::parse($orderDate),
                        'model' => $modelClass
                    ]);
                }
            });
        }
        
        $this->info("Total Invoices Found: " . $invoices->count());

        // 2. Parse and Sort
        $this->info("Parsing metadata and sorting...");
        
        $processedInvoices = $invoices->map(function($inv) {
            // Parse Category, SalesType, TaxStatus from OLD invoice string
            $parts = explode('/', $inv['invoice_no']);
            
            $category = InvoiceSequence::CATEGORY_SKINCARE; // Default
            $salesType = InvoiceSequence::SALES_ONLINE; // Default
            $taxStatus = InvoiceSequence::TAX_PKP; // Default
            
            if (count($parts) >= 4) {
                $suffix = $parts[2];
                $taxCode = $parts[3]; // 01 or 02
                
                // Map Suffix
                if (str_contains($suffix, 'KOP') || str_contains($suffix, 'HPNSDA')) {
                    $category = InvoiceSequence::CATEGORY_KOPI;
                }
                if (str_contains($suffix, 'AMP')) {
                    $category = InvoiceSequence::CATEGORY_SKINCARE;
                }
                
                // Map Sales Type based on Suffix and Platform
                if (str_contains($suffix, 'OL') || $suffix === 'AMP-OL') {
                    $salesType = InvoiceSequence::SALES_ONLINE;
                } elseif ($suffix === 'AMP-KOS') {
                    $salesType = InvoiceSequence::SALES_OFFLINE;
                } elseif ($suffix === 'AMP') {
                    // Legacy AMP suffix - check platform
                    if ($inv['platform'] === 'Offline') {
                        $salesType = InvoiceSequence::SALES_OFFLINE;
                    } else {
                        $salesType = InvoiceSequence::SALES_ONLINE;
                    }
                } else {
                    // Fallback based on platform
                    if ($inv['platform'] === 'Offline') {
                        $salesType = InvoiceSequence::SALES_OFFLINE;
                    } else {
                        $salesType = InvoiceSequence::SALES_ONLINE;
                    }
                }
                
                // Map Tax Status
                $taxStatus = ($taxCode == '01') ? InvoiceSequence::TAX_PKP : InvoiceSequence::TAX_NON_PKP;
            } else {
                if ($inv['platform'] == 'Offline') {
                    $salesType = InvoiceSequence::SALES_OFFLINE;
                }
            }

            $inv['category'] = $category;
            $inv['sales_type'] = $salesType;
            $inv['tax_status'] = $taxStatus;
            
            return $inv;
        })->sortBy(function($inv) {
            return $inv['order_date']->timestamp;
        })->values();

        // 3. Regenerate with In-Memory Counters (Monthly Reset)
        $this->info("Regenerating invoice numbers...");
        $bar = $this->output->createProgressBar($processedInvoices->count());
        $bar->start();

        $counters = []; // Key: "YYMM|CAT|TYPE|TAX"

        foreach ($processedInvoices as $inv) {
            $yearMonth = $inv['order_date']->format('ym');
            $key = "{$yearMonth}|{$inv['category']}|{$inv['sales_type']}|{$inv['tax_status']}";
            
            if (!isset($counters[$key])) {
                $counters[$key] = 0;
            }
            $counters[$key]++;
            $currentCount = $counters[$key];

            // Generate Suffix
            $suffix = ($inv['sales_type'] === InvoiceSequence::SALES_OFFLINE) 
                ? config('invoice.format.suffix_offline', 'AMP-KOS') 
                : config('invoice.format.suffix_online', 'AMP-OL');
            
            $taxCode = ($inv['tax_status'] === InvoiceSequence::TAX_PKP) ? '01' : '02';
            
            // Format Number - 4 digit untuk semua
            $counterLength = 4;
            $numberStr = str_pad($currentCount, $counterLength, '0', STR_PAD_LEFT);
            
            $newInvoiceNumber = "$numberStr/$yearMonth/$suffix/$taxCode";

            // Update DB
            try {
                $field = ($inv['platform'] === 'Offline') ? 'invoice_number' : 'no_invoice';
                // Direct DB update for speed and avoiding model events/mutators if any
                // But we use Model::where to be safe with table names
                $inv['model']::where('id', $inv['id'])->update([$field => $newInvoiceNumber]);
            } catch (\Exception $e) {
                $this->error("Failed to update {$inv['id']}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // 4. Sync InvoiceSequence Table
        $this->info("Syncing InvoiceSequence table...");
        foreach ($counters as $key => $count) {
            list($ym, $cat, $type, $tax) = explode('|', $key);
            
            InvoiceSequence::create([
                'year_month' => $ym,
                'category_type' => $cat,
                'sales_type' => $type,
                'tax_status' => $tax,
                'counter' => $count,
                'last_updated' => now()
            ]);
        }

        $this->info("Done! All invoices have been renumbered with monthly reset.");
    }
}
