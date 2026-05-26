<?php

namespace App\Exports;

use App\Models\ReturPenjualan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class ReturPenjualanDetailExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithColumnFormatting
{
    private $counter = 1;
    private $currentRow = 2; // Start after headings
    private $headerRows = []; // Track header rows for styling

    public function query()
    {
        return ReturPenjualan::query()->with([
                'order.platform', 
                'user', 
                'details.product', 
                'details.orderItem.platformProduct.mappingBarang'
            ])
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Retur',
            'Nomor Order',
            'No. Resi',
            'Platform',
            'Tanggal Penjualan',
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

    public function map($retur): array
    {
        $rows = [];
        $resi = $retur->order->resi ?? ($retur->order->no_resi ?? '-');

        // --- 1. Header Row ---
        // Calculate totals for this specific retur
        $returTotalQty = 0;
        $returTotalHarga = 0;
        
        foreach ($retur->details as $detail) {
            $harga = $this->calculatePrice($detail);
            $returTotalQty += $detail->qty;
            $returTotalHarga += $harga * $detail->qty;
        }
        
        $rows[] = [
            'RETUR PENJUALAN',
            $retur->kode_retur,
            $this->formatOrderNumber($retur->order->order_number),
            $this->formatOrderNumber($resi),
            $retur->order->platform->name ?? '-',
            $retur->order->tanggal ? $retur->order->tanggal->format('d/m/Y') : '-',
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

        // Track this row as header
        $this->headerRows[] = $this->currentRow;
        $this->currentRow++;

        // --- 2. Detail Rows ---
        foreach ($retur->details as $detail) {
            $harga = $this->calculatePrice($detail);
            
            // Get platform product name (variant) and actual product name separately
            $platformProductName = $detail->orderItem && $detail->orderItem->platformProduct ? 
                $detail->orderItem->platformProduct->name : '-';
            $productName = $detail->product ? $detail->product->name : '-';
            
            $rows[] = [
                $this->getCounter(),
                $retur->kode_retur,
                $this->formatOrderNumber($retur->order->order_number),
                $this->formatOrderNumber($resi),
                $retur->order->platform->name ?? '-',
                $retur->order->tanggal ? $retur->order->tanggal->format('d/m/Y') : '-',
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
            $this->currentRow++;
        }

        // --- 3. Spacer Rows ---
        $rows[] = array_fill(0, 16, '');
        $this->currentRow++;
        $rows[] = array_fill(0, 16, '');
        $this->currentRow++;

        return $rows;
    }

    private function calculatePrice($detail)
    {
        if (!$detail->orderItem) {
            return 0;
        }
        
        $orderItem = $detail->orderItem;
        $platformProduct = $orderItem->platformProduct;
        
        if (!$platformProduct || !$platformProduct->mappingBarang) {
            // If no mapping, use original price
            return $orderItem->price_after_discount;
        } else {
            // Calculate total quantity in the package from mapping
            $totalPackageQty = $platformProduct->mappingBarang
                ->where('is_active', true)
                ->sum('quantity');
            
            if ($totalPackageQty > 1) {
                // If package contains more than 1 item, divide the price
                return $orderItem->price_after_discount / $totalPackageQty;
            } else {
                // If package contains only 1 item, use original price
                return $orderItem->price_after_discount;
            }
        }
    }

    private function getCounter()
    {
        return $this->counter++;
    }

    /**
     * Format order number to prevent scientific notation in Excel
     */
    private function formatOrderNumber($orderNumber)
    {
        if (empty($orderNumber)) {
            return '-';
        }
        
        // Convert to string and add prefix to force text format
        $formatted = (string)$orderNumber;
        
        // For very long numbers (like TikTok), ensure they're treated as text
        if (strlen($formatted) > 15) {
            return "'" . $formatted;
        }
        
        return $formatted;
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
            ]
        ];

        // Apply borders to all used rows
        $lastRow = $this->currentRow - 1;
        $styles["A1:P{$lastRow}"] = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        // Style header rows for each retur with green background
        foreach ($this->headerRows as $row) {
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

        return $styles;
    }

    public function columnFormats(): array
    {
        return [
            'B' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // Kode Retur as text
            'C' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // Nomor Order as text
            'D' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT, // No. Resi as text
            'M:Q' => '#,##0.00', // Format currency columns
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Additional formatting if needed
            },
        ];
    }
}
