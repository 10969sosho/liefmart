<?php

namespace App\Console\Commands;

use App\Models\BarangKeluar;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecapPenerimaanReplace extends Command
{
    protected $signature = 'penerimaan:recap-replace
                            {identifier : kode_penerimaan atau nomor_po}
                            {file : CSV file path}
                            {--out= : Output file path (default: storage/app/recaps/...)}';

    protected $description = 'Generate recap dokumentasi hasil replace penerimaan dari CSV (item dibuat, stock dibuat, stock sold, extra yang tidak bisa dihapus)';

    public function handle()
    {
        $identifier = (string) $this->argument('identifier');
        $filePath = (string) $this->argument('file');
        $out = $this->option('out');

        $penerimaan = Penerimaan::withoutGlobalScopes()
            ->where('kode_penerimaan', $identifier)
            ->first();

        if (! $penerimaan) {
            $penerimaan = Penerimaan::withoutGlobalScopes()
                ->where('nomor_po', $identifier)
                ->first();
        }

        if (! $penerimaan) {
            $this->error("Penerimaan tidak ditemukan untuk identifier: {$identifier}");

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

        $parsed = $this->parseCsvDesiredMap($filePath);
        if ($parsed['unresolved_count'] > 0) {
            $this->error('Ada produk CSV yang tidak bisa dimapping ke products. Recap dibatalkan.');
            foreach (array_slice($parsed['unresolved_names'], 0, 50) as $name) {
                $this->line(" - {$name}");
            }
            if (count($parsed['unresolved_names']) > 50) {
                $this->line(' - ...');
            }

            return 1;
        }

        $desiredGroups = $parsed['desired_groups'];
        $desiredByProductEd = $parsed['desired_by_product_ed'];

        $details = PenerimaanDetail::where('penerimaan_id', $penerimaan->id)
            ->with('product:id,name')
            ->get();

        $detailIds = $details->pluck('id')->all();

        $stocks = collect();
        if (! empty($detailIds)) {
            $stocks = WarehouseStock::withoutGlobalScopes()
                ->whereIn('penerimaan_detail_id', $detailIds)
                ->get();
        }

        $stockIds = $stocks->pluck('id')->all();

        $soldByStockId = collect();
        $orderItemRefSet = [];
        $offlineSaleRefSet = [];

        if (! empty($stockIds)) {
            $soldByStockId = BarangKeluar::selectRaw('warehouse_stock_id, SUM(qty) as sold_qty')
                ->whereIn('warehouse_stock_id', $stockIds)
                ->groupBy('warehouse_stock_id')
                ->pluck('sold_qty', 'warehouse_stock_id');

            $orderItemRefSet = array_fill_keys(DB::table('order_items')
                ->whereIn('warehouse_stock_id', $stockIds)
                ->whereNotNull('warehouse_stock_id')
                ->pluck('warehouse_stock_id')
                ->unique()
                ->all(), true);

            $offlineSaleRefSet = array_fill_keys(DB::table('offline_sale_items')
                ->whereIn('warehouse_stock_id', $stockIds)
                ->pluck('warehouse_stock_id')
                ->unique()
                ->all(), true);
        }

        $stocksByDetailId = [];
        foreach ($stocks as $stock) {
            $stocksByDetailId[$stock->penerimaan_detail_id][] = $stock;
        }

        $latestDetailCreatedAt = $details->max(fn ($d) => $d->created_at?->getTimestamp() ?? 0);
        $latestStockCreatedAt = $stocks->max(fn ($s) => $s->created_at ? Carbon::parse($s->created_at)->getTimestamp() : 0);
        $latestCreatedAt = max((int) $latestDetailCreatedAt, (int) $latestStockCreatedAt);
        $threshold = $latestCreatedAt > 0
            ? Carbon::createFromTimestamp($latestCreatedAt)->subMinutes(30)
            : now()->subMinutes(30);

        $createdDetails = $details
            ->filter(fn ($d) => $d->created_at && $d->created_at->gte($threshold))
            ->values();

        $createdStocks = $stocks
            ->filter(fn ($s) => $s->created_at && Carbon::parse($s->created_at)->gte($threshold))
            ->values();

        $detailSoldRemaining = [];
        foreach ($details as $detail) {
            $sold = 0.0;
            $remaining = 0.0;
            $eds = [];
            foreach (($stocksByDetailId[$detail->id] ?? []) as $stock) {
                $sold += (float) ($soldByStockId[$stock->id] ?? 0);
                $remaining += (float) $stock->qty;
                $eds[] = $stock->expired_date ? Carbon::parse($stock->expired_date)->format('Y-m-d') : '';
            }
            $detailSoldRemaining[$detail->id] = [
                'sold' => $sold,
                'remaining' => $remaining,
                'eds' => array_values(array_unique($eds)),
            ];
        }

        $soldStocks = [];
        foreach ($stocks as $stock) {
            $sold = (float) ($soldByStockId[$stock->id] ?? 0);
            if ($sold > 0) {
                $soldStocks[] = ['sold' => $sold, 'stock' => $stock];
            }
        }
        usort($soldStocks, fn ($a, $b) => $b['sold'] <=> $a['sold']);

        $extraStocks = [];
        foreach ($stocks as $stock) {
            $edKey = $stock->expired_date ? Carbon::parse($stock->expired_date)->format('Y-m-d') : '';
            $peKey = $stock->product_id.'|'.$edKey;
            if (! isset($desiredByProductEd[$peKey])) {
                $extraStocks[] = $stock;
            }
        }

        $currentGroups = [];
        foreach ($stocks as $stock) {
            $edKey = $stock->expired_date ? Carbon::parse($stock->expired_date)->format('Y-m-d') : '';
            $detail = $details->firstWhere('id', $stock->penerimaan_detail_id);
            $hpp = $detail ? (float) $detail->harga_hpp : 0.0;
            $key = $stock->product_id.'|'.$edKey.'|'.number_format(round($hpp, 2), 2, '.', '');
            if (! isset($currentGroups[$key])) {
                $currentGroups[$key] = [
                    'product_id' => $stock->product_id,
                    'product_name' => $detail?->product?->name ?? $detail?->product_id ?? (string) $stock->product_id,
                    'ed' => $edKey,
                    'hpp' => (float) number_format(round($hpp, 2), 2, '.', ''),
                    'sold' => 0.0,
                    'remaining' => 0.0,
                ];
            }
            $currentGroups[$key]['sold'] += (float) ($soldByStockId[$stock->id] ?? 0);
            $currentGroups[$key]['remaining'] += (float) $stock->qty;
        }

        $outPath = $this->resolveOutPath($out, $penerimaan->nomor_po, $penerimaan->kode_penerimaan);
        $text = $this->buildReportText([
            'penerimaan' => $penerimaan,
            'csv_path' => $filePath,
            'threshold' => $threshold,
            'details' => $details,
            'stocks' => $stocks,
            'desired_groups' => $desiredGroups,
            'current_groups' => $currentGroups,
            'created_details' => $createdDetails,
            'created_stocks' => $createdStocks,
            'sold_stocks' => $soldStocks,
            'extra_stocks' => $extraStocks,
            'sold_by_stock_id' => $soldByStockId,
            'order_ref_set' => $orderItemRefSet,
            'offline_ref_set' => $offlineSaleRefSet,
            'detail_sold_remaining' => $detailSoldRemaining,
        ]);

        $dir = dirname($outPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents($outPath, $text);

        $this->info("Recap tersimpan: {$outPath}");

        return 0;
    }

    protected function resolveOutPath(?string $out, string $nomorPo, string $kodePenerimaan): string
    {
        if (is_string($out) && trim($out) !== '') {
            return $out;
        }

        $safe = preg_replace('/[^A-Z0-9\\-_.]+/i', '_', $nomorPo ?: $kodePenerimaan);
        $file = 'recap_replace_'.$safe.'_'.now()->format('Ymd_His').'.txt';

        return storage_path('app/recaps/'.$file);
    }

    protected function buildReportText(array $ctx): string
    {
        $p = $ctx['penerimaan'];
        $soldTotal = (float) $ctx['sold_by_stock_id']->sum();

        $lines = [];
        $lines[] = 'RECAP REPLACE PENERIMAAN';
        $lines[] = 'PO: '.$p->nomor_po;
        $lines[] = 'KODE PENERIMAAN: '.$p->kode_penerimaan.' (ID: '.$p->id.')';
        $lines[] = 'CSV: '.$ctx['csv_path'];
        $lines[] = 'Generated: '.now()->format('Y-m-d H:i:s');
        $lines[] = '';
        $lines[] = 'SUMMARY';
        $lines[] = '- total_harga: '.$p->total_harga;
        $lines[] = '- total_detail: '.$ctx['details']->count();
        $lines[] = '- total_stock: '.$ctx['stocks']->count();
        $lines[] = '- total_sold_barang_keluar: '.number_format($soldTotal, 2, '.', '');
        $lines[] = '';

        $lines[] = 'DETAIL DIBUAT (estimasi created_at >= '.$ctx['threshold']->format('Y-m-d H:i:s').')';
        foreach ($ctx['created_details'] as $d) {
            $sr = $ctx['detail_sold_remaining'][$d->id] ?? ['sold' => 0, 'remaining' => 0, 'eds' => []];
            $edStr = implode(',', $sr['eds']);
            $lines[] = '- detail_id='.$d->id.' product="'.$d->product?->name.'" qty='.$d->qty.' hpp='.$d->harga_hpp.' ed=['.$edStr.'] sold='.$sr['sold'].' remaining_stock='.$sr['remaining'];
        }
        $lines[] = '';

        $lines[] = 'STOCK DIBUAT (estimasi created_at >= '.$ctx['threshold']->format('Y-m-d H:i:s').')';
        foreach ($ctx['created_stocks'] as $s) {
            $sold = (float) ($ctx['sold_by_stock_id'][$s->id] ?? 0);
            $edKey = $s->expired_date ? Carbon::parse($s->expired_date)->format('Y-m-d') : '';
            $lines[] = '- stock_id='.$s->id.' detail_id='.$s->penerimaan_detail_id.' product_id='.$s->product_id.' ed='.$edKey.' remaining_qty='.$s->qty.' sold='.$sold;
        }
        $lines[] = '';

        $lines[] = 'STOCK SOLD (barang_keluar) - TOP 200';
        foreach (array_slice($ctx['sold_stocks'], 0, 200) as $row) {
            $s = $row['stock'];
            $sold = (float) $row['sold'];
            $edKey = $s->expired_date ? Carbon::parse($s->expired_date)->format('Y-m-d') : '';
            $lines[] = '- stock_id='.$s->id.' detail_id='.$s->penerimaan_detail_id.' product_id='.$s->product_id.' ed='.$edKey.' sold='.$sold.' remaining_qty='.$s->qty;
        }
        if (count($ctx['sold_stocks']) > 200) {
            $lines[] = '- ... ('.(count($ctx['sold_stocks']) - 200).' baris lagi)';
        }
        $lines[] = '';

        $lines[] = 'STOCK EXTRA (tidak ada di CSV berdasarkan product+ED)';
        foreach ($ctx['extra_stocks'] as $s) {
            $sold = (float) ($ctx['sold_by_stock_id'][$s->id] ?? 0);
            $edKey = $s->expired_date ? Carbon::parse($s->expired_date)->format('Y-m-d') : '';
            $refs = [];
            if ($sold > 0) {
                $refs[] = 'sold';
            }
            if (isset($ctx['order_ref_set'][$s->id])) {
                $refs[] = 'order_item_ref';
            }
            if (isset($ctx['offline_ref_set'][$s->id])) {
                $refs[] = 'offline_sale_ref';
            }
            $lines[] = '- stock_id='.$s->id.' detail_id='.$s->penerimaan_detail_id.' product_id='.$s->product_id.' ed='.$edKey.' remaining_qty='.$s->qty.' sold='.$sold.' refs=['.implode(',', $refs).']';
        }
        $lines[] = '';

        $lines[] = 'DESIRED (CSV) GROUPS (product+ED+HPP)';
        foreach ($ctx['desired_groups'] as $g) {
            $lines[] = '- product_id='.$g['product_id'].' product="'.$g['product_name'].'" ed='.$g['ed'].' hpp='.$g['cogs'].' qty='.$g['qty'];
        }
        $lines[] = '';

        $lines[] = 'CURRENT GROUPS (from warehouse_stock + harga_hpp detail)';
        foreach ($ctx['current_groups'] as $g) {
            $total = (float) $g['sold'] + (float) $g['remaining'];
            $lines[] = '- product_id='.$g['product_id'].' product="'.$g['product_name'].'" ed='.$g['ed'].' hpp='.$g['hpp'].' sold='.$g['sold'].' remaining='.$g['remaining'].' total='.$total;
        }
        $lines[] = '';

        $lines[] = 'CATATAN';
        $lines[] = '- Item/detail yang sudah terhapus tidak bisa ditampilkan nama persisnya jika tidak ada audit log.';

        return implode("\n", $lines)."\n";
    }

    protected function parseCsvDesiredMap(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        $nameIndex = 0;
        $qtyIndex = 1;
        $priceIndex = 2;
        $edIndex = null;

        if (is_array($header) && count($header) > 0) {
            $normalizedHeader = array_map(fn ($h) => strtoupper(trim((string) $h)), $header);
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

            $qty = $this->parseNumber($data[$qtyIndex] ?? null);
            $cogs = $this->parseNumber($data[$priceIndex] ?? null);
            $ed = $this->parseExpiredDate($edIndex !== null ? ($data[$edIndex] ?? null) : null);

            $rows[] = [
                'name' => $name,
                'qty' => $qty,
                'cogs' => $cogs,
                'ed' => $ed,
            ];
        }

        fclose($handle);

        $unresolved = [];
        $desiredMap = [];

        foreach ($rows as $row) {
            $product = $this->findProductByName($row['name']);
            if (! $product) {
                $unresolved[] = $row['name'];

                continue;
            }

            $edKey = $row['ed'] ? $row['ed']->format('Y-m-d') : '';
            $cogsKey = number_format(round((float) $row['cogs'], 2), 2, '.', '');
            $key = $product->id.'|'.$edKey.'|'.$cogsKey;

            if (! isset($desiredMap[$key])) {
                $desiredMap[$key] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'ed' => $edKey,
                    'cogs' => (float) $cogsKey,
                    'qty' => 0.0,
                ];
            }

            $desiredMap[$key]['qty'] += (float) $row['qty'];
        }

        $desiredByProductEd = [];
        foreach ($desiredMap as $g) {
            $peKey = $g['product_id'].'|'.$g['ed'];
            if (! isset($desiredByProductEd[$peKey])) {
                $desiredByProductEd[$peKey] = 0.0;
            }
            $desiredByProductEd[$peKey] += (float) $g['qty'];
        }

        return [
            'desired_groups' => array_values($desiredMap),
            'desired_by_product_ed' => $desiredByProductEd,
            'unresolved_names' => $unresolved,
            'unresolved_count' => count($unresolved),
        ];
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
            $tokens = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) >= 3));
            if (count($tokens) < 3) {
                continue;
            }

            usort($tokens, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
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
