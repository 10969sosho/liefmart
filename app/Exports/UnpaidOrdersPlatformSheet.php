<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class UnpaidOrdersPlatformSheet extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithCustomValueBinder, WithTitle
{
    protected $platform;
    protected $orders;

    public function __construct($platform, $orders)
    {
        $this->platform = $platform;
        $this->orders = $orders;
    }

    /**
     * Custom rounding function: max 1 decimal place
     * Rule: 5+ rounds up, 4- rounds down, with special case for .45 pattern
     */
    private function customRound($value, $decimals = 1)
    {
        // Convert to string to avoid floating point issues
        $valueStr = number_format($value, 10, '.', '');
        $valueStr = rtrim($valueStr, '0');
        $valueStr = rtrim($valueStr, '.');
        
        $parts = explode('.', $valueStr);
        if (count($parts) < 2 || strlen($parts[1]) <= $decimals) {
            return round($value, $decimals);
        }
        
        $intPart = $parts[0];
        $decPart = $parts[1];
        $keepDigits = substr($decPart, 0, $decimals);
        $nextDigit = isset($decPart[$decimals]) ? (int)$decPart[$decimals] : 0;
        
        // Special case: X.45 exactly should round down (user's specific rule)
        if (strlen($decPart) == 2 && $decPart == '45') {
            return (float)($intPart . '.4');
        }
        
        // Normal rounding: 5+ up, 4- down
        if ($nextDigit >= 5) {
            $keepDigitsInt = (int)$keepDigits + 1;
            if ($keepDigitsInt >= pow(10, $decimals)) {
                $intPart = (int)$intPart + 1;
                $keepDigits = str_repeat('0', $decimals);
            } else {
                $keepDigits = str_pad($keepDigitsInt, $decimals, '0', STR_PAD_LEFT);
            }
        } else {
            $keepDigits = str_pad($keepDigits, $decimals, '0', STR_PAD_RIGHT);
        }
        
        return (float)($intPart . '.' . $keepDigits);
    }

    public function bindValue(Cell $cell, $value)
    {
        // Get the column index (1-based)
        $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
        
        // Column 4 is "No. Order" and Column 5 is "No. Invoice" - force these to be text
        if (($columnIndex === 4 || $columnIndex === 5) && is_string($value) && !empty($value) && $value !== '-') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        // For all other values, use the default behavior
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return $this->orders;
    }

    public function title(): string
    {
        return $this->platform;
    }

    public function headings(): array
    {
        $baseHeadings = [
            'No',
            'Tanggal Order',
            'Hari Order',
            'No. Order',
            'No. Invoice',
            'Status Pajak',
            'Harga (Rp)',
        ];

        // Platform-specific cost headers
        $costHeadings = $this->getPlatformSpecificCostHeaders();
        
        $endHeadings = [
            'Adjustment (Rp)',
            'Adjustment (%)',
            'Keterangan Adjustment',
            'Total (%)',
            'Nominal Fix (Rp)',
            'Saldo Masuk (Rp)',
            'Tanggal Pembayaran',
            'Hari Pembayaran',
            'Outstanding (Rp)',
            'Status'
        ];

        return array_merge($baseHeadings, $costHeadings, $endHeadings);
    }

    private function getPlatformSpecificCostHeaders(): array
    {
        switch (strtolower($this->platform)) {
            case 'shopee':
                return [
                    'Voucher (Rp)',
                    'Voucher (%)',
                    'Komisi (Rp)',
                    'Komisi (%)',
                    'Biaya Admin (Rp)',
                    'Biaya Admin (%)',
                    'Biaya Layanan (Rp)',
                    'Biaya Layanan (%)',
                    'Biaya 5 (Rp)',
                    'Biaya 5 (%)',
                    'Biaya 6 (Rp)',
                    'Biaya 6 (%)',
                    'Biaya 7 (Rp)',
                    'Biaya 7 (%)',
                    'Biaya 8 (Rp)',
                    'Biaya 8 (%)',
                    'Biaya 9 (Rp)',
                    'Biaya 9 (%)',
                    'Biaya 10 (Rp)',
                    'Biaya 10 (%)',
                    'Biaya 11 (Rp)',
                    'Biaya 11 (%)',
                    'Biaya 12 (Rp)',
                    'Biaya 12 (%)',
                ];

            case 'tiktok':
                return [
                    'Biaya Admin (Rp)',
                    'Biaya Admin (%)',
                    'Affiliate (Rp)',
                    'Affiliate (%)',
                    'Shipping (Rp)',
                    'Shipping (%)',
                    'Voucher Fee (Rp)',
                    'Voucher Fee (%)',
                    'Cashback (Rp)',
                    'Cashback (%)',
                    'Biaya 6 (Rp)',
                    'Biaya 6 (%)',
                    'Biaya 7 (Rp)',
                    'Biaya 7 (%)',
                    'Biaya 8 (Rp)',
                    'Biaya 8 (%)',
                    'Biaya 9 (Rp)',
                    'Biaya 9 (%)',
                    'Biaya 10 (Rp)',
                    'Biaya 10 (%)',
                    'Biaya 11 (Rp)',
                    'Biaya 11 (%)',
                    'Biaya 12 (Rp)',
                    'Biaya 12 (%)',
                ];

            default:
                // Fallback generic headers
                return [
                    'Biaya 1 (Rp)',
                    'Biaya 1 (%)',
                    'Biaya 2 (Rp)',
                    'Biaya 2 (%)',
                    'Biaya 3 (Rp)',
                    'Biaya 3 (%)',
                    'Biaya 4 (Rp)',
                    'Biaya 4 (%)',
                    'Biaya 5 (Rp)',
                    'Biaya 5 (%)',
                    'Biaya 6 (Rp)',
                    'Biaya 6 (%)',
                    'Biaya 7 (Rp)',
                    'Biaya 7 (%)',
                    'Biaya 8 (Rp)',
                    'Biaya 8 (%)',
                    'Biaya 9 (Rp)',
                    'Biaya 9 (%)',
                    'Biaya 10 (Rp)',
                    'Biaya 10 (%)',
                    'Biaya 11 (Rp)',
                    'Biaya 11 (%)',
                    'Biaya 12 (Rp)',
                    'Biaya 12 (%)',
                ];
        }
    }

    public function map($order): array
    {
        static $counters = [];
        
        // Initialize counter for this platform if not exists
        if (!isset($counters[$this->platform])) {
            $counters[$this->platform] = 1;
        }
        
        $no = $counters[$this->platform]++;
        
        // Calculate price from order_items table using price_after_discount column
        // For returned orders, we should use current quantity (after returns), not original
        $harga = 0;
        if ($order->orderItems && $order->orderItems->count() > 0) {
            $harga = $order->orderItems->sum(function($item) {
                $currentQty = $item->quantity ?? 0; // This is already quantity after returns
                $price = $item->price_after_discount ?? 0;
                return $price * $currentQty;
            });
        }
        
        // Add shipping cost to the total
        $shippingCost = $order->shipping_cost ?? 0;
        $totalOrderValue = $harga + $shippingCost;
        
        // Use total order value (items + shipping) as the final price
        $harga = (float)($totalOrderValue ?? 0);

        // Calculate days since order and determine day name
        $dayOfWeek = $order->tanggal ? $order->tanggal->format('l') : '-';
        
        // Convert day names to Indonesian
        $dayNames = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        $hariOrder = $dayNames[$dayOfWeek] ?? $dayOfWeek;

        // Determine tax status - since no invoice yet, we set as pending
        $taxStatus = 'Pending';
        
        // Determine order status based on return conditions
        $status = 'BELUM LUNAS';
        $outstanding = $harga;
        
        // Check if this is a fully returned order
        if (isset($order->is_return_unpaid) && $order->is_return_unpaid) {
            $status = $order->unpaid_reason ?? 'RETUR FULL';
            $outstanding = 0;
        } else {
            // Check if there are any returns for this order
            $hasPartialReturns = false;
            $totalCurrentQuantity = 0;
            $totalReturnedIndividual = 0;
            
            if (!$order->orderItems || $order->orderItems->isEmpty()) {
                // No order items, skip return checking
            } else {
                foreach ($order->orderItems as $item) {
                $currentQuantity = $item->quantity;
                $totalCurrentQuantity += $currentQuantity;
                
                // Get returned quantity for this item (individual items)
                $returnedQuantityIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $item->id)
                    ->whereHas('returPenjualan', function($q) { 
                        $q->whereIn('status', ['draft', 'selesai']); 
                    })
                    ->sum('qty');
                
                    $totalReturnedIndividual += $returnedQuantityIndividual;
                }
            }
            
            // If there are any returns but not full return, this is partial return
            if ($totalReturnedIndividual > 0) {
                // Calculate how many items were returned
                $totalReturnedPackages = 0;
                if ($order->orderItems && $order->orderItems->count() > 0) {
                    foreach ($order->orderItems as $item) {
                    $returnedIndividual = \App\Models\ReturPenjualanDetail::where('order_item_id', $item->id)
                        ->whereHas('returPenjualan', function($q) { 
                            $q->whereIn('status', ['draft', 'selesai']); 
                        })
                        ->sum('qty');
                    
                    // Convert to package quantity
                    $packageQuantity = 1;
                    if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                        $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
                    }
                    
                        $returnedPackages = $packageQuantity > 0 ? $returnedIndividual / $packageQuantity : $returnedIndividual;
                        $totalReturnedPackages += $returnedPackages;
                    }
                }
                
                if ($totalReturnedPackages == 1) {
                    $status = 'SALAH BARANG (RETUR SEBAGIAN)';
                } else {
                    $status = "PENGEMBALIAN DANA (barang dikirim {$totalReturnedPackages} pcs)";
                }
                
                $hasPartialReturns = true;
            }
        }
        
        // Base data
        $baseData = [
            $no,
            $order->tanggal ? $order->tanggal->format('d/m/Y') : '-',
            $hariOrder,
            "'" . ($order->order_number ?? '-'),
            '-', // No invoice yet for unpaid orders
            $taxStatus,
            $harga, // Price column filled with total order value
        ];

        // All cost fields are 0 since no payment processed (24 fields: 12 nominal + 12 percentage)
        $costData = array_fill(0, 24, 0);
        
        // End data
        $endData = [
            0, // Adjustment - empty since no payment processed
            0, // Adjustment % - empty since no payment processed
            '-', // Keterangan Adjustment - empty since no payment processed
            0, // Total % - empty since no payment processed
            0, // Nominal Fix - empty since no payment processed
            0, // Saldo Masuk - empty since no payment processed
            '-', // Tanggal Pembayaran - empty since no payment
            '-', // Hari Pembayaran - empty since no payment
            number_format($outstanding, 2, '.', ''), // Outstanding - calculated based on return status
            $status // Status - detailed status based on return conditions
        ];

        return array_merge($baseData, $costData, $endData);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style for headers
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'D9E1F2']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]
        ];
    }
} 