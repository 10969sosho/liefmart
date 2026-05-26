<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinanceOffline;

class InspectFinanceLinks extends Command
{
    protected $signature = 'inspect:finance-links';
    protected $description = 'Inspect specific finance records for mixed sales';

    public function handle()
    {
        $ids = [69, 70, 71, 72, 80, 81, 82];
        
        foreach ($ids as $id) {
            $finance = FinanceOffline::with(['barangKeluarItems.offlineSaleItem.offlineSale'])->find($id);
            if (!$finance) {
                $this->error("Finance $id not found");
                continue;
            }

            $this->info("--- Finance ID: $id (Inv: {$finance->invoice_number}, Nom: {$finance->nominal}) ---");
            
            $sales = collect();
            foreach ($finance->barangKeluarItems as $bk) {
                $saleId = $bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale ? $bk->offlineSaleItem->offlineSale->id : 'NULL';
                $saleDate = $bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale ? $bk->offlineSaleItem->offlineSale->sale_date->format('Y-m-d') : 'N/A';
                $saleSJ = $bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale ? $bk->offlineSaleItem->offlineSale->surat_jalan_number : 'N/A';
                
                $this->info("   BK ID: {$bk->id} | Sale ID: $saleId | Date: $saleDate | SJ: $saleSJ");

                if ($bk->offlineSaleItem && $bk->offlineSaleItem->offlineSale) {
                    $sales->push($bk->offlineSaleItem->offlineSale);
                }
            }
            
            $uniqueSales = $sales->unique('id');
            foreach ($uniqueSales as $sale) {
                $this->info("   Linked Sale: {$sale->id} | {$sale->sale_date->format('Y-m-d')} | {$sale->customer_name} | {$sale->surat_jalan_number} | Total: {$sale->total_amount}");
            }
            
            if ($uniqueSales->count() > 1) {
                $this->error("   !!! MULTIPLE SALES LINKED !!!");
            }
        }
    }
}
