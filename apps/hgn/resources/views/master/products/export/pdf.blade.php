<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Master Products</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 18px; color: #111; }
        .header { text-align: center; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 4px 0 0 0; color: #555; }
        .meta { margin-bottom: 10px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 6px; border: 1px solid #ddd; font-size: 10px; }
        .meta td.label { width: 16%; background: #f5f5f5; font-weight: bold; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: top; font-size: 9px; }
        .table th { background: #f5f5f5; font-weight: bold; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #666; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Data Master Produk</h1>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Search</td>
                <td>{{ $filters['search'] ?: '-' }}</td>
                <td class="label">Kategori Utama</td>
                <td>{{ $filters['main_category'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Brand</td>
                <td>{{ $filters['brand'] ?: '-' }}</td>
                <td class="label">Status</td>
                <td>{{ $filters['status'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Urut</td>
                <td colspan="3">{{ ($filters['order_by'] ?: 'created_at') . ' ' . ($filters['order_direction'] ?: 'desc') }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th class="nowrap">No</th>
                <th>Nama</th>
                <th class="nowrap">SKU</th>
                <th class="nowrap">Barcode</th>
                <th>Kategori Utama</th>
                <th>Brand</th>
                <th>Sub Brand</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Ukuran</th>
                <th>Varian</th>
                <th class="text-right nowrap">Harga Awal</th>
                <th class="text-right nowrap">Diskon (%)</th>
                <th class="text-right nowrap">Harga Akhir</th>
                <th>Status</th>
                <th class="nowrap">Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $i => $product)
                @php
                    $initialPrice = $product->initial_price ?? 0;
                    $discountPercentage = $product->discount_percentage ?? 0;
                    $finalPrice = $initialPrice;
                    if ($discountPercentage > 0) {
                        $finalPrice = $initialPrice * (1 - $discountPercentage / 100);
                    }
                @endphp
                <tr>
                    <td class="text-center nowrap">{{ $i + 1 }}</td>
                    <td>
                        <div>{{ $product->name ?? '-' }}</div>
                        @if($product->description)
                            <div class="muted">{{ $product->description }}</div>
                        @endif
                    </td>
                    <td class="nowrap">{{ $product->sku ?? '-' }}</td>
                    <td class="nowrap">{{ $product->barcode ?? '-' }}</td>
                    <td>{{ $product->mainCategory->name ?? '-' }}</td>
                    <td>{{ $product->brand->name ?? '-' }}</td>
                    <td>{{ $product->subBrand->name ?? '-' }}</td>
                    <td>{{ $product->productCategory->name ?? '-' }}</td>
                    <td>{{ $product->productType->name ?? '-' }}</td>
                    <td>{{ $product->productSize->name ?? '-' }}</td>
                    <td>{{ $product->productVariant->name ?? '-' }}</td>
                    <td class="text-right nowrap">{{ number_format($initialPrice, 0, ',', '.') }}</td>
                    <td class="text-right nowrap">{{ number_format($discountPercentage, 1) }}</td>
                    <td class="text-right nowrap">{{ number_format($finalPrice, 0, ',', '.') }}</td>
                    <td class="text-center nowrap">{{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}</td>
                    <td class="nowrap">{{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

