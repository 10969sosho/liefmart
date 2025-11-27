<?php

namespace App\Services\Analytics;

use App\Queries\Analytics\GrossProfit\SalesByPlatformProductQuery;
use App\Queries\Analytics\GrossProfit\GrossProfitReportQuery;
use Illuminate\Support\Facades\DB;

/**
 * GrossProfitAnalyticsService
 * 
 * Service layer untuk Gross Profit Analytics
 * Hanya orchestrator, tidak ada perhitungan PHP
 */
class GrossProfitAnalyticsService
{
    /**
     * Get sales by platform product (gross profit)
     */
    public function getSalesByPlatformProduct(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
            'order_number' => $filters['order_number'] ?? null,
            'search' => $filters['search'] ?? null,
            'sort' => $filters['sort'] ?? 'revenue_highest',
        ];
        
        $query = SalesByPlatformProductQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = SalesByPlatformProductQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = SalesByPlatformProductQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $products = [];
        foreach ($results as $row) {
            $products[] = (array)$row;
        }
        
        $summary = [
            'total_platform_products' => (int)($summaryResult->total_platform_products ?? 0),
            'total_platform_products_after_returns' => (int)($summaryResult->total_platform_products_after_returns ?? 0),
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
     * Get gross profit report (online)
     */
    public function getGrossProfitReport(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $startDate = $filters['start_date'] ?? now()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');
        
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
            'sort' => $filters['sort'] ?? 'date_newest',
        ];
        
        $query = GrossProfitReportQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = GrossProfitReportQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = GrossProfitReportQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $platformSummaryQuery = GrossProfitReportQuery::buildPlatformSummary($filters);
        $platformSummaryResults = DB::select($platformSummaryQuery);
        
        $orders = [];
        foreach ($results as $row) {
            $orders[] = (array)$row;
        }
        
        $platformSummary = [];
        foreach ($platformSummaryResults as $row) {
            $platformSummary[] = (array)$row;
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'total_value_without_ppn' => (float)($summaryResult->total_value_without_ppn ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'avg_profit_margin' => (float)($summaryResult->avg_profit_margin ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
        ];
        
        return [
            'orders' => $orders,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
            'platform_summary' => $platformSummary,
        ];
    }
}

