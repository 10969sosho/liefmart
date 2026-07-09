<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\SalesByDayOfWeekExport;
use App\Models\Order;
use App\Models\Platform;
use Carbon\Carbon;

class SalesByDayOfWeekHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_day_of_week';
    }

    public function handle(array $filters): array
    {
        $platforms = Platform::all();
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $selectedPlatform = $filters['platform_id'] ?? null;

        // Apply quick date range if set
        if (isset($filters['quick_range'])) {
            $range = $filters['quick_range'];
            $endDate = now()->format('Y-m-d');
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
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

        // Initialize day of week summary
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        $dayOfWeekSummary = [];
        foreach ($dayNames as $dayNum => $dayName) {
            $dayOfWeekSummary[$dayNum] = [
                'day_name' => $dayName,
                'order_count' => 0,
                'total_value' => 0,
                'total_volume' => 0,
            ];
        }

        // Group by day of week
        foreach ($validOrders as $order) {
            $dayOfWeek = $order->order_date->dayOfWeek;

            $dayOfWeekSummary[$dayOfWeek]['order_count']++;
            $dayOfWeekSummary[$dayOfWeek]['total_value'] += $order->financialTransactions->sum('nominal_fix');
            $dayOfWeekSummary[$dayOfWeek]['total_volume'] += $order->items->sum('quantity');
        }

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

        $filename = 'analisis-saldo-masuk-per-hari-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesByDayOfWeekExport($dayOfWeekSummary, $summary, $startDate, $endDate, $platformName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
