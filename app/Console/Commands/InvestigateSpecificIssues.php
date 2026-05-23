<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OfflineSale;
use App\Models\FinanceOffline;

class InvestigateSpecificIssues extends Command
{
    protected $signature = 'investigate:issues';
    protected $description = 'Investigate reported finance/sales issues';

    public function handle()
    {
        $this->info("=== Investigating Issues (Broad Search - CORRECTED YEAR) ===");

        // Search by Date Range Only (One year forward)
        $startDate = '2025-12-20';
        $endDate = '2026-01-15';
        
        $sales = OfflineSale::whereBetween('sale_date', [$startDate, $endDate])
            ->orderBy('sale_date')
            ->get();

        $this->info("Found " . $sales->count() . " sales between $startDate and $endDate");

        foreach ($sales as $sale) {
            $check = false;
            // Filter output to relevant ones to avoid spam
            if (stripos($sale->customer_name, 'Kas') !== false) $check = true;
            if (stripos($sale->customer_name, 'Bertha') !== false) $check = true;
            if (stripos($sale->customer_name, 'Dita') !== false) $check = true;
            if ($sale->sale_date->format('m-d') == '12-28') $check = true;
            
            if ($check) {
                $this->printSaleDetails($sale);
            }
        }
    }

    private function printSaleDetails($sale)
    {
        $this->info("Sale ID: {$sale->id} | Date: {$sale->sale_date->format('Y-m-d')} | Cust: {$sale->customer_name} | SJ: {$sale->surat_jalan_number} | Total: " . number_format($sale->total_amount, 2));
        
        // Find finance
        $finances = collect();
        foreach ($sale->items as $item) {
            foreach ($item->barangKeluar as $bk) {
                if ($bk->financeOffline) {
                    $finances->push($bk->financeOffline);
                }
            }
        }
        
        $uniqueFinances = $finances->unique('id');
        
        if ($uniqueFinances->isEmpty()) {
            $this->warn("  -> NO Finance Linked");
        } else {
            foreach ($uniqueFinances as $fin) {
                $this->info("  -> Linked Finance ID: {$fin->id} | Inv: {$fin->invoice_number} | Nominal: " . number_format($fin->nominal, 2) . " | Date: {$fin->tanggal_invoice}");
            }
        }
    }
}
