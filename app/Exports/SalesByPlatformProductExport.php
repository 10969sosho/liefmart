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
            'Jumlah QTY (Platform)',
            'Jumlah Masuk Pembayaran (Rp)',
            'Jumlah Masuk Pembayaran - PPN (Rp)',
            'Harga Modal Total (COGS) (Rp)',
            'Gross Profit Total (Rp)',
            'Margin per pcs (%)'
        ];
    }

    public function map($row): array
    {
        // Calculate values according to the new requirements
        $revenue = $row['revenue'] ?? 0;
        $revenueWithoutPPN = $revenue / 1.11;
        $capital = $row['capital'] ?? 0;
        $grossProfit = $revenueWithoutPPN - $capital;
        
        // Margin per pcs = (gross profit total / jumlah masuk pembayaran-PPN) x 100%
        $marginPercent = $revenueWithoutPPN > 0 ? ($grossProfit / $revenueWithoutPPN) : 0;

        return [
            isset($row['order_date']) ? Carbon::parse($row['order_date'])->format('d M Y') : '-', // Tanggal
            isset($row['order_number']) ? (string)$row['order_number'] : '-', // No Pesanan as text
            isset($row['invoice_number']) ? (string)$row['invoice_number'] : '-', // No Invoice as text
            $row['platform_product_name'] ?? '-', // Nama Produk (Platform)
            $row['platform_product_variant'] ?? '-', // Variasi (Platform)
            $row['quantity'] ?? 0, // Jumlah QTY (Platform)
            $revenue, // Jumlah Masuk Pembayaran (Rp)
            $revenueWithoutPPN, // Jumlah Masuk Pembayaran - PPN (Rp)
            $capital, // Harga Modal Total (COGS) (Rp)
            $grossProfit, // Gross Profit Total (Rp)
            $marginPercent // Margin per pcs (%) - dalam format desimal untuk Excel
        ];
    }

    public function title(): string
    {
        return 'Sales by Platform Product';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row - CLEAN STYLING
        $sheet->getStyle('A9:K9')->applyFromArray([
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
        $sheet->mergeCells('A1:K1');
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

        // Format currency columns (G, H, I, J)
        $lastRow = $sheet->getHighestRow();
        $currencyColumns = ['G', 'H', 'I', 'J'];
        foreach ($currencyColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
        
        // Format percentage columns (K)
        $percentageColumns = ['K'];
        foreach ($percentageColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->getNumberFormat()->setFormatCode('0.00%');
        }
        
        // Set default cell style to remove any unwanted formatting
        $sheet->getStyle('A1:K1000')->applyFromArray([
            'fill' => [
                'fillType' => 'none'
            ],
            'font' => [
                'color' => ['rgb' => '000000'],
                'bold' => false  // Explicitly set bold to false
            ]
        ]);
        
        // Apply clean styling to all data rows - NO BOLD TEXT, NO CENTER ALIGNMENT
        $sheet->getStyle('A10:K' . $lastRow)->applyFromArray([
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
        $numericColumns = ['F', 'G', 'H', 'I', 'J'];
        foreach ($numericColumns as $col) {
            $sheet->getStyle($col . '10:' . $col . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => 'right',
                    'vertical' => 'center'
                ]
            ]);
        }
        
        // Special alignment for percentage columns (right aligned)
        $percentageColumns = ['K'];
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