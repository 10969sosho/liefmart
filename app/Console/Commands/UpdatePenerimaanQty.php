<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePenerimaanQty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penerimaan:update-qty 
                            {nomor_po : Nomor PO untuk penerimaan yang akan diupdate}
                            {product_name : Nama produk (partial match)}
                            {new_qty : Qty baru}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update qty penerimaan dan warehouse_stock untuk produk tertentu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $nomorPo = $this->argument('nomor_po');
        $productName = $this->argument('product_name');
        $newQty = (float) $this->argument('new_qty');

        $this->info("Mencari penerimaan dengan nomor PO: {$nomorPo}");

        // Find penerimaan
        $penerimaan = Penerimaan::where('nomor_po', $nomorPo)->first();

        if (!$penerimaan) {
            $this->error("Penerimaan dengan nomor PO '{$nomorPo}' tidak ditemukan.");
            return 1;
        }

        $this->info("Penerimaan ditemukan: {$penerimaan->kode_penerimaan} (ID: {$penerimaan->id})");

        // Find product by name (partial match)
        $product = Product::where('name', 'like', "%{$productName}%")->first();

        if (!$product) {
            $this->error("Produk dengan nama mengandung '{$productName}' tidak ditemukan.");
            return 1;
        }

        $this->info("Produk ditemukan: {$product->name} (ID: {$product->id})");

        // Find penerimaan detail
        $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$penerimaanDetail) {
            $this->error("Detail penerimaan untuk produk '{$product->name}' tidak ditemukan.");
            return 1;
        }

        $oldQty = $penerimaanDetail->qty;
        $qtyDiff = $newQty - $oldQty;

        $this->info("Qty saat ini: {$oldQty}");
        $this->info("Qty baru: {$newQty}");
        $this->info("Selisih: {$qtyDiff}");

        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan update?')) {
                $this->info('Update dibatalkan.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // Update penerimaan_detail qty
            $penerimaanDetail->qty = $newQty;
            $penerimaanDetail->save();

            $this->info("✅ Penerimaan detail qty berhasil diupdate");

            // Update warehouse_stock qty using delta
            $warehouseStocks = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->get();

            if ($warehouseStocks->count() > 0) {
                $this->info("Menemukan {$warehouseStocks->count()} warehouse_stock record(s)");

                // Apply delta to the first stock record (LIFO/FIFO logic simplified)
                // If qtyDiff is positive, add to stock
                // If qtyDiff is negative, reduce from stock (check if enough)
                
                $remainingDiff = abs($qtyDiff);
                $isAddition = $qtyDiff > 0;
                
                if ($isAddition) {
                    // Add to the first stock record found
                    $stock = $warehouseStocks->first();
                    $this->line("  Adding {$remainingDiff} to Stock ID {$stock->id}: {$stock->qty} -> " . ($stock->qty + $remainingDiff));
                    $stock->qty += $remainingDiff;
                    $stock->save();
                } else {
                    // Reduce from stocks
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
                }

                // Verify total
                $newTotalStockQty = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->sum('qty');
                $this->line("  Total warehouse stock setelah update: {$newTotalStockQty}");

                $this->info("✅ Warehouse stock qty berhasil diupdate");
            } else {
                $this->warn("Tidak ada warehouse_stock yang ditemukan untuk penerimaan_detail ini.");
            }

            // Recalculate total harga
            $penerimaan->recalculateTotalHarga();
            $this->info("✅ Total harga penerimaan berhasil dihitung ulang");

            DB::commit();

            $this->info("\n✅ Update berhasil!");
            $this->info("Penerimaan: {$penerimaan->kode_penerimaan}");
            $this->info("Produk: {$product->name}");
            $this->info("Qty: {$oldQty} -> {$newQty}");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            return 1;
        }
    }
}

