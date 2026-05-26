<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FinanceOffline;
use App\Models\OfflineSale;
use App\Models\BarangKeluar;
use Illuminate\Support\Facades\DB;

class FixFinanceAudit extends Command
{
    protected $signature = 'fix:finance-audit';
    protected $description = 'Fix Finance Offline discrepancies (Split merged, fix nominals, fix invoices)';

    public function handle()
    {
        $this->info('Starting Finance Audit Fix...');

        DB::beginTransaction();

        try {
            // 1. Fix Finance 27 (Nominal 0)
            $this->fixZeroNominal(27);

            // 2. Fix Finance 28 (Nominal 0)
            $this->fixZeroNominal(28);

            // 3. Fix Finance 54 (Nominal 170k -> 218.7k)
            $this->fixFinance54();

            // 4. Split Finance 70 (Sale 63 & 67)
            $this->splitFinance70();

            // 5. Fix Finance 71 (Sale 64 & 66)
            $this->fixFinance71();

            DB::commit();
            $this->info('All fixes applied successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    private function fixZeroNominal($id)
    {
        $finance = FinanceOffline::with(['barangKeluarItems.offlineSaleItem.offlineSale'])->find($id);
        if (!$finance) return;

        $sales = $finance->barangKeluarItems->map(function($bk) {
            return $bk->offlineSaleItem->offlineSale;
        })->unique('id');

        $totalSales = $sales->sum('total_amount');
        $newNominal = round($totalSales * 1.11, 2); // Add 11% Tax

        if ($newNominal > 0) {
            $finance->nominal = $newNominal;
            $finance->save();
            $this->info("Fixed Finance {$id} Nominal: 0 -> {$newNominal}");
        }
    }

    private function fixFinance54()
    {
        $finance = FinanceOffline::find(54);
        // Sale 47 Total: 197027.03
        // Target: 197027.03 * 1.11 = 218699.99 -> 218700
        if ($finance) {
            $finance->nominal = 218700.00;
            $finance->save();
            $this->info("Fixed Finance 54 Nominal: 170000 -> 218700");
        }
    }

    private function splitFinance70()
    {
        $finance70 = FinanceOffline::find(70);
        if (!$finance70) return;

        // Sale 67 (0019...) stays on 70
        // Sale 63 (0017...) moves to New
        
        $sale67 = OfflineSale::where('surat_jalan_number', 'like', '%0019%')->first();
        $sale63 = OfflineSale::where('surat_jalan_number', 'like', '%0017%')->first();

        // Update Finance 70
        $finance70->invoice_number = $sale67->surat_jalan_number;
        $finance70->nominal = round($sale67->total_amount * 1.11, 2);
        $finance70->save();
        $this->info("Updated Finance 70 for Sale 67 (Inv: {$finance70->invoice_number}, Nom: {$finance70->nominal})");

        // Create New Finance for Sale 63
        $nominal63 = round($sale63->total_amount * 1.11, 2);
        $newFinance = FinanceOffline::create([
            'invoice_number' => $sale63->surat_jalan_number,
            'nominal' => $nominal63,
            'tanggal_invoice' => $sale63->sale_date,
            'status' => $sale63->status,
            'main_category_id' => $sale63->main_category_id,
        ]);
        $this->info("Created New Finance {$newFinance->id} for Sale 63 (Inv: {$newFinance->invoice_number}, Nom: {$newFinance->nominal})");

        // Move BKs for Sale 63 to New Finance
        $bks63 = $sale63->items->flatMap->barangKeluar;
        foreach ($bks63 as $bk) {
            $bk->finance_offline_id = $newFinance->id;
            $bk->save();
        }
        $this->info("Moved " . $bks63->count() . " BKs to Finance {$newFinance->id}");
    }

    private function fixFinance71()
    {
        $finance71 = FinanceOffline::find(71);
        if (!$finance71) return;

        // Sale 64 (0001/2601...) stays on 71
        // Sale 66 (0005/2512... DUMMY?) moves to New
        
        $sale64 = OfflineSale::where('surat_jalan_number', 'like', '%0001/2601%')->first();
        $sale66 = OfflineSale::find(66);

        // Update Finance 71
        $finance71->invoice_number = $sale64->surat_jalan_number;
        // Nominal 69500 is already correct for Sale 64 (62612.61 * 1.11 = 69500)
        $finance71->save();
        $this->info("Updated Finance 71 Invoice to {$finance71->invoice_number}");

        // Create New Finance for Sale 66
        $nominal66 = round($sale66->total_amount * 1.11, 2);
        $newFinance = FinanceOffline::create([
            'invoice_number' => $sale66->surat_jalan_number,
            'nominal' => $nominal66,
            'tanggal_invoice' => $sale66->sale_date,
            'status' => $sale66->status,
            'main_category_id' => $sale66->main_category_id,
        ]);
        $this->info("Created New Finance {$newFinance->id} for Sale 66 (Inv: {$newFinance->invoice_number}, Nom: {$newFinance->nominal})");

        // Move BKs for Sale 66 to New Finance
        $bks66 = $sale66->items->flatMap->barangKeluar;
        foreach ($bks66 as $bk) {
            $bk->finance_offline_id = $newFinance->id;
            $bk->save();
        }
        $this->info("Moved " . $bks66->count() . " BKs to Finance {$newFinance->id}");
    }
}
