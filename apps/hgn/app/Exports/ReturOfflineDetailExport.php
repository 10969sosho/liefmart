<?php

namespace App\Exports;

use App\Models\ReturOfflineSale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReturOfflineDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithColumnFormatting
{
    protected $data;

    public function __construct()
    {
        $this->prepareData();
    }

    private function prepareData()
    {
        $returOfflineSales = ReturOfflineSale::with(['offlineSale', 'user', 'details.product', 'details.offlineSaleItem'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->data = collect();

        foreach ($returOfflineSales as $retur) {
            // Add header row for each retur
            $this->data->push([
                'type' => 'header',
                'retur' => $retur,
                'detail' => null
            ]);

            // Add detail rows for each retur
            foreach ($retur->details as $detail) {
                $this->data->push([
                    'type' => 'detail',
                    'retur' => $retur,
                    'detail' => $detail
                ]);
            }

            // Add empty rows for spacing
            $this->data->push([
                'type' => 'spacer',
                'retur' => null,
                'detail' => null
            ]);
            $this->data->push([
                'type' => 'spacer',
                'retur' => null,
                'detail' => null
            ]);
        }
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Retur',
            'No. Surat Jalan',
            'Tanggal Retur',
            'Status',
            'User',
            'Customer',
            'Nama Produk',
            'Harga Satuan',
            'Qty Retur',
            'Diskon',
            'Total Diskon',
            'Subtotal Retur',
            'Kondisi',
            'Alasan'
        ];
    }

    public function map($row): array
    {
        if ($row['type'] === 'header') {
            $retur = $row['retur'];
            $noSuratJalan = $retur->offlineSale->surat_jalan_number ?? '-';
            $customer = $retur->offlineSale->customerInfo->name ?? $retur->offlineSale->customer_name ?? '-';
            
            // Calculate totals for this specific retur
            $returTotalQty = 0;
            $returTotalSubtotal = 0;
            $returTotalDiscount = 0;
            $returTotalAfterDiscount = 0;
            
            foreach ($retur->details as $detail) {
                $harga = $detail->offlineSaleItem ? $detail->offlineSaleItem->unit_price : 0;
                $qty = $detail->qty;
                $subtotal = $harga * $qty;
                $diskon = 0;
                $currentTotal = $subtotal;
                
                // Calculate cascading discounts
                for($i = 1; $i <= 5; $i++) {
                    $percentField = "discount_percent_" . $i;
                    $amountField = "discount_amount_" . $i;
                    $percent = $detail->offlineSaleItem ? ($detail->offlineSaleItem->$percentField ?? 0) : 0;
                    $amount = $detail->offlineSaleItem ? ($detail->offlineSaleItem->$amountField ?? 0) : 0;
                    
                    if($percent > 0) {
                        $d = $currentTotal * ($percent / 100);
                        $diskon += $d;
                        $currentTotal -= $d;
                        $currentTotal = round($currentTotal, 2);
                    }
                    if($amount > 0) {
                        $d = $amount * $qty;
                        $diskon += $d;
                        $currentTotal -= $d;
                        $currentTotal = round($currentTotal, 2);
                    }
                }
                
                $returTotalQty += $qty;
                $returTotalSubtotal += $subtotal;
                $returTotalDiscount += $diskon;
                $returTotalAfterDiscount += $currentTotal;
            }
            
            return [
                'RETUR OFFLINE',
                $retur->kode_retur,
                $noSuratJalan,
                $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-',
                ucfirst($retur->status),
                $retur->user->name ?? '-',
                $customer,
                'Total Items: ' . $retur->details->count(),
                '',
                'Total QTY: ' . $returTotalQty,
                '',
                'Total Diskon: Rp ' . number_format($returTotalDiscount, 0, ',', '.'),
                'Total Subtotal: Rp ' . number_format($returTotalSubtotal, 0, ',', '.'),
                'Total Value: Rp ' . number_format($returTotalAfterDiscount, 0, ',', '.'),
                ''
            ];
        } elseif ($row['type'] === 'detail') {
            $retur = $row['retur'];
            $detail = $row['detail'];
            $noSuratJalan = $retur->offlineSale->surat_jalan_number ?? '-';
            $customer = $retur->offlineSale->customerInfo->name ?? $retur->offlineSale->customer_name ?? '-';
            
            $harga = $detail->offlineSaleItem ? $detail->offlineSaleItem->unit_price : 0;
            $qty = $detail->qty;
            $subtotal = $harga * $qty;
            $diskon = 0;
            $currentTotal = $subtotal;
            $diskonText = [];
            
            // Calculate cascading discounts
            for($i = 1; $i <= 5; $i++) {
                $percentField = "discount_percent_" . $i;
                $amountField = "discount_amount_" . $i;
                $percent = $detail->offlineSaleItem ? ($detail->offlineSaleItem->$percentField ?? 0) : 0;
                $amount = $detail->offlineSaleItem ? ($detail->offlineSaleItem->$amountField ?? 0) : 0;
                
                if($percent > 0) {
                    $d = $currentTotal * ($percent / 100);
                    $diskon += $d;
                    $diskonText[] = number_format($percent, 2, ',', '.') . '%';
                    $currentTotal -= $d;
                    $currentTotal = round($currentTotal, 2);
                }
                if($amount > 0) {
                    $d = $amount * $qty;
                    $diskon += $d;
                    $diskonText[] = 'Rp ' . number_format($amount, 0, ',', '.');
                    $currentTotal -= $d;
                    $currentTotal = round($currentTotal, 2);
                }
            }
            
            return [
                $this->getCounter(),
                $retur->kode_retur,
                $noSuratJalan,
                $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-',
                ucfirst($retur->status),
                $retur->user->name ?? '-',
                $customer,
                $detail->product->name ?? '-',
                round($harga, 2),
                $qty,
                implode(' + ', $diskonText) ?: '-',
                round($diskon, 2),
                round($currentTotal, 2),
                $detail->kondisi,
                $detail->alasan ?? '-'
            ];
        } else {
            // Spacer row
            return array_fill(0, 15, '');
        }
    }

    private $counter = 1;

    private function getCounter()
    {
        return $this->counter++;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
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
            'A1:O1000' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]
        ];

        // Style header rows for each retur with green background
        $row = 2;
        foreach ($this->data as $item) {
            if ($item['type'] === 'header') {
                $styles[$row] = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '000000']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00FF00'] // Bright green
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ];
            }
            $row++;
        }

        return $styles;
    }

    public function columnFormats(): array
    {
        return [
            'I:O' => '#,##0.00', // Format currency columns
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Auto-adjust column widths
                foreach (range('A', 'O') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
