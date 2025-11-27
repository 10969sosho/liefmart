<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Illuminate\Support\Facades\DB;

/**
 * SalesByDateNumberQuery
 * 
 * Query untuk analytics Sales by Date Number (1-31)
 */
class SalesByDateNumberQuery
{
    /**
     * Build query untuk sales by date number
     */
    public static function build(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = '';
        if ($startDate && $endDate) {
            $startDateQuoted = DB::getPdo()->quote($startDate);
            $endDateQuoted = DB::getPdo()->quote($endDate);
            $dateFilter = " AND o.tanggal BETWEEN {$startDateQuoted} AND {$endDateQuoted}";
        }
        
        $platformFilter = '';
        if ($platformId) {
            $platformFilter = " AND o.platform_id = " . intval($platformId);
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                DAY(o.tanggal) as date_number,
                COUNT(DISTINCT ot.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT ot.order_id) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT ot.order_id)
                    ELSE 0
                END as avg_order_value
            FROM order_totals ot
            INNER JOIN all_transactions at ON ot.order_id = at.order_id
            INNER JOIN orders o ON ot.order_id = o.id
            WHERE 1=1{$dateFilter}{$platformFilter}
            GROUP BY DAY(o.tanggal)
            ORDER BY date_number";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = '';
        if ($startDate && $endDate) {
            $startDateQuoted = DB::getPdo()->quote($startDate);
            $endDateQuoted = DB::getPdo()->quote($endDate);
            $dateFilter = " AND o.tanggal BETWEEN {$startDateQuoted} AND {$endDateQuoted}";
        }
        
        $platformFilter = '';
        if ($platformId) {
            $platformFilter = " AND o.platform_id = " . intval($platformId);
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                COUNT(DISTINCT ot.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT ot.order_id) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT ot.order_id)
                    ELSE 0
                END as avg_order_value
            FROM order_totals ot
            INNER JOIN all_transactions at ON ot.order_id = at.order_id
            INNER JOIN orders o ON ot.order_id = o.id
            WHERE 1=1{$dateFilter}{$platformFilter}";
    }
    
    /**
     * Build query untuk platform summary
     */
    public static function buildPlatformSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = '';
        if ($startDate && $endDate) {
            $startDateQuoted = DB::getPdo()->quote($startDate);
            $endDateQuoted = DB::getPdo()->quote($endDate);
            $dateFilter = " AND o.tanggal BETWEEN {$startDateQuoted} AND {$endDateQuoted}";
        }
        
        $platformFilter = '';
        if ($platformId) {
            $platformFilter = " AND o.platform_id = " . intval($platformId);
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                o.platform_id,
                p.name as platform_name,
                COUNT(DISTINCT ot.order_id) as order_count,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume
            FROM order_totals ot
            INNER JOIN all_transactions at ON ot.order_id = at.order_id
            INNER JOIN orders o ON ot.order_id = o.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            WHERE 1=1{$dateFilter}{$platformFilter}
            GROUP BY o.platform_id, p.name
            ORDER BY total_value DESC";
    }
}

