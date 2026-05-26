<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\PenerimaanDetail;
use App\Models\BarangKeluar;
use App\Models\ReturPenjualanDetail;
use App\Models\ReturOfflineSaleDetail;
use App\Models\ReturPembelianDetail;
use Illuminate\Support\Facades\DB;

class CheckAllWarehouseStock extends Command
{
    protected $signature = 'stock:check-all {--product_id= : Check specific product ID} {--output=table : Output format (csv or table)}';
    protected $description = 'Check all warehouse stock and compare with real stock calculation (penerimaan_detail - barang_keluar + retur)';

    public function handle()
    {
        $productId = $this->option('product_id');
        $outputFormat = $this->option('output');
        
        $this->info("Memeriksa semua warehouse stock...");
        $this->line('');
        
        // Build query for products
        $productQuery = Product::query();
        if ($productId) {
            $productQuery->where('id', $productId);
        }
        
        $products = $productQuery->orderBy('id')->get();
        
        $this->info("Total produk yang akan diperiksa: " . $products->count());
        $this->line('');
        
        $discrepancies = [];
        $totalChecked = 0;
        $totalDiscrepancies = 0;
        
        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->start();
        
        foreach ($products as $product) {
            $totalChecked++;
            
            // Calculate real stock berdasarkan MUTASI (sama seperti calculateRunningBalance)
            // Get all stock IN movements (warehouse_stock records)
            $stockInMovements = WarehouseStock::where('product_id', $product->id)
                ->where('is_damaged', false)
                ->with('penerimaanDetail')
                ->get();
            
            // Group warehouse_stock by penerimaan_detail_id to avoid double counting
            // Satu penerimaan_detail bisa punya banyak warehouse_stock records (beda expired_date)
            // Gunakan logika yang sama dengan calculateRunningBalance: hitung semua warehouse_stock records
            // dan gunakan penerimaan_detail.qty untuk setiap penerimaan (tidak peduli qty warehouse_stock saat ini)
            $penerimaanGroups = [];
            $returStocks = [];
            
            foreach ($stockInMovements as $movement) {
                if ($movement->penerimaanDetail && !in_array($movement->source_type, ['retur_penjualan', 'retur_offline', 'penyesuaian'])) {
                    // Group by penerimaan_detail_id (hanya simpan sekali per penerimaan_detail)
                    $pdId = $movement->penerimaan_detail_id;
                    if (!isset($penerimaanGroups[$pdId])) {
                        $penerimaanGroups[$pdId] = $movement->penerimaanDetail->qty;
                    }
                    // Jika sudah ada, tidak perlu dihitung lagi (setiap penerimaan_detail hanya dihitung sekali)
                }
                // Note: penyesuaian akan dihitung terpisah di bawah, tidak perlu ditambahkan ke returStocks
            }
            
            // Calculate total stock IN dari mutasi
            $totalStockIn = 0;
            $totalPenerimaanQty = array_sum($penerimaanGroups);
            $totalReturQty = 0;
            $totalReturPenjualanQty = 0;
            $totalReturOfflineQty = 0;
            
            // Sum penerimaan (setiap penerimaan_detail hanya dihitung sekali dengan qty original)
            $totalStockIn += $totalPenerimaanQty;
            
            // Sum retur menggunakan retur_detail.qty (sumber kebenaran, bukan warehouse_stock.qty)
            // Retur Penjualan
            $totalReturPenjualanQty = ReturPenjualanDetail::where('product_id', $product->id)->sum('qty');
            
            // Retur Offline
            $totalReturOfflineQty = ReturOfflineSaleDetail::where('product_id', $product->id)->sum('qty');
            
            $totalReturQty = $totalReturPenjualanQty + $totalReturOfflineQty;
            $totalStockIn += $totalReturQty;
            
            // Sum penyesuaian (warehouse_stock dengan source_type = 'penyesuaian')
            $totalPenyesuaianQty = WarehouseStock::where('product_id', $product->id)
                ->where('is_damaged', false)
                ->where('source_type', 'penyesuaian')
                ->sum('qty');
            $totalStockIn += $totalPenyesuaianQty;
            
            // 2. Barang Keluar (Barang Keluar)
            $totalBarangKeluarQty = BarangKeluar::whereHas('warehouseStock', function($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->sum('qty');
            
            // Retur Pembelian (return to supplier - mengurangi stock, tapi sudah tercermin di warehouse_stock)
            // Catatan: Retur pembelian mengurangi warehouse_stock langsung, jadi tidak perlu dikurangi lagi
            $totalReturPembelianQty = ReturPembelianDetail::where('product_id', $product->id)
                ->sum('qty');
            
            // Calculate expected/real stock berdasarkan MUTASI
            // Formula: Sum(penerimaan_detail.qty dari warehouse_stock) - barang_keluar + Sum(retur dari warehouse_stock)
            // Real stock bisa negatif jika barang keluar > (penerimaan + retur)
            $realStock = $totalStockIn - $totalBarangKeluarQty;
            
            // Get current warehouse stock (only non-damaged, excluding penyesuaian)
            // Penyesuaian hanya untuk koreksi mutasi, tidak menambah stock fisik
            $currentWarehouseStock = WarehouseStock::where('product_id', $product->id)
                ->where('is_damaged', false)
                ->where('source_type', '!=', 'penyesuaian')
                ->sum('qty');
            
            // Calculate difference
            $difference = $currentWarehouseStock - $realStock;
            
            // Check if there's a discrepancy (allow small rounding differences)
            if (abs($difference) > 0.01) {
                $totalDiscrepancies++;
                $discrepancies[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'penerimaan_qty' => (float)$totalPenerimaanQty,
                    'barang_keluar_qty' => (float)$totalBarangKeluarQty,
                    'retur_qty' => (float)$totalReturQty,
                    'retur_penjualan_qty' => (float)$totalReturPenjualanQty,
                    'retur_offline_qty' => (float)$totalReturOfflineQty,
                    'retur_pembelian_qty' => (float)$totalReturPembelianQty,
                    'penyesuaian_qty' => (float)$totalPenyesuaianQty,
                    'real_stock' => (float)$realStock,
                    'warehouse_stock_qty' => (float)$currentWarehouseStock,
                    'difference' => (float)$difference,
                ];
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->line('');
        $this->line('');
        
        // Display results
        $this->info("=== HASIL PEMERIKSAAN ===");
        $this->info("Total produk diperiksa: {$totalChecked}");
        $this->info("Total produk dengan selisih: {$totalDiscrepancies}");
        $this->line('');
        
        if (empty($discrepancies)) {
            $this->info("✓ Semua warehouse stock sudah sesuai!");
            return 0;
        }
        
        $this->warn("Ditemukan {$totalDiscrepancies} produk dengan selisih:");
        $this->line('');
        
        if ($outputFormat === 'csv') {
            $this->outputCsv($discrepancies);
        } else {
            $this->outputTable($discrepancies);
        }
        
        return 0;
    }
    
    private function outputTable($discrepancies)
    {
        $headers = [
            'Product ID',
            'Product Name',
            'SKU',
            'Penerimaan',
            'Barang Keluar',
            'Retur',
            'Real Stock',
            'Warehouse Stock',
            'Selisih'
        ];
        
        $rows = [];
        foreach ($discrepancies as $item) {
            $rows[] = [
                $item['product_id'],
                substr($item['product_name'], 0, 40),
                $item['product_sku'],
                number_format($item['penerimaan_qty'], 2),
                number_format($item['barang_keluar_qty'], 2),
                number_format($item['retur_qty'], 2),
                number_format($item['real_stock'], 2),
                number_format($item['warehouse_stock_qty'], 2),
                number_format($item['difference'], 2),
            ];
        }
        
        $this->table($headers, $rows);
        
        // Show detailed breakdown for first 10 discrepancies
        if (count($discrepancies) > 0) {
            $this->line('');
            $this->info("=== DETAIL 10 PRODUK PERTAMA DENGAN SELISIH ===");
            
            foreach (array_slice($discrepancies, 0, 10) as $item) {
                $this->line('');
                $this->info("Product ID: {$item['product_id']} - {$item['product_name']}");
                $this->line("  SKU: {$item['product_sku']}");
                $this->line("  Penerimaan Detail: " . number_format($item['penerimaan_qty'], 2));
                $this->line("  Barang Keluar: " . number_format($item['barang_keluar_qty'], 2));
                $this->line("  Retur Penjualan: " . number_format($item['retur_penjualan_qty'], 2));
                $this->line("  Retur Offline: " . number_format($item['retur_offline_qty'], 2));
                $this->line("  Total Retur (Masuk): " . number_format($item['retur_qty'], 2));
                $this->line("  Penyesuaian: " . number_format($item['penyesuaian_qty'] ?? 0, 2));
                $this->line("  Retur Pembelian (Info): " . number_format($item['retur_pembelian_qty'], 2) . " (sudah tercermin di warehouse_stock)");
                $this->line("  ──────────────────────────────");
                $this->line("  Real Stock (dari mutasi): " . number_format($item['real_stock'], 2));
                $this->line("  Warehouse Stock (saat ini): " . number_format($item['warehouse_stock_qty'], 2));
                $this->line("  Selisih: " . number_format($item['difference'], 2));
                
                // Analisa selisih
                if (abs($item['difference']) > 0.01) {
                    $this->line("");
                    $this->warn("  ⚠️  KEMUNGKINAN PENYEBAB SELISIH:");
                    if ($item['difference'] > 0) {
                        $this->line("     - Warehouse stock lebih besar dari perhitungan mutasi");
                        $this->line("     - Mungkin ada stock yang tidak tercatat di penerimaan_detail atau retur");
                        $this->line("     - Atau ada perbedaan kecil dalam pencatatan barang keluar");
                    } else {
                        $this->line("     - Warehouse stock lebih kecil dari perhitungan mutasi");
                        $this->line("     - Mungkin ada stock yang hilang atau tidak tercatat dengan benar");
                    }
                }
            }
        }
    }
    
    private function outputCsv($discrepancies)
    {
        $filename = storage_path('app/warehouse_stock_discrepancies_' . date('Y-m-d_His') . '.csv');
        
        $file = fopen($filename, 'w');
        
        // Write header
        fputcsv($file, [
            'Product ID',
            'Product Name',
            'SKU',
            'Penerimaan Qty',
            'Barang Keluar Qty',
            'Retur Penjualan Qty',
            'Retur Offline Qty',
            'Total Retur Qty',
            'Retur Pembelian Qty',
            'Real Stock',
            'Warehouse Stock Qty',
            'Difference'
        ]);
        
        // Write data
        foreach ($discrepancies as $item) {
            fputcsv($file, [
                $item['product_id'],
                $item['product_name'],
                $item['product_sku'],
                $item['penerimaan_qty'],
                $item['barang_keluar_qty'],
                $item['retur_penjualan_qty'],
                $item['retur_offline_qty'],
                $item['retur_qty'],
                $item['retur_pembelian_qty'],
                $item['real_stock'],
                $item['warehouse_stock_qty'],
                $item['difference'],
            ]);
        }
        
        fclose($file);
        
        $this->info("File CSV telah disimpan: {$filename}");
    }
}

