<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * SingleItemQuery
 * 
 * Query untuk analytics Single Item Report
 * Menampilkan order yang hanya memiliki 1 item
 */
class SingleItemQuery
{
    /**
     * Build query untuk single item orders
     */
    public static function build(array $filters = [], int $perPage = 25, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'date_newest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        $orderBy = self::buildOrderBy($sortBy);
        $offset = ($page - 1) * $perPage;
        
        return "
            {$baseCTE},
            single_item_orders AS (
                SELECT 
                    ot.order_id,
                    COUNT(DISTINCT oi.id) as item_count
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY ot.order_id
                HAVING COUNT(DISTINCT oi.id) = 1
            )
            SELECT 
                o.id,
                o.order_number,
                o.tanggal,
                o.platform_id,
                p.name as platform_name,
                ot.order_total_value,
                ot.order_total_nominal,
                ot.order_total_hpp,
                ot.order_total_gross_profit,
                ot.order_total_volume,
                (SELECT platform_product_name FROM platform_products pp 
                 INNER JOIN order_items oi ON oi.platform_product_id = pp.id 
                 WHERE oi.order_id = o.id LIMIT 1) as product_name,
                (SELECT quantity FROM order_items WHERE order_id = o.id LIMIT 1) as quantity,
                (SELECT price_after_discount FROM order_items WHERE order_id = o.id LIMIT 1) as price_after_discount
            FROM single_item_orders sio
            INNER JOIN orders o ON sio.order_id = o.id
            INNER JOIN order_totals ot ON ot.order_id = o.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}";
    }
    
    /**
     * Build query untuk count
     */
    public static function buildCount(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE},
            single_item_orders AS (
                SELECT 
                    ot.order_id
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY ot.order_id
                HAVING COUNT(DISTINCT oi.id) = 1
            )
            SELECT COUNT(*) as total
            FROM single_item_orders";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE},
            single_item_orders AS (
                SELECT 
                    ot.order_id
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY ot.order_id
                HAVING COUNT(DISTINCT oi.id) = 1
            )
            SELECT 
                COUNT(DISTINCT sio.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT sio.order_id) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT sio.order_id)
                    ELSE 0
                END as avg_order_value
            FROM single_item_orders sio
            INNER JOIN order_totals ot ON ot.order_id = sio.order_id";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        $sortColumn = 'o.tanggal';
        $sortDirection = 'DESC';
        
        switch ($sortBy) {
            case 'date_oldest':
                $sortColumn = 'o.tanggal';
                $sortDirection = 'ASC';
                break;
            case 'value_highest':
                $sortColumn = 'ot.order_total_value';
                $sortDirection = 'DESC';
                break;
            case 'value_lowest':
                $sortColumn = 'ot.order_total_value';
                $sortDirection = 'ASC';
                break;
            case 'date_newest':
            default:
                $sortColumn = 'o.tanggal';
                $sortDirection = 'DESC';
                break;
        }
        
        return "ORDER BY {$sortColumn} {$sortDirection}";
    }
}

