<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * SalesValueQuery
 * 
 * Query untuk analytics Sales Value Report
 * Similar to SalesByPlatform but focused on value analysis
 */
class SalesValueQuery
{
    /**
     * Build query untuk sales value report
     */
    public static function build(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $baseCTE = BaseTransactionQuery::baseCTEWithReturFilter($filters);
        
        return "
            {$baseCTE}
            SELECT 
                o.id,
                o.order_number,
                o.tanggal,
                o.platform_id,
                p.name as platform_name,
                ot.order_total_value as total_value,
                ot.order_total_nominal as total_nominal,
                ot.order_total_hpp as total_hpp,
                ot.order_total_gross_profit as total_gross_profit,
                ot.order_total_volume as total_volume
            FROM orders o
            INNER JOIN filtered_order_totals ot ON ot.order_id = o.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}
            ORDER BY ot.order_total_value DESC, o.tanggal DESC";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $baseCTE = BaseTransactionQuery::baseCTEWithReturFilter($filters);
        
        return "
            {$baseCTE}
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT o.id) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT o.id)
                    ELSE 0
                END as avg_order_value
            FROM orders o
            INNER JOIN filtered_order_totals ot ON ot.order_id = o.id
            WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}";
    }
}

