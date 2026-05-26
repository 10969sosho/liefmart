<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\BarangKeluar;
use App\Models\ReturPenjualanDetail;
use App\Models\ReturOfflineSaleDetail;
use Illuminate\Support\Facades\DB;

class FixNegativeStockMutation extends Command
{
    protected $signature = 'stock:fix-negative-mutation 
                            {--product_id= : Fix specific product ID}
                            {--dry-run : Show what would be fixed without making changes}
                            {--force : Force fix without confirmation}';
    
    protected $description = 'Fix negative stock mutations by adding penyesuaian barang as warehouse_stock';

    public function handle()
    {
        $productId = $this->option('product_id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
            $this->line('');
        }
        
        // Build query for products
        $productQuery = Product::query();
        if ($productId) {
            $productQuery->where('id', $productId);
        }
        
        $products = $productQuery->orderBy('id')->get();
        
        $this->info("Memeriksa produk dengan real stock negatif...");
        $this->info("Total produk yang akan diperiksa: " . $products->count());
        $this->line('');
        
        $fixes = [];
        $totalChecked = 0;
        
        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();
        
        foreach ($products as $product) {
            $totalChecked++;
            
            // Calculate real stock
            $realStock = $this->calculateRealStock($product->id);
            
            // If real stock is negative, we need to fix it to 0 by adding penyesuaian
            if ($realStock < 0) {
                // Add penyesuaian to make real stock 0
                $adjustmentQty = abs($realStock); // How much we need to add as penyesuaian
                
                $fixes[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'real_stock' => $realStock,
                    'adjustment_qty' => $adjustmentQty,
                ];
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->line('');
        $this->line('');
        
        if (empty($fixes)) {
            $this->info("✓ Tidak ada produk dengan real stock negatif!");
            return 0;
        }
        
        $this->warn("Ditemukan " . count($fixes) . " produk dengan real stock negatif:");
        $this->line('');
        
        // Show summary table
        $headers = ['Product ID', 'Product Name', 'SKU', 'Real Stock', 'Penyesuaian yang akan ditambahkan'];
        $rows = [];
        foreach ($fixes as $fix) {
            $rows[] = [
                $fix['product_id'],
                substr($fix['product_name'], 0, 40),
                $fix['product_sku'],
                number_format($fix['real_stock'], 0),
                '+' . number_format($fix['adjustment_qty'], 0) . ' unit',
            ];
        }
        $this->table($headers, $rows);
        $this->line('');
        
        if ($dryRun) {
            $this->info("DRY RUN - Tidak ada perubahan yang dilakukan. Hapus --dry-run untuk melakukan perbaikan.");
            return 0;
        }
        
        // Ask for confirmation
        if (!$force && !$this->confirm('Apakah Anda yakin ingin memperbaiki semua produk dengan real stock negatif ini?', true)) {
            $this->info('Operasi dibatalkan.');
            return 0;
        }
        
        $this->line('');
        $this->info("Memulai perbaikan...");
        $this->line('');
        
        $fixProgressBar = $this->output->createProgressBar(count($fixes));
        $fixProgressBar->start();
        
        DB::beginTransaction();
        
        try {
            foreach ($fixes as $fix) {
                $this->fixProduct($fix);
                $fixProgressBar->advance();
            }
            
            DB::commit();
            $fixProgressBar->finish();
            $this->line('');
            $this->line('');
            $this->info("✓ Berhasil memperbaiki " . count($fixes) . " produk!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $fixProgressBar->finish();
            $this->line('');
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            $this->error("Semua perubahan telah di-rollback.");
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Calculate real stock from mutations
     */
    private function calculateRealStock($productId)
    {
        // Get all warehouse_stock records to find penerimaan
        $stockInMovements = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
            ->with('penerimaanDetail')
            ->get();
        
        // Group by penerimaan_detail_id to avoid double counting
        $penerimaanGroups = [];
        foreach ($stockInMovements as $movement) {
            if ($movement->penerimaanDetail && !in_array($movement->source_type, ['retur_penjualan', 'retur_offline', 'penyesuaian'])) {
                $pdId = $movement->penerimaan_detail_id;
                if (!isset($penerimaanGroups[$pdId])) {
                    $penerimaanGroups[$pdId] = $movement->penerimaanDetail->qty;
                }
            }
        }
        
        // Calculate total penerimaan
        $totalPenerimaanQty = array_sum($penerimaanGroups);
        
        // Get retur quantities from retur_detail tables
        $totalReturPenjualanQty = ReturPenjualanDetail::where('product_id', $productId)->sum('qty');
        $totalReturOfflineQty = ReturOfflineSaleDetail::where('product_id', $productId)->sum('qty');
        $totalReturQty = $totalReturPenjualanQty + $totalReturOfflineQty;
        
        // Get penyesuaian (warehouse_stock dengan source_type = 'penyesuaian')
        $totalPenyesuaianQty = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
            ->where('source_type', 'penyesuaian')
            ->sum('qty');
        
        // Calculate total stock IN
        $totalStockIn = $totalPenerimaanQty + $totalReturQty + $totalPenyesuaianQty;
        
        // Calculate total barang keluar
        $totalBarangKeluarQty = BarangKeluar::whereHas('warehouseStock', function($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->sum('qty');
        
        // Real stock = (Penerimaan + Retur) - Barang Keluar (can be negative)
        $realStock = $totalStockIn - $totalBarangKeluarQty;
        
        return $realStock;
    }
    
    /**
     * Fix negative stock for a specific product by adding penyesuaian
     */
    private function fixProduct($fix)
    {
        $productId = $fix['product_id'];
        $adjustmentQty = $fix['adjustment_qty'];
        
        // Get Gudang A location
        $gudangA = \App\Models\Lokasi::where('kode', 'GUDANG_A')->first();
        if (!$gudangA) {
            throw new \Exception("Lokasi Gudang A tidak ditemukan");
        }
        
        // Get product to find default tax_id if needed
        $product = \App\Models\Product::find($productId);
        if (!$product) {
            throw new \Exception("Product ID {$productId} tidak ditemukan");
        }
        
        // Find reference warehouse stock to get tax_id and other attributes
        $referenceStock = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
            ->where('qty', '>', 0)
            ->orderBy('created_at', 'asc')
            ->first();
        
        $taxId = $referenceStock ? $referenceStock->tax_id : null;
        $expiredDate = $referenceStock ? $referenceStock->expired_date : null;
        
        // Create penyesuaian warehouse_stock record
        WarehouseStock::create([
            'product_id' => $productId,
            'lokasi_id' => $gudangA->id,
            'penerimaan_detail_id' => null, // Penyesuaian tidak terkait dengan penerimaan
            'tax_id' => $taxId,
            'qty' => round($adjustmentQty),
            'qty_damaged' => 0,
            'expired_date' => $expiredDate,
            'status_ed' => $expiredDate ? 'aman' : null,
            'is_damaged' => false,
            'source_type' => 'penyesuaian',
            'source_id' => null,
            'source_date' => now(),
            'catatan' => 'Penyesuaian barang - Koreksi mutasi stock negatif',
        ]);
    }
}

