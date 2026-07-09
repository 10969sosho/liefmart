<?php

namespace App\Exports\Handlers\Offline;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\OfflineMonthlySalesExport;
use App\Models\Customer;
use App\Models\OfflineSale;
use Carbon\Carbon;

class OfflineMonthlySalesSummaryHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'offline_monthly_sales_summary';
    }

    public function handle(array $filters): array
    {
        $selectedYear = $filters['year'] ?? date('Y');
        $selectedCustomer = $filters['customer_id'] ?? null;

        $customers = Customer::orderBy('name')->get();

        $query = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'customerInfo'])
            ->whereYear('sale_date', $selectedYear);

        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }

        $sales = $query->get();

        // Group by month
        $monthlySummary = $sales->groupBy(function ($sale) {
            return $sale->sale_date->format('Y-m');
        })->map(function ($monthSales, $yearMonth) {
            $totalVolume = $monthSales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });

            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->format('M Y'),
                'total_orders' => $monthSales->count(),
                'total_value' => $monthSales->sum('total_amount'),
                'total_volume' => $totalVolume,
                'avg_order_value' => $monthSales->count() > 0 ? $monthSales->sum('total_amount') / $monthSales->count() : 0,
                'avg_order_volume' => $monthSales->count() > 0 ? $totalVolume / $monthSales->count() : 0,
            ];
        })->sortBy('year_month')->values();

        // Calculate year summary
        $yearSummary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('total_amount'),
            'total_volume' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
        ];

        $yearSummary['avg_order_value'] = $yearSummary['total_orders'] > 0 ? $yearSummary['total_value'] / $yearSummary['total_orders'] : 0;
        $yearSummary['avg_order_volume'] = $yearSummary['total_orders'] > 0 ? $yearSummary['total_volume'] / $yearSummary['total_orders'] : 0;

        $customerName = $selectedCustomer ? $customers->where('id', $selectedCustomer)->first()->name ?? 'Unknown' : null;

        $filename = 'penjualan-bulanan-offline-' . $selectedYear . '-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new OfflineMonthlySalesExport($monthlySummary, $yearSummary, $selectedYear, $customerName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
