<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TiktokFinancialTransaction;
use App\Models\Tiktok2FinancialTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Models\InvoiceSequence;

class ScanTiktokFinanceProblems extends Command
{
    protected $signature = 'tiktok:scan-problems';
    protected $description = 'Scan Tiktok financial transactions for invoice issues';

    public function handle()
    {
        $this->info('Scanning Tiktok Financial Transactions...');
        $this->scanPlatform(TiktokFinancialTransaction::class, 'TikTok 1');
        
        $this->info('Scanning Tiktok 2 Financial Transactions...');
        $this->scanPlatform(Tiktok2FinancialTransaction::class, 'TikTok 2');
        
        return 0;
    }

    private function scanPlatform($modelClass, $platformName)
    {
        // 1. Check for Split Invoices with Same Category
        $this->info("\nChecking for Split Invoices in {$platformName}...");
        
        // Get all transactions grouped by order number
        // We only care about orders with multiple transactions
        $ordersWithMultipleTrans = $modelClass::select('no_order')
            ->groupBy('no_order')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('no_order');

        $splitIssues = [];
        
        foreach ($ordersWithMultipleTrans as $noOrder) {
            $transactions = $modelClass::where('no_order', $noOrder)->get();
            
            // Group transactions by "Tax Category"
            $taxCategories = [];
            
            foreach ($transactions as $trans) {
                if (empty($trans->no_invoice)) continue;
                
                // Determine category based on invoice number structure or recreating logic
                // Assuming invoice format helps identify category
                // Or better, we can't easily reverse engineer tax_id from invoice number alone accurately 
                // without looking at the code logic, but we can check if they are "compatible"
                
                // Invoice format usually: PREFIX/SEQUENCE/TAX_CODE
                // Example: INV/2023/10/001/01 (PKP) or /02 (Non-PKP)
                // Actually the code says:
                // HPNSDA-OLK/01 -> Kopi PKP
                // AMP/01 -> PKP
                // AMP/02 -> Non-PKP
                
                $type = 'UNKNOWN';
                if (strpos($trans->no_invoice, 'AMP/01') !== false || strpos($trans->no_invoice, 'AMP-OL/01') !== false || strpos($trans->no_invoice, 'AMP-KOS/01') !== false) $type = 'PKP';
                elseif (strpos($trans->no_invoice, 'AMP/02') !== false || strpos($trans->no_invoice, 'AMP-OL/02') !== false || strpos($trans->no_invoice, 'AMP-KOS/02') !== false) $type = 'NONPKP';
                
                if ($type !== 'UNKNOWN') {
                    if (!isset($taxCategories[$type])) {
                        $taxCategories[$type] = [];
                    }
                    $taxCategories[$type][] = $trans->no_invoice;
                }
            }
            
            foreach ($taxCategories as $type => $invoices) {
                if (count(array_unique($invoices)) > 1) {
                    $splitIssues[] = [
                        'no_order' => $noOrder,
                        'type' => $type,
                        'invoices' => array_unique($invoices)
                    ];
                }
            }
        }
        
        if (count($splitIssues) > 0) {
            $this->error("Found " . count($splitIssues) . " orders with split invoices for same category:");
            foreach ($splitIssues as $issue) {
                $this->line("  Order: {$issue['no_order']} | Type: {$issue['type']} | Invoices: " . implode(', ', $issue['invoices']));
            }
        } else {
            $this->info("No split invoice issues found.");
        }

        // 2. Check for Empty Invoice Numbers
        $this->info("\nChecking for Empty Invoice Numbers in {$platformName}...");
        $emptyInvoices = $modelClass::whereNull('no_invoice')
            ->orWhere('no_invoice', '')
            ->pluck('no_order');
            
        if ($emptyInvoices->count() > 0) {
            $this->error("Found " . $emptyInvoices->count() . " transactions with empty invoice numbers:");
            foreach ($emptyInvoices as $noOrder) {
                $this->line("  Order: {$noOrder}");
            }
        } else {
            $this->info("No empty invoice numbers found.");
        }
        
        // 3. Check for Gaps (Refined Check)
        $this->info("\nChecking for Invoice Gaps in {$platformName} (Sequential Check)...");
        
        // Get all invoices
        $invoices = $modelClass::whereNotNull('no_invoice')
            ->where('no_invoice', '!=', '')
            ->pluck('no_invoice')
            ->toArray();
            
        // Group by suffix (everything after the sequence number)
        // Format: SEQUENCE/YYMM/CODE/TAX_ID
        // Example: 011246/2509/AMP/01
        
        $sequences = [];
        foreach ($invoices as $inv) {
            // Match sequence number at the start
            if (preg_match('/^(\d+)\/(.*)$/', $inv, $matches)) {
                $num = intval($matches[1]);
                $suffix = $matches[2];
                $sequences[$suffix][] = $num;
            }
        }
        
        foreach ($sequences as $suffix => $nums) {
            sort($nums);
            $nums = array_unique($nums);
            $min = min($nums);
            $max = max($nums);
            
            // Check for gaps
            $expected = range($min, $max);
            $missing = array_diff($expected, $nums);
            
            if (count($missing) > 0) {
                $this->warn("  Potential gaps for group {$suffix}:");
                $this->line("    Range: {$min} - {$max}");
                $this->line("    Missing Count: " . count($missing));
                
                // Show first 10 missing
                $shown = array_slice($missing, 0, 10);
                $this->line("    Examples: " . implode(', ', $shown) . (count($missing) > 10 ? '...' : ''));
            }
        }
    }
}
