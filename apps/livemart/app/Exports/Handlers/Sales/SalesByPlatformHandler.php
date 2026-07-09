<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\SalesByPlatformExport;
use App\Models\Order;
use App\Models\Platform;
use App\Models\ReturPenjualan;
use Carbon\Carbon;

class SalesByPlatformHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'sales_by_platform';
    }

    public function handle(array $filters): array
    {
        $platforms = Platform::all();

        $startDate = $filters['start_date'] ?? Carbon::today()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? Carbon::today()->format('Y-m-d');
        $platformId = $filters['platform_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'date_newest';

        // Build the query for orders
        $query = Order::withoutGlobalScope('mainCategory')->with([
            'platform',
            'orderItems',
            'orderItems.platformProduct.mappingBarang' => function ($query) {
                $query->where('is_active', true);
            },
            'orderItems.platformProduct.mappingBarang.product',
        ])->whereNotNull('platform_id')
          ->whereHas('orderItems', function ($q) {
              $q->where('quantity', '>', 0);
          });

        // Apply date filter
        try {
            $startDateCarbon = Carbon::parse($startDate)->startOfDay();
            $endDateCarbon = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon]);
        } catch (\Exception $e) {
            $query->whereDate('tanggal', Carbon::today());
        }

        // Apply platform filter if set
        if ($platformId) {
            $query->where('platform_id', $platformId);
        }

        // Get the orders
        $orders = $query->get();

        // Sort orders based on user selection
        switch ($sortBy) {
            case 'value_highest':
                $orders = $orders->sortByDesc(function ($order) {
                    return $order->orderItems->where('quantity', '>', 0)->sum(function ($item) {
                        return $item->price_after_discount * $item->quantity;
                    });
                });
                break;
            case 'value_lowest':
                $orders = $orders->sortBy(function ($order) {
                    return $order->orderItems->where('quantity', '>', 0)->sum(function ($item) {
                        return $item->price_after_discount * $item->quantity;
                    });
                });
                break;
            case 'volume_highest':
                $orders = $orders->sortByDesc(function ($order) {
                    return $order->orderItems->where('quantity', '>', 0)->sum('quantity');
                });
                break;
            case 'volume_lowest':
                $orders = $orders->sortBy(function ($order) {
                    return $order->orderItems->where('quantity', '>', 0)->sum('quantity');
                });
                break;
            case 'date_newest':
                $orders = $orders->sortByDesc('tanggal');
                break;
            case 'date_oldest':
                $orders = $orders->sortBy('tanggal');
                break;
            default:
                $orders = $orders->sortByDesc('tanggal');
                break;
        }

        // Calculate total value and volume for each order
        $orders = $orders->map(function ($order) {
            $order->total_value = $order->orderItems->where('quantity', '>', 0)->sum(function ($item) {
                return $item->price_after_discount * $item->quantity;
            });
            $order->total_volume = $order->orderItems->where('quantity', '>', 0)->sum('quantity');
            return $order;
        });

        $orderIds = $orders->pluck('id')->toArray();
        $totalReturns = empty($orderIds)
            ? 0
            : ReturPenjualan::whereIn('order_id', $orderIds)->count();

        $validOrders = $orders;

        $summary = [
            'total_orders' => $validOrders->count(),
            'total_value' => $validOrders->sum('total_value'),
            'total_volume' => $validOrders->sum('total_volume'),
            'avg_order_value' => $validOrders->count() > 0 ?
                $validOrders->sum('total_value') / $validOrders->count() : 0,
            'avg_order_volume' => $validOrders->count() > 0 ?
                $validOrders->sum('total_volume') / $validOrders->count() : 0,
            'total_returns' => $totalReturns,
        ];

        $filename = 'daftar-pesanan-platform-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new SalesByPlatformExport($validOrders->values(), $summary, $startDate, $endDate, $platformId),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
