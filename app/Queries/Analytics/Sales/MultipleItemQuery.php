<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * MultipleItemQuery
 * 
 * Query untuk analytics Multiple Item Report
 * Menampilkan order yang memiliki lebih dari 1 item
 */
class MultipleItemQuery
{
    /**
     * Build query untuk multiple item orders
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
            multiple_item_orders AS (
                SELECT 
                    ot.order_id,
                    COUNT(DISTINCT oi.id) as item_count
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY ot.order_id
                HAVING COUNT(DISTINCT oi.id) > 1
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
                mio.item_count,
                (SELECT GROUP_CONCAT(pp.platform_product_name SEPARATOR ', ') 
                 FROM order_items oi 
                 INNER JOIN platform_products pp ON oi.platform_product_id = pp.id 
                 WHERE oi.order_id = o.id) as product_names
            FROM multiple_item_orders mio
            INNER JOIN orders o ON mio.order_id = o.id
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
            multiple_item_orders AS (
                SELECT 
                    ot.order_id
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY ot.order_id
                HAVING COUNT(DISTINCT oi.id) > 1
            )
            SELECT COUNT(*) as total
            FROM multiple_item_orders";
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
            multiple_item_orders AS (
                SELECT 
                    ot.order_id,
                    COUNT(DISTINCT oi.id) as item_count
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY ot.order_id
                HAVING COUNT(DISTINCT oi.id) > 1
            )
            SELECT 
                COUNT(DISTINCT mio.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                COALESCE(AVG(mio.item_count), 0) as avg_items_per_order,
                CASE 
                    WHEN COUNT(DISTINCT mio.order_id) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT mio.order_id)
                    ELSE 0
                END as avg_order_value
            FROM multiple_item_orders mio
            INNER JOIN order_totals ot ON ot.order_id = mio.order_id";
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
            case 'items_highest':
                $sortColumn = 'mio.item_count';
                $sortDirection = 'DESC';
                break;
            case 'items_lowest':
                $sortColumn = 'mio.item_count';
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

