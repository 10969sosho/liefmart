<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\SalesByDateNumberExport;
use App\Models\Order;
use App\Models\Platform;
use Carbon\Carbon;

class SalesByDateNumberHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_date_number';
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
        $query = Order::with([
            'platform',
            'orderItems',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
        ]);

        $query->whereBetween('tanggal', [$startDate, $endDate]);

        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }

        $allOrders = $query->get();

        // Keep only orders that have any financial transaction with saldo_masuk > 0
        $orders = $allOrders->filter(function ($order) {
            return (
                $order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0
            );
        });

        // Group orders by date number (1-31) and compute totals from transactions
        $grouped = $orders->groupBy(function ($order) {
            return Carbon::parse($order->tanggal)->format('d');
        })->map(function ($dateOrders, $dateNumber) {
            $totalValue = 0;
            $totalVolume = 0;
            foreach ($dateOrders as $order) {
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                    $totalValue += $t->saldo_masuk;
                    $totalVolume += $t->qty > 0 ? $t->qty : 0;
                }
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                    $totalValue += $t->saldo_masuk;
                    $totalVolume += $t->qty > 0 ? $t->qty : 0;
                }
            }
            return [
                'date_number' => $dateNumber,
                'order_count' => $dateOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        });

        // Create complete 01-31 array
        $dateNumberSummary = [];
        for ($i = 1; $i <= 31; $i++) {
            $key = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            if (isset($grouped[$key])) {
                $dateNumberSummary[$i] = $grouped[$key];
            } else {
                $dateNumberSummary[$i] = [
                    'date_number' => $key,
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_volume' => 0,
                ];
            }
        }

        // Calculate overall summary
        $totalValue = 0;
        $totalVolume = 0;
        foreach ($orders as $order) {
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += $t->qty > 0 ? $t->qty : 0;
            }
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += $t->qty > 0 ? $t->qty : 0;
            }
        }

        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
        ];

        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $platformName = $selectedPlatform ? $platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown' : null;

        $filename = 'analisis-saldo-masuk-per-tanggal-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesByDateNumberExport($dateNumberSummary, $summary, $startDate, $endDate, $platformName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
