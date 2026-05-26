<?php

namespace App\Console\Commands;

use App\Models\BarangKeluar;
use App\Models\Lokasi;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\Satuan;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevisePenerimaanFromCsv extends Command
{
    protected $signature = 'penerimaan:revise-from-csv 
                            {kode_penerimaan : Kode penerimaan}
                            {file : CSV file path}
                            {--force : Skip confirmation}';

    protected $description = 'Replace/revisi penerimaan dari CSV (qty, cogs, item, warehouse stock) dan menjaga koneksi penjualan/barang keluar via stock';

    public function handle()
    {
        $identifier = $this->argument('kode_penerimaan');
        $filePath = $this->argument('file');

        $this->info("Mencari penerimaan dengan identifier: {$identifier}");

        $penerimaan = Penerimaan::withoutGlobalScopes()
            ->where('kode_penerimaan', $identifier)
            ->first();

        if (! $penerimaan) {
            $penerimaan = Penerimaan::withoutGlobalScopes()
                ->where('nomor_po', $identifier)
                ->first();
        }

        if (! $penerimaan) {
            $this->error("Penerimaan dengan identifier '{$identifier}' tidak ditemukan (coba cek kode_penerimaan atau nomor_po).");

            return 1;
        }

        if (! file_exists($filePath)) {
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

        $rawRows = [];

        if (($handle = fopen($filePath, 'r')) === false) {
            $this->error("Gagal membuka file CSV: {$filePath}");

            return 1;
        }

        $header = fgetcsv($handle);
        $nameIndex = 0;
        $qtyIndex = 1;
        $priceIndex = 2;
        $edIndex = null;

        if (is_array($header) && count($header) > 0) {
            $normalizedHeader = array_map(function ($h) {
                return strtoupper(trim((string) $h));
            }, $header);

            foreach ($normalizedHeader as $idx => $col) {
                if ($col === 'NAMA BARANG' || $col === 'NAMA' || str_contains($col, 'NAMA')) {
                    $nameIndex = $idx;
                } elseif ($col === 'QTY' || str_contains($col, 'QTY')) {
                    $qtyIndex = $idx;
                } elseif ($col === 'HARGA' || str_contains($col, 'HARGA')) {
                    $priceIndex = $idx;
                } elseif ($col === 'ED' || str_contains($col, 'ED')) {
                    $edIndex = $idx;
                }
            }
        }

        while (($data = fgetcsv($handle)) !== false) {
            if (! is_array($data) || count($data) < 3) {
                continue;
            }

            $name = trim((string) ($data[$nameIndex] ?? ''));
            if ($name === '') {
                continue;
            }

            $newQty = $this->parseNumber($data[$qtyIndex] ?? null);
            $newCogs = $this->parseNumber($data[$priceIndex] ?? null);
            $expiredDate = $this->parseExpiredDate($edIndex !== null ? ($data[$edIndex] ?? null) : null);

            $rawRows[] = [
                'name' => $name,
                'qty' => $newQty,
                'cogs' => $newCogs,
                'expired_date' => $expiredDate,
            ];
        }

        fclose($handle);

        if (empty($rawRows)) {
            $this->error('Tidak ada data valid di CSV.');

            return 1;
        }

        $this->info("\nBaris CSV valid: ".count($rawRows).' items');

        if (! $this->option('force')) {
            if (! $this->confirm('Apakah Anda yakin ingin melanjutkan revisi?')) {
                $this->info('Revisi dibatalkan.');

                return 0;
            }
        }

        $unresolved = [];
        $desiredMap = [];

        foreach ($rawRows as $row) {
            $product = $this->findProductByName($row['name']);
            if (! $product) {
                $unresolved[] = $row['name'];

                continue;
            }

            $expiredKey = $row['expired_date'] ? $row['expired_date']->format('Y-m-d') : '';
            $cogsKey = number_format(round((float) $row['cogs'], 2), 2, '.', '');
            $key = $product->id.'|'.$expiredKey.'|'.$cogsKey;

            if (! isset($desiredMap[$key])) {
                $desiredMap[$key] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'expired_date' => $row['expired_date'],
                    'harga_hpp' => (float) $cogsKey,
                    'qty' => 0.0,
                ];
            }

            $desiredMap[$key]['qty'] += (float) $row['qty'];
        }

        if (! empty($unresolved)) {
            $this->error('Produk tidak ditemukan untuk beberapa baris CSV. Tidak ada perubahan yang dilakukan.');
            foreach (array_slice($unresolved, 0, 50) as $name) {
                $this->line(" - {$name}");
            }
            if (count($unresolved) > 50) {
                $this->line(' - ...');
            }

            return 1;
        }

        $desiredEntries = array_values($desiredMap);
        if (empty($desiredEntries)) {
            $this->error('Tidak ada produk valid setelah matching ke master products.');

            return 1;
        }

        try {
            DB::beginTransaction();

            $gudangALokasi = Lokasi::where('kode', 'GUDANG_A')->first();
            if (! $gudangALokasi) {
                $gudangALokasi = Lokasi::first();
                if (! $gudangALokasi) {
                    throw new \Exception('Tidak ada lokasi ditemukan');
                }
                $this->warn("GUDANG_A tidak ditemukan, menggunakan lokasi pertama: {$gudangALokasi->name}");
            }

            $lokasiId = $penerimaan->lokasi_id ?: $gudangALokasi->id;
            $pcsSatuanId = Satuan::where('kode', 'PCS')->value('id');
            if (! $pcsSatuanId) {
                $pcsSatuanId = Satuan::query()->value('id');
            }
            if (! $pcsSatuanId) {
                throw new \Exception('Satuan PCS tidak ditemukan');
            }

            $details = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)->get();
            $detailIds = $details->pluck('id')->all();

            $stocks = collect();
            if (! empty($detailIds)) {
                $stocks = WarehouseStock::withoutGlobalScopes()
                    ->whereIn('penerimaan_detail_id', $detailIds)
                    ->get();
            }

            $stockIds = $stocks->pluck('id')->all();

            $soldByStockId = collect();
            $orderItemRefStockIds = [];
            $offlineSaleRefStockIds = [];

            if (! empty($stockIds)) {
                $soldByStockId = BarangKeluar::selectRaw('warehouse_stock_id, SUM(qty) as sold_qty')
                    ->whereIn('warehouse_stock_id', $stockIds)
                    ->groupBy('warehouse_stock_id')
                    ->pluck('sold_qty', 'warehouse_stock_id');

                $orderItemRefStockIds = DB::table('order_items')
                    ->whereIn('warehouse_stock_id', $stockIds)
                    ->whereNotNull('warehouse_stock_id')
                    ->pluck('warehouse_stock_id')
                    ->unique()
                    ->all();

                $offlineSaleRefStockIds = DB::table('offline_sale_items')
                    ->whereIn('warehouse_stock_id', $stockIds)
                    ->pluck('warehouse_stock_id')
                    ->unique()
                    ->all();
            }

            $orderItemRefSet = array_fill_keys($orderItemRefStockIds, true);
            $offlineSaleRefSet = array_fill_keys($offlineSaleRefStockIds, true);

            $stocksByDetailId = [];
            $stocksByProductId = [];
            foreach ($stocks as $stock) {
                $productId = (int) $stock->product_id;
                if (! isset($stocksByProductId[$productId])) {
                    $stocksByProductId[$productId] = [];
                }
                $stocksByProductId[$productId][] = $stock;

                $detailId = $stock->penerimaan_detail_id;
                if ($detailId) {
                    if (! isset($stocksByDetailId[$detailId])) {
                        $stocksByDetailId[$detailId] = [];
                    }
                    $stocksByDetailId[$detailId][] = $stock;
                }
            }

            $desiredByProductId = [];
            foreach ($desiredEntries as $entry) {
                $productId = (int) $entry['product_id'];
                if (! isset($desiredByProductId[$productId])) {
                    $desiredByProductId[$productId] = [];
                }
                $desiredByProductId[$productId][] = $entry;
            }

            $assignedStockIds = [];
            $createdDetails = 0;
            $createdStocks = 0;
            $deletedStocks = 0;
            $deletedDetails = 0;
            $skippedDeleteStocks = 0;
            $adjustedOversold = 0;

            foreach ($desiredByProductId as $targetProductId => $entriesForProduct) {
                usort($entriesForProduct, function ($a, $b) {
                    $qtyA = (float) ($a['qty'] ?? 0);
                    $qtyB = (float) ($b['qty'] ?? 0);
                    if ($qtyA === $qtyB) {
                        return 0;
                    }

                    return $qtyB <=> $qtyA;
                });

                $existingProductStocks = $stocksByProductId[$targetProductId] ?? [];

                usort($existingProductStocks, function ($a, $b) use ($soldByStockId) {
                    $soldA = (float) ($soldByStockId[$a->id] ?? 0);
                    $soldB = (float) ($soldByStockId[$b->id] ?? 0);
                    if ($soldA === $soldB) {
                        return $a->id <=> $b->id;
                    }

                    return $soldB <=> $soldA;
                });

                $allocations = array_fill(0, count($entriesForProduct), []);
                $capacity = [];
                foreach ($entriesForProduct as $i => $e) {
                    $capacity[$i] = (float) ($e['qty'] ?? 0);
                }

                foreach ($existingProductStocks as $stock) {
                    $sold = (float) ($soldByStockId[$stock->id] ?? 0);
                    $bestIdx = null;
                    $bestCap = -INF;

                    foreach ($capacity as $i => $cap) {
                        if ($cap >= $sold && $cap > $bestCap) {
                            $bestCap = $cap;
                            $bestIdx = $i;
                        }
                    }

                    if ($bestIdx === null) {
                        foreach ($capacity as $i => $cap) {
                            if ($cap > $bestCap) {
                                $bestCap = $cap;
                                $bestIdx = $i;
                            }
                        }
                    }

                    if ($bestIdx === null) {
                        $bestIdx = 0;
                    }

                    $allocations[$bestIdx][] = $stock;
                    $capacity[$bestIdx] = ($capacity[$bestIdx] ?? 0) - $sold;
                    $assignedStockIds[$stock->id] = true;
                }

                foreach ($entriesForProduct as $idx => $entry) {
                    $targetQtyCsv = (float) ($entry['qty'] ?? 0);
                    $targetCogs = (float) ($entry['harga_hpp'] ?? 0);
                    $targetExpired = $entry['expired_date'] ? Carbon::parse($entry['expired_date'])->startOfDay() : null;

                    $entryStocks = $allocations[$idx] ?? [];

                    if (empty($entryStocks)) {
                        $detail = PenerimaanDetail::create([
                            'penerimaan_id' => $penerimaan->id,
                            'product_id' => $targetProductId,
                            'qty' => $targetQtyCsv,
                            'satuan_id' => $pcsSatuanId,
                            'harga_hpp' => $targetCogs,
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
                            'is_free' => 0,
                            'subtotal' => max(0, ($targetQtyCsv * $targetCogs)),
                            'catatan' => null,
                        ]);
                        $createdDetails++;

                        WarehouseStock::withoutGlobalScopes()->create([
                            'product_id' => $targetProductId,
                            'lokasi_id' => $lokasiId,
                            'penerimaan_detail_id' => $detail->id,
                            'tax_id' => $penerimaan->tax_category_id,
                            'qty' => $targetQtyCsv,
                            'expired_date' => $targetExpired ? $targetExpired->format('Y-m-d') : null,
                            'status_ed' => 'aman',
                            'catatan' => '',
                            'source_type' => 'penerimaan',
                            'source_id' => $penerimaan->id,
                            'source_date' => ($penerimaan->tanggal_penerimaan ?? now())->format('Y-m-d'),
                        ]);
                        $createdStocks++;

                        continue;
                    }

                    $detailIdsInEntry = [];
                    foreach ($entryStocks as $s) {
                        if ($s->penerimaan_detail_id) {
                            $detailIdsInEntry[$s->penerimaan_detail_id] = true;
                        }
                    }
                    $detailIdsInEntry = array_keys($detailIdsInEntry);

                    $reuseDetailId = null;
                    if (count($detailIdsInEntry) === 1) {
                        $candidateDetailId = (int) $detailIdsInEntry[0];
                        $allStocksForCandidate = $stocksByDetailId[$candidateDetailId] ?? [];
                        if (count($allStocksForCandidate) === count($entryStocks)) {
                            $reuseDetailId = $candidateDetailId;
                        }
                    }

                    if ($reuseDetailId) {
                        $detail = PenerimaanDetail::find($reuseDetailId);
                    } else {
                        $detail = PenerimaanDetail::create([
                            'penerimaan_id' => $penerimaan->id,
                            'product_id' => $targetProductId,
                            'qty' => $targetQtyCsv,
                            'satuan_id' => $pcsSatuanId,
                            'harga_hpp' => $targetCogs,
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
                            'is_free' => 0,
                            'subtotal' => max(0, ($targetQtyCsv * $targetCogs)),
                            'catatan' => null,
                        ]);
                        $createdDetails++;
                    }

                    $soldTotal = 0.0;
                    $oldRemainingTotal = 0.0;
                    foreach ($entryStocks as $stock) {
                        $soldTotal += (float) ($soldByStockId[$stock->id] ?? 0);
                        $oldRemainingTotal += (float) $stock->qty;
                    }

                    $effectiveQty = $targetQtyCsv;
                    if ($soldTotal > $effectiveQty + 0.00001) {
                        $effectiveQty = $soldTotal;
                        $adjustedOversold++;
                    }

                    $newRemainingTotal = $effectiveQty - $soldTotal;

                    $computedNewQtys = [];
                    if ($oldRemainingTotal > 0) {
                        $ratio = $newRemainingTotal / $oldRemainingTotal;
                        $running = 0.0;
                        foreach ($entryStocks as $k => $stock) {
                            $isLast = ($k === count($entryStocks) - 1);
                            $newQty = $isLast ? ($newRemainingTotal - $running) : round(((float) $stock->qty) * $ratio, 2);
                            if ($newQty < 0) {
                                $newQty = 0.0;
                            }
                            $computedNewQtys[$stock->id] = $newQty;
                            $running += $newQty;
                        }
                    } else {
                        foreach ($entryStocks as $k => $stock) {
                            $computedNewQtys[$stock->id] = ($k === 0) ? round($newRemainingTotal, 2) : 0.0;
                        }
                    }

                    $detail->product_id = $targetProductId;
                    $detail->qty = $effectiveQty;
                    $detail->harga_hpp = $targetCogs;
                    $detail->subtotal = max(0, ($effectiveQty * $targetCogs));
                    $detail->save();

                    foreach ($entryStocks as $stock) {
                        $stock->product_id = $targetProductId;
                        $stock->lokasi_id = $lokasiId;
                        $stock->tax_id = $penerimaan->tax_category_id;
                        $stock->expired_date = $targetExpired ? $targetExpired->format('Y-m-d') : null;
                        $stock->penerimaan_detail_id = $detail->id;
                        $stock->source_type = 'penerimaan';
                        $stock->source_id = $penerimaan->id;
                        $stock->source_date = ($penerimaan->tanggal_penerimaan ?? now())->format('Y-m-d');
                        $stock->qty = (float) ($computedNewQtys[$stock->id] ?? $stock->qty);
                        $stock->save();
                    }
                }
            }

            $extraStocks = $stocks->filter(function ($stock) use ($assignedStockIds) {
                return ! isset($assignedStockIds[$stock->id]);
            });

            foreach ($extraStocks as $stock) {
                $soldQty = (float) ($soldByStockId[$stock->id] ?? 0);
                $hasRef = $soldQty > 0
                    || isset($orderItemRefSet[$stock->id])
                    || isset($offlineSaleRefSet[$stock->id]);

                if ($hasRef) {
                    $skippedDeleteStocks++;

                    continue;
                }

                $detailId = $stock->penerimaan_detail_id;
                WarehouseStock::withoutGlobalScopes()->where('id', $stock->id)->delete();
                $deletedStocks++;

                if ($detailId) {
                    $stillHasStock = WarehouseStock::withoutGlobalScopes()
                        ->where('penerimaan_detail_id', $detailId)
                        ->exists();
                    if (! $stillHasStock) {
                        PenerimaanDetail::where('id', $detailId)->delete();
                        $deletedDetails++;
                    }
                }
            }

            $detailIdsThisPenerimaan = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)->pluck('id')->all();
            if (! empty($detailIdsThisPenerimaan)) {
                $detailIdsWithStock = WarehouseStock::withoutGlobalScopes()
                    ->whereIn('penerimaan_detail_id', $detailIdsThisPenerimaan)
                    ->pluck('penerimaan_detail_id')
                    ->unique()
                    ->all();

                $detailIdWithStockSet = array_fill_keys($detailIdsWithStock, true);
                foreach ($detailIdsThisPenerimaan as $detailId) {
                    if (isset($detailIdWithStockSet[$detailId])) {
                        continue;
                    }
                    PenerimaanDetail::where('id', $detailId)->delete();
                    $deletedDetails++;
                }
            }

            $penerimaan->recalculateTotalHarga();
            $this->info("\nTotal harga penerimaan berhasil dihitung ulang: ".number_format($penerimaan->total_harga, 2));
            $this->info("Ringkas: detail dibuat={$createdDetails}, detail dihapus={$deletedDetails}, stock dibuat={$createdStocks}, stock dihapus={$deletedStocks}, stock tidak bisa dihapus={$skippedDeleteStocks}, qty-diset-minimal-sold={$adjustedOversold}");

            DB::commit();

            $this->info("\nRevisi dari CSV selesai.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    protected function findProductByName(string $rawName): ?Product
    {
        $rawName = trim($rawName);
        if ($rawName === '') {
            return null;
        }

        $candidates = [];
        $candidates[] = $rawName;

        $withoutPrefix = preg_replace('/^\d+\s*-\s*/', '', $rawName);
        if ($withoutPrefix && $withoutPrefix !== $rawName) {
            $candidates[] = trim($withoutPrefix);
        }

        $normalizedSpaces = preg_replace('/\s+/', ' ', $rawName);
        if ($normalizedSpaces && $normalizedSpaces !== $rawName) {
            $candidates[] = trim($normalizedSpaces);
        }

        $withoutPrefixNormalized = preg_replace('/\s+/', ' ', (string) $withoutPrefix);
        if ($withoutPrefixNormalized && $withoutPrefixNormalized !== $normalizedSpaces) {
            $candidates[] = trim($withoutPrefixNormalized);
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        foreach ($candidates as $name) {
            $product = Product::withoutGlobalScopes()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();
            if ($product) {
                return $product;
            }
        }

        foreach ($candidates as $name) {
            $escaped = addcslashes($name, '%_');
            $product = Product::withoutGlobalScopes()
                ->where('name', 'like', '%'.$escaped.'%')
                ->orderByRaw('ABS(LENGTH(name) - ?) asc', [mb_strlen($name)])
                ->first();
            if ($product) {
                return $product;
            }
        }

        foreach ($candidates as $name) {
            $tokens = preg_split('/[^A-Z0-9]+/i', strtoupper($name));
            $tokens = array_values(array_filter($tokens, function ($t) {
                return mb_strlen($t) >= 3;
            }));

            if (count($tokens) < 3) {
                continue;
            }

            usort($tokens, function ($a, $b) {
                return mb_strlen($b) <=> mb_strlen($a);
            });

            $tokens = array_slice($tokens, 0, 6);

            for ($k = count($tokens); $k >= 3; $k--) {
                $subset = array_slice($tokens, 0, $k);
                $query = Product::withoutGlobalScopes()->newQuery();
                foreach ($subset as $t) {
                    $query->whereRaw('UPPER(name) LIKE ?', ['%'.$t.'%']);
                }

                $product = $query->orderByRaw('ABS(LENGTH(name) - ?) asc', [mb_strlen($name)])->first();
                if ($product) {
                    return $product;
                }
            }
        }

        return null;
    }

    protected function parseExpiredDate($value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        $value = str_replace(['"', "'"], '', $value);
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
        } catch (\Throwable $e) {
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
        }

        return null;
    }

    protected function parseNumber($value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace([' ', '"', "'"], '', $value);
        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($hasComma && ! $hasDot) {
            $value = str_replace(',', '.', $value);
        } elseif (! $hasComma && $hasDot) {
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                $value = str_replace('.', '', $value);
            }
        } else {
            $value = str_replace(',', '', $value);
        }

        return (float) $value;
    }
}
