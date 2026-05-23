<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinanceOffline;
use App\Models\OfflineSale;
use App\Models\BarangKeluar;
use Illuminate\Support\Facades\DB;

class FixSpecificFinanceIssues extends Command
{
    protected $signature = 'fix:specific-issues';
    protected $description = 'Fix specific reported finance issues (Bertha/Kas merge, Dita/11Rp, Kas 30/12)';

    public function handle()
    {
        $this->info('Starting Specific Finance Fixes...');
        
        DB::beginTransaction();
        try {
            // 1. Fix Bertha (24/12) and Kas (28/12) - Re-create to ensure separation
            $this->recreateFinanceForSale(62); // Bertha
            $this->recreateFinanceForSale(68); // Kas (28/12) - One of them
            // Also check other Kas 28/12 sales just in case?
            $this->recreateFinanceForSale(65); // Kas (28/12) - Large one
            $this->recreateFinanceForSale(67); // Kas (28/12)
            
            // 2. Fix Dita (9/1) and Kas (11 Rp, 28/12) - Re-create and set Status
            $this->recreateFinanceForSale(64, true); // Dita - set status pending
            $this->recreateFinanceForSale(66); // Kas 11 Rp

            // 3. Fix Kas (30/12) - Ensure SJ/INV match
            $this->recreateFinanceForSale(63); // Kas 30/12

            DB::commit();
            $this->info('All specific fixes applied successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    private function recreateFinanceForSale($saleId, $setStatusPending = false)
    {
        $sale = OfflineSale::find($saleId);
        if (!$sale) {
            $this->warn("Sale $saleId not found.");
            return;
        }

        $this->info("Processing Sale {$saleId} ({$sale->customer_name})...");

        // Find existing finance(s) linked to this sale
        $existingFinances = collect();
        foreach ($sale->items as $item) {
            foreach ($item->barangKeluar as $bk) {
                if ($bk->financeOffline) {
                    $existingFinances->push($bk->financeOffline);
                }
            }
        }
        $existingFinances = $existingFinances->unique('id');

        // Detach BKs and Delete existing finances
        // NOTE: Be careful if a finance is shared. But we are assuming we want to SPLIT everything.
        // If a finance is shared, deleting it affects other sales.
        // So we should only remove the BK links first.
        
        foreach ($sale->items as $item) {
            foreach ($item->barangKeluar as $bk) {
                $bk->finance_offline_id = null;
                $bk->save();
            }
        }

        // Now delete the old finances IF they have no more BKs
        foreach ($existingFinances as $fin) {
            if ($fin->barangKeluarItems()->count() == 0) {
                $fin->delete();
                $this->info("  Deleted old Finance {$fin->id}");
            } else {
                $this->warn("  Finance {$fin->id} still has other items. Not deleting.");
            }
        }

        // Create New Finance
        // Calculate Nominal with 11% Tax
        $nominal = round($sale->total_amount * 1.11, 2);
        
        // Ensure invoice number matches SJ exactly
        $invoiceNumber = trim($sale->surat_jalan_number);

        // Map Sale Status to Finance Status
        $financeStatus = 'unpaid';
        if ($sale->status == 'paid') {
            $financeStatus = 'paid';
        }
        
        // Override if setStatusPending requested
        if ($setStatusPending) {
            $financeStatus = 'unpaid';
        }

        $newFinance = FinanceOffline::create([
            'invoice_number' => $invoiceNumber,
            'nominal' => $nominal,
            'tanggal_invoice' => $sale->sale_date, // Use Sale Date as Invoice Date
            'status' => $financeStatus,
            'main_category_id' => $sale->main_category_id,
        ]);

        // Update Sale Status if requested
        if ($setStatusPending) {
            $sale->status = 'pending';
            $sale->save();
            $this->info("  Set Sale Status to pending.");
        }

        // Link BKs to New Finance
        foreach ($sale->items as $item) {
            foreach ($item->barangKeluar as $bk) {
                $bk->finance_offline_id = $newFinance->id;
                $bk->save();
            }
        }

        $this->info("  Created New Finance {$newFinance->id} | Inv: {$newFinance->invoice_number} | Nom: {$newFinance->nominal}");
    }
}
