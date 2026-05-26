<?php

namespace App\Console\Commands;

use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\BarangKeluar;
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
                            {kode_penerimaan : Kode penerimaan (contoh: GR-LM-20260228084753)}
                            {--product= : Pencarian produk (nama/SKU, contains)}
                            {--product-id= : Product ID (prioritas jika diisi)}
                            {--detail-id= : Penerimaan detail ID (prioritas tertinggi)}
                            {--new-qty= : Qty baru (wajib jika pakai --product/--product-id)}
                            {--new-harga= : Harga HPP/satuan baru (opsional)}
                            {--create-detail : Buat detail penerimaan jika belum ada (wajib isi --new-qty & --new-harga)}
                            {--delete-detail : Hapus baris item dari penerimaan setelah penyesuaian}
                            {--recalc-all : Hitung ulang subtotal semua detail penerimaan (tanpa ubah qty/harga)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisi qty penerimaan + update warehouse stock; jika qty dikurangi dan bentrok penjualan, buat warehouse_stock baru + tandai "adjust stock"';

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

        if ($this->option('recalc-all')) {
            if (!$this->option('force')) {
                if (!$this->confirm('Hitung ulang subtotal semua detail penerimaan?')) {
                    $this->info('Dibatalkan.');
                    return 0;
                }
            }

            try {
                DB::beginTransaction();

                $details = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)->get();
                $changed = 0;

                foreach ($details as $detail) {
                    $newSubtotal = $this->calculateSubtotal($detail);
                    if (abs(((float) $detail->subtotal) - $newSubtotal) > 0.00001) {
                        $detail->subtotal = $newSubtotal;
                        $detail->save();
                        $changed++;
                    }
                }

                $penerimaan->recalculateTotalHarga();
                DB::commit();

                $this->info("✅ Recalc selesai. Detail berubah: {$changed}");
                return 0;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Terjadi kesalahan: " . $e->getMessage());
                return 1;
            }
        }

        $productIdOption = $this->option('product-id');
        $productQueryOption = $this->option('product');
        $detailIdOption = $this->option('detail-id');
        $newQtyOption = $this->option('new-qty');
        $newHargaOption = $this->option('new-harga');

        $productsToUpdate = [];
        if ($detailIdOption) {
            if ($newQtyOption === null || $newQtyOption === '') {
                $this->error('Jika menggunakan --detail-id, --new-qty wajib diisi.');
                return 1;
            }

            $productsToUpdate[] = [
                'detail_id' => (int) $detailIdOption,
                'new_qty' => (float) $newQtyOption,
                'new_harga' => $newHargaOption !== null && $newHargaOption !== '' ? (float) $newHargaOption : null,
            ];
        } elseif ($productIdOption || $productQueryOption) {
            if ($newQtyOption === null || $newQtyOption === '') {
                $this->error('Jika menggunakan --product/--product-id, --new-qty wajib diisi.');
                return 1;
            }

            $productsToUpdate[] = [
                'product_id' => $productIdOption ? (int) $productIdOption : null,
                'query' => $productQueryOption ? (string) $productQueryOption : null,
                'new_qty' => (float) $newQtyOption,
                'new_harga' => $newHargaOption !== null && $newHargaOption !== '' ? (float) $newHargaOption : null,
            ];
        } else {
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
        }

        $this->info("\nProduk yang akan direvisi:");
        foreach ($productsToUpdate as $productUpdate) {
            $label = $productUpdate['detail_id'] ?? ($productUpdate['product_id'] ?? ($productUpdate['query'] ?? ($productUpdate['name'] ?? 'N/A')));
            $oldLabel = $productUpdate['old_qty'] ?? '?';
            $this->line("  - {$label}: {$oldLabel} -> {$productUpdate['new_qty']} pcs");
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
                $product = null;
                $penerimaanDetail = null;

                if (isset($productUpdate['detail_id']) && $productUpdate['detail_id']) {
                    $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
                        ->where('id', (int) $productUpdate['detail_id'])
                        ->first();

                    if (! $penerimaanDetail) {
                        $this->error("Detail penerimaan ID {$productUpdate['detail_id']} tidak ditemukan pada penerimaan ini.");
                        continue;
                    }

                    $product = Product::find($penerimaanDetail->product_id);
                    if (! $product) {
                        $this->error("Produk untuk detail ID {$productUpdate['detail_id']} tidak ditemukan.");
                        continue;
                    }

                    $this->info("\n--- Memproses Detail ID: {$productUpdate['detail_id']} ---");
                } elseif (isset($productUpdate['product_id']) && $productUpdate['product_id']) {
                    $product = Product::find($productUpdate['product_id']);
                    $this->info("\n--- Memproses Product ID: {$productUpdate['product_id']} ---");
                } elseif (isset($productUpdate['query']) && $productUpdate['query']) {
                    $query = trim((string) $productUpdate['query']);
                    $this->info("\n--- Memproses query produk: {$query} ---");
                    $product = Product::where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->first();
                } else {
                    $this->info("\n--- Memproses: {$productUpdate['name']} ---");
                    $product = Product::where('name', 'like', "%{$productUpdate['name']}%")->first();
                }

                if (!$product) {
                    $this->error("Produk tidak ditemukan.");
                    continue;
                }

                $this->info("Produk ditemukan: {$product->name} (ID: {$product->id})");

                // Find penerimaan detail
                if (! $penerimaanDetail) {
                    $penerimaanDetail = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
                        ->where('product_id', $product->id)
                        ->first();
                }

                if (!$penerimaanDetail) {
                    if (! $this->option('create-detail')) {
                        $this->error("Detail penerimaan untuk produk '{$product->name}' tidak ditemukan.");
                        continue;
                    }

                    if (! isset($productUpdate['new_harga']) || $productUpdate['new_harga'] === null) {
                        $this->error("Untuk create detail baru, --new-harga wajib diisi.");
                        continue;
                    }

                    $satuanId = $this->resolveSatuanIdForProduct($product->id);
                    $penerimaanDetail = PenerimaanDetail::create([
                        'penerimaan_id' => $penerimaan->id,
                        'product_id' => $product->id,
                        'qty' => 0,
                        'satuan_id' => $satuanId,
                        'harga_hpp' => (float) $productUpdate['new_harga'],
                        'diskon_persen_1' => 0,
                        'diskon_nominal_1' => 0,
                        'diskon_persen_2' => 0,
                        'diskon_nominal_2' => 0,
                        'diskon_persen_3' => 0,
                        'diskon_nominal_3' => 0,
                        'diskon_persen_4' => 0,
                        'diskon_nominal_4' => 0,
                        'diskon_persen_5' => 0,
                        'diskon_nominal_5' => 0,
                        'is_free' => false,
                        'subtotal' => 0,
                        'catatan' => null,
                    ]);

                    $this->info("✅ Detail penerimaan baru dibuat (detail_id: {$penerimaanDetail->id})");
                }

                $oldQty = (float) $penerimaanDetail->qty;
                $newQty = (float) $productUpdate['new_qty'];
                $qtyDiff = $newQty - $oldQty;

                $this->info("Qty saat ini: {$oldQty}");
                $this->info("Qty baru: {$newQty}");
                $this->info("Selisih: {$qtyDiff}");

                $penerimaanDetail->qty = $newQty;
                if (isset($productUpdate['new_harga']) && $productUpdate['new_harga'] !== null) {
                    $oldHarga = (float) $penerimaanDetail->harga_hpp;
                    $newHarga = (float) $productUpdate['new_harga'];
                    $this->info("Harga HPP: {$oldHarga} -> {$newHarga}");
                    $penerimaanDetail->harga_hpp = $newHarga;
                }

                $penerimaanDetail->subtotal = $this->calculateSubtotal($penerimaanDetail);
                $penerimaanDetail->save();
                $this->info("✅ Penerimaan detail berhasil diupdate");

                $warehouseStocks = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->get();
                if ($warehouseStocks->count() <= 0) {
                    $this->info("Tidak ada warehouse_stock yang ditemukan. Membuat warehouse stock baru...");

                    $newStock = WarehouseStock::create([
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

                    $warehouseStocks = collect([$newStock]);
                }

                $stockIds = $warehouseStocks->pluck('id')->all();
                $soldQty = (float) BarangKeluar::whereIn('warehouse_stock_id', $stockIds)->sum('qty');
                $this->info("Total penjualan (barang_keluar) dari penerimaan detail ini: {$soldQty}");

                $excessSold = $soldQty - $newQty;
                if ($excessSold > 0.00001) {
                    $this->warn("Qty penerimaan lebih kecil dari penjualan. Selisih penjualan yang harus dipindah: {$excessSold}");
                    $adjustStock = $this->getOrCreateAdjustStock($penerimaanDetail, $penerimaan, $gudangALokasi);
                    $this->info("✅ Menggunakan warehouse_stock adjust. ID: {$adjustStock->id}");

                    $movedQty = $this->moveStockOutToAdjustStock($stockIds, $adjustStock, (float) $excessSold);
                    if ($movedQty + 0.00001 < $excessSold) {
                        $this->warn("Warning: qty penjualan yang berhasil dipindah kurang dari target. Target: {$excessSold}, pindah: {$movedQty}");
                    }

                    $soldQty = (float) ($soldQty - $movedQty);
                }

                $desiredRemaining = max(0, (float) ($newQty - $soldQty));
                $currentRemaining = (float) $warehouseStocks->sum('qty');
                $delta = (float) ($desiredRemaining - $currentRemaining);

                if (abs($delta) > 0.00001) {
                    if ($delta > 0) {
                        $firstStock = $warehouseStocks->first();
                        $fromQty = (float) $firstStock->qty;
                        $toQty = $fromQty + $delta;
                        $firstStock->qty = $toQty;
                        $firstStock->save();
                        $this->info("✅ Menambah qty warehouse {$delta} (Stock ID {$firstStock->id}: {$fromQty} -> {$toQty})");
                    } else {
                        $remainingToReduce = abs($delta);
                        foreach ($warehouseStocks as $stock) {
                            if ($remainingToReduce <= 0.00001) {
                                break;
                            }

                            $stockQty = (float) $stock->qty;
                            if ($stockQty <= 0) {
                                continue;
                            }

                            $reduceAmount = min($stockQty, $remainingToReduce);
                            $newStockQty = $stockQty - $reduceAmount;
                            $stock->qty = $newStockQty;
                            $stock->save();

                            $remainingToReduce -= $reduceAmount;
                        }
                        $this->info("✅ Mengurangi qty warehouse ".abs($delta));
                    }
                }

                // Verify total warehouse stock
                $newTotalStockQty = WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->sum('qty');
                $this->info("Total warehouse stock setelah update: {$newTotalStockQty}");

                if ($this->option('delete-detail')) {
                    WarehouseStock::where('penerimaan_detail_id', $penerimaanDetail->id)->update(['penerimaan_detail_id' => null]);
                    $penerimaanDetail->delete();
                    $this->info("✅ Detail penerimaan dihapus");
                }
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

    private function appendAdjustStockNote($existing)
    {
        $existing = $existing === null ? '' : (string) $existing;
        if (stripos($existing, 'adjust stock') !== false) {
            return $existing;
        }
        $existing = trim($existing);
        return $existing === '' ? 'adjust stock' : ($existing.' | adjust stock');
    }

    private function calculateSubtotal($penerimaanDetail): float
    {
        if ((bool) ($penerimaanDetail->is_free ?? false)) {
            return 0.0;
        }

        $qty = (float) ($penerimaanDetail->qty ?? 0);
        $harga = (float) ($penerimaanDetail->harga_hpp ?? 0);
        $subtotal = $qty * $harga;

        for ($i = 1; $i <= 5; $i++) {
            $diskonPersenField = "diskon_persen_{$i}";
            $diskonNominalField = "diskon_nominal_{$i}";
            $diskonPersen = (float) ($penerimaanDetail->{$diskonPersenField} ?? 0);
            $diskonNominal = (float) ($penerimaanDetail->{$diskonNominalField} ?? 0);

            if ($diskonPersen > 0) {
                $subtotal -= ($subtotal * ($diskonPersen / 100));
            } elseif ($diskonNominal > 0) {
                $subtotal -= $diskonNominal;
            }
        }

        return (float) $subtotal;
    }

    private function resolveSatuanIdForProduct(int $productId): int
    {
        $existingSatuanId = PenerimaanDetail::where('product_id', $productId)
            ->orderByDesc('id')
            ->value('satuan_id');

        if ($existingSatuanId) {
            return (int) $existingSatuanId;
        }

        $pcs = \App\Models\Satuan::where('kode', 'PCS')
            ->orWhere('name', 'Pcs')
            ->orWhere('name', 'PCS')
            ->first();

        if ($pcs) {
            return (int) $pcs->id;
        }

        $first = \App\Models\Satuan::first();
        return (int) ($first ? $first->id : 1);
    }

    private function getOrCreateAdjustStock($penerimaanDetail, $penerimaan, $gudangALokasi)
    {
        $existing = WarehouseStock::where('product_id', $penerimaanDetail->product_id)
            ->whereNull('penerimaan_detail_id')
            ->where('source_type', 'penyesuaian')
            ->where('source_id', $penerimaan->id)
            ->where('catatan', 'like', '%adjust stock%')
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return WarehouseStock::create([
            'product_id' => $penerimaanDetail->product_id,
            'lokasi_id' => $gudangALokasi->id,
            'penerimaan_detail_id' => null,
            'tax_id' => $penerimaan->tax_category_id,
            'qty' => 0,
            'expired_date' => null,
            'status_ed' => 'aman',
            'catatan' => 'adjust stock',
            'source_type' => 'penyesuaian',
            'source_id' => $penerimaan->id,
            'source_date' => $penerimaan->tanggal_penerimaan ?? now(),
        ]);
    }

    private function moveStockOutToAdjustStock(array $sourceWarehouseStockIds, $adjustStock, float $qtyToMove): float
    {
        $stockOutItems = BarangKeluar::whereIn('warehouse_stock_id', $sourceWarehouseStockIds)
            ->orderBy('tanggal_keluar', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $remainingToMove = (float) $qtyToMove;
        $moved = 0.0;

        foreach ($stockOutItems as $stockOut) {
            if ($remainingToMove <= 0.00001) {
                break;
            }

            $bkQty = (float) $stockOut->qty;
            if ($bkQty <= 0) {
                continue;
            }

            if ($bkQty <= $remainingToMove + 0.00001) {
                $stockOut->warehouse_stock_id = $adjustStock->id;
                $stockOut->catatan = $this->appendAdjustStockNote($stockOut->catatan);
                $stockOut->save();
                $remainingToMove -= $bkQty;
                $moved += $bkQty;
            } else {
                $moveQty = $remainingToMove;
                $keepQty = $bkQty - $moveQty;

                $stockOut->qty = $keepQty;
                $stockOut->save();

                BarangKeluar::create([
                    'kode_barang_keluar' => BarangKeluar::generateKode(),
                    'order_item_id' => $stockOut->order_item_id,
                    'offline_sale_item_id' => $stockOut->offline_sale_item_id,
                    'warehouse_stock_id' => $adjustStock->id,
                    'finance_offline_id' => $stockOut->finance_offline_id,
                    'qty' => $moveQty,
                    'tanggal_keluar' => $stockOut->tanggal_keluar,
                    'catatan' => $this->appendAdjustStockNote($stockOut->catatan),
                ]);

                $moved += $moveQty;
                $remainingToMove = 0.0;
            }
        }

        return (float) $moved;
    }
}
