<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Illuminate\Support\Facades\DB;

/**
 * SalesByDayOfWeekQuery
 * 
 * Query untuk analytics Sales by Day of Week
 * Semua perhitungan dilakukan di SQL, zero PHP calculation
 */
class SalesByDayOfWeekQuery
{
    /**
     * Build query untuk sales by day of week
     * 
     * @param array $filters ['start_date', 'end_date', 'platform_id']
     * @return string Complete SQL query
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
        
        // Build base CTE
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        // Main query: Group by day of week
        // Note: MySQL DAYOFWEEK returns 1=Sunday, 2=Monday, ..., 7=Saturday
        // We convert to 0-6 format (0=Sunday, 1=Monday, ..., 6=Saturday)
        // Business Logic: Saturday gets 1/6 of Monday's orders (ALL IN SQL, zero PHP calculation)
        $query = "
            {$baseCTE},
            aggregated_sales AS (
                SELECT 
                    DAYOFWEEK(o.tanggal) - 1 as day_number,
                    COUNT(DISTINCT ot.order_id) as order_count,
                    COALESCE(SUM(ot.order_total_value), 0) as total_value,
                    COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                    COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                    COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                    COALESCE(SUM(ot.order_total_volume), 0) as total_volume
                FROM order_totals ot
                INNER JOIN all_transactions at ON ot.order_id = at.order_id
                INNER JOIN orders o ON ot.order_id = o.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY DAYOFWEEK(o.tanggal) - 1
            ),
            monday_data AS (
                SELECT * FROM aggregated_sales WHERE day_number = 1
            ),
            adjusted_data AS (
                SELECT 
                    day_number,
                    CASE 
                        WHEN day_number = 1 THEN order_count * 5 / 6  -- Monday: subtract 1/6
                        WHEN day_number = 6 THEN order_count + COALESCE((SELECT order_count FROM monday_data), 0) / 6  -- Saturday: add 1/6 from Monday
                        ELSE order_count
                    END as order_count,
                    CASE 
                        WHEN day_number = 1 THEN total_value * 5 / 6
                        WHEN day_number = 6 THEN total_value + COALESCE((SELECT total_value FROM monday_data), 0) / 6
                        ELSE total_value
                    END as total_value,
                    CASE 
                        WHEN day_number = 1 THEN total_nominal * 5 / 6
                        WHEN day_number = 6 THEN total_nominal + COALESCE((SELECT total_nominal FROM monday_data), 0) / 6
                        ELSE total_nominal
                    END as total_nominal,
                    CASE 
                        WHEN day_number = 1 THEN total_hpp * 5 / 6
                        WHEN day_number = 6 THEN total_hpp + COALESCE((SELECT total_hpp FROM monday_data), 0) / 6
                        ELSE total_hpp
                    END as total_hpp,
                    CASE 
                        WHEN day_number = 1 THEN total_volume * 5 / 6
                        WHEN day_number = 6 THEN total_volume + COALESCE((SELECT total_volume FROM monday_data), 0) / 6
                        ELSE total_volume
                    END as total_volume
                FROM aggregated_sales
            )
            SELECT 
                day_number as day_of_week,
                CASE 
                    WHEN day_number = 1 AND (SELECT order_count FROM monday_data) > 0 THEN CAST(order_count AS UNSIGNED)
                    WHEN day_number = 6 AND (SELECT order_count FROM monday_data) > 0 THEN CAST(order_count AS UNSIGNED)
                    ELSE CAST(order_count AS UNSIGNED)
                END as total_orders,
                total_value,
                total_nominal,
                total_hpp,
                (total_value - total_hpp) as total_gross_profit,
                total_volume,
                CASE 
                    WHEN order_count > 0 THEN total_value / order_count
                    ELSE 0
                END as avg_order_value,
                CASE 
                    WHEN order_count > 0 THEN total_volume / order_count
                    ELSE 0
                END as avg_order_volume
            FROM adjusted_data
            ORDER BY day_number";
        
        return $query;
    }
    
    /**
     * Build query untuk total summary
     * 
     * @param array $filters
     * @return string SQL query
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
                END as avg_order_value,
                CASE 
                    WHEN COUNT(DISTINCT ot.order_id) > 0 
                    THEN COALESCE(SUM(ot.order_total_volume), 0) / COUNT(DISTINCT ot.order_id)
                    ELSE 0
                END as avg_order_volume
            FROM order_totals ot
            INNER JOIN all_transactions at ON ot.order_id = at.order_id
            INNER JOIN orders o ON ot.order_id = o.id
            WHERE 1=1{$dateFilter}{$platformFilter}";
    }
    
    /**
     * Build query untuk platform summary
     * 
     * @param array $filters
     * @return string SQL query
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

