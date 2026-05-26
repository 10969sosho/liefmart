<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * DailySalesQuery
 * 
 * Query untuk analytics Daily Sales Report
 * Menampilkan penjualan per hari (per tanggal, bukan per hari dalam seminggu)
 * Berbeda dengan Sales by Date Number (yang menampilkan per tanggal 1-31)
 * Berbeda dengan Sales by Day of Week (yang menampilkan per hari Minggu-Sabtu)
 */
class DailySalesQuery
{
    /**
     * Build query untuk daily sales report
     */
    public static function build(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                DATE(o.tanggal) as sale_date,
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
            WHERE 1=1{$dateFilter}{$platformFilter}
            GROUP BY DATE(o.tanggal)
            ORDER BY sale_date DESC";
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
            {$baseCTE}
            SELECT 
                COUNT(DISTINCT DATE(o.tanggal)) as total_days,
                COUNT(DISTINCT ot.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT DATE(o.tanggal)) > 0 
                    THEN COUNT(DISTINCT ot.order_id) / COUNT(DISTINCT DATE(o.tanggal))
                    ELSE 0
                END as avg_orders_per_day,
                CASE 
                    WHEN COUNT(DISTINCT DATE(o.tanggal)) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT DATE(o.tanggal))
                    ELSE 0
                END as avg_value_per_day,
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
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                o.platform_id,
                p.name as platform_name,
                COUNT(DISTINCT DATE(o.tanggal)) as total_days,
                COUNT(DISTINCT ot.order_id) as order_count,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_gross_profit), 0) as total_gross_profit,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT DATE(o.tanggal)) > 0 
                    THEN COUNT(DISTINCT ot.order_id) / COUNT(DISTINCT DATE(o.tanggal))
                    ELSE 0
                END as avg_orders_per_day,
                CASE 
                    WHEN COUNT(DISTINCT DATE(o.tanggal)) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT DATE(o.tanggal))
                    ELSE 0
                END as avg_value_per_day
            FROM order_totals ot
            INNER JOIN all_transactions at ON ot.order_id = at.order_id
            INNER JOIN orders o ON ot.order_id = o.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            WHERE 1=1{$dateFilter}{$platformFilter}
            GROUP BY o.platform_id, p.name
            ORDER BY total_value DESC";
    }
}

