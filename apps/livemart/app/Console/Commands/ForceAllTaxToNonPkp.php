<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceAllTaxToNonPkp extends Command
{
    protected $signature = 'tax:force-all-nonpkp {--tax-id=4} {--dry-run}';

    protected $description = 'Force ALL tax_id / tax_category_id to a single Non-PKP tax category ID across penerimaan, warehouse_stock, and products';

    public function handle()
    {
        $targetTaxId = (int) $this->option('tax-id');
        $dryRun = (bool) $this->option('dry-run');

        $countsBefore = $this->getCounts($targetTaxId);
        $this->line(json_encode(['before' => $countsBefore], JSON_UNESCAPED_SLASHES));

        if ($dryRun) {
            $this->info('Dry run: no changes applied.');
            return 0;
        }

        DB::transaction(function () use ($targetTaxId) {
            DB::table('penerimaan')
                ->where(function ($q) use ($targetTaxId) {
                    $q->whereNull('tax_category_id')->orWhere('tax_category_id', '!=', $targetTaxId);
                })
                ->update([
                    'tax_category_id' => $targetTaxId,
                    'updated_at' => now(),
                ]);

            DB::table('warehouse_stock')
                ->where(function ($q) use ($targetTaxId) {
                    $q->whereNull('tax_id')->orWhere('tax_id', '!=', $targetTaxId);
                })
                ->update([
                    'tax_id' => $targetTaxId,
                    'updated_at' => now(),
                ]);

            DB::table('products')
                ->where(function ($q) use ($targetTaxId) {
                    $q->whereNull('tax_category_id')->orWhere('tax_category_id', '!=', $targetTaxId);
                })
                ->update([
                    'tax_category_id' => $targetTaxId,
                    'updated_at' => now(),
                ]);
        });

        $countsAfter = $this->getCounts($targetTaxId);
        $this->line(json_encode(['after' => $countsAfter], JSON_UNESCAPED_SLASHES));

        return 0;
    }

    private function getCounts(int $targetTaxId): array
    {
        $penerimaanNotTarget = (int) DB::table('penerimaan')
            ->where(function ($q) use ($targetTaxId) {
                $q->whereNull('tax_category_id')->orWhere('tax_category_id', '!=', $targetTaxId);
            })
            ->count();

        $warehouseStockNotTarget = (int) DB::table('warehouse_stock')
            ->where(function ($q) use ($targetTaxId) {
                $q->whereNull('tax_id')->orWhere('tax_id', '!=', $targetTaxId);
            })
            ->count();

        $productsNotTarget = (int) DB::table('products')
            ->where(function ($q) use ($targetTaxId) {
                $q->whereNull('tax_category_id')->orWhere('tax_category_id', '!=', $targetTaxId);
            })
            ->count();

        $hgnPoPenerimaan = DB::table('penerimaan')
            ->select('id', 'kode_penerimaan', 'nomor_po', 'tax_category_id')
            ->where('nomor_po', 'like', '%HGN%')
            ->orWhere('nomor_po', 'like', '%HGN-SDA%')
            ->orWhere('nomor_po', 'like', '%/HGN%')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'kode_penerimaan' => (string) $row->kode_penerimaan,
                    'nomor_po' => (string) $row->nomor_po,
                    'tax_category_id' => $row->tax_category_id === null ? null : (int) $row->tax_category_id,
                ];
            })
            ->toArray();

        return [
            'target_tax_id' => $targetTaxId,
            'penerimaan_not_target' => $penerimaanNotTarget,
            'warehouse_stock_not_target' => $warehouseStockNotTarget,
            'products_not_target' => $productsNotTarget,
            'recent_hgn_like_penerimaan' => $hgnPoPenerimaan,
        ];
    }
}

