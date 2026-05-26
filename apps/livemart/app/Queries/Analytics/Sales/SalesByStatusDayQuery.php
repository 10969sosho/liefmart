<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Illuminate\Support\Facades\DB;

/**
 * SalesByStatusDayQuery
 * 
 * Query untuk analytics Sales by Status Day
 * Handles comma-separated status_hari values
 */
class SalesByStatusDayQuery
{
    /**
     * Build query untuk get all unique statuses
     */
    public static function buildAllStatuses(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        
        $dateFilter = '';
        if ($startDate && $endDate) {
            $startDateQuoted = DB::getPdo()->quote($startDate);
            $endDateQuoted = DB::getPdo()->quote($endDate);
            $dateFilter = " WHERE o.tanggal BETWEEN {$startDateQuoted} AND {$endDateQuoted}";
        }
        
        return "
            SELECT DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(o.status_hari, ',', numbers.n), ',', -1)) as status
            FROM (
                SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
            ) numbers
            INNER JOIN orders o 
                ON CHAR_LENGTH(o.status_hari) - CHAR_LENGTH(REPLACE(o.status_hari, ',', '')) >= numbers.n - 1
            {$dateFilter}
                AND o.status_hari IS NOT NULL
                AND o.status_hari != ''
            UNION
            SELECT DISTINCT o.status_hari as status
            FROM orders o
            {$dateFilter}
                AND o.status_hari IS NOT NULL
                AND o.status_hari != ''
                AND o.status_hari NOT LIKE '%,%'
            ORDER BY status";
    }
    
    /**
     * Build query untuk status day matrix (status x day_of_week)
     */
    public static function buildStatusDayMatrix(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $statusHari = $filters['status_hari'] ?? null;
        
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
        
        $statusFilter = '';
        if ($statusHari) {
            $statusQuoted = DB::getPdo()->quote($statusHari);
            $statusFilter = " AND (
                o.status_hari = {$statusQuoted}
                OR o.status_hari LIKE " . DB::getPdo()->quote($statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari) . "
            )";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE},
            expanded_orders AS (
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(o.status_hari, ',', numbers.n), ',', -1)) as status,
                    DAYOFWEEK(o.tanggal) - 1 as day_of_week,
                    o.platform_id
                FROM (
                    SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                ) numbers
                INNER JOIN orders o 
                    ON CHAR_LENGTH(o.status_hari) - CHAR_LENGTH(REPLACE(o.status_hari, ',', '')) >= numbers.n - 1
                INNER JOIN order_totals ot ON ot.order_id = o.id
                WHERE o.status_hari IS NOT NULL
                    AND o.status_hari != ''
                    {$dateFilter}{$platformFilter}{$statusFilter}
                UNION
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.status_hari as status,
                    DAYOFWEEK(o.tanggal) - 1 as day_of_week,
                    o.platform_id
                FROM orders o
                INNER JOIN order_totals ot ON ot.order_id = o.id
                WHERE o.status_hari IS NOT NULL
                    AND o.status_hari != ''
                    AND o.status_hari NOT LIKE '%,%'
                    {$dateFilter}{$platformFilter}{$statusFilter}
            )
            SELECT 
                eo.status,
                eo.day_of_week,
                COUNT(DISTINCT eo.order_id) as order_count,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume
            FROM expanded_orders eo
            INNER JOIN order_totals ot ON ot.order_id = eo.order_id
            GROUP BY eo.status, eo.day_of_week
            ORDER BY eo.status, eo.day_of_week";
    }
    
    /**
     * Build query untuk status summary (group by status only)
     */
    public static function buildStatusSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $statusHari = $filters['status_hari'] ?? null;
        
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
        
        $statusFilter = '';
        if ($statusHari) {
            $statusQuoted = DB::getPdo()->quote($statusHari);
            $statusFilter = " AND (
                o.status_hari = {$statusQuoted}
                OR o.status_hari LIKE " . DB::getPdo()->quote($statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari) . "
            )";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE},
            expanded_orders AS (
                SELECT 
                    o.id as order_id,
                    TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(o.status_hari, ',', numbers.n), ',', -1)) as status
                FROM (
                    SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                ) numbers
                INNER JOIN orders o 
                    ON CHAR_LENGTH(o.status_hari) - CHAR_LENGTH(REPLACE(o.status_hari, ',', '')) >= numbers.n - 1
                INNER JOIN order_totals ot ON ot.order_id = o.id
                WHERE o.status_hari IS NOT NULL
                    AND o.status_hari != ''
                    {$dateFilter}{$platformFilter}{$statusFilter}
                UNION
                SELECT 
                    o.id as order_id,
                    o.status_hari as status
                FROM orders o
                INNER JOIN order_totals ot ON ot.order_id = o.id
                WHERE o.status_hari IS NOT NULL
                    AND o.status_hari != ''
                    AND o.status_hari NOT LIKE '%,%'
                    {$dateFilter}{$platformFilter}{$statusFilter}
            )
            SELECT 
                eo.status,
                COUNT(DISTINCT eo.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume
            FROM expanded_orders eo
            INNER JOIN order_totals ot ON ot.order_id = eo.order_id
            GROUP BY eo.status
            ORDER BY eo.status";
    }
    
    /**
     * Build query untuk day of week summary
     */
    public static function buildDayOfWeekSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $statusHari = $filters['status_hari'] ?? null;
        
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
        
        $statusFilter = '';
        if ($statusHari) {
            $statusQuoted = DB::getPdo()->quote($statusHari);
            $statusFilter = " AND (
                o.status_hari = {$statusQuoted}
                OR o.status_hari LIKE " . DB::getPdo()->quote($statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari) . "
            )";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                DAYOFWEEK(o.tanggal) - 1 as day_of_week,
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume
            FROM orders o
            INNER JOIN order_totals ot ON ot.order_id = o.id
            WHERE 1=1{$dateFilter}{$platformFilter}{$statusFilter}
            GROUP BY DAYOFWEEK(o.tanggal) - 1
            ORDER BY day_of_week";
    }
    
    /**
     * Build query untuk overall summary
     */
    public static function buildOverallSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $statusHari = $filters['status_hari'] ?? null;
        
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
        
        $statusFilter = '';
        if ($statusHari) {
            $statusQuoted = DB::getPdo()->quote($statusHari);
            $statusFilter = " AND (
                o.status_hari = {$statusQuoted}
                OR o.status_hari LIKE " . DB::getPdo()->quote($statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari) . "
            )";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                COUNT(DISTINCT o.id) as total_all_orders,
                COUNT(DISTINCT ot.order_id) as total_filtered_orders,
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
            FROM orders o
            LEFT JOIN order_totals ot ON ot.order_id = o.id
            WHERE 1=1{$dateFilter}{$platformFilter}{$statusFilter}";
    }
    
    /**
     * Build query untuk platform summary
     */
    public static function buildPlatformSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $statusHari = $filters['status_hari'] ?? null;
        
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
        
        $statusFilter = '';
        if ($statusHari) {
            $statusQuoted = DB::getPdo()->quote($statusHari);
            $statusFilter = " AND (
                o.status_hari = {$statusQuoted}
                OR o.status_hari LIKE " . DB::getPdo()->quote($statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari . ',%') . "
                OR o.status_hari LIKE " . DB::getPdo()->quote('%,' . $statusHari) . "
            )";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                o.platform_id,
                p.name as platform,
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume
            FROM orders o
            INNER JOIN order_totals ot ON ot.order_id = o.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            WHERE 1=1{$dateFilter}{$platformFilter}{$statusFilter}
            GROUP BY o.platform_id, p.name
            ORDER BY total_value DESC";
    }
}

