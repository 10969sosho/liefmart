<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\ReturOfflineSaleDetail;

class OfflineSalesDetailReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents, WithColumnFormatting
{
    protected $sales;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedCustomer;
    protected $selectedProduct;
    protected $data;

    public function __construct($sales, $summary, $startDate, $endDate, $selectedCustomer = null, $selectedProduct = null)
    {
        $this->sales = collect($sales);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedCustomer = $selectedCustomer;
        $this->selectedProduct = $selectedProduct;
        $this->prepareData();
    }

    private function prepareData()
    {
        $this->data = collect();

        foreach ($this->sales as $sale) {
            // Add header row for each sale
            $this->data->push([
                'type' => 'header',
                'sale' => $sale,
                'item' => null
            ]);

            // Add detail rows for each sale item
            foreach ($sale->items as $item) {
                $this->data->push([
                    'type' => 'detail',
                    'sale' => $sale,
                    'item' => $item
                ]);
            }

            // Add empty rows for spacing
            $this->data->push([
                'type' => 'spacer',
                'sale' => null,
                'item' => null
            ]);
            $this->data->push([
                'type' => 'spacer',
                'sale' => null,
                'item' => null
            ]);
        }
    }

    /**
     * Calculate total value after all cascading discounts
     */
    private function calculateTotalAfterDiscounts($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        
        // Start with base total (price × quantity)
        $currentTotal = $basePrice * $qty;
        
        // Apply percentage discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }
        
        // Apply nominal discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }
        
        return \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
    }

    /**
     * Calculate total discount amount
     */
    private function calculateTotalDiscount($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        $baseTotal = $basePrice * $qty;
        $finalTotal = $this->calculateTotalAfterDiscounts($item);
        
        return $baseTotal - $finalTotal;
    }

    /**
     * Get discount summary text
     */
    private function getDiscountSummary($item)
    {
        $discounts = [];
        
        // Check percentage discounts
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $discounts[] = number_format($discountPercent, 0) . '%';
            }
        }
        
        // Check nominal discounts
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $discounts[] = 'Rp ' . number_format($discountAmount, 0);
            }
        }
        
        return empty($discounts) ? '-' : implode(' + ', $discounts);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Penjualan',
            'No. Surat Jalan',
            'Customer',
            'Nama Produk',
            'Qty',
            'Satuan',
            'Harga Satuan',
            'Diskon %',
            'Diskon Nominal',
            'Subtotal',
            'Total Diskon',
            'Total Setelah Diskon',
            'QTY Retur',
            'Catatan'
        ];
    }

    public function map($row): array
    {
        if ($row['type'] === 'header') {
            $sale = $row['sale'];
            return [
                'PENJUALAN OFFLINE',
                $sale->sale_date ? $sale->sale_date->format('d/m/Y') : '-',
                $sale->surat_jalan_number,
                $sale->customerInfo ? $sale->customerInfo->name : $sale->customer_name,
                'Status: ' . $sale->status,
                'Total Items: ' . $sale->items->count(),
                'Total Value: Rp ' . number_format($sale->value_after_returns ?? 0, 0, ',', '.'),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ];
        } elseif ($row['type'] === 'detail') {
            $sale = $row['sale'];
            $item = $row['item'];
            
            // Calculate qty retur untuk item ini
            $qtyRetur = ReturOfflineSaleDetail::where('offline_sale_item_id', $item->id)
                ->whereHas('returOfflineSale', function($q) { $q->where('status', 'selesai'); })
                ->sum('qty');
            $qtyRetur = (float) $qtyRetur;
            
            // Calculate subtotal before discount
            $subtotalSebelumDiskon = $item->quantity * $item->unit_price;
            $subtotal = $subtotalSebelumDiskon;
            
            // Calculate cascading discounts (bertingkat)
            $totalDiskonPersen = 0;
            $totalDiskonNominal = 0;
            $discountPercentages = []; // Array to store individual percentages
            
            for ($i = 1; $i <= 5; $i++) {
                $diskonPersen = $item->{"discount_percent_$i"} ?? 0;
                $diskonNominal = $item->{"discount_amount_$i"} ?? 0;
                
                if ($diskonPersen > 0) {
                    $potongan = $subtotal * ($diskonPersen / 100);
                    $subtotal -= $potongan;
                    $totalDiskonPersen += $diskonPersen;
                    $discountPercentages[] = number_format($diskonPersen, 0) . '%'; // Store individual percentage
                } elseif ($diskonNominal > 0) {
                    $subtotal -= $diskonNominal;
                    $totalDiskonNominal += $diskonNominal;
                }
            }
            
            $totalDiskon = $subtotalSebelumDiskon - $subtotal;
            $hargaTotalSetelahDiskon = $subtotal;
            
            return [
                $this->getCounter(),
                $sale->sale_date ? $sale->sale_date->format('d/m/Y') : '-',
                $sale->surat_jalan_number,
                $sale->customerInfo ? $sale->customerInfo->name : $sale->customer_name,
                $item->product ? $item->product->name : 'Unknown Product',
                (int) $item->quantity,
                $item->product && $item->product->satuan ? $item->product->satuan->name : '-',
                round((float) $item->unit_price, 2),
                empty($discountPercentages) ? '-' : implode('+', $discountPercentages), // Format individual percentages like "4%+1%"
                round((float) $totalDiskonNominal, 2), // Diskon nominal 2 desimal
                round((float) $subtotalSebelumDiskon, 2), // Subtotal 2 desimal
                round((float) $totalDiskon, 2), // Total diskon 2 desimal
                round((float) $hargaTotalSetelahDiskon, 2), // Total setelah diskon 2 desimal
                (int) $qtyRetur, // QTY Retur
                $item->notes ?? '-' // Catatan
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

        return $styles;
    }

    public function columnFormats(): array
    {
        return [
            'H:O' => '#,##0.00', // Format currency columns
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Add summary at the end
                $row = $event->sheet->getHighestRow() + 2;
                
                // Add summary header
                $event->sheet->setCellValue('A' . $row, 'RINGKASAN');
                $event->sheet->getStyle('A' . $row)->getFont()->setBold(true);
                
                $row++;
                $event->sheet->setCellValue('A' . $row, 'Total Penjualan:');
                $event->sheet->setCellValue('B' . $row, $this->summary['total_orders']);
                
                $row++;
                $event->sheet->setCellValue('A' . $row, 'Total Value:');
                $event->sheet->setCellValue('B' . $row, 'Rp ' . number_format($this->summary['total_value'], 0, ',', '.'));
                
                $row++;
                $event->sheet->setCellValue('A' . $row, 'Total Volume:');
                $event->sheet->setCellValue('B' . $row, number_format($this->summary['total_volume']) . ' pcs');
                
                $row++;
                $event->sheet->setCellValue('A' . $row, 'Rata-rata per Penjualan:');
                $event->sheet->setCellValue('B' . $row, 'Rp ' . number_format($this->summary['avg_order_value'], 0, ',', '.'));
                
                $row++;
                $event->sheet->setCellValue('A' . $row, 'Periode:');
                $event->sheet->setCellValue('B' . $row, $this->startDate . ' - ' . $this->endDate);
            },
        ];
    }
}