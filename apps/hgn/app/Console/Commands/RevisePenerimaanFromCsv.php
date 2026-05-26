<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Lokasi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevisePenerimaanFromCsv extends Command
{
    protected $signature = 'penerimaan:revise-from-csv 
                            {kode_penerimaan : Kode penerimaan}
                            {file : CSV file path}
                            {--force : Skip confirmation}';

    protected $description = 'Revisi qty dan cogs penerimaan dari CSV dan update warehouse stock';

    public function handle()
    {
        $kodePenerimaan = $this->argument('kode_penerimaan');
        $filePath = $this->argument('file');

        $this->info("Mencari penerimaan dengan kode: {$kodePenerimaan}");

        $penerimaan = Penerimaan::where('kode_penerimaan', $kodePenerimaan)->first();

        if (!$penerimaan) {
            $this->error("Penerimaan dengan kode '{$kodePenerimaan}' tidak ditemukan.");
            return 1;
        }

        if (!file_exists($filePath)) {
            $altPath = base_path($filePath);
            if (file_exists($altPath)) {
                $filePath = $altPath;
            } else {
                $this->error("File CSV tidak ditemukan: {$filePath}");
                return 1;
            }
        }

        $this->info("Penerimaan ditemukan: {$penerimaan->kode_penerimaan} (ID: {$penerimaan->id})");
        $this->info("Nomor PO: {$penerimaan->nomor_po}");
        $this->info("Membaca data dari CSV: {$filePath}");

        $rows = [];

        if (($handle = fopen($filePath, 'r')) === false) {
            $this->error("Gagal membuka file CSV: {$filePath}");
            return 1;
        }

        $header = fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 3) {
                continue;
            }

            $name = trim($data[0]);
            if ($name === '') {
                continue;
            }

            $newQty = $this->parseNumber($data[1]);
            $newCogs = $this->parseNumber($data[2]);

            $rows[] = [
                'name' => $name,
                'new_qty' => $newQty,
                'new_cogs' => $newCogs,
            ];
        }

        fclose($handle);

        if (empty($rows)) {
            $this->error('Tidak ada data valid di CSV.');
            return 1;
        }

        $this->info("\nProduk yang akan direvisi: " . count($rows) . " items");

        if (!$this->option('force')) {
            if (!$this->confirm('Apakah Anda yakin ingin melanjutkan revisi?')) {
                $this->info('Revisi dibatalkan.');
                return 0;
            }
        }

        try {
            DB::beginTransaction();

            $gudangALokasi = Lokasi::where('kode', 'GUDANG_A')->first();
            if (!$gudangALokasi) {
                $gudangALokasi = Lokasi::first();
                if (!$gudangALokasi) {
                    throw new \Exception('Tidak ada lokasi ditemukan');
                }
                $this->warn("GUDANG_A tidak ditemukan, menggunakan lokasi pertama: {$gudangALokasi->name}");
            }

            foreach ($rows as $productUpdate) {
                $this->info("\n--- Memproses: {$productUpdate['name']} ---");

                $product = Product::where('name', $productUpdate['name'])->first();

                if (!$product) {
                    $product = Product::where('name', 'like', "{$productUpdate['name']}%")
                        ->orWhere('name', 'like', "%{$productUpdate['name']}%")
                        ->first();
                }

                if (!$product) {
                    $this->error("Produk '{$productUpdate['name']}' tidak ditemukan.");
                    continue;
                }

                $this->info("Produk ditemukan: {$product->name} (ID: {$product->id})");

                $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$penerimaanDetail) {
                    $this->error("Detail penerimaan untuk produk '{$product->name}' tidak ditemukan.");
                    continue;
                }

                $oldQty = (float) $penerimaanDetail->qty;
                $newQty = (float) $productUpdate['new_qty'];
                $oldCogs = (float) $penerimaanDetail->harga_hpp;
                $newCogs = (float) $productUpdate['new_cogs'];
                $qtyDiff = $newQty - $oldQty;

                $this->info("Qty: {$oldQty} -> {$newQty} (Diff: {$qtyDiff})");
                $this->info("COGS: {$oldCogs} -> {$newCogs}");

                $penerimaanDetail->qty = $newQty;
                $penerimaanDetail->harga_hpp = $newCogs;

                $discountTotal = 0;
                for ($i = 1; $i <= 5; $i++) {
                    $field = "diskon_nominal_{$i}";
                    $discountTotal += (float) $penerimaanDetail->{$field};
                }

                $subtotal = ($newQty * $newCogs) - $discountTotal;
                if ($subtotal < 0) {
                    $subtotal = 0;
                }

                $penerimaanDetail->subtotal = $subtotal;
                $penerimaanDetail->save();

                $warehouseStocks = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->get();

                if ($warehouseStocks->count() > 0) {
                    if ($qtyDiff > 0) {
                        $firstStock = $warehouseStocks->first();
                        $firstStock->qty += $qtyDiff;
                        $firstStock->save();
                        $this->info("Menambahkan {$qtyDiff} pcs ke warehouse stock ID {$firstStock->id}");
                    } elseif ($qtyDiff < 0 && $oldQty > 0) {
                        $ratio = $newQty / $oldQty;
                        foreach ($warehouseStocks as $stock) {
                            $newStockQty = $stock->qty * $ratio;
                            $stock->qty = $newStockQty;
                            $stock->save();
                        }
                        $this->info('Warehouse stock qty dikurangi secara proporsional');
                    } else {
                        $this->info('Qty tidak berubah, warehouse stock tidak diupdate');
                    }
                } else {
                    if ($newQty > 0) {
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
                        $this->info('Warehouse stock baru dibuat');
                    }
                }
            }

            $penerimaan->recalculateTotalHarga();
            $this->info("\nTotal harga penerimaan berhasil dihitung ulang: " . number_format($penerimaan->total_harga, 2));

            DB::commit();

            $this->info("\nRevisi dari CSV selesai.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function parseNumber($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace([' ', '"', "'"], '', $value);
        $value = str_replace(',', '', $value);

        return (float) $value;
    }
}

