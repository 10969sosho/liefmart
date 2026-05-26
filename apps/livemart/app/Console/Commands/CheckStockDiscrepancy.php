<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\PenerimaanDetail;
use App\Models\BarangKeluar;
use Illuminate\Support\Facades\DB;

class CheckStockDiscrepancy extends Command
{
    protected $signature = 'stock:check-discrepancy {product_name?}';
    protected $description = 'Check stock discrepancy between warehouse_stock and penerimaan_detail for a product';

    public function handle()
    {
        $productName = $this->argument('product_name') ?? 'SALSA NAIL POLISH REMOVER 40ML';
        
        $this->info("Checking stock for: {$productName}");
        $this->line('');
        
        // Find the product
        $product = Product::where('name', 'like', '%' . $productName . '%')->first();
        
        if (!$product) {
            $this->error("Product not found: {$productName}");
            return 1;
        }
        
        $this->info("Product ID: {$product->id}");
        $this->info("Product Name: {$product->name}");
        $this->info("Product SKU: {$product->sku}");
        $this->line('');
        
        // Get all warehouse_stock records for this product
        $warehouseStocks = WarehouseStock::where('product_id', $product->id)
            ->where('is_damaged', false)
            ->with(['penerimaanDetail', 'tax'])
            ->get();
        
        $this->info("=== WAREHOUSE_STOCK RECORDS ===");
        $this->info("Total records: " . $warehouseStocks->count());
        
        $totalWarehouseQty = 0;
        $warehouseStocksByPenerimaan = [];
        
        foreach ($warehouseStocks as $ws) {
            $totalWarehouseQty += $ws->qty;
            
            $penerimaanDetailId = $ws->penerimaan_detail_id ?? 'NULL';
            if (!isset($warehouseStocksByPenerimaan[$penerimaanDetailId])) {
                $warehouseStocksByPenerimaan[$penerimaanDetailId] = [];
            }
            $warehouseStocksByPenerimaan[$penerimaanDetailId][] = $ws;
            
            $this->line("  ID: {$ws->id}, Qty: {$ws->qty}, Penerimaan Detail ID: {$penerimaanDetailId}, Tax ID: " . ($ws->tax_id ?? 'NULL') . ", Source Type: " . ($ws->source_type ?? 'NULL'));
        }
        
        $this->info("Total warehouse_stock.qty: {$totalWarehouseQty}");
        $this->line('');
        
        // Get all penerimaan_detail records for this product
        $penerimaanDetails = PenerimaanDetail::where('product_id', $product->id)
            ->with(['penerimaan'])
            ->orderBy('id', 'asc')
            ->get();
        
        $this->info("=== PENERIMAAN_DETAIL (BARANG DITERIMA) ===");
        $this->info("Total records: " . $penerimaanDetails->count());
        
        $totalPenerimaanQty = 0;
        foreach ($penerimaanDetails as $pd) {
            $totalPenerimaanQty += $pd->qty;
            $this->line("  ID: {$pd->id}, Qty: {$pd->qty}, Penerimaan ID: {$pd->penerimaan_id}, Tanggal: " . ($pd->penerimaan->tanggal_penerimaan ?? 'N/A') . ", Kode: " . ($pd->penerimaan->kode_penerimaan ?? 'N/A'));
        }
        
        $this->info("TOTAL BARANG DITERIMA (penerimaan_detail.qty): {$totalPenerimaanQty}");
        $this->line('');
        
        // Get all barang_keluar (stock OUT) for this product
        $barangKeluar = BarangKeluar::whereHas('warehouseStock', function($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->with('warehouseStock')
            ->orderBy('tanggal_keluar', 'asc')
            ->get();
        
        $this->info("=== BARANG_KELUAR (STOCK OUT) ===");
        $this->info("Total records: " . $barangKeluar->count());
        
        $totalBarangKeluarQty = 0;
        foreach ($barangKeluar as $bk) {
            $totalBarangKeluarQty += $bk->qty;
            $this->line("  ID: {$bk->id}, Qty: {$bk->qty}, Tanggal: {$bk->tanggal_keluar}");
        }
        
        $this->info("TOTAL BARANG KELUAR (barang_keluar.qty): {$totalBarangKeluarQty}");
        $this->line('');
        
        // Get all retur (stock IN from returns)
        $returnStocks = WarehouseStock::where('product_id', $product->id)
            ->where('is_damaged', false)
            ->whereIn('source_type', ['retur_penjualan', 'retur_offline'])
            ->with(['returPenjualan', 'returOfflineSale'])
            ->orderBy('id', 'asc')
            ->get();
        
        $this->info("=== BARANG RETUR (STOCK IN FROM RETUR) ===");
        $this->info("Total records: " . $returnStocks->count());
        
        $totalReturQty = 0;
        foreach ($returnStocks as $rs) {
            $totalReturQty += $rs->qty;
            $returInfo = '';
            if ($rs->source_type === 'retur_penjualan' && $rs->returPenjualan) {
                $returInfo = "Retur Penjualan ID: {$rs->returPenjualan->id}, Tanggal: " . ($rs->returPenjualan->tanggal_retur ?? 'N/A');
            } elseif ($rs->source_type === 'retur_offline' && $rs->returOfflineSale) {
                $returInfo = "Retur Offline ID: {$rs->returOfflineSale->id}, Tanggal: " . ($rs->returOfflineSale->tanggal_retur ?? 'N/A');
            }
            $this->line("  Warehouse Stock ID: {$rs->id}, Qty: {$rs->qty}, Source Type: {$rs->source_type}, {$returInfo}");
        }
        
        $this->info("TOTAL BARANG RETUR (warehouse_stock.qty dari retur): {$totalReturQty}");
        $this->line('');
        
        // Calculate expected stock using mutation logic
        $expectedStock = 0;
        
        // Stock IN from penerimaan_detail
        $expectedStock += $totalPenerimaanQty;
        
        // Stock IN from returns
        $expectedStock += $totalReturQty;
        
        // Stock OUT
        $expectedStock -= $totalBarangKeluarQty;
        
        $this->info("=== RINGKASAN PERHITUNGAN ===");
        $this->info("1. BARANG DITERIMA (penerimaan_detail): {$totalPenerimaanQty}");
        $this->info("2. BARANG RETUR (retur masuk): +{$totalReturQty}");
        $this->info("3. BARANG KELUAR (barang_keluar): -{$totalBarangKeluarQty}");
        $this->line("   ─────────────────────────────");
        $this->info("   STOK SEHARUSNYA: " . max(0, $expectedStock));
        $this->line('');
        $this->info("Total warehouse_stock.qty (yang ditampilkan di menu): {$totalWarehouseQty}");
        $this->info("Selisih: " . ($totalWarehouseQty - max(0, $expectedStock)));
        $this->line('');
        
        // Check for discrepancies
        $this->info("=== DISCREPANCY ANALYSIS ===");
        
        // Check if warehouse_stock records match penerimaan_detail
        foreach ($warehouseStocksByPenerimaan as $pdId => $wsRecords) {
            if ($pdId === 'NULL') {
                continue;
            }
            
            $pd = PenerimaanDetail::find($pdId);
            if ($pd) {
                $wsTotalQty = collect($wsRecords)->sum('qty');
                if ($wsTotalQty != $pd->qty) {
                    $this->warn("  Penerimaan Detail ID {$pdId}: penerimaan_detail.qty = {$pd->qty}, but warehouse_stock total = {$wsTotalQty}");
                }
            }
        }
        
        // Check for warehouse_stock records with qty > 0 but no corresponding penerimaan_detail
        foreach ($warehouseStocks as $ws) {
            if ($ws->qty > 0 && !$ws->penerimaan_detail_id && !$ws->source_type) {
                $this->warn("  Warehouse Stock ID {$ws->id} has qty {$ws->qty} but no penerimaan_detail_id or source_type");
            }
        }
        
        return 0;
    }
}

