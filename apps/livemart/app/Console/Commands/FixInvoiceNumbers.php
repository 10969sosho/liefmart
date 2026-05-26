<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ShopeeFinancialTransaction;
use App\Models\TiktokFinancialTransaction;
use App\Models\Order;
use App\Models\InvoiceSequence;
use Carbon\Carbon;

class FixInvoiceNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:fix 
                            {--check : Only check for issues without fixing}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and fix invoice numbers in shopee_financial_transactions and tiktok_financial_transactions tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $checkOnly = $this->option('check');
        $dryRun = $this->option('dry-run');

        $this->info('=================================================');
        $this->info('INVOICE NUMBER CHECKER AND FIXER');
        $this->info('=================================================');
        $this->newLine();

        // Step 1: Check for date mismatches with orders table
        $this->info('STEP 1: Checking tanggal_order consistency with orders table...');
        $dateMismatches = $this->checkDateMismatches();
        $this->newLine();

        // Step 2: Check for duplicate invoice numbers
        $this->info('STEP 2: Checking for duplicate invoice numbers...');
        $duplicates = $this->checkDuplicates();
        $this->newLine();

        // Step 3: Check for gaps in invoice numbering
        $this->info('STEP 3: Checking for gaps in invoice numbering...');
        $gaps = $this->checkGaps();
        $this->newLine();

        // Step 4: Check for invoice numbers that don't match their order dates
        $this->info('STEP 4: Checking for invoice numbers with wrong month/year...');
        $wrongMonthYear = $this->checkWrongMonthYear();
        $this->newLine();

        // Summary
        $this->info('=================================================');
        $this->info('SUMMARY');
        $this->info('=================================================');
        $this->info("Date mismatches found: " . count($dateMismatches));
        $this->info("Duplicate invoices found: " . count($duplicates));
        $this->info("Gaps in numbering found: " . count($gaps));
        $this->info("Wrong month/year in invoice: " . count($wrongMonthYear));
        $this->newLine();

        $totalIssues = count($dateMismatches) + count($duplicates) + count($gaps) + count($wrongMonthYear);

        if ($totalIssues === 0) {
            $this->info('✓ No issues found! All invoice numbers are correct.');
            return 0;
        }

        if ($checkOnly) {
            $this->warn('Issues found. Run without --check to fix them.');
            return 1;
        }

        // Ask for confirmation
        if (!$dryRun && !$this->confirm('Do you want to fix these issues?', true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Fix issues
        $this->info('=================================================');
        $this->info('FIXING ISSUES');
        $this->info('=================================================');
        $this->newLine();

        DB::beginTransaction();
        try {
            // Fix date mismatches first
            if (count($dateMismatches) > 0) {
                $this->info('Fixing date mismatches...');
                $this->fixDateMismatches($dateMismatches, $dryRun);
                $this->newLine();
            }

            // Regenerate all invoice numbers
            $this->info('Regenerating all invoice numbers...');
            $this->regenerateAllInvoices($dryRun);
            $this->newLine();

            if (!$dryRun) {
                DB::commit();
                $this->info('✓ All issues fixed successfully!');
            } else {
                DB::rollBack();
                $this->info('✓ Dry run completed. No changes were made.');
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Check for date mismatches between financial transactions and orders table
     */
    protected function checkDateMismatches()
    {
        $mismatches = [];

        // Check Shopee
        $shopeeTransactions = ShopeeFinancialTransaction::with('order')->get();
        foreach ($shopeeTransactions as $transaction) {
            if ($transaction->order) {
                $transactionDate = Carbon::parse($transaction->tanggal_order)->format('Y-m-d');
                $orderDate = Carbon::parse($transaction->order->tanggal)->format('Y-m-d');
                
                if ($transactionDate !== $orderDate) {
                    $mismatches[] = [
                        'table' => 'shopee_financial_transactions',
                        'id' => $transaction->id,
                        'no_order' => $transaction->no_order,
                        'transaction_date' => $transactionDate,
                        'order_date' => $orderDate
                    ];
                    $this->warn("  Shopee ID {$transaction->id}: {$transaction->no_order} - Transaction date: {$transactionDate}, Order date: {$orderDate}");
                }
            }
        }

        // Check TikTok
        $tiktokTransactions = TiktokFinancialTransaction::with('order')->get();
        foreach ($tiktokTransactions as $transaction) {
            if ($transaction->order) {
                $transactionDate = Carbon::parse($transaction->tanggal_order)->format('Y-m-d');
                $orderDate = Carbon::parse($transaction->order->tanggal)->format('Y-m-d');
                
                if ($transactionDate !== $orderDate) {
                    $mismatches[] = [
                        'table' => 'tiktok_financial_transactions',
                        'id' => $transaction->id,
                        'no_order' => $transaction->no_order,
                        'transaction_date' => $transactionDate,
                        'order_date' => $orderDate
                    ];
                    $this->warn("  TikTok ID {$transaction->id}: {$transaction->no_order} - Transaction date: {$transactionDate}, Order date: {$orderDate}");
                }
            }
        }

        if (count($mismatches) === 0) {
            $this->info('  ✓ No date mismatches found.');
        }

        return $mismatches;
    }

    /**
     * Check for duplicate invoice numbers across both tables
     */
    protected function checkDuplicates()
    {
        $duplicates = [];
        $allInvoices = [];

        // Collect all invoice numbers from both tables
        $shopeeInvoices = ShopeeFinancialTransaction::whereNotNull('no_invoice')
            ->get(['id', 'no_invoice', 'no_order'])
            ->map(function($item) {
                return [
                    'table' => 'shopee_financial_transactions',
                    'id' => $item->id,
                    'no_invoice' => $item->no_invoice,
                    'no_order' => $item->no_order
                ];
            });

        $tiktokInvoices = TiktokFinancialTransaction::whereNotNull('no_invoice')
            ->get(['id', 'no_invoice', 'no_order'])
            ->map(function($item) {
                return [
                    'table' => 'tiktok_financial_transactions',
                    'id' => $item->id,
                    'no_invoice' => $item->no_invoice,
                    'no_order' => $item->no_order
                ];
            });

        $allInvoices = $shopeeInvoices->concat($tiktokInvoices);

        // Group by invoice number to find duplicates
        $grouped = $allInvoices->groupBy('no_invoice');
        
        foreach ($grouped as $invoiceNumber => $items) {
            if ($items->count() > 1) {
                $duplicates[] = [
                    'no_invoice' => $invoiceNumber,
                    'count' => $items->count(),
                    'items' => $items->toArray()
                ];
                
                $this->warn("  Duplicate invoice: {$invoiceNumber} (found {$items->count()} times)");
                foreach ($items as $item) {
                    $this->warn("    - {$item['table']} ID {$item['id']}: {$item['no_order']}");
                }
            }
        }

        if (count($duplicates) === 0) {
            $this->info('  ✓ No duplicate invoices found.');
        }

        return $duplicates;
    }

    /**
     * Check for gaps in invoice numbering per month/year/PKP status
     */
    protected function checkGaps()
    {
        $gaps = [];
        
        // Get all invoices and group by month/year and suffix (which includes PKP/non-PKP)
        $allInvoices = [];
        
        $shopeeInvoices = ShopeeFinancialTransaction::whereNotNull('no_invoice')->get(['no_invoice', 'tanggal_order']);
        $tiktokInvoices = TiktokFinancialTransaction::whereNotNull('no_invoice')->get(['no_invoice', 'tanggal_order']);
        
        foreach ($shopeeInvoices->concat($tiktokInvoices) as $transaction) {
            $invoice = $transaction->no_invoice;
            $parts = explode('/', $invoice);
            
            if (count($parts) >= 4) {
                $counter = intval($parts[0]);
                $yearMonth = $parts[1];
                $suffix = $parts[2];
                $taxCode = $parts[3];
                
                $key = "{$yearMonth}_{$suffix}_{$taxCode}";
                
                if (!isset($allInvoices[$key])) {
                    $allInvoices[$key] = [];
                }
                
                $allInvoices[$key][] = $counter;
            }
        }
        
        // Check for gaps in each group
        foreach ($allInvoices as $key => $counters) {
            sort($counters);
            $expected = 1;
            
            foreach ($counters as $counter) {
                if ($counter > $expected) {
                    // Found a gap
                    for ($missing = $expected; $missing < $counter; $missing++) {
                        $gaps[] = [
                            'group' => $key,
                            'missing_number' => $missing
                        ];
                    }
                }
                $expected = $counter + 1;
            }
        }
        
        if (count($gaps) > 0) {
            $grouped = collect($gaps)->groupBy('group');
            foreach ($grouped as $group => $items) {
                $this->warn("  Gap in {$group}: Missing " . $items->pluck('missing_number')->implode(', '));
            }
        } else {
            $this->info('  ✓ No gaps found in invoice numbering.');
        }
        
        return $gaps;
    }

    /**
     * Check for invoices with wrong month/year based on their order date
     */
    protected function checkWrongMonthYear()
    {
        $wrongMonthYear = [];
        
        $shopeeTransactions = ShopeeFinancialTransaction::whereNotNull('no_invoice')->get();
        foreach ($shopeeTransactions as $transaction) {
            $invoice = $transaction->no_invoice;
            $parts = explode('/', $invoice);
            
            if (count($parts) >= 2) {
                $invoiceYearMonth = $parts[1];
                $orderYearMonth = Carbon::parse($transaction->tanggal_order)->format('ym');
                
                if ($invoiceYearMonth !== $orderYearMonth) {
                    $wrongMonthYear[] = [
                        'table' => 'shopee_financial_transactions',
                        'id' => $transaction->id,
                        'no_order' => $transaction->no_order,
                        'no_invoice' => $invoice,
                        'invoice_year_month' => $invoiceYearMonth,
                        'order_year_month' => $orderYearMonth
                    ];
                    $this->warn("  Shopee ID {$transaction->id}: Invoice has {$invoiceYearMonth} but order is {$orderYearMonth}");
                }
            }
        }
        
        $tiktokTransactions = TiktokFinancialTransaction::whereNotNull('no_invoice')->get();
        foreach ($tiktokTransactions as $transaction) {
            $invoice = $transaction->no_invoice;
            $parts = explode('/', $invoice);
            
            if (count($parts) >= 2) {
                $invoiceYearMonth = $parts[1];
                $orderYearMonth = Carbon::parse($transaction->tanggal_order)->format('ym');
                
                if ($invoiceYearMonth !== $orderYearMonth) {
                    $wrongMonthYear[] = [
                        'table' => 'tiktok_financial_transactions',
                        'id' => $transaction->id,
                        'no_order' => $transaction->no_order,
                        'no_invoice' => $invoice,
                        'invoice_year_month' => $invoiceYearMonth,
                        'order_year_month' => $orderYearMonth
                    ];
                    $this->warn("  TikTok ID {$transaction->id}: Invoice has {$invoiceYearMonth} but order is {$orderYearMonth}");
                }
            }
        }
        
        if (count($wrongMonthYear) === 0) {
            $this->info('  ✓ All invoices have correct month/year.');
        }
        
        return $wrongMonthYear;
    }

    /**
     * Fix date mismatches by updating transaction dates to match order dates
     */
    protected function fixDateMismatches($mismatches, $dryRun = false)
    {
        foreach ($mismatches as $mismatch) {
            $order = Order::where('order_number', $mismatch['no_order'])->first();
            if (!$order) continue;
            
            if ($dryRun) {
                $this->info("  [DRY RUN] Would update {$mismatch['table']} ID {$mismatch['id']}: {$mismatch['transaction_date']} → {$mismatch['order_date']}");
            } else {
                if ($mismatch['table'] === 'shopee_financial_transactions') {
                    $transaction = ShopeeFinancialTransaction::find($mismatch['id']);
                } else {
                    $transaction = TiktokFinancialTransaction::find($mismatch['id']);
                }
                
                if ($transaction) {
                    $transaction->tanggal_order = $order->tanggal;
                    $transaction->hari_order = $order->hari;
                    $transaction->save();
                    $this->info("  ✓ Updated {$mismatch['table']} ID {$mismatch['id']}: {$mismatch['transaction_date']} → {$mismatch['order_date']}");
                }
            }
        }
    }

    /**
     * Regenerate all invoice numbers based on correct dates and PKP/non-PKP status
     */
    protected function regenerateAllInvoices($dryRun = false)
    {
        // First, clear all invoice sequences to start fresh
        if (!$dryRun) {
            InvoiceSequence::truncate();
            $this->info('  Cleared invoice sequences table.');
        }

        // Get all transactions sorted by order date
        $shopeeTransactions = ShopeeFinancialTransaction::with('order.orderItems.warehouseStock')
            ->whereHas('order')
            ->get()
            ->sortBy(function($transaction) {
                return $transaction->tanggal_order->format('Y-m-d H:i:s') . '-' . $transaction->id;
            });

        $tiktokTransactions = TiktokFinancialTransaction::with('order.orderItems.warehouseStock')
            ->whereHas('order')
            ->get()
            ->sortBy(function($transaction) {
                return $transaction->tanggal_order->format('Y-m-d H:i:s') . '-' . $transaction->id;
            });

        // Combine and sort all transactions by date
        $allTransactions = collect();
        
        foreach ($shopeeTransactions as $transaction) {
            $allTransactions->push([
                'type' => 'shopee',
                'transaction' => $transaction
            ]);
        }
        
        foreach ($tiktokTransactions as $transaction) {
            $allTransactions->push([
                'type' => 'tiktok',
                'transaction' => $transaction
            ]);
        }
        
        // Sort by date
        $allTransactions = $allTransactions->sortBy(function($item) {
            return $item['transaction']->tanggal_order->format('Y-m-d H:i:s') . '-' . $item['transaction']->id;
        });

        $this->info("  Processing " . $allTransactions->count() . " transactions...");
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar($allTransactions->count());
        $progressBar->start();

        foreach ($allTransactions as $item) {
            $transaction = $item['transaction'];
            $type = $item['type'];
            
            // Get tax_id from first order item's warehouse stock
            $taxId = null;
            if ($transaction->order && $transaction->order->orderItems->count() > 0) {
                $firstItem = $transaction->order->orderItems->first();
                if ($firstItem && $firstItem->warehouseStock) {
                    $taxId = $firstItem->warehouseStock->tax_id;
                }
            }
            
            // Skip if no tax_id found
            if (!$taxId) {
                $progressBar->advance();
                continue;
            }
            
            // Generate new invoice number
            try {
                if ($type === 'shopee') {
                    $newInvoice = ShopeeFinancialTransaction::generateInvoiceNumber($transaction->order, $taxId);
                } else {
                    $newInvoice = TiktokFinancialTransaction::generateInvoiceNumber($transaction->order, $taxId);
                }
                
                if ($dryRun) {
                    // Just log what would be done
                    if ($transaction->no_invoice !== $newInvoice) {
                        // Don't show individual updates in dry run to avoid clutter
                    }
                } else {
                    // Update the invoice number
                    $transaction->no_invoice = $newInvoice;
                    $transaction->save();
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Error processing {$type} transaction ID {$transaction->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->newLine();
        
        if ($dryRun) {
            $this->info("  [DRY RUN] Would regenerate " . $allTransactions->count() . " invoice numbers.");
        } else {
            $this->info("  ✓ Regenerated " . $allTransactions->count() . " invoice numbers.");
        }
    }
}

