<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\BarangKeluar;
use App\Models\ShopeeFinancialTransaction;
use App\Models\Shopee2FinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\Tiktok2FinancialTransaction;
use App\Models\TokopediaFinancialTransaction;
use App\Models\BlibliFinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResyncAllPlatformInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platforms:resync-invoices 
                            {--platform= : Platform ID to process (1=Shopee, 2=Tokopedia, 3=Tiktok, 4=Blibli, 6=Shopee2, 7=Tiktok2)}
                            {--dry-run : Run without making changes}
                            {--force : Force regenerate even if invoice exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync invoices for all platforms to create separate invoices for each tax_id (PKP and Non-PKP)';

    /**
     * Platform configuration
     */
    protected $platformConfig = [
        1 => ['model' => ShopeeFinancialTransaction::class, 'name' => 'Shopee'],
        2 => ['model' => TokopediaFinancialTransaction::class, 'name' => 'Tokopedia'],
        3 => ['model' => TiktokFinancialTransaction::class, 'name' => 'Tiktok'],
        4 => ['model' => BlibliFinancialTransaction::class, 'name' => 'Blibli'],
        6 => ['model' => Shopee2FinancialTransaction::class, 'name' => 'Shopee2'],
        7 => ['model' => Tiktok2FinancialTransaction::class, 'name' => 'Tiktok2'],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $platformId = $this->option('platform');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $platformsToProcess = $platformId ? [$platformId] : array_keys($this->platformConfig);

        $this->info('Starting invoice resync for all platforms...');
        $this->newLine();

        $totalProcessed = 0;
        $totalCreated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($platformsToProcess as $pid) {
            if (!isset($this->platformConfig[$pid])) {
                $this->warn("Platform ID {$pid} not found in configuration");
                continue;
            }

            $config = $this->platformConfig[$pid];
            $this->info("Processing platform: {$config['name']} (ID: {$pid})");
            $this->line(str_repeat('=', 60));

            // Get orders that need resync - only orders with multiple tax_id but missing invoices
            // Use raw query to find orders efficiently
            $tableName = (new $config['model'])->getTable();
            
            $orderIds = DB::select("
                SELECT o.id
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                INNER JOIN barang_keluar bk ON oi.id = bk.order_item_id
                INNER JOIN warehouse_stock ws ON bk.warehouse_stock_id = ws.id
                WHERE o.platform_id = ?
                AND ws.tax_id IS NOT NULL
                GROUP BY o.id
                HAVING COUNT(DISTINCT ws.tax_id) > 1
                AND (
                    SELECT COUNT(*)
                    FROM {$tableName} inv
                    WHERE inv.order_id = o.id
                ) < COUNT(DISTINCT ws.tax_id)
            ", [$pid]);
            
            if (empty($orderIds)) {
                $this->info("No orders with multiple tax_id but missing invoices found for {$config['name']}");
                $this->newLine();
                continue;
            }
            
            $orderIds = array_column($orderIds, 'id');
            
            // Get orders in batches
            $orders = collect();
            foreach (array_chunk($orderIds, 100) as $chunk) {
                $orders = $orders->concat(
                    Order::whereIn('id', $chunk)
                        ->select('id', 'order_number', 'platform_id', 'tanggal', 'hari')
                        ->get()
                );
            }

            $this->info("Found {$orders->count()} orders to check");
            $this->newLine();

            $bar = $this->output->createProgressBar($orders->count());
            $bar->start();

            $platformStats = [
                'processed' => 0,
                'created' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            foreach ($orders as $order) {
                try {
                    // Load order items fresh to avoid memory issues
                    $order = Order::with(['orderItems' => function($q) {
                        $q->select('id', 'order_id', 'platform_product_id', 'quantity', 'price_after_discount');
                    }])->find($order->id);
                    
                    // Group barang keluar by tax_id (select only needed columns)
                    $barangKeluarItems = BarangKeluar::whereHas('orderItem', function($q) use ($order) {
                        $q->where('order_id', $order->id);
                    })
                    ->with(['warehouseStock' => function($q) {
                        $q->select('id', 'tax_id');
                    }, 'orderItem' => function($q) {
                        $q->select('id', 'order_id', 'platform_product_id', 'quantity', 'price_after_discount');
                    }])
                    ->select('id', 'order_item_id', 'warehouse_stock_id', 'qty')
                    ->get();

                    $taxGroups = [];
                    foreach ($barangKeluarItems as $bk) {
                        if ($bk->warehouseStock && $bk->warehouseStock->tax_id) {
                            $taxId = $bk->warehouseStock->tax_id;
                            if (!isset($taxGroups[$taxId])) {
                                $taxGroups[$taxId] = [];
                            }
                            $taxGroups[$taxId][] = $bk;
                        }
                    }

                    if (empty($taxGroups)) {
                        $platformStats['skipped']++;
                        $bar->advance();
                        continue;
                    }

                    // Check existing transactions
                    $transactionModel = $config['model'];
                    $existingTransactions = $transactionModel::where('order_id', $order->id)->get();
                    
                    // Check if we need to create missing invoices
                    $existingTaxIds = [];
                    foreach ($existingTransactions as $trans) {
                        $taxId = $this->extractTaxIdFromInvoice($trans->no_invoice);
                        if ($taxId) {
                            $existingTaxIds[] = $taxId;
                        }
                    }

                    $missingTaxIds = array_diff(array_keys($taxGroups), $existingTaxIds);

                    if (empty($missingTaxIds) && !$force) {
                        $platformStats['skipped']++;
                        $bar->advance();
                        continue;
                    }

                    // If force mode, delete existing transactions first
                    if ($force && $existingTransactions->count() > 0) {
                        if (!$dryRun) {
                            $transactionModel::where('order_id', $order->id)->delete();
                            $this->line("  Deleted existing transactions for order {$order->order_number}");
                        } else {
                            $this->line("  [DRY RUN] Would delete existing transactions for order {$order->order_number}");
                        }
                        $missingTaxIds = array_keys($taxGroups);
                    }

                    // Get existing transaction data to copy (if any)
                    $existingTrans = $existingTransactions->first();
                    
                    if (!$existingTrans && !$force) {
                        $platformStats['skipped']++;
                        $this->warn("  Order {$order->order_number} has no existing transaction to replicate");
                        $bar->advance();
                        continue;
                    }

                    // Calculate total nominal for proportion calculation
                    $totalNominal = $order->orderItems->sum(function($item) {
                        return $item->price_after_discount * $item->quantity;
                    });

                    // Create invoices for all tax_ids
                    foreach ($taxGroups as $taxId => $group) {
                        // Check if invoice for this tax_id already exists
                        $hasInvoice = false;
                        foreach ($existingTransactions as $trans) {
                            $transTaxId = $this->extractTaxIdFromInvoice($trans->no_invoice);
                            if ($transTaxId == $taxId) {
                                $hasInvoice = true;
                                break;
                            }
                        }

                        if ($hasInvoice && !$force) {
                            continue; // Skip if invoice already exists
                        }

                        if (!$dryRun) {
                            // Calculate qty and nominal for this tax_id
                            $groupQty = 0;
                            $groupNominal = 0;
                            foreach ($group as $bk) {
                                $groupQty += $bk->qty;
                                if ($bk->orderItem) {
                                    $groupNominal += $bk->orderItem->price_after_discount * $bk->orderItem->quantity;
                                }
                            }
                            
                            // Calculate proportion
                            $proportion = $totalNominal > 0 ? ($groupNominal / $totalNominal) : (1 / count($taxGroups));
                            
                            // Create new transaction
                            $newTrans = new $transactionModel();
                            $newTrans->tanggal_order = $order->tanggal;
                            $newTrans->hari_order = $order->hari;
                            $newTrans->no_order = $order->order_number;
                            $newTrans->order_id = $order->id;
                            $newTrans->qty = $groupQty;
                            $newTrans->nominal_harga = $groupNominal;
                            
                            // Distribute discounts and payment proportionally
                            if ($existingTrans) {
                                $newTrans->nominal_diskon1 = ($existingTrans->nominal_diskon1 ?? 0) * $proportion;
                                $newTrans->nominal_diskon2 = ($existingTrans->nominal_diskon2 ?? 0) * $proportion;
                                $newTrans->nominal_diskon3 = ($existingTrans->nominal_diskon3 ?? 0) * $proportion;
                                $newTrans->nominal_diskon4 = ($existingTrans->nominal_diskon4 ?? 0) * $proportion;
                                $newTrans->nominal_diskon5 = ($existingTrans->nominal_diskon5 ?? 0) * $proportion;
                                $newTrans->nominal_diskon6 = ($existingTrans->nominal_diskon6 ?? 0) * $proportion;
                                $newTrans->nominal_diskon7 = ($existingTrans->nominal_diskon7 ?? 0) * $proportion;
                                $newTrans->nominal_diskon8 = ($existingTrans->nominal_diskon8 ?? 0) * $proportion;
                                $newTrans->nominal_diskon9 = ($existingTrans->nominal_diskon9 ?? 0) * $proportion;
                                $newTrans->nominal_diskon10 = ($existingTrans->nominal_diskon10 ?? 0) * $proportion;
                                $newTrans->nominal_diskon11 = ($existingTrans->nominal_diskon11 ?? 0) * $proportion;
                                $newTrans->nominal_diskon12 = ($existingTrans->nominal_diskon12 ?? 0) * $proportion;
                                $newTrans->adjustment = ($existingTrans->adjustment ?? 0) * $proportion;
                                $newTrans->adjustment_description = $existingTrans->adjustment_description ?? null;
                                $newTrans->saldo_masuk = ($existingTrans->saldo_masuk ?? 0) * $proportion;
                                $newTrans->tanggal_masuk_pembayaran = $existingTrans->tanggal_masuk_pembayaran ?? null;
                                $newTrans->hari_masuk_pembayaran = $existingTrans->hari_masuk_pembayaran ?? null;
                                // Only set is_locked if the column exists in the model
                                if (isset($existingTrans->is_locked)) {
                                    $newTrans->is_locked = $existingTrans->is_locked;
                                }
                            } else {
                                // No existing transaction, set defaults
                                $newTrans->nominal_diskon1 = 0;
                                $newTrans->nominal_diskon2 = 0;
                                $newTrans->nominal_diskon3 = 0;
                                $newTrans->nominal_diskon4 = 0;
                                $newTrans->nominal_diskon5 = 0;
                                $newTrans->nominal_diskon6 = 0;
                                $newTrans->nominal_diskon7 = 0;
                                $newTrans->nominal_diskon8 = 0;
                                $newTrans->nominal_diskon9 = 0;
                                $newTrans->nominal_diskon10 = 0;
                                $newTrans->nominal_diskon11 = 0;
                                $newTrans->nominal_diskon12 = 0;
                                $newTrans->adjustment = 0;
                                $newTrans->saldo_masuk = 0;
                            }
                            
                            // Generate invoice number for this tax_id
                            $newTrans->no_invoice = $transactionModel::generateInvoiceNumber($order, $taxId);
                            
                            // Calculate values
                            if (method_exists($newTrans, 'calculateNominalFix')) {
                                $newTrans->calculateNominalFix()
                                         ->calculateOutstanding()
                                         ->calculatePercentages();
                            } else {
                                // Fallback calculation
                                $newTrans->nominal_fix = $newTrans->nominal_harga + 
                                    $newTrans->nominal_diskon1 + $newTrans->nominal_diskon2 + 
                                    $newTrans->nominal_diskon3 + $newTrans->nominal_diskon4 +
                                    $newTrans->nominal_diskon5 + $newTrans->nominal_diskon6 +
                                    ($newTrans->nominal_diskon7 ?? 0) + ($newTrans->nominal_diskon8 ?? 0) +
                                    ($newTrans->nominal_diskon9 ?? 0) + ($newTrans->nominal_diskon10 ?? 0) +
                                    ($newTrans->nominal_diskon11 ?? 0) + ($newTrans->nominal_diskon12 ?? 0) +
                                    ($newTrans->adjustment ?? 0);
                                $newTrans->outstanding = $newTrans->nominal_fix - ($newTrans->saldo_masuk ?? 0);
                            }
                            
                            $newTrans->save();
                            $platformStats['created']++;
                            
                            $taxCategory = in_array($taxId, [1, 3, 5, 7]) ? 'PKP' : 'Non-PKP';
                            $this->line("  Created invoice {$newTrans->no_invoice} for order {$order->order_number} ({$taxCategory})");
                        } else {
                            $this->line("  [DRY RUN] Would create invoice for order {$order->order_number}, tax_id: {$taxId}");
                            $platformStats['created']++;
                        }
                    }

                    $platformStats['processed']++;
                } catch (\Exception $e) {
                    $platformStats['errors']++;
                    $this->error("  Error processing order {$order->order_number}: " . $e->getMessage());
                    Log::error("ResyncAllPlatformInvoices error for order {$order->order_number}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Platform {$config['name']} completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Processed', $platformStats['processed']],
                    ['Created', $platformStats['created']],
                    ['Skipped', $platformStats['skipped']],
                    ['Errors', $platformStats['errors']],
                ]
            );

            $totalProcessed += $platformStats['processed'];
            $totalCreated += $platformStats['created'];
            $totalSkipped += $platformStats['skipped'];
            $totalErrors += $platformStats['errors'];

            $this->newLine();
        }

        $this->info("🎉 All platforms resync completed!");
        $this->table(
            ['Status', 'Total'],
            [
                ['Processed', $totalProcessed],
                ['Created', $totalCreated],
                ['Skipped', $totalSkipped],
                ['Errors', $totalErrors],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        }

        return 0;
    }

    /**
     * Extract tax_id from invoice number
     */
    private function extractTaxIdFromInvoice($invoiceNumber)
    {
        if (!$invoiceNumber) {
            return null;
        }

        // Invoice format: 000002/2508/HGNSDA-OL/01 or 000002/2508/HGNSDA-OL/02
        // Last part is tax code: 01 = PKP, 02 = Non-PKP
        if (preg_match('/\/(\d{2})$/', $invoiceNumber, $matches)) {
            $taxCode = $matches[1];
            
            // Map tax code to tax_id
            if (strpos($invoiceNumber, 'HGNSDA-OL') !== false) {
                // Skincare
                return $taxCode == '01' ? 3 : 4;
            } elseif (strpos($invoiceNumber, 'HPNSDA-OLK') !== false) {
                // Coffee
                return $taxCode == '01' ? 1 : 2;
            }
        }

        return null;
    }
}

