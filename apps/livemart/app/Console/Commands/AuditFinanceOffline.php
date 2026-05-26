<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinanceOffline;
use App\Models\OfflineSale;
use Illuminate\Support\Facades\DB;

class AuditFinanceOffline extends Command
{
    protected $signature = 'audit:finance-offline';
    protected $description = 'Audit Finance Offline consistency with Sales (SJ/INV match and Nominal)';

    public function handle()
    {
        $this->info('Starting Finance Offline Audit...');

        // Get all finance records with their BKs and related Sales
        $finances = FinanceOffline::with(['barangKeluarItems.offlineSaleItem.offlineSale'])->get();
        
        $errors = 0;
        $checked = 0;

        foreach ($finances as $finance) {
            $checked++;
            
            // 1. Identify associated Offline Sales
            $sales = collect();
            
            foreach ($finance->barangKeluarItems as $bk) {
                if ($bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                    $sales->push($bk->offlineSaleItem->offlineSale);
                }
            }
            
            $uniqueSales = $sales->unique('id');
            
            if ($uniqueSales->isEmpty()) {
                $this->warn("Finance ID {$finance->id} has no linked Offline Sales (via BK).");
                continue;
            }

            // 2. Check for multiple sales linked to one finance (should verify if this is allowed/expected)
            if ($uniqueSales->count() > 1) {
                $this->warn("Finance ID {$finance->id} is linked to MULTIPLE Sales: " . $uniqueSales->pluck('surat_jalan_number')->implode(', '));
            }

            // 3. Verify Data
            foreach ($uniqueSales as $sale) {
                $hasError = false;
                $msg = "Finance {$finance->id} vs Sale {$sale->id} ({$sale->surat_jalan_number}): ";

                // Check 1: Invoice Number vs Surat Jalan
                // Note: User says "no SJ akan selalu sama dengan no INV kan sekarang"
                if ($finance->invoice_number !== $sale->surat_jalan_number) {
                    $this->error($msg . "MISMATCH SJ/INV! Finance Inv: '{$finance->invoice_number}' vs Sale SJ: '{$sale->surat_jalan_number}'");
                    $hasError = true;
                }

                // Check 2: Nominal vs Total Amount
                // Note: We need to be careful about PPN.
                // Usually Nominal = Total Amount (which includes Tax).
                // Let's check strict equality first, then allow small float diffs.
                
                // If multiple sales in one finance, we might need to sum them, but user implies 1:1 match now.
                // If 1 finance has multiple sales, we compare Finance Nominal vs Sum of Sales Total?
                // Or Finance Nominal should match EACH sale? (Unlikely if merged).
                // Given the recent fix (Bertha/Kas separation), we expect 1:1 mostly.
                
                $expectedNominal = $sale->total_amount;
                
                // If multiple sales, let's sum them all for the comparison against finance nominal?
                // But the loop is per sale. Let's look at the finance nominal vs Sum of ALL linked sales.
            }
            
            // Aggregate check
            $totalSalesAmount = $uniqueSales->sum('total_amount');
            $expectedNominal = round($totalSalesAmount * 1.11, 2);
            
            // Check against Tax-Inclusive Amount
            $diff = abs($finance->nominal - $expectedNominal);
            
            // Also check against raw amount just in case (for logging)
            $diffRaw = abs($finance->nominal - $totalSalesAmount);

            if ($diff > 100.0) { // Allow 100 rupiah diff for rounding/loose
                 // If it matches the RAW amount, maybe it's a non-tax transaction?
                 if ($diffRaw <= 1.0) {
                     $this->warn("Finance ID {$finance->id} Matches RAW Total (No Tax). Finance: " . number_format($finance->nominal, 2));
                 } else {
                     $this->error("Finance ID {$finance->id} Nominal Mismatch! Finance: " . number_format($finance->nominal, 2) . " vs Expected (w/ Tax): " . number_format($expectedNominal, 2) . " (Diff: {$diff})");
                     $errors++;
                 }
            } else {
                 // Also check if INV matches SJ for the primary sale (or all)
                 foreach ($uniqueSales as $sale) {
                     if ($finance->invoice_number !== $sale->surat_jalan_number) {
                         // Already logged error above
                         $errors++;
                     }
                 }
            }
        }

        $this->info("Audit Complete. Checked: {$checked}. Discrepancies found: {$errors}.");
    }
}
