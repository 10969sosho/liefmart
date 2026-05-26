<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Lokasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevisePenerimaanQty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penerimaan:revise-qty 
                            {kode_penerimaan : Kode penerimaan (contoh: PR-20250930-587)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisi qty penerimaan dan tambahkan selisih ke warehouse stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $kodePenerimaan = $this->argument('kode_penerimaan');

        $this->info("Mencari penerimaan dengan kode: {$kodePenerimaan}");

        // Find penerimaan by kode_penerimaan
        $penerimaan = Penerimaan::where('kode_penerimaan', $kodePenerimaan)->first();

        if (!$penerimaan) {
            $this->error("Penerimaan dengan kode '{$kodePenerimaan}' tidak ditemukan.");
            return 1;
        }

        $this->info("Penerimaan ditemukan: {$penerimaan->kode_penerimaan} (ID: {$penerimaan->id})");
        $this->info("Nomor PO: {$penerimaan->nomor_po}");

        // Define the products to update
        $productsToUpdate = [
            [
                'name' => 'VIVA COMPACT POWDER STANDAR CLASSIC 19G -  NATURAL',
                'new_qty' => 8,
                'old_qty' => 7
            ],
            [
                'name' => 'VIVA COVERING CREAM BEIGE',
                'new_qty' => 265,
                'old_qty' => 251
            ]
        ];

        $this->info("\nProduk yang akan direvisi:");
        foreach ($productsToUpdate as $productUpdate) {
            $this->line("  - {$productUpdate['name']}: {$productUpdate['old_qty']} -> {$productUpdate['new_qty']} pcs");
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan revisi?')) {
                $this->info('Revisi dibatalkan.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // Get Gudang A location
            $gudangALokasi = Lokasi::where('kode', 'GUDANG_A')->first();
            if (!$gudangALokasi) {
                throw new \Exception('Lokasi Gudang A tidak ditemukan');
            }

            foreach ($productsToUpdate as $productUpdate) {
                $this->info("\n--- Memproses: {$productUpdate['name']} ---");

                // Find product by name (exact or partial match)
                $product = Product::where('name', 'like', "%{$productUpdate['name']}%")->first();

                if (!$product) {
                    $this->error("Produk '{$productUpdate['name']}' tidak ditemukan.");
                    continue;
                }

                $this->info("Produk ditemukan: {$product->name} (ID: {$product->id})");

                // Find penerimaan detail
                $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$penerimaanDetail) {
                    $this->error("Detail penerimaan untuk produk '{$product->name}' tidak ditemukan.");
                    continue;
                }

                $oldQty = (float) $penerimaanDetail->qty;
                $newQty = (float) $productUpdate['new_qty'];
                $qtyDiff = $newQty - $oldQty;

                $this->info("Qty saat ini: {$oldQty}");
                $this->info("Qty baru: {$newQty}");
                $this->info("Selisih: {$qtyDiff}");

                // Update penerimaan_detail qty
                $penerimaanDetail->qty = $newQty;
                $penerimaanDetail->save();
                $this->info("✅ Penerimaan detail qty berhasil diupdate");

                // Check existing warehouse stock
                $warehouseStocks = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->get();
                $currentTotalStockQty = $warehouseStocks->sum('qty');

                if ($warehouseStocks->count() > 0) {
                    $this->info("Menemukan {$warehouseStocks->count()} warehouse_stock record(s) dengan total qty: {$currentTotalStockQty}");

                    if ($qtyDiff > 0) {
                        // Add the difference to warehouse stock
                        // Try to add to the first existing stock, or create new one
                        $firstStock = $warehouseStocks->first();
                        
                        if ($firstStock) {
                            // Add difference to first stock
                            $firstStock->qty += $qtyDiff;
                            $firstStock->save();
                            $this->info("✅ Menambahkan {$qtyDiff} pcs ke warehouse stock ID {$firstStock->id}");
                        } else {
                            // Create new warehouse stock
                            WarehouseStock::create([
                                'product_id' => $penerimaanDetail->product_id,
                                'lokasi_id' => $gudangALokasi->id,
                                'penerimaan_detail_id' => $penerimaanDetail->id,
                                'tax_id' => $penerimaan->tax_category_id,
                                'qty' => $qtyDiff,
                                'expired_date' => null,
                                'status_ed' => 'aman',
                                'catatan' => '',
                                'source_type' => 'penerimaan',
                                'source_id' => $penerimaan->id,
                                'source_date' => $penerimaan->tanggal_penerimaan ?? now(),
                            ]);
                            $this->info("✅ Membuat warehouse stock baru dengan qty: {$qtyDiff}");
                        }
                    } elseif ($qtyDiff < 0) {
                        // Reduce warehouse stock using delta (not ratio)
                        $remainingDiff = abs($qtyDiff);
                        
                        foreach ($warehouseStocks as $stock) {
                            if ($remainingDiff <= 0) break;
                            
                            $reduceAmount = min($stock->qty, $remainingDiff);
                            $newStockQty = $stock->qty - $reduceAmount;
                            
                            $this->line("  Reducing {$reduceAmount} from Stock ID {$stock->id}: {$stock->qty} -> {$newStockQty}");
                            $stock->qty = $newStockQty;
                            $stock->save();
                            
                            $remainingDiff -= $reduceAmount;
                        }
                        
                        if ($remainingDiff > 0) {
                            $this->warn("  Warning: Could not reduce full amount. Remaining diff: {$remainingDiff}");
                        }
                        
                        $this->info("✅ Warehouse stock qty berhasil dikurangi");
                    }
                } else {
                    // No warehouse stock exists, create new one for the full new quantity
                    $this->info("Tidak ada warehouse_stock yang ditemukan. Membuat warehouse stock baru...");
                    
                    WarehouseStock::create([
                        'product_id' => $penerimaanDetail->product_id,
                        'lokasi_id' => $gudangALokasi->id,
                        'penerimaan_detail_id' => $penerimaanDetail->id,
                        'tax_id' => $penerimaan->tax_category_id,
                        'qty' => $newQty,
                        'expired_date' => null,
                        'status_ed' => 'aman',
                        'catatan' => '',
                        'source_type' => 'penerimaan',
                        'source_id' => $penerimaan->id,
                        'source_date' => $penerimaan->tanggal_penerimaan ?? now(),
                    ]);
                    $this->info("✅ Warehouse stock baru dibuat dengan qty: {$newQty}");
                }

                // Verify total warehouse stock
                $newTotalStockQty = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->sum('qty');
                $this->info("Total warehouse stock setelah update: {$newTotalStockQty}");
            }

            // Recalculate total harga
            $penerimaan->recalculateTotalHarga();
            $this->info("\n✅ Total harga penerimaan berhasil dihitung ulang");

            DB::commit();

            $this->info("\n✅ Revisi berhasil!");
            $this->info("Penerimaan: {$penerimaan->kode_penerimaan}");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

