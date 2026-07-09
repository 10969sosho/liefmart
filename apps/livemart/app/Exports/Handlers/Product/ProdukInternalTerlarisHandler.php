<?php

namespace App\Exports\Handlers\Product;

use App\Exports\Handlers\ExportHandlerInterface;
use App\Exports\ProdukInternalTerlarisExport;
use App\Models\Platform;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProdukInternalTerlarisHandler implements ExportHandlerInterface
{
    public function getType(): string
    {
        return 'produk_internal_terlaris';
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

        // Main query - same as exportProdukInternalTerlaris but get ALL data
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('mapping_barangs', 'order_items.platform_product_id', '=', 'mapping_barangs.platform_product_id')
            ->join('products', 'mapping_barangs.product_id', '=', 'products.id')
            ->whereBetween('orders.tanggal', [$startDateCarbon, $endDateCarbon])
            ->whereNotNull('orders.platform_id')
            ->where('mapping_barangs.is_active', 1)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.sku as product_sku',
                DB::raw('SUM(order_items.quantity * mapping_barangs.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('GROUP_CONCAT(DISTINCT orders.platform_id ORDER BY orders.platform_id SEPARATOR ",") as platform_ids')
            )
            ->groupBy('products.id', 'products.name', 'products.sku');

        // Apply filters
        if ($selectedPlatform) {
            $query->where('orders.platform_id', $selectedPlatform);
        }

        $query->whereNotExists(function ($subquery) {
            $subquery->select(DB::raw(1))
                ->from('retur_penjualans')
                ->whereColumn('retur_penjualans.order_id', 'orders.id')
                ->whereIn('retur_penjualans.status', ['draft', 'selesai']);
        });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%$search%")
                  ->orWhere('products.sku', 'like', "%$search%");
            });
        }

        // Apply sorting
        switch ($sortBy) {
            case 'quantity_lowest':
                $query->orderByRaw('total_quantity ASC');
                break;
            case 'order_count_highest':
                $query->orderByRaw('order_count DESC');
                break;
            case 'order_count_lowest':
                $query->orderByRaw('order_count ASC');
                break;
            case 'quantity_highest':
            default:
                $query->orderByRaw('total_quantity DESC');
                break;
        }

        // Get ALL products (no pagination)
        $products = $query->get();

        // Get platform names
        $platformIds = [];
        foreach ($products as $product) {
            if ($product->platform_ids) {
                $platformIds = array_merge($platformIds, explode(',', $product->platform_ids));
            }
        }
        $platformIds = array_unique($platformIds);
        $platformNames = Platform::whereIn('id', $platformIds)->pluck('name', 'id');

        // Get returns for all products
        $productIds = $products->pluck('product_id')->toArray();
        $returnsData = [];
        if (!empty($productIds)) {
            $returnsData = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('mapping_barangs', 'order_items.platform_product_id', '=', 'mapping_barangs.platform_product_id')
                ->join('retur_penjualan_details', 'order_items.id', '=', 'retur_penjualan_details.order_item_id')
                ->join('retur_penjualans', 'retur_penjualan_details.retur_penjualan_id', '=', 'retur_penjualans.id')
                ->whereIn('mapping_barangs.product_id', $productIds)
                ->where('mapping_barangs.is_active', 1)
                ->whereBetween('orders.tanggal', [$startDateCarbon, $endDateCarbon])
                ->whereIn('retur_penjualans.status', ['draft', 'selesai'])
                ->when($selectedPlatform, function ($q) use ($selectedPlatform) {
                    $q->where('orders.platform_id', $selectedPlatform);
                })
                ->select(
                    'mapping_barangs.product_id',
                    DB::raw('SUM(retur_penjualan_details.qty) as qty_retur')
                )
                ->groupBy('mapping_barangs.product_id')
                ->get()
                ->keyBy('product_id');
        }

        // Transform products
        $transformedProducts = $products->map(function ($product) use ($platformNames, $returnsData) {
            $platformIdArray = $product->platform_ids ? explode(',', $product->platform_ids) : [];
            $platforms = [];
            foreach ($platformIdArray as $pid) {
                if (isset($platformNames[$pid])) {
                    $platforms[] = $platformNames[$pid];
                }
            }

            $qtyRetur = isset($returnsData[$product->product_id])
                ? (float) $returnsData[$product->product_id]->qty_retur
                : 0;

            $netQuantity = max(0, $product->total_quantity - $qtyRetur);

            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'product_sku' => $product->product_sku ?? '-',
                'total_quantity' => (float) $product->total_quantity,
                'qty_retur' => $qtyRetur,
                'net_quantity' => $netQuantity,
                'order_count' => (int) $product->order_count,
                'platforms' => implode(', ', array_unique($platforms)),
            ];
        });

        // Calculate summary
        $summary = [
            'total_products' => $transformedProducts->count(),
            'total_quantity' => $transformedProducts->sum('net_quantity'),
            'total_returns' => $transformedProducts->sum('qty_retur'),
            'total_orders' => $transformedProducts->sum('order_count'),
        ];

        $filename = 'produk-internal-terlaris-' . date('Y-m-d') . '.xlsx';

        return [
            'export' => new ProdukInternalTerlarisExport($transformedProducts, $summary, $startDate, $endDate),
            'filename' => $filename,
            'filters' => $filters,
        ];
    }
}
