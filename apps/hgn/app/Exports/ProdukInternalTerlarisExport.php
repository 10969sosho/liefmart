<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProdukInternalTerlarisExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $products;
    protected $summary;
    protected $startDate;
    protected $endDate;

    public function __construct($products, $summary, $startDate, $endDate)
    {
        $this->products = $products;
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return collect($this->products);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Produk',
            'SKU',
            'Platform',
            'Jumlah Terjual (pcs)',
            'Retur (pcs)',
            'Net Terjual (pcs)',
            'Jumlah Order',
        ];
    }

    public function map($product): array
    {
        static $index = 0;
        $index++;
        
        return [
            $index,
            $product['product_name'] ?? '-',
            $product['product_sku'] ?? '-',
            $product['platforms'] ?? '-',
            (float) ($product['total_quantity'] ?? 0),
            (float) ($product['qty_retur'] ?? 0),
            (float) ($product['net_quantity'] ?? 0),
            (int) ($product['order_count'] ?? 0),
        ];
    }

    public function title(): string
    {
        return 'Produk Internal Terlaris';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }
}

