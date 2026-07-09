<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\MonthlySalesSummaryExport;
use App\Models\Order;
use App\Models\Platform;
use Carbon\Carbon;

class MonthlySalesSummaryHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'monthly_sales_summary';
    }

    public function handle(array $filters): array
    {
        $platforms = Platform::all();
        $startDate = $filters['start_date'] ?? now()->subMonths(6)->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $selectedPlatform = $filters['platform_id'] ?? null;

        // Apply quick date range if set
        if (isset($filters['quick_range'])) {
            $range = $filters['quick_range'];
            $endDate = now()->format('Y-m-d');
            switch ($range) {
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
                case '6months':
                    $startDate = now()->subMonths(6)->format('Y-m-d');
                    break;
                case '1year':
                    $startDate = now()->subYear()->format('Y-m-d');
                    break;
            }
        }

        // Build the query for orders
        $query = Order::with(['platform', 'items', 'financialTransactions']);

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }

        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }

        $orders = $query->get();

        // Filter orders that have financial transactions
        $validOrders = $orders->filter(function ($order) {
            return $order->financialTransactions->isNotEmpty();
        });

        // Group by month and calculate summary
        $monthlySummary = $validOrders->groupBy(function ($order) {
            return $order->order_date->format('Y-m');
        })->map(function ($monthOrders, $yearMonth) {
            $totalValue = $monthOrders->sum(function ($order) {
                return $order->financialTransactions->sum('nominal_fix');
            });

            $totalVolume = $monthOrders->sum(function ($order) {
                return $order->items->sum('quantity');
            });

            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->format('M Y'),
                'order_count' => $monthOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        })->sortBy('year_month')->values();

        // Calculate summary
        $summary = [
            'total_orders' => $validOrders->count(),
            'total_value' => $validOrders->sum(function ($order) {
                return $order->financialTransactions->sum('nominal_fix');
            }),
            'total_volume' => $validOrders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
        ];

        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $platformName = $selectedPlatform ? $platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown' : null;

        $filename = 'analisis-saldo-masuk-bulanan-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new MonthlySalesSummaryExport($monthlySummary, $summary, $startDate, $endDate, $platformName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
