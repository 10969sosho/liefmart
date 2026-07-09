<?php

namespace App\Exports\Handlers\Product;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\ProdukPlatformTerlarisExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProdukPlatformTerlarisHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'produk_platform_terlaris';
    }

    public function handle(array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        $selectedPlatform = $filters['platform_id'] ?? null;
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sort'] ?? 'quantity_highest';

        // Parse dates
        try {
            $startDateCarbon = Carbon::parse($startDate)->startOfDay();
            $endDateCarbon = Carbon::parse($endDate)->endOfDay();
        } catch (\Exception $e) {
            $startDateCarbon = Carbon::today()->startOfDay();
            $endDateCarbon = Carbon::today()->endOfDay();
            $startDate = $startDateCarbon->format('Y-m-d');
            $endDate = $endDateCarbon->format('Y-m-d');
        }

        // Build main query - same as exportProdukPlatformTerlaris but get ALL data
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('platform_products', 'order_items.platform_product_id', '=', 'platform_products.id')
            ->join('platforms', 'platform_products.platform_id', '=', 'platforms.id')
            ->whereBetween('orders.tanggal', [$startDateCarbon, $endDateCarbon])
            ->whereNotNull('orders.platform_id')
            ->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('retur_penjualans')
                    ->whereColumn('retur_penjualans.order_id', 'orders.id')
                    ->whereIn('retur_penjualans.status', ['draft', 'selesai']);
            })
            ->select(
                'platform_products.id as platform_product_id',
                'platform_products.platform_product_name',
                'platform_products.variant',
                'platforms.id as platform_id',
                'platforms.name as platform_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT order_items.order_id) as order_count'),
                DB::raw('SUM(order_items.price_after_discount * order_items.quantity) as total_value'),
                DB::raw('(
                    SELECT COALESCE(SUM(rpd.qty), 0)
                    FROM retur_penjualan_details rpd
                    JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                    JOIN order_items oi2 ON rpd.order_item_id = oi2.id
                    JOIN orders o2 ON oi2.order_id = o2.id
                    WHERE oi2.platform_product_id = platform_products.id
                      AND rp.status IN ("draft", "selesai")
                      AND o2.tanggal BETWEEN "' . $startDateCarbon->format('Y-m-d H:i:s') . '" 
                      AND "' . $endDateCarbon->format('Y-m-d H:i:s') . '"
                ) as qty_retur_individual'),
                DB::raw('(
                    SELECT COALESCE(SUM(mb.quantity), 1)
                    FROM mapping_barangs mb
                    WHERE mb.platform_product_id = platform_products.id
                      AND mb.is_active = 1
                ) as package_quantity')
            )
            ->groupBy('platform_products.id', 'platform_products.platform_product_name', 'platform_products.variant', 'platforms.id', 'platforms.name');

        // Apply filters
        if ($selectedPlatform) {
            $query->where('platforms.id', $selectedPlatform);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('platform_products.platform_product_name', 'like', "%$search%")
                  ->orWhere('platform_products.variant', 'like', "%$search%");
            });
        }

        // Apply sorting
        switch ($sortBy) {
            case 'quantity_lowest':
                $query->orderByRaw('(SUM(order_items.quantity) - (qty_retur_individual / package_quantity)) ASC');
                break;
            case 'value_highest':
                $query->orderByRaw('SUM(order_items.price_after_discount * order_items.quantity) DESC');
                break;
            case 'value_lowest':
                $query->orderByRaw('SUM(order_items.price_after_discount * order_items.quantity) ASC');
                break;
            case 'order_count_highest':
                $query->orderByRaw('COUNT(DISTINCT order_items.order_id) DESC');
                break;
            case 'order_count_lowest':
                $query->orderByRaw('COUNT(DISTINCT order_items.order_id) ASC');
                break;
            case 'quantity_highest':
            default:
                $query->orderByRaw('(SUM(order_items.quantity) - (qty_retur_individual / package_quantity)) DESC');
                break;
        }

        // Get ALL products (no pagination)
        $products = $query->get();

        // Transform results
        $transformedProducts = $products->map(function ($product) {
            $qtyReturPackage = $product->package_quantity > 0
                ? $product->qty_retur_individual / $product->package_quantity
                : $product->qty_retur_individual;

            $netQuantity = max(0, $product->total_quantity - $qtyReturPackage);

            return [
                'platform_product_id' => $product->platform_product_id,
                'platform_product_name' => $product->platform_product_name,
                'variant' => $product->variant ?? '-',
                'platform_id' => $product->platform_id,
                'platform_name' => $product->platform_name,
                'total_quantity' => (float) $product->total_quantity,
                'qty_retur' => (float) $qtyReturPackage,
                'net_quantity' => (float) $netQuantity,
                'order_count' => (int) $product->order_count,
                'total_value' => (float) $product->total_value,
            ];
        });

        // Calculate summary
        $summary = [
            'total_products' => $transformedProducts->count(),
            'total_quantity' => $transformedProducts->sum('net_quantity'),
            'total_returns' => $transformedProducts->sum('qty_retur'),
            'total_orders' => $transformedProducts->sum('order_count'),
            'total_value' => $transformedProducts->sum('total_value'),
        ];

        $filename = 'produk-platform-terlaris-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new ProdukPlatformTerlarisExport($transformedProducts, $summary, $startDate, $endDate),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
