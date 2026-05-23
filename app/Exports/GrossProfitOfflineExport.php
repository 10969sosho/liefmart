<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GrossProfitOfflineExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $profitData;

    public function __construct($profitData)
    {
        $this->profitData = $profitData;
    }

    public function collection()
    {
        return $this->profitData;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Penjualan',
            'Tanggal Pembayaran',
            'Customer',
            'No. PO',
            'No. Invoice',
            'Nama Produk',
            'Qty',
            'SKU',
            'Pembayaran per INV',
            'Pembayaran per INV -PPN',
            'Pembayaran per Produk -PPN',
            'Pembayaran per PCS -PPN',
            'Harga Modal per produk(COGS)',
            'Harga Modal Total',
            'Profit per PCS',
            'Profit per Produk',
            'Profit per INV',
            'Margin per PCS %',
            'Margin per Produk %',
            'Margin per INV %'
        ];
    }

    public function map($item): array
    {
        static $counter = 1;
        
        return [
            $counter++,
            $item['sale_date'] ? \Carbon\Carbon::parse($item['sale_date'])->format('d/m/Y') : '-',
            $item['payment_date'] ? \Carbon\Carbon::parse($item['payment_date'])->format('d/m/Y') : '-',
            $item['customer_name'] ?? '-',
            $item['po_number'],
            $item['invoice_number'],
            $item['product_name'],
            (int) $item['quantity'],
            $item['sku'],
            round((float) $item['payment_per_invoice'], 2),
            round((float) $item['payment_per_invoice_without_ppn'], 2),
            round((float) $item['payment_per_product_without_ppn'], 2),
            round((float) $item['payment_per_pcs_without_ppn'], 2),
            round((float) $item['cost_price'], 2),
            round((float) $item['total_cost_price'], 2),
            round((float) $item['profit_per_unit'], 2),
            round((float) $item['profit_per_product'], 2),
            round((float) $item['profit_per_invoice'], 2),
            round((float) $item['margin_per_unit'], 2),
            round((float) $item['margin_per_product'], 2),
            round((float) $item['margin_per_invoice'], 2)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ],
            // Apply borders to all cells
            'A1:T1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];
    }
}
