<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OfflineMonthlySalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $monthlySummary;
    protected $summary;
    protected $selectedYear;
    protected $selectedCustomer;

    public function __construct($monthlySummary, $summary, $selectedYear, $selectedCustomer = null)
    {
        $this->monthlySummary = collect($monthlySummary);
        $this->summary = $summary;
        $this->selectedYear = $selectedYear;
        $this->selectedCustomer = $selectedCustomer;
    }

    public function collection()
    {
        return $this->monthlySummary;
    }

    public function headings(): array
    {
        return [
            'Bulan',
            'Jumlah Penjualan',
            'Total Value (Rp)',
            'Avg Value/Order (Rp)',
            'Total Volume (pcs)',
            'Avg Volume/Order',
        ];
    }

    public function map($row): array
    {
        return [
            $row['month_name'],
            $row['total_orders'],
            $row['total_value'],
            $row['avg_order_value'],
            $row['total_volume'],
            $row['avg_order_volume'],
        ];
    }
} 