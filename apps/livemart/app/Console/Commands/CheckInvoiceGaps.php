<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;

class CheckInvoiceGaps extends Command
{
    protected $signature = 'finance:check-invoice-gaps';
    protected $description = 'Check for gaps in invoice numbering sequences';

    public function handle()
    {
        $this->info("Scanning for invoice gaps...");
        
        // 1. Collect all invoices numbers
        $invoices = collect();
        
        $platforms = [
            'Tiktok' => \App\Models\TiktokFinancialTransaction::class,
            'Shopee' => \App\Models\ShopeeFinancialTransaction::class,
            'Shopee2' => \App\Models\Shopee2FinancialTransaction::class,
            'Offline' => \App\Models\FinanceOffline::class,
        ];

        foreach ($platforms as $name => $modelClass) {
            if (!class_exists($modelClass)) continue;
            
            $colName = $name === 'Offline' ? 'invoice_number' : 'no_invoice';
            
            // Get raw invoice numbers
            $numbers = $modelClass::whereNotNull($colName)
                ->where($colName, '!=', '')
                ->pluck($colName);
                
            $invoices = $invoices->concat($numbers);
        }
        
        $this->info("Total Invoices Scanned: " . $invoices->count());
        
        // 2. Group and Analyze
        // Format: COUNTER/YYMM/SUFFIX/TAX_CODE
        // Group Key: YYMM-SUFFIX-TAX_CODE
        
        $groups = [];
        
        foreach ($invoices as $inv) {
            $parts = explode('/', $inv);
            
            if (count($parts) < 4) {
                // Non-standard format, skip or log
                continue;
            }
            
            $counter = intval($parts[0]);
            $yymm = $parts[1];
            $suffix = $parts[2];
            $taxCode = $parts[3];
            
            $key = "$yymm-$suffix-$taxCode";
            
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $counter;
        }
        
        // 3. Check Gaps per Group
        $totalGaps = 0;
        
        // Sort keys for cleaner output
        ksort($groups);
        
        $headers = ['Group (YYMM-SUFFIX-TAX)', 'Max Counter', 'Missing Numbers (Gaps)'];
        $rows = [];
        
        foreach ($groups as $key => $counters) {
            sort($counters);
            $counters = array_unique($counters); // Remove duplicates if any
            
            $max = max($counters);
            $min = min($counters); 
            
            $startGap = [];
            $internalGap = [];
            
            // 1. Check Start Gap (Missing 1 to Min-1)
            if ($min > 1) {
                if ($min > 11) {
                    $startGap[] = "1 - " . ($min - 1);
                } else {
                    for($i=1; $i<$min; $i++) $startGap[] = $i;
                }
            }
            
            // 2. Check Internal Gaps
            $last = $min;
            $internalGapCount = 0;
            
            // Sort again just to be safe
            sort($counters);
            
            foreach ($counters as $c) {
                if ($c == $min) continue; // Skip first
                
                if ($c > $last + 1) {
                    // Gap found
                    $gapSize = $c - $last - 1;
                    if ($gapSize == 1) {
                        $internalGap[] = $last + 1;
                    } else {
                        $internalGap[] = ($last + 1) . "-" . ($c - 1);
                    }
                    $internalGapCount += $gapSize;
                }
                $last = $c;
                
                if (count($internalGap) > 5) {
                    $internalGap[] = "...";
                    break;
                }
            }
            
            if (!empty($startGap) || !empty($internalGap)) {
                $startStr = empty($startGap) ? '-' : (is_array($startGap) ? implode(', ', $startGap) : $startGap);
                $internalStr = empty($internalGap) ? '-' : implode(', ', $internalGap);
                
                $rows[] = [
                    $key, 
                    "$min - $max", 
                    $startStr,
                    $internalStr
                ];
                $totalGaps++;
            }
        }
        
        if (empty($rows)) {
            $this->info("NO GAPS FOUND! All sequences are perfect.");
        } else {
            $this->warn("Found gaps in the following sequences:");
            $this->table(['Group', 'Range', 'Start Missing', 'Internal Gaps (Loncat)'], $rows);
            $this->warn("Note: 'Start Missing' usually means older data is not in DB.");
            $this->warn("Note: 'Internal Gaps' means skipped numbers within the sequence.");
        }
    }
}
