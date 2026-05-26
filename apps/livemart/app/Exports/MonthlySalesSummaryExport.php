<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MonthlySalesSummaryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $monthlySummary;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;

    public function __construct($monthlySummary, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        // Lightweight initialization - no memory/time limit changes
        $this->monthlySummary = collect($monthlySummary);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
    }

    public function collection()
    {
        // Simply return the pre-processed data
        return $this->monthlySummary;
    }

    public function headings(): array
    {
        return [
            'Bulan',
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
        // Handle both object and array formats
        if (is_object($row)) {
            $monthName = $row->month_name ?? '';
            $orderCount = $row->order_count ?? 0;
            $totalValue = $row->total_value ?? 0;
            $totalVolume = $row->total_volume ?? 0;
            $avgOrderValue = $row->avg_order_value ?? ($orderCount > 0 ? $totalValue / $orderCount : 0);
            $avgOrderVolume = $row->avg_order_volume ?? ($orderCount > 0 ? $totalVolume / $orderCount : 0);
            $valueVolumeRatio = $row->value_volume_ratio ?? ($totalVolume > 0 ? $totalValue / $totalVolume : 0);
        } else {
            $monthName = $row['month_name'] ?? '';
            $orderCount = $row['order_count'] ?? 0;
            $totalValue = $row['total_value'] ?? 0;
            $totalVolume = $row['total_volume'] ?? 0;
            $avgOrderValue = $row['avg_order_value'] ?? ($orderCount > 0 ? $totalValue / $orderCount : 0);
            $avgOrderVolume = $row['avg_order_volume'] ?? ($orderCount > 0 ? $totalVolume / $orderCount : 0);
            $valueVolumeRatio = $row['value_volume_ratio'] ?? ($totalVolume > 0 ? $totalValue / $totalVolume : 0);
        }

        return [
            $monthName,
            $orderCount,
            $totalValue,
            $totalVolume,
            round($avgOrderValue, 0),
            round($avgOrderVolume, 1),
            round($valueVolumeRatio, 0),
        ];
    }
} 