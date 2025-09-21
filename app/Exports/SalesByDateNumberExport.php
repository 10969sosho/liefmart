<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class SalesByDateNumberExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithCustomValueBinder
{
    protected $dateNumberSummary;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;
    private static $index = 1;

    public function __construct($dateNumberSummary, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        $this->dateNumberSummary = collect($dateNumberSummary);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
        self::$index = 1;
    }

    public function bindValue(Cell $cell, $value)
    {
        // Handle large numbers and text properly
        if (is_numeric($value) && strlen($value) > 15) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return $this->dateNumberSummary;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Jumlah Order',
            'Total Saldo Masuk (Rp)',
            'Total Volume (pcs)',
            'Avg Saldo/Order (Rp)',
            'Avg Volume/Order',
            'Saldo/Volume (Rp)',
        ];
    }

    public function map($row): array
    {
        $avgValue = $row['order_count'] > 0 ? $row['total_value'] / $row['order_count'] : 0;
        $avgVolume = $row['order_count'] > 0 ? $row['total_volume'] / $row['order_count'] : 0;
        $valueVolumeRatio = $row['total_volume'] > 0 ? $row['total_value'] / $row['total_volume'] : 0;

        return [
            $row['date_number'],
            $row['order_count'],
            $row['total_value'],
            $row['total_volume'],
            round($avgValue, 0),
            round($avgVolume, 1),
            round($valueVolumeRatio, 0),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport $event) {
                // Disable query log to save memory
                \DB::disableQueryLog();
            },
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                
                // Add title
                $sheet->insertNewRowBefore(1, 4);
                $sheet->mergeCells('A1:H1');
                $sheet->setCellValue('A1', 'LAPORAN ANALISIS SALDO MASUK PER TANGGAL (1-31)');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add period
                $sheet->mergeCells('A2:H2');
                $periodText = 'Periode: ' . \Carbon\Carbon::parse($this->startDate)->format('d M Y');
                if ($this->startDate != $this->endDate) {
                    $periodText .= ' - ' . \Carbon\Carbon::parse($this->endDate)->format('d M Y');
                }
                $sheet->setCellValue('A2', $periodText);
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add platform filter info
                if ($this->selectedPlatform) {
                    $sheet->mergeCells('A3:H3');
                    $sheet->setCellValue('A3', 'Platform: ' . $this->selectedPlatform);
                    $sheet->getStyle('A3')->getFont()->setBold(true);
                    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Add export date
                $sheet->mergeCells('A4:H4');
                $sheet->setCellValue('A4', 'Tanggal Export: ' . date('d/m/Y H:i:s'));
                $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Style headers
                $headerRow = $this->selectedPlatform ? 6 : 5;
                $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('4472C4');
                $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFont()->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A'.$headerRow.':H'.$headerRow)->getFont()->setBold(true);
                
                // Add borders
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A'.$headerRow.':H'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Add total row
                $totalRow = $lastRow + 1;
                $sheet->setCellValue('A'.$totalRow, '');
                $sheet->setCellValue('B'.$totalRow, 'TOTAL');
                $sheet->setCellValue('C'.$totalRow, number_format($this->summary['total_orders']));
                $sheet->setCellValue('D'.$totalRow, number_format($this->summary['total_value'], 0, ',', '.'));
                $sheet->setCellValue('E'.$totalRow, number_format($this->summary['total_volume']));
                $sheet->setCellValue('F'.$totalRow, number_format($this->summary['avg_order_value'], 0, ',', '.'));
                $sheet->setCellValue('G'.$totalRow, number_format($this->summary['avg_order_volume'], 1));
                $sheet->setCellValue('H'.$totalRow, $this->summary['total_volume'] > 0 ? number_format($this->summary['total_value'] / $this->summary['total_volume'], 0, ',', '.') : '0');
                
                // Style total row
                $sheet->getStyle('A'.$totalRow.':H'.$totalRow)->getFont()->setBold(true);
                $sheet->getStyle('A'.$totalRow.':H'.$totalRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E7F1FF');
                $sheet->getStyle('A'.$totalRow.':H'.$totalRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
} 