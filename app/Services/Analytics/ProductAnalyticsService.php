<?php

namespace App\Services\Analytics;

use App\Queries\Analytics\Product\BestSellingPlatformQuery;
use App\Queries\Analytics\Product\BestSellingInternalQuery;
use App\Queries\Analytics\Product\MasterProductQuery;
use App\Queries\Analytics\Product\MasterProductSpecialQuery;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ProductAnalyticsService
 * 
 * Service layer untuk Product Analytics
 * Hanya orchestrator, tidak ada perhitungan PHP
 */
class ProductAnalyticsService
{
    /**
     * Get best selling platform products
     */
    public function getBestSellingPlatform(array $filters = [], int $perPage = 100, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = BestSellingPlatformQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = BestSellingPlatformQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = BestSellingPlatformQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $products = [];
        foreach ($results as $row) {
            $products[] = [
                'platform_product_id' => (int)$row->platform_product_id,
                'platform_product_name' => $row->platform_product_name,
                'variant' => $row->variant ?? '-',
                'platform_id' => (int)$row->platform_id,
                'platform_name' => $row->platform_name,
                'total_quantity' => (float)$row->total_quantity,
                'order_count' => (int)$row->order_count,
                'total_value' => (float)$row->total_value,
            ];
        }
        
        $summary = [
            'total_products' => (int)($summaryResult->total_products ?? 0),
            'total_quantity' => (float)($summaryResult->total_quantity ?? 0),
            'total_quantity_with_returns' => (float)($summaryResult->total_quantity_with_returns ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_returns_count' => (int)($summaryResult->total_returns_count ?? 0),
        ];
        
        return [
            'products' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get best selling internal products
     */
    public function getBestSellingInternal(array $filters = [], int $perPage = 100, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = BestSellingInternalQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = BestSellingInternalQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = BestSellingInternalQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        // Get platform names for display
        $platformIds = [];
        foreach ($results as $row) {
            if ($row->platform_ids) {
                $ids = explode(',', $row->platform_ids);
                $platformIds = array_merge($platformIds, $ids);
            }
        }
        $platformIds = array_unique($platformIds);
        
        $platforms = \App\Models\Platform::whereIn('id', $platformIds)->pluck('name', 'id');
        
        $products = [];
        foreach ($results as $row) {
            $platformNames = [];
            if ($row->platform_ids) {
                $ids = explode(',', $row->platform_ids);
                foreach ($ids as $id) {
                    if (isset($platforms[$id])) {
                        $platformNames[] = $platforms[$id];
                    }
                }
            }
            
            $products[] = [
                'product_id' => (int)$row->product_id,
                'product_name' => $row->product_name,
                'product_sku' => $row->product_sku,
                'order_count' => (int)$row->order_count,
                'total_quantity' => (float)$row->total_quantity,
                'platform_names' => $platformNames,
            ];
        }
        
        $summary = [
            'total_products' => (int)($summaryResult->total_products ?? 0),
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_qty' => (float)($summaryResult->total_qty ?? 0),
        ];
        
        return [
            'products' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Prepare and validate filters
     */
    private function prepareFilters(array $filters): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        
        if (!$startDate) {
            $startDate = Carbon::today()->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = Carbon::today()->format('Y-m-d');
        }
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
            'search' => $filters['search'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'order_number' => $filters['order_number'] ?? null,
            'brands' => $filters['brands'] ?? [],
            'sub_brands' => $filters['sub_brands'] ?? [],
            'product_categories' => $filters['product_categories'] ?? [],
            'product_types' => $filters['product_types'] ?? [],
            'product_sizes' => $filters['product_sizes'] ?? [],
            'product_variants' => $filters['product_variants'] ?? [],
        ];
    }
    
    /**
     * Get master product sales
     */
    public function getMasterProductSales(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = MasterProductQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = MasterProductQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = MasterProductQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $products = [];
        foreach ($results as $row) {
            $products[] = (array)$row;
        }
        
        $summary = [
            'total_products' => (int)($summaryResult->total_products ?? 0),
            'total_rows' => (int)($summaryResult->total_rows ?? 0),
            'total_revenue' => (float)($summaryResult->total_revenue ?? 0),
            'total_revenue_without_ppn' => (float)($summaryResult->total_revenue_without_ppn ?? 0),
            'total_capital' => (float)($summaryResult->total_capital ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_quantity' => (float)($summaryResult->total_quantity ?? 0),
            'profit_margin' => (float)($summaryResult->profit_margin ?? 0),
        ];
        
        return [
            'products' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get master product special sales
     */
    public function getMasterProductSpecialSales(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = MasterProductSpecialQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = MasterProductSpecialQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = MasterProductSpecialQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $products = [];
        foreach ($results as $row) {
            $products[] = (array)$row;
        }
        
        $summary = [
            'total_products' => (int)($summaryResult->total_products ?? 0),
            'total_rows' => (int)($summaryResult->total_rows ?? 0),
            'total_revenue' => (float)($summaryResult->total_revenue ?? 0),
            'total_revenue_without_ppn' => (float)($summaryResult->total_revenue_without_ppn ?? 0),
            'total_capital' => (float)($summaryResult->total_capital ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_quantity' => (float)($summaryResult->total_quantity ?? 0),
            'profit_margin' => (float)($summaryResult->profit_margin ?? 0),
        ];
        
        return [
            'products' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
}

