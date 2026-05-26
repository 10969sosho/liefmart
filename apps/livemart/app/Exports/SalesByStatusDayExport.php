<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesByStatusDayExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $statusDaySummary;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedPlatform;

    public function __construct($statusDaySummary, $summary, $startDate, $endDate, $selectedPlatform = null)
    {
        $this->statusDaySummary = collect($statusDaySummary);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedPlatform = $selectedPlatform;
    }

    public function collection()
    {
        return $this->statusDaySummary;
    }

    public function headings(): array
    {
        return [
            'Status',
            'Jumlah Order',
            'Total Value (Rp)',
            'Total Volume (pcs)',
            'Avg Value/Order (Rp)',
            'Avg Volume/Order',
        ];
    }

    public function map($row): array
    {
        $orderCount = $row['order_count'] ?? 0;
        $totalValue = $row['total_value'] ?? 0;
        $totalVolume = $row['total_volume'] ?? 0;

        $avgValue = $orderCount > 0 ? $totalValue / $orderCount : 0;
        $avgVolume = $orderCount > 0 ? $totalVolume / $orderCount : 0;

        return [
            $row['status'] ?? '',
            $orderCount,
            $totalValue,
            $totalVolume,
            round($avgValue, 1),
            round($avgVolume, 1),
        ];
    }
} 