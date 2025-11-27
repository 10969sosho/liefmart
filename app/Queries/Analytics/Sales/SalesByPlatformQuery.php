<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * SalesByPlatformQuery
 * 
 * Query untuk analytics Sales by Platform
 * Supports retur penjualan exclusion
 */
class SalesByPlatformQuery
{
    /**
     * Build query untuk sales by platform with pagination
     */
    public static function build(array $filters = [], int $perPage = 50, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'date_newest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        // Use base CTE with retur filter
        $baseCTE = BaseTransactionQuery::baseCTEWithReturFilter($filters);
        
        // Build ORDER BY
        $orderBy = self::buildOrderBy($sortBy);
        
        $offset = ($page - 1) * $perPage;
        
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
            {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}";
    }
    
    /**
     * Build query untuk count total orders
     */
    public static function buildCount(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $baseCTE = BaseTransactionQuery::baseCTEWithReturFilter($filters);
        
        return "
            {$baseCTE}
            SELECT COUNT(DISTINCT o.id) as total
            FROM orders o
            INNER JOIN filtered_order_totals ot ON ot.order_id = o.id
            WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}";
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
                COUNT(DISTINCT CASE WHEN ot.order_id IS NOT NULL THEN o.id END) as orders_with_transactions,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                COUNT(DISTINCT rp.order_id) as total_returns,
                COUNT(DISTINCT CASE WHEN rp.order_id IS NOT NULL THEN o.id END) as orders_with_returns
            FROM orders o
            LEFT JOIN filtered_order_totals ot ON ot.order_id = o.id
            LEFT JOIN retur_penjualan rp ON rp.order_id = o.id AND rp.status IN ('draft', 'selesai')
            WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        switch ($sortBy) {
            case 'value_highest':
                return 'ORDER BY ot.order_total_value DESC, o.tanggal DESC';
            case 'value_lowest':
                return 'ORDER BY ot.order_total_value ASC, o.tanggal DESC';
            case 'volume_highest':
                return 'ORDER BY ot.order_total_volume DESC, o.tanggal DESC';
            case 'volume_lowest':
                return 'ORDER BY ot.order_total_volume ASC, o.tanggal DESC';
            case 'date_oldest':
                return 'ORDER BY o.tanggal ASC';
            case 'date_newest':
            default:
                return 'ORDER BY o.tanggal DESC';
        }
    }
}

