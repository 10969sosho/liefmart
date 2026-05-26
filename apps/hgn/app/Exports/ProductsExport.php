<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
{
    protected Builder $query;
    protected int $counter = 1;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Produk',
            'SKU',
            'Barcode',
            'Kategori Utama',
            'Brand',
            'Sub Brand',
            'Kategori Produk',
            'Tipe Produk',
            'Ukuran',
            'Varian',
            'Harga Awal',
            'Diskon (%)',
            'Harga Akhir',
            'Deskripsi',
            'Status',
            'Dibuat',
            'Diupdate',
        ];
    }

    public function map($product): array
    {
        $initialPrice = (float)($product->initial_price ?? 0);
        $discountPercentage = (float)($product->discount_percentage ?? 0);
        $finalPrice = $initialPrice;
        if ($discountPercentage > 0) {
            $finalPrice = $initialPrice * (1 - ($discountPercentage / 100));
        }

        return [
            $this->counter++,
            $product->name ?? '-',
            $product->sku ?? '-',
            $product->barcode ?? '-',
            $product->mainCategory?->name ?? '-',
            $product->brand?->name ?? '-',
            $product->subBrand?->name ?? '-',
            $product->productCategory?->name ?? '-',
            $product->productType?->name ?? '-',
            $product->productSize?->name ?? '-',
            $product->productVariant?->name ?? '-',
            $initialPrice,
            $discountPercentage,
            $finalPrice,
            $product->description ?? '-',
            $product->is_active ? 'Aktif' : 'Tidak Aktif',
            $product->created_at ? $product->created_at->format('d/m/Y H:i:s') : '-',
            $product->updated_at ? $product->updated_at->format('d/m/Y H:i:s') : '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'L' => '#,##0',
            'M' => '0.0',
            'N' => '#,##0',
        ];
    }
}
