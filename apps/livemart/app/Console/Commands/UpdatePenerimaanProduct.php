<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePenerimaanProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'penerimaan:update-product 
                            {nomor_po : Nomor PO untuk penerimaan yang akan diupdate}
                            {old_product_name : Nama produk lama}
                            {new_product_name : Nama produk baru}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update product_id pada penerimaan_detail dan warehouse_stock untuk PO tertentu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $nomorPo = $this->argument('nomor_po');
        $oldProductName = $this->argument('old_product_name');
        $newProductName = $this->argument('new_product_name');

        $this->info("Mencari penerimaan dengan nomor PO: {$nomorPo}");

        // Find penerimaan - need to disable global scope temporarily
        $penerimaan = Penerimaan::withoutGlobalScopes()
            ->where('nomor_po', $nomorPo)
            ->first();

        if (!$penerimaan) {
            $this->error("Penerimaan dengan nomor PO '{$nomorPo}' tidak ditemukan.");
            return 1;
        }

        $this->info("✅ Penerimaan ditemukan: {$penerimaan->kode_penerimaan} (ID: {$penerimaan->id})");
        $this->info("   Total Harga: " . number_format($penerimaan->total_harga, 2));

        // Find old product
        $oldProduct = Product::where('name', 'like', "%{$oldProductName}%")->first();

        if (!$oldProduct) {
            $this->error("Produk lama dengan nama mengandung '{$oldProductName}' tidak ditemukan.");
            return 1;
        }

        $this->info("✅ Produk lama ditemukan: {$oldProduct->name} (ID: {$oldProduct->id})");

        // Find new product
        $newProduct = Product::where('name', 'like', "%{$newProductName}%")->first();

        if (!$newProduct) {
            $this->error("Produk baru dengan nama mengandung '{$newProductName}' tidak ditemukan.");
            return 1;
        }

        $this->info("✅ Produk baru ditemukan: {$newProduct->name} (ID: {$newProduct->id})");

        // Find penerimaan detail
        $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
            ->where('product_id', $oldProduct->id)
            ->first();

        if (!$penerimaanDetail) {
            $this->error("Detail penerimaan untuk produk '{$oldProduct->name}' tidak ditemukan pada PO ini.");
            return 1;
        }

        $this->info("✅ Penerimaan detail ditemukan (ID: {$penerimaanDetail->id})");
        $this->info("   Qty: {$penerimaanDetail->qty}");
        $this->info("   Harga HPP: " . number_format($penerimaanDetail->harga_hpp, 2));
        $this->info("   Subtotal: " . number_format($penerimaanDetail->subtotal, 2));

        // Check warehouse_stocks
        $warehouseStocks = WarehouseStock::withoutGlobalScopes()
            ->where('penerimaan_detail_id', $penerimaanDetail->id)
            ->get();

        $this->info("\n📦 Warehouse Stocks ditemukan: {$warehouseStocks->count()} record(s)");
        if ($warehouseStocks->count() > 0) {
            foreach ($warehouseStocks as $stock) {
                $this->line("   - Stock ID {$stock->id}: Qty = {$stock->qty}, Product ID = {$stock->product_id}");
            }
        }

        if (!$this->option('force')) {
            $this->warn("\n⚠️  PERINGATAN: Akan mengubah product_id dari {$oldProduct->id} ke {$newProduct->id}");
            $this->warn("   Pada penerimaan_detail ID: {$penerimaanDetail->id}");
            if ($warehouseStocks->count() > 0) {
                $this->warn("   Dan {$warehouseStocks->count()} warehouse_stock record(s)");
            }
            
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan update?')) {
                $this->info('Update dibatalkan.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            // Update penerimaan_detail product_id
            $oldProductId = $penerimaanDetail->product_id;
            $penerimaanDetail->product_id = $newProduct->id;
            $penerimaanDetail->save();

            $this->info("\n✅ Penerimaan detail product_id berhasil diupdate: {$oldProductId} -> {$newProduct->id}");

            // Update warehouse_stock product_id
            if ($warehouseStocks->count() > 0) {
                $updatedCount = 0;
                foreach ($warehouseStocks as $stock) {
                    $stock->product_id = $newProduct->id;
                    $stock->save();
                    $updatedCount++;
                }
                $this->info("✅ {$updatedCount} warehouse_stock record(s) berhasil diupdate");
            } else {
                $this->warn("Tidak ada warehouse_stock yang ditemukan untuk penerimaan_detail ini.");
            }

            // Recalculate total harga (meskipun tidak berubah, untuk konsistensi)
            $penerimaan->recalculateTotalHarga();
            $this->info("✅ Total harga penerimaan berhasil dihitung ulang");

            DB::commit();

            $this->info("\n✅ Update berhasil!");
            $this->info("Penerimaan: {$penerimaan->kode_penerimaan}");
            $this->info("PO: {$penerimaan->nomor_po}");
            $this->info("Produk: {$oldProduct->name} -> {$newProduct->name}");
            $this->info("Product ID: {$oldProductId} -> {$newProduct->id}");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}

