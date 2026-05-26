<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesByPlatformProductExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $platformProductRows;
    protected $summary;
    protected $filters;

    public function __construct($platformProductRows, $summary, $filters = [])
    {
        $this->platformProductRows = $platformProductRows;
        $this->summary = $summary;
        $this->filters = $filters;
    }

    public function collection()
    {
        return collect($this->platformProductRows);
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'No Pesanan',
            'No Invoice',
            'Nama Produk (Platform)',
            'Variasi (Platform)',
            'QTY',
            'Jumlah Masuk Pembayaran per Produk (Rp)',
            'Jumlah Masuk Pembayaran - PPN per Produk (Rp)',
            'Harga Modal Total (COGS) (Rp)',
            'Gross Profit per Produk (%)',
            'Margin per Produk (Rp)',
            'Margin per Produk (%)',
            'Gross Profit per Order (Rp)',
            'Margin per Order (%)'
        ];
    }

    public function map($row): array
    {
        // Use data from query that already calculates everything
        $revenue = $row['revenue'] ?? 0; // Revenue per product (already proportional)
        $revenueWithoutPPN = $row['revenue_without_ppn'] ?? 0; // Revenue per product without PPN
        $capital = $row['capital'] ?? 0; // COGS per product
        $grossProfitPerProductPercent = $row['gross_profit_per_product_percent'] ?? 0; // Gross profit per product (%)
        $marginPerProductRp = $row['margin_per_product_rp'] ?? 0; // Margin per product (Rp)
        $marginPerProductPercent = $row['margin_per_product_percent'] ?? 0; // Margin per product (%)
        $grossProfitPerOrder = $row['gross_profit_per_order_rp'] ?? 0; // Gross profit per order (Rp)
        $marginPerOrderPercent = $row['margin_per_order_percent'] ?? 0; // Margin per order (%)

        return [
            isset($row['order_date']) ? Carbon::parse($row['order_date'])->format('d M Y') : '-', // Tanggal
            isset($row['order_number']) ? (string)$row['order_number'] : '-', // No Pesanan as text
            isset($row['invoice_number']) ? (string)$row['invoice_number'] : '-', // No Invoice as text
            $row['platform_product_name'] ?? '-', // Nama Produk (Platform)
            $row['product_variant'] ?? ($row['platform_product_variant'] ?? '-'), // Variasi (Platform)
            $row['quantity'] ?? 0, // QTY
            $revenue, // Jumlah Masuk Pembayaran per Produk (Rp)
            $revenueWithoutPPN, // Jumlah Masuk Pembayaran - PPN per Produk (Rp)
            $capital, // Harga Modal Total (COGS) (Rp)
            $grossProfitPerProductPercent / 100, // Gross Profit per Produk (%) - convert to decimal for Excel
            $marginPerProductRp, // Margin per Produk (Rp)
            $marginPerProductPercent / 100, // Margin per Produk (%) - convert to decimal for Excel
            $grossProfitPerOrder, // Gross Profit per Order (Rp)
            $marginPerOrderPercent / 100 // Margin per Order (%) - convert to decimal for Excel
        ];
    }

    public function title(): string
    {
        return 'Sales by Platform Product';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row - CLEAN STYLING
        $sheet->getStyle('A9:N9')->applyFromArray([
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
        
        // Add summary information at the top - CLEAN STYLING
        $sheet->insertNewRowBefore(1, 8);
        
        // Title styling
        $sheet->setCellValue('A1', 'ANALISIS GROSS PROFIT PER PRODUK PLATFORM');
        $sheet->mergeCells('A1:N1');
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
        $summaryLabels = ['A4', 'A5', 'A6', 'A7', 'A8'];
        $summaryValues = ['B4', 'B5', 'B6', 'B7', 'B8'];
        
        $sheet->setCellValue('A4', 'Periode:');
        $sheet->setCellValue('B4', ($this->filters['start_date'] ?? date('Y-m-d')) . ' s/d ' . ($this->filters['end_date'] ?? date('Y-m-d')));
        
        $sheet->setCellValue('A5', 'Total Produk Platform:');
        $sheet->setCellValue('B5', number_format($this->summary['total_platform_products']));
        
        $sheet->setCellValue('A6', 'Total Saldo Masuk:');
        $sheet->setCellValue('B6', 'Rp ' . number_format($this->summary['total_revenue'], 0, ',', '.'));
        
        $sheet->setCellValue('A7', 'Total Modal:');
        $sheet->setCellValue('B7', 'Rp ' . number_format($this->summary['total_capital'], 0, ',', '.'));
        
        $sheet->setCellValue('A8', 'Gross Profit:');
        $sheet->setCellValue('B8', 'Rp ' . number_format($this->summary['total_gross_profit'], 0, ',', '.'));
        
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

        // Format currency columns (G, H, I, K, M)
        $lastRow = $sheet->getHighestRow();
        $currencyColumns = ['G', 'H', 'I', 'K', 'M'];
        foreach ($currencyColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
        
        // Format percentage columns (J, L, N)
        $percentageColumns = ['J', 'L', 'N'];
        foreach ($percentageColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->getNumberFormat()->setFormatCode('0.00%');
        }
        
        // Set default cell style to remove any unwanted formatting
        $sheet->getStyle('A1:N1000')->applyFromArray([
            'fill' => [
                'fillType' => 'none'
            ],
            'font' => [
                'color' => ['rgb' => '000000'],
                'bold' => false  // Explicitly set bold to false
            ]
        ]);
        
        // Apply clean styling to all data rows - NO BOLD TEXT, NO CENTER ALIGNMENT
        $sheet->getStyle('A10:N' . $lastRow)->applyFromArray([
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
        $numericColumns = ['F', 'G', 'H', 'I', 'K', 'M'];
        foreach ($numericColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => 'right',
                    'vertical' => 'center'
                ]
            ]);
        }
        
        // Special alignment for percentage columns (right aligned)
        $percentageColumns = ['J', 'L', 'N'];
        foreach ($percentageColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => 'right',
                    'vertical' => 'center'
                ]
            ]);
        }
        
        return [];
    }
}