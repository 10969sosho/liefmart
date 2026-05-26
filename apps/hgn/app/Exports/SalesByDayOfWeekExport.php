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

class SalesByDayOfWeekExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $dayOfWeekSummary;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;

    public function __construct($dayOfWeekSummary, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        // Convert to collection with proper ordering (Monday to Sunday)
        $orderedData = collect();
        $dayOrder = [1, 2, 3, 4, 5, 6, 0]; // Monday to Sunday
        foreach ($dayOrder as $dayNum) {
            if (isset($dayOfWeekSummary[$dayNum])) {
                $orderedData->push($dayOfWeekSummary[$dayNum]);
            }
        }
        
        $this->dayOfWeekSummary = $orderedData;
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
    }

    public function collection()
    {
        return $this->dayOfWeekSummary;
    }

    public function headings(): array
    {
        return [
            'Hari',
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
            $row['day_name'],
            $row['order_count'],
            $row['total_value'],
            $row['total_volume'],
            round($avgValue, 0),
            round($avgVolume, 1),
            round($valueVolumeRatio, 0),
        ];
    }
} 