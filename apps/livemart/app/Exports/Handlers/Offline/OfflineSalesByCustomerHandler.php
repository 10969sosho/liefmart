<?php

namespace App\Exports\Handlers\Offline;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\OfflineSalesByCustomerExport;
use App\Models\Customer;
use App\Models\OfflineSale;

class OfflineSalesByCustomerHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'offline_sales_by_customer';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? date('Y-m-01');
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $selectedCustomer = $filters['customer_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'value_highest';

        $customers = Customer::orderBy('name')->get();

        $query = OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'customerInfo']);

        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }

        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }

        $sales = $query->get();

        // Group by customer
        $customerSummary = $sales->groupBy('customer_id')->map(function ($customerSales, $customerId) {
            $customer = $customerSales->first()->customerInfo;
            $customerName = $customer ? $customer->name : 'Unknown';

            $totalVolume = $customerSales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });

            return [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'total_orders' => $customerSales->count(),
                'total_value' => $customerSales->sum('total_amount'),
                'total_volume' => $totalVolume,
                'avg_order_value' => $customerSales->count() > 0 ? $customerSales->sum('total_amount') / $customerSales->count() : 0,
                'avg_order_volume' => $customerSales->count() > 0 ? $totalVolume / $customerSales->count() : 0,
            ];
        });

        // Sort data
        switch ($sortBy) {
            case 'value_highest':
                $customerSummary = $customerSummary->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $customerSummary = $customerSummary->sortBy('total_value');
                break;
            case 'volume_highest':
                $customerSummary = $customerSummary->sortByDesc('total_volume');
                break;
            case 'volume_lowest':
                $customerSummary = $customerSummary->sortBy('total_volume');
                break;
            case 'orders_highest':
                $customerSummary = $customerSummary->sortByDesc('total_orders');
                break;
            case 'orders_lowest':
                $customerSummary = $customerSummary->sortBy('total_orders');
                break;
            case 'name_asc':
                $customerSummary = $customerSummary->sortBy('customer_name');
                break;
            case 'name_desc':
                $customerSummary = $customerSummary->sortByDesc('customer_name');
                break;
            default:
                $customerSummary = $customerSummary->sortByDesc('total_value');
        }

        // Calculate summary
        $summary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('total_amount'),
            'total_volume' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
        ];

        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $customerName = $selectedCustomer ? $customers->where('id', $selectedCustomer)->first()->name ?? 'Unknown' : null;

        $filename = 'penjualan-offline-by-customer-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new OfflineSalesByCustomerExport($customerSummary, $summary, $startDate, $endDate, $customerName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
