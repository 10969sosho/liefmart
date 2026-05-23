<?php

namespace App\Console\Commands;

use App\Models\BarangKeluar;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWarehouseStockDiscrepancies extends Command
{
    protected $signature = 'stock:fix-discrepancies 
                            {--product_id= : Fix specific product ID}
                            {--dry-run : Show what would be fixed without making changes}
                            {--force : Force fix without confirmation}';

    protected $description = 'Fix warehouse stock discrepancies by adjusting warehouse_stock.qty to match real stock from mutations';

    public function handle()
    {
        $productId = $this->option('product_id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->line('');
        }

        // Build query for products
        $productQuery = Product::query();
        if ($productId) {
            $productQuery->where('id', $productId);
        }

        $products = $productQuery->orderBy('id')->get();

        $this->info('Memeriksa dan memperbaiki warehouse stock...');
        $this->info('Total produk yang akan diperiksa: '.$products->count());
        $this->line('');

        $fixes = [];
        $totalChecked = 0;
        $totalFixed = 0;

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();

        foreach ($products as $product) {
            $totalChecked++;

            // Calculate real stock (same logic as CheckAllWarehouseStock)
            $realStock = $this->calculateRealStock($product->id);

            // Get current warehouse stock
            $currentWarehouseStock = WarehouseStock::where('product_id', $product->id)
                ->where('is_damaged', false)
                ->sum('qty');

            // Calculate difference
            $difference = $currentWarehouseStock - $realStock;

            // Check if there's a discrepancy (allow small rounding differences)
            if (abs($difference) > 0.01) {
                $fixes[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'current_stock' => $currentWarehouseStock,
                    'real_stock' => $realStock,
                    'difference' => $difference,
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        if (empty($fixes)) {
            $this->info('✓ Semua warehouse stock sudah sesuai!');

            return 0;
        }

        $this->warn('Ditemukan '.count($fixes).' produk dengan selisih yang perlu diperbaiki:');
        $this->line('');

        // Show summary table
        $headers = ['Product ID', 'Product Name', 'SKU', 'Current Stock', 'Real Stock', 'Selisih'];
        $rows = [];
        foreach ($fixes as $fix) {
            $rows[] = [
                $fix['product_id'],
                substr($fix['product_name'], 0, 40),
                $fix['product_sku'],
                number_format($fix['current_stock'], 2),
                number_format($fix['real_stock'], 2),
                number_format($fix['difference'], 2),
            ];
        }
        $this->table($headers, $rows);
        $this->line('');

        if ($dryRun) {
            $this->info('DRY RUN - Tidak ada perubahan yang dilakukan. Hapus --dry-run untuk melakukan perbaikan.');

            return 0;
        }

        // Ask for confirmation
        if (! $force && ! $this->confirm('Apakah Anda yakin ingin memperbaiki semua selisih ini?', true)) {
            $this->info('Operasi dibatalkan.');

            return 0;
        }

        $this->line('');
        $this->info('Memulai perbaikan...');
        $this->line('');

        $fixProgressBar = $this->output->createProgressBar(count($fixes));
        $fixProgressBar->start();

        DB::beginTransaction();

        try {
            foreach ($fixes as $fix) {
                $this->fixProductStock($fix['product_id'], $fix['real_stock']);
                $totalFixed++;
                $fixProgressBar->advance();
            }

            DB::commit();
            $fixProgressBar->finish();
            $this->line('');
            $this->line('');
            $this->info("✓ Berhasil memperbaiki {$totalFixed} produk!");

        } catch (\Exception $e) {
            DB::rollBack();
            $fixProgressBar->finish();
            $this->line('');
            $this->error('Terjadi kesalahan: '.$e->getMessage());
            $this->error('Semua perubahan telah di-rollback.');

            return 1;
        }

        return 0;
    }

    /**
     * Calculate real stock from mutations (same logic as CheckAllWarehouseStock)
     */
    private function calculateRealStock($productId)
    {
        // Get all stock IN movements (warehouse_stock records)
        $stockInMovements = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
            ->with('penerimaanDetail')
            ->get();

        // Group by penerimaan_detail_id to avoid double counting
        $penerimaanGroups = [];
        $returStocks = [];

        foreach ($stockInMovements as $movement) {
            if ($movement->penerimaanDetail && ! in_array($movement->source_type, ['retur_penjualan', 'retur_offline'])) {
                // Group by penerimaan_detail_id (only count once per penerimaan_detail)
                $pdId = $movement->penerimaan_detail_id;
                if (! isset($penerimaanGroups[$pdId])) {
                    $penerimaanGroups[$pdId] = $movement->penerimaanDetail->qty;
                }
            } else {
                // For returns, add directly
                $returStocks[] = $movement;
            }
        }

        // Calculate total stock IN
        $totalStockIn = array_sum($penerimaanGroups);

        // Add retur quantities from retur_detail tables (sumber kebenaran, bukan warehouse_stock.qty)
        $totalReturPenjualanQty = \App\Models\ReturPenjualanDetail::where('product_id', $productId)
            ->where('kondisi', 'BAGUS')
            ->sum('qty');
        $totalReturOfflineQty = \App\Models\ReturOfflineSaleDetail::where('product_id', $productId)
            ->where('kondisi', 'BAGUS')
            ->sum('qty');
        $totalStockIn += ($totalReturPenjualanQty + $totalReturOfflineQty);

        // Add penyesuaian (warehouse_stock dengan source_type = 'penyesuaian')
        $totalPenyesuaianQty = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
            ->where('source_type', 'penyesuaian')
            ->sum('qty');
        $totalStockIn += $totalPenyesuaianQty;

        // Calculate total barang keluar
        $totalBarangKeluarQty = BarangKeluar::whereHas('warehouseStock', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        })
            ->sum('qty');

        // Real stock = (Penerimaan + Retur) - Barang Keluar
        // Can be negative if barang keluar > (penerimaan + retur)
        $realStock = $totalStockIn - $totalBarangKeluarQty;

        // Round to integer (qty should be whole number), but can be negative
        return round($realStock);
    }

    /**
     * Fix stock for a specific product
     * Distributes the target stock proportionally to existing warehouse_stock records
     * Excludes penyesuaian records (they are part of the mutation calculation)
     */
    private function fixProductStock($productId, $targetRealStock)
    {
        // Get all non-damaged warehouse_stock records EXCEPT penyesuaian (penyesuaian is part of mutation)
        $warehouseStocks = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
            ->where('source_type', '!=', 'penyesuaian')
            ->orderBy('qty', 'desc')
            ->get();

        if ($warehouseStocks->isEmpty()) {
            return;
        }

        // Calculate current total
        $currentTotal = $warehouseStocks->sum('qty');

        if (abs($currentTotal - $targetRealStock) < 0.01) {
            // Already correct, no changes needed
            return;
        }

        // If target stock is 0 or negative, set all to 0 (warehouse_stock cannot be negative in database)
        // Note: The display will show negative real stock from mutation calculation
        if ($targetRealStock <= 0) {
            foreach ($warehouseStocks as $ws) {
                $ws->qty = 0;
                $ws->save();
            }

            return;
        }

        // If current total is 0 but target is > 0, distribute evenly among all records (rounded)
        if ($currentTotal <= 0) {
            $perRecord = round($targetRealStock / $warehouseStocks->count());
            $distributed = 0;
            $count = $warehouseStocks->count();
            foreach ($warehouseStocks as $index => $ws) {
                if ($index === $count - 1) {
                    // Last record gets the remainder
                    $ws->qty = max(0, round($targetRealStock - $distributed));
                } else {
                    $ws->qty = max(0, $perRecord);
                    $distributed += $ws->qty;
                }
                $ws->save();
            }

            return;
        }

        // Proportional distribution: adjust each record proportionally to maintain ratios
        // Round to integer for each qty
        $ratio = $targetRealStock / $currentTotal;
        $distributed = 0;
        $count = $warehouseStocks->count();

        foreach ($warehouseStocks as $index => $ws) {
            if ($index === $count - 1) {
                // Last record gets the remainder to ensure exact total (rounded to integer)
                $ws->qty = max(0, round($targetRealStock - $distributed));
            } else {
                $newQty = $ws->qty * $ratio;
                $ws->qty = max(0, round($newQty));
                $distributed += $ws->qty;
            }
            $ws->save();
        }
    }
}
