<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OfflineSalesByCustomerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $customerSummary;
    protected $summary;
    protected $startDate;
    protected $endDate;
    protected $selectedCustomer;

    public function __construct($customerSummary, $summary, $startDate, $endDate, $selectedCustomer = null)
    {
        $this->customerSummary = collect($customerSummary);
        $this->summary = $summary;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedCustomer = $selectedCustomer;
    }

    public function collection()
    {
        return $this->customerSummary;
    }

    public function headings(): array
    {
        return [
            'Customer',
            'Jumlah Penjualan',
            'Total Value (Rp)',
            'Avg Value/Order (Rp)',
            'Total Volume (pcs)',
            'Avg Volume/Order',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer['customer_name'],
            $customer['total_orders'],
            $customer['total_value'],
            $customer['avg_order_value'],
            $customer['total_volume'],
            $customer['avg_order_volume'],
        ];
    }
} 