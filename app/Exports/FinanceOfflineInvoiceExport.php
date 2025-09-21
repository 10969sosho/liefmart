<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FinanceOfflineInvoiceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, WithCustomValueBinder
{
    protected $invoices;

    public function __construct($invoices)
    {
        $this->invoices = $invoices;
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Invoice',
            'Tanggal Invoice',
            'No. SJ',
            'Tax ID',
            'Kategori',
            'Customer',
            'DPP (Rp)',
            'PPN (Rp)',
            'Total (Rp)',
            'Status',
            'Total Dibayar (Rp)',
            'Sisa Tagihan (Rp)',
            'Jumlah Cetak',
            'Terakhir Dicetak'
        ];
    }

    public function map($invoice): array
    {
        $firstItem = $invoice->barangKeluarItems->first();
        $taxId = $firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->tax_id ? $firstItem->warehouseStock->tax_id : null;
        
        $dpp = \App\Helpers\NumberFormatter::calculateDPP($invoice->nominal);
        $ppn = 0;
        $grandTotal = $dpp;
        
        if ($taxId == 3) {
            $dpp11_12 = \App\Helpers\NumberFormatter::calculateDPP1112($dpp);
            $ppn = \App\Helpers\NumberFormatter::calculatePPN($dpp11_12);
            $grandTotal = \App\Helpers\NumberFormatter::calculateGrandTotal($dpp, $ppn);
        } else {
            $grandTotal = \App\Helpers\NumberFormatter::roundToWholeNumber($dpp);
        }
        
        $totalPaid = \App\Helpers\NumberFormatter::roundToWholeNumber($invoice->payments->sum('amount'));
        $remainingAmount = max(0, $grandTotal - $totalPaid);
        
        // Get SJ numbers, tax_id and customer
        $sjNumber = '-';
        $customer = '-';
        $taxLabel = '-';
        
        if ($firstItem) {
            if ($firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale) {
                $sjNumber = $firstItem->offlineSaleItem->offlineSale->surat_jalan_number;
                $customer = $firstItem->offlineSaleItem->offlineSale->customer_name;
            }
            
            if ($firstItem->warehouseStock && $firstItem->warehouseStock->tax_id) {
                $taxId = $firstItem->warehouseStock->tax_id;
                
                if ($taxId == 3) {
                    $taxLabel = 'PKP';
                } elseif ($taxId == 4) {
                    $taxLabel = 'Non-PKP';
                }
            }
        }
        
        // Get main category name
        $mainCategoryName = 'N/A';
        if ($firstItem && $firstItem->offlineSaleItem && $firstItem->offlineSaleItem->offlineSale && $firstItem->offlineSaleItem->offlineSale->mainCategory) {
            $mainCategoryName = $firstItem->offlineSaleItem->offlineSale->mainCategory->name;
        } elseif ($firstItem && $firstItem->warehouseStock && $firstItem->warehouseStock->product && $firstItem->warehouseStock->product->mainCategory) {
            $mainCategoryName = $firstItem->warehouseStock->product->mainCategory->name;
        } elseif (session()->has('main_category_name')) {
            $mainCategoryName = session('main_category_name');
        }
        
        // Get status
        if ($totalPaid >= $grandTotal) {
            $status = 'Lunas';
        } elseif ($totalPaid > 0) {
            $status = 'Sebagian';
        } else {
            $status = 'Belum Bayar';
        }
        
        return [
            '', // No - will be filled by Excel
            $invoice->invoice_number,
            $invoice->tanggal_invoice->format('d/m/Y'),
            $sjNumber,
            $taxLabel,
            $mainCategoryName,
            $customer,
            $dpp,
            $ppn,
            $grandTotal,
            $status,
            $totalPaid,
            $remainingAmount,
            $invoice->print_count,
            $invoice->last_printed_at ? $invoice->last_printed_at->format('d/m/Y H:i') : '-'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // DPP
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // PPN
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Dibayar
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Sisa Tagihan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(15);
        $sheet->getColumnDimension('N')->setWidth(12);
        $sheet->getColumnDimension('O')->setWidth(20);

        // Auto-fit row height
        $sheet->getDefaultRowDimension()->setRowHeight(-1);

        // Freeze header row
        $sheet->freezePane('A2');

        return [];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
        } else {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
        }
        return true;
    }
}
