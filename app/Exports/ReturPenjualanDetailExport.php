<?php

namespace App\Exports;

use App\Models\ReturPenjualan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class ReturPenjualanDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithColumnFormatting
{
    protected $data;

    public function __construct()
    {
        $this->prepareData();
    }

    private function prepareData()
    {
        $returPenjualans = ReturPenjualan::with([
                'order.platform', 
                'user', 
                'details.product', 
                'details.orderItem.platformProduct.mappingBarang'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->data = collect();

        foreach ($returPenjualans as $retur) {
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
            'Nomor Order',
            'No. Resi',
            'Platform',
            'Tanggal Retur',
            'Status',
            'User',
            'Nama Produk',
            'Varian Produk',
            'Harga Produk',
            'Qty',
            'Total Harga',
            'Kondisi',
            'Alasan'
        ];
    }

    public function map($row): array
    {
        if ($row['type'] === 'header') {
            $retur = $row['retur'];
            $resi = $retur->order->resi ?? ($retur->order->no_resi ?? '-');
            
            // Calculate totals for this specific retur
            $returTotalQty = 0;
            $returTotalHarga = 0;
            
            foreach ($retur->details as $detail) {
                // Calculate correct price per individual product for paket
                if (!$detail->orderItem) {
                    $harga = 0;
                } else {
                    $platformProduct = $detail->orderItem->platformProduct;
                    if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                        // Non-paket product: use original price
                        $harga = $detail->orderItem->price_after_discount;
                    } else {
                        // Paket product: calculate total quantity in the package
                        $totalPackageQty = $platformProduct->mappingBarang->sum('quantity');
                        
                        // Calculate price per individual product
                        $harga = $totalPackageQty > 0 ? 
                            $detail->orderItem->price_after_discount / $totalPackageQty : 
                            $detail->orderItem->price_after_discount;
                    }
                }
                
                $returTotalQty += $detail->qty;
                $returTotalHarga += $harga * $detail->qty;
            }
            
            return [
                'RETUR PENJUALAN',
                $retur->kode_retur,
                (string)$retur->order->order_number,
                (string)$resi,
                $retur->order->platform->name ?? '-',
                $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-',
                Str::ucfirst($retur->status),
                $retur->user->name,
                'Total Items: ' . $retur->details->count(),
                '',
                '',
                'Total QTY: ' . $returTotalQty,
                'Total Value: Rp ' . number_format($returTotalHarga, 0, ',', '.'),
                '',
                ''
            ];
        } elseif ($row['type'] === 'detail') {
            $retur = $row['retur'];
            $detail = $row['detail'];
            $resi = $retur->order->resi ?? ($retur->order->no_resi ?? '-');
            
            // Calculate correct price per individual product for paket
            if (!$detail->orderItem) {
                $harga = 0;
            } else {
                $platformProduct = $detail->orderItem->platformProduct;
                if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                    // Non-paket product: use original price
                    $harga = $detail->orderItem->price_after_discount;
                } else {
                    // Paket product: calculate total quantity in the package
                    $totalPackageQty = $platformProduct->mappingBarang->sum('quantity');
                    
                    // Calculate price per individual product
                    $harga = $totalPackageQty > 0 ? 
                        $detail->orderItem->price_after_discount / $totalPackageQty : 
                        $detail->orderItem->price_after_discount;
                }
            }
            
            // Get platform product name (variant) and actual product name separately
            $platformProductName = $detail->orderItem && $detail->orderItem->platformProduct ? 
                $detail->orderItem->platformProduct->name : '-';
            $productName = $detail->product ? $detail->product->name : '-';
            
            return [
                $this->getCounter(),
                $retur->kode_retur,
                (string)$retur->order->order_number,
                (string)$resi,
                $retur->order->platform->name ?? '-',
                $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-',
                Str::ucfirst($retur->status),
                $retur->user->name,
                $platformProductName,
                $productName,
                round($harga, 2),
                $detail->qty,
                round($harga * $detail->qty, 2),
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
            'B' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // Nomor Order as text
            'C' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // No. Resi as text
            'K:O' => '#,##0.00', // Format currency columns
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
