<?php

namespace App\Exports\Handlers\Sales;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\InternalProductSalesExport;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InternalProductSalesHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'internal_product_sales';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $platformId = $filters['platform_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'qty_highest';

        try {
            $startDateCarbon = Carbon::parse($startDate)->startOfDay();
            $endDateCarbon = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            $startDateCarbon = Carbon::today()->startOfDay();
            $endDateCarbon = Carbon::today()->endOfDay();
        }

        // Get order IDs in date range with platform filter
        $orderIdsQuery = Order::withoutGlobalScope('mainCategory')
            ->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon])
            ->whereNotNull('platform_id')
            ->whereHas('orderItems.platformProduct.mappingBarang', function ($query) {
                $query->where('is_active', true);
            });

        if ($platformId) {
            $orderIdsQuery->where('platform_id', $platformId);
        }

        $orderIds = $orderIdsQuery->pluck('id')->toArray();

        // Get all retur details for these orders
        $allReturDetails = [];
        if (!empty($orderIds)) {
            $allReturDetails = DB::table('retur_penjualan_details')
                ->join('retur_penjualans', 'retur_penjualan_details.retur_penjualan_id', '=', 'retur_penjualans.id')
                ->whereIn('retur_penjualans.order_id', $orderIds)
                ->whereIn('retur_penjualans.status', ['draft', 'selesai'])
                ->select('retur_penjualan_details.order_item_id', DB::raw('SUM(retur_penjualan_details.qty) as total_qty'))
                ->groupBy('retur_penjualan_details.order_item_id')
                ->pluck('total_qty', 'retur_penjualan_details.order_item_id')
                ->toArray();
        }

        // Group by internal product
        $productData = [];

        if (!empty($orderIds)) {
            foreach (array_chunk($orderIds, 100) as $chunk) {
                $chunkOrders = Order::withoutGlobalScope('mainCategory')
                    ->whereIn('id', $chunk)
                    ->with(['orderItems' => function ($query) {
                        $query->with(['platformProduct.mappingBarang' => function ($q) {
                            $q->where('is_active', true)->with('product');
                        }]);
                    }])
                    ->get();

                foreach ($chunkOrders as $order) {
                    foreach ($order->orderItems as $orderItem) {
                        if (!$orderItem->platformProduct || !$orderItem->platformProduct->mappingBarang || $orderItem->platformProduct->mappingBarang->isEmpty()) {
                            continue;
                        }

                        $itemQtyReturIndividual = isset($allReturDetails[$orderItem->id])
                            ? (float) $allReturDetails[$orderItem->id]
                            : 0.0;

                        foreach ($orderItem->platformProduct->mappingBarang as $mapping) {
                            if (!$mapping->product) {
                                continue;
                            }

                            $productId = $mapping->product->id;
                            $productName = $mapping->product->name;
                            $productSku = $mapping->product->sku ?? '-';
                            $mappingQty = (float) $mapping->quantity;

                            $totalPackageQty = $orderItem->platformProduct->mappingBarang->sum('quantity');

                            $itemQtyRetur = $totalPackageQty > 0 ? ($itemQtyReturIndividual * $mappingQty) / $totalPackageQty : 0;

                            $currentItemQty = (float) ($orderItem->quantity ?? 0);
                            $originalQty = $currentItemQty + ($totalPackageQty > 0 ? $itemQtyReturIndividual / $totalPackageQty : 0);
                            $remainingQty = max(0.0, $originalQty - ($totalPackageQty > 0 ? $itemQtyReturIndividual / $totalPackageQty : 0));

                            $internalQty = $remainingQty * $mappingQty;

                            $itemPrice = (float) ($orderItem->price_after_discount ?? 0);
                            $itemValue = $internalQty * ($itemPrice / max($totalPackageQty, 1));

                            if ($internalQty > 0) {
                                if (!isset($productData[$productId])) {
                                    $productData[$productId] = [
                                        'product_name' => $productName,
                                        'product_sku' => $productSku,
                                        'total_qty' => 0,
                                        'total_value' => 0,
                                        'order_count' => 0,
                                        'order_ids' => [],
                                    ];
                                }

                                $productData[$productId]['total_qty'] += $internalQty;
                                $productData[$productId]['total_value'] += $itemValue;

                                if (!in_array($order->id, $productData[$productId]['order_ids'])) {
                                    $productData[$productId]['order_ids'][] = $order->id;
                                    $productData[$productId]['order_count']++;
                                }
                            }
                        }
                    }
                }

                unset($chunkOrders);
            }
        }

        // Convert to collection for sorting
        $productsCollection = collect($productData)->map(function ($data, $productId) {
            return (object) [
                'product_id' => $productId,
                'product_name' => $data['product_name'],
                'product_sku' => $data['product_sku'],
                'total_qty' => round($data['total_qty'], 0),
                'total_value' => round($data['total_value'], 2),
                'order_count' => $data['order_count'],
            ];
        });

        // Apply sorting
        switch ($sortBy) {
            case 'qty_lowest':
                $productsCollection = $productsCollection->sortBy('total_qty');
                break;
            case 'value_highest':
                $productsCollection = $productsCollection->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $productsCollection = $productsCollection->sortBy('total_value');
                break;
            case 'name_asc':
                $productsCollection = $productsCollection->sortBy('product_name');
                break;
            case 'name_desc':
                $productsCollection = $productsCollection->sortByDesc('product_name');
                break;
            case 'qty_highest':
            default:
                $productsCollection = $productsCollection->sortByDesc('total_qty');
                break;
        }

        $products = $productsCollection->values();

        $summary = [
            'total_products' => $productsCollection->count(),
            'total_orders' => $productsCollection->sum('order_count'),
            'total_value' => $productsCollection->sum('total_value'),
            'total_qty' => $productsCollection->sum('total_qty'),
        ];

        $filename = 'analytics-penjualan-master-internal-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new InternalProductSalesExport($products, $summary, $startDate, $endDate),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
