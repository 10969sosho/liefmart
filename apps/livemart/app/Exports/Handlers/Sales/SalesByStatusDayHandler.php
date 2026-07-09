<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\SalesByStatusDayExport;
use App\Models\Order;
use App\Models\Platform;
use Carbon\Carbon;

class SalesByStatusDayHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_status_day';
    }

    public function handle(array $filters): array
    {
        $platforms = Platform::all();
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $selectedPlatform = $filters['platform_id'] ?? null;
        $selectedStatus = $filters['status'] ?? null;

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
        if ($selectedStatus) {
            $query->where(function ($q) use ($selectedStatus) {
                $q->where('status_hari', $selectedStatus)
                  ->orWhere('status_hari', 'LIKE', $selectedStatus . ',%')
                  ->orWhere('status_hari', 'LIKE', '%,' . $selectedStatus . ',%')
                  ->orWhere('status_hari', 'LIKE', '%,' . $selectedStatus);
            });
        }

        $orders = $query->get();

        // Build list of statuses
        $rawStatuses = Order::distinct()->pluck('status_hari')->filter()->values()->toArray();
        $allStatuses = [];
        foreach ($rawStatuses as $status) {
            if (strpos($status, ',') !== false) {
                foreach (array_map('trim', explode(',', $status)) as $s) {
                    if (!empty($s) && !in_array($s, $allStatuses)) {
                        $allStatuses[] = $s;
                    }
                }
            } else {
                if (!in_array($status, $allStatuses)) {
                    $allStatuses[] = $status;
                }
            }
        }
        sort($allStatuses);

        // Init matrix
        $statusDayMatrix = [];
        foreach ($allStatuses as $status) {
            $statusDayMatrix[$status] = [];
            foreach (range(0, 6) as $dayNum) {
                $statusDayMatrix[$status][$dayNum] = [
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_volume' => 0,
                ];
            }
        }

        // Fill matrix based on financial transactions
        foreach ($orders as $order) {
            $dayOfWeek = Carbon::parse($order->tanggal)->dayOfWeek;
            $totalValue = 0;
            $totalVolume = 0;
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += max(0, $t->qty);
            }
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += max(0, $t->qty);
            }

            if (!empty($order->status_hari)) {
                if (strpos($order->status_hari, ',') !== false) {
                    foreach (array_map('trim', explode(',', $order->status_hari)) as $s) {
                        if (!empty($s) && isset($statusDayMatrix[$s][$dayOfWeek])) {
                            $statusDayMatrix[$s][$dayOfWeek]['order_count']++;
                            $statusDayMatrix[$s][$dayOfWeek]['total_value'] += $totalValue;
                            $statusDayMatrix[$s][$dayOfWeek]['total_volume'] += $totalVolume;
                        }
                    }
                } else {
                    $s = $order->status_hari;
                    if (isset($statusDayMatrix[$s][$dayOfWeek])) {
                        $statusDayMatrix[$s][$dayOfWeek]['order_count']++;
                        $statusDayMatrix[$s][$dayOfWeek]['total_value'] += $totalValue;
                        $statusDayMatrix[$s][$dayOfWeek]['total_volume'] += $totalVolume;
                    }
                }
            }
        }

        // Build summary
        $totalValue = 0;
        $totalVolume = 0;
        foreach ($orders as $order) {
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += max(0, $t->qty);
            }
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += max(0, $t->qty);
            }
        }

        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
        ];

        // Aggregate by status only for export rows
        $rows = [];
        foreach ($statusDayMatrix as $status => $byDay) {
            $orderCount = 0;
            $totalVal = 0;
            $totalVol = 0;
            foreach ($byDay as $data) {
                $orderCount += $data['order_count'] ?? 0;
                $totalVal += $data['total_value'] ?? 0;
                $totalVol += $data['total_volume'] ?? 0;
            }
            $rows[] = [
                'status' => $status,
                'order_count' => $orderCount,
                'total_value' => $totalVal,
                'total_volume' => $totalVol,
                'avg_order_value' => $orderCount > 0 ? $totalVal / $orderCount : 0,
                'avg_order_volume' => $orderCount > 0 ? $totalVol / $orderCount : 0,
            ];
        }

        $platformName = $selectedPlatform ? ($platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown') : null;

        $filename = 'laporan-penjualan-status-hari-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesByStatusDayExport($rows, $summary, $startDate, $endDate, $platformName),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
