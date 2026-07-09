<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\SalesDetailReportExport;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class SalesDetailReportHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_detail_report';
    }

    public function handle(array $filters): array
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        try {
            $startDateCarbon = Carbon::parse($startDate)->startOfDay();
            $endDateCarbon = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            $startDateCarbon = Carbon::today()->startOfDay();
            $endDateCarbon = Carbon::today()->endOfDay();
        }

        $platformId = $filters['platform_id'] ?? null;

        // Build the query for order items with eager loading
        $query = OrderItem::with([
            'order.platform',
            'order.orderItems.platformProduct',
            'platformProduct.mappingBarang',
        ])->whereHas('order', function ($q) use ($startDateCarbon, $endDateCarbon, $platformId) {
            $q->withoutGlobalScope('mainCategory')
              ->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon]);

            if ($platformId) {
                $q->where('platform_id', $platformId);
            }
        });

        // Apply sorting
        $sortBy = $filters['sort'] ?? 'date_newest';
        switch ($sortBy) {
            case 'date_oldest':
                $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                      ->orderBy('orders.tanggal', 'asc')
                      ->orderBy('orders.id', 'asc')
                      ->orderBy('order_items.id', 'asc')
                      ->select('order_items.*');
                break;
            case 'value_highest':
                $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                      ->orderBy('orders.total', 'desc')
                      ->orderBy('orders.id', 'asc')
                      ->orderBy('order_items.id', 'asc')
                      ->select('order_items.*');
                break;
            case 'value_lowest':
                $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                      ->orderBy('orders.total', 'asc')
                      ->orderBy('orders.id', 'asc')
                      ->orderBy('order_items.id', 'asc')
                      ->select('order_items.*');
                break;
            case 'date_newest':
            default:
                $query->join('orders', 'order_items.order_id', '=', 'orders.id')
                      ->orderBy('orders.tanggal', 'desc')
                      ->orderBy('orders.id', 'desc')
                      ->orderBy('order_items.id', 'desc')
                      ->select('order_items.*');
                break;
        }

        // Calculate summary
        $summaryQuery = Order::withoutGlobalScope('mainCategory')
            ->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon]);

        if ($platformId) {
            $summaryQuery->where('platform_id', $platformId);
        }

        $summary = [
            'total_orders' => $summaryQuery->count(),
            'total_value' => 0,
            'total_volume' => 0,
        ];

        $totals = OrderItem::whereHas('order', function ($q) use ($startDateCarbon, $endDateCarbon, $platformId) {
            $q->withoutGlobalScope('mainCategory')
              ->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon]);

            if ($platformId) {
                $q->where('platform_id', $platformId);
            }
        })->selectRaw("
            SUM(price_after_discount * quantity) as total_value,
            SUM(quantity) as total_volume
        ")->first();

        $summary['total_value'] = $totals->total_value ?? 0;
        $summary['total_volume'] = $totals->total_volume ?? 0;
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $filename = 'laporan-detail-penjualan-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesDetailReportExport($query, $summary, $startDate, $endDate, $platformId),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
