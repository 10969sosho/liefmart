<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class SalesByMasterProductExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    protected $productRows;
    protected $summary;
    protected $filters;

    public function __construct($productRows, $summary, $filters = [])
    {
        $this->productRows = $productRows;
        $this->summary = $summary;
        $this->filters = $filters;
    }

    public function collection()
    {
        return collect($this->productRows);
    }

    public function headings(): array
    {
        return [
            'Tanggal (Pembayaran Masuk)',
            'No Pesanan',
            'No Invoice',
            'Nama Produk (Platform)',
            'Variasi (Platform)',
            'Jumlah QTY (PCS) (Platform)',
            'SKU',
            'Master Barang',
            'QTY',
            'Jumlah masuk pembayaran (Rp)',
            'Jumlah masuk pembayaran - PPN (Rp)',
            'Harga pricelist per item (Rp)',
            'Harga pricelist per item X QTY (Rp)',
            'Total harga pricelist (Rp)',
            'Persen dalam order (%)',
            'Masuk pembayaran per produk (Rp)',
            'Masuk pembayaran per produk - PPN (Rp)',
            'Harga Modal (COGS) (Rp)',
            'Profit per PCS (Rp)',
            'Gross Profit total (Rp)',
            'Margin per pcs (%)',
            'Margin per item (%)'
        ];
    }

    public function map($row): array
    {
        // ALL CALCULATIONS ARE NOW DONE IN SQL - JUST USE THE VALUES DIRECTLY
        // Data comes as array from Query class - use exact same columns as view
        
        // Use order_date_formatted if available, otherwise format order_date
        $orderDate = $row['order_date_formatted'] ?? 
            (isset($row['order_date']) ? Carbon::parse($row['order_date'])->format('d/m/Y') : '-');
        
        // All values below are already calculated in SQL - use them directly
        $platformQty = (float)($row['platform_quantity'] ?? 0); // QTY dari platform
        $qty = (float)($row['quantity'] ?? 0); // QTY master barang
        $proportionPercent = (float)($row['proportion_percent'] ?? 0);
        
        // Use values directly from SQL (already calculated)
        $paymentAmount = (float)($row['order_total_payment'] ?? 0);
        $paymentAmountWithoutPPN = (float)($row['order_total_payment_without_ppn'] ?? 0); // Already calculated in SQL
        $pricelistPerItem = (float)($row['price'] ?? 0);
        $pricelistTotal = (float)($row['pricelist_total'] ?? 0);
        $totalPricelistOrder = (float)($row['total_order_value_from_products'] ?? 0);
        
        // These are all calculated in SQL - use them directly
        $paymentPerProduct = (float)($row['payment_per_product_per_pcs'] ?? 0);
        $paymentPerProductWithoutPPN = (float)($row['payment_per_product_without_ppn'] ?? 0);
        $unitCost = (float)($row['unit_cost'] ?? 0); // Use unit_cost (not modal_per_pcs)
        $profitPerPCS = (float)($row['profit_per_pcs'] ?? 0);
        $grossProfitTotal = (float)($row['gross_profit_total'] ?? 0);
        $marginPerPCS = (float)($row['margin_per_pcs'] ?? 0); // Keep as percentage (not decimal)
        $marginPerItem = (float)($row['margin_per_item'] ?? 0); // Keep as percentage (not decimal)

        return [
            $orderDate, // Tanggal (Pembayaran Masuk) - formatted as d/m/Y
            isset($row['order_number']) ? str_replace(',', '', (string)$row['order_number']) : '-', // No Pesanan as text (remove commas if any)
            isset($row['invoice_number']) ? (string)$row['invoice_number'] : '-', // No Invoice as text
            $row['platform_product_name'] ?? '-', // Nama Produk (Platform)
            $row['platform_product_variant'] ?? '-', // Variasi (Platform)
            $platformQty, // Jumlah QTY (PCS) (Platform)
            isset($row['sku']) ? (string)$row['sku'] : '-', // SKU as text
            $row['product_name'] ?? '-', // Master Barang
            $qty, // QTY (master barang)
            round($paymentAmount, 2), // Jumlah masuk pembayaran (Rp) - order_total_payment
            round($paymentAmountWithoutPPN, 2), // Jumlah masuk pembayaran - PPN (Rp) - use pre-calculated value
            round($pricelistPerItem, 2), // Harga pricelist per item (Rp)
            round($pricelistTotal, 2), // Harga pricelist per item X QTY (Rp)
            round($totalPricelistOrder, 2), // Total harga pricelist (Rp)
            $proportionPercent / 100, // Persen dalam order (%) - convert to decimal for Excel percentage format
            round($paymentPerProduct, 2), // Masuk pembayaran per produk (Rp)
            round($paymentPerProductWithoutPPN, 2), // Masuk pembayaran per produk - PPN (Rp)
            round($unitCost, 2), // Harga Modal (COGS) (Rp)
            round($profitPerPCS, 2), // Profit per PCS (Rp)
            round($grossProfitTotal, 2), // Gross Profit total (Rp)
            $marginPerPCS / 100, // Margin per pcs (%) - convert to decimal for Excel percentage format
            $marginPerItem / 100 // Margin per item (%) - convert to decimal for Excel percentage format
        ];
    }

    public function title(): string
    {
        return 'Sales by Master Product';
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // No Pesanan as text
            'C' => NumberFormat::FORMAT_TEXT, // No Invoice as text
            'G' => NumberFormat::FORMAT_TEXT, // SKU as text
            'F' => NumberFormat::FORMAT_NUMBER, // Jumlah QTY (PCS) (Platform)
            'I' => NumberFormat::FORMAT_NUMBER, // QTY
            'J' => '#,##0.00', // Jumlah masuk pembayaran (Rp)
            'K' => '#,##0.00', // Jumlah masuk pembayaran - PPN (Rp)
            'L' => '#,##0.00', // Harga pricelist per item (Rp)
            'M' => '#,##0.00', // Harga pricelist per item X QTY (Rp)
            'N' => '#,##0.00', // Total harga pricelist (Rp)
            'O' => '0.00%', // Persen dalam order (%)
            'P' => '#,##0.00', // Masuk pembayaran per produk (Rp)
            'Q' => '#,##0.00', // Masuk pembayaran per produk - PPN (Rp)
            'R' => '#,##0.00', // Harga Modal (COGS) (Rp)
            'S' => '#,##0.00', // Profit per PCS (Rp)
            'T' => '#,##0.00', // Gross Profit total (Rp)
            'U' => '0.00%', // Margin per pcs (%)
            'V' => '0.00%', // Margin per item (%)
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                
                // Force text format for specific columns without apostrophe
                $textColumns = ['B', 'C', 'G']; // No Pesanan, No Invoice, SKU
                
                foreach ($textColumns as $column) {
                    for ($row = 11; $row <= $lastRow; $row++) {
                        $cell = $column . $row;
                        $value = $sheet->getCell($cell)->getValue();
                        
                        if ($value !== null && $value !== '-') {
                            // Remove commas and set as text without apostrophe
                            $textValue = str_replace(',', '', (string)$value);
                            $sheet->setCellValueExplicit($cell, $textValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    }
                }
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row - CLEAN STYLING
        $sheet->getStyle('A10:V10')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => 'solid',
                'color' => ['rgb' => '4361ee']
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center'
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ]);
        
        // Remove any conditional formatting that might cause blue highlights
        // Apply clean styling to prevent unwanted highlights

        // Add summary information at the top - CLEAN STYLING
        $sheet->insertNewRowBefore(1, 9);
        
        // Title styling
        $sheet->setCellValue('A1', 'ANALISIS GROSS PROFIT PER MASTER PRODUK');
        $sheet->mergeCells('A1:V1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true, 
                'size' => 16,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center'
            ],
            'fill' => [
                'fillType' => 'none'
            ]
        ]);

        // Summary section styling
        $sheet->setCellValue('A3', 'RINGKASAN:');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => 'left',
                'vertical' => 'center'
            ],
            'fill' => [
                'fillType' => 'none'
            ]
        ]);
        
        // Summary data styling - LEFT ALIGNED, NO CENTER
        $summaryLabels = ['A4', 'A5', 'A6', 'A7', 'A8', 'A9'];
        $summaryValues = ['B4', 'B5', 'B6', 'B7', 'B8', 'B9'];
        
        $sheet->setCellValue('A4', 'Periode:');
        $sheet->setCellValue('B4', ($this->filters['start_date'] ?? date('Y-m-d')) . ' s/d ' . ($this->filters['end_date'] ?? date('Y-m-d')));
        
        $sheet->setCellValue('A5', 'Total Produk:');
        $sheet->setCellValue('B5', number_format($this->summary['total_products']));
        
        $sheet->setCellValue('A6', 'Total Saldo Masuk:');
        $sheet->setCellValue('B6', 'Rp ' . number_format($this->summary['total_revenue'], 0, ',', '.'));
        
        $sheet->setCellValue('A7', 'Total Saldo Masuk - PPN:');
        $sheet->setCellValue('B7', 'Rp ' . number_format($this->summary['total_revenue_without_ppn'], 0, ',', '.'));
        
        $sheet->setCellValue('A8', 'Total Modal:');
        $sheet->setCellValue('B8', 'Rp ' . number_format($this->summary['total_capital'], 0, ',', '.'));
        
        $sheet->setCellValue('A9', 'Gross Profit:');
        $sheet->setCellValue('B9', 'Rp ' . number_format($this->summary['total_gross_profit'], 0, ',', '.'));
        
        // Apply consistent styling to summary labels and values
        foreach ($summaryLabels as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => '000000']
                ],
                'alignment' => [
                    'horizontal' => 'left',
                    'vertical' => 'center'
                ],
                'fill' => [
                    'fillType' => 'none'
                ]
            ]);
        }
        
        foreach ($summaryValues as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => false,
                    'size' => 11,
                    'color' => ['rgb' => '000000']
                ],
                'alignment' => [
                    'horizontal' => 'left',
                    'vertical' => 'center'
                ],
                'fill' => [
                    'fillType' => 'none'
                ]
            ]);
        }

        // Format currency columns (J, K, L, M, N, P, Q, R, S, T) with 2 decimal places
        $lastRow = $sheet->getHighestRow();
        $currencyColumns = ['J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T'];
        foreach ($currencyColumns as $col) {
            $sheet->getStyle($col . '11:' . $col . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        
        // Format percentage columns (O, U, V)
        $percentageColumns = ['O', 'U', 'V'];
        foreach ($percentageColumns as $col) {
            $sheet->getStyle($col . '11:' . $col . $lastRow)->getNumberFormat()->setFormatCode('0.00%');
        }
        
        // Format text columns (B, C, G) to ensure they stay as text
        $textColumns = ['B', 'C', 'G'];
        foreach ($textColumns as $col) {
            $sheet->getStyle($col . '11:' . $col . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }
        
        // Format number columns (F, I) as numbers without decimal places
        $numberColumns = ['F', 'I'];
        foreach ($numberColumns as $col) {
            $sheet->getStyle($col . '11:' . $col . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
        
        // Remove any conditional formatting and ensure no blue highlights
        // Apply clean styling to prevent unwanted highlights
        
        // Set default cell style to remove any unwanted formatting
        // Note: getDefaultStyle() is not available on Worksheet, use getStyle() instead
        $sheet->getStyle('A1:V1000')->applyFromArray([
            'fill' => [
                'fillType' => 'none'
            ],
            'font' => [
                'color' => ['rgb' => '000000'],
                'bold' => false  // Explicitly set bold to false
            ]
        ]);
        
        // Apply clean styling to all data rows - NO BOLD TEXT, NO CENTER ALIGNMENT
        $sheet->getStyle('A11:V' . $lastRow)->applyFromArray([
            'fill' => [
                'fillType' => 'none'
            ],
            'font' => [
                'color' => ['rgb' => '000000'],
                'bold' => false,  // Explicitly set bold to false
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => 'left',  // LEFT ALIGNED, NOT CENTERED
                'vertical' => 'center'
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);
        
        // Special alignment for numeric columns (right aligned)
        $numericColumns = ['F', 'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T'];
        foreach ($numericColumns as $col) {
            $sheet->getStyle($col . '11:' . $col . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => 'right',
                    'vertical' => 'center'
                ]
            ]);
        }
        
        // Special alignment for percentage columns (right aligned)
        $percentageColumns = ['O', 'U', 'V'];
        foreach ($percentageColumns as $col) {
            $sheet->getStyle($col . '11:' . $col . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => 'right',
                    'vertical' => 'center'
                ]
            ]);
        }
        
        return [];
    }
} 