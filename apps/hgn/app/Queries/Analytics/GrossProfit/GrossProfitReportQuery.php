<?php

namespace App\Queries\Analytics\GrossProfit;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * GrossProfitReportQuery
 * 
 * Query untuk analytics Gross Profit Report (Online)
 * Menampilkan summary gross profit per order atau per platform
 * Lebih sederhana dari Sales by Platform Product (tidak detail per item)
 */
class GrossProfitReportQuery
{
    /**
     * Build query untuk gross profit report
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
            gross_profit_data AS (
                SELECT 
                    o.id as order_id,
                    o.order_number,
                    o.tanggal as order_date,
                    o.platform_id,
                    p.name as platform_name,
                    ot.order_total_value,
                    ot.order_total_nominal,
                    ot.order_total_hpp,
                    ot.order_total_gross_profit,
                    ot.order_total_volume,
                    (ot.order_total_value / 1.11) as order_total_value_without_ppn,
                    ((ot.order_total_value / 1.11) - ot.order_total_hpp) as gross_profit_simple,
                    CASE 
                        WHEN (ot.order_total_value / 1.11) > 0 
                        THEN (((ot.order_total_value / 1.11) - ot.order_total_hpp) / (ot.order_total_value / 1.11)) * 100
                        ELSE 0
                    END as profit_margin,
                    COALESCE((
                        SELECT no_invoice 
                        FROM shopee_financial_transactions 
                        WHERE order_id = o.id AND saldo_masuk > 0 
                        ORDER BY tanggal_masuk_pembayaran ASC 
                        LIMIT 1
                    ), (
                        SELECT no_invoice 
                        FROM tiktok_financial_transactions 
                        WHERE order_id = o.id AND saldo_masuk > 0 
                        ORDER BY tanggal_masuk_pembayaran ASC 
                        LIMIT 1
                    ), (
                        SELECT no_invoice 
                        FROM tokopedia_financial_transactions 
                        WHERE order_id = o.id AND saldo_masuk > 0 
                        ORDER BY tanggal_masuk_pembayaran ASC 
                        LIMIT 1
                    ), (
                        SELECT no_invoice 
                        FROM blibli_financial_transactions 
                        WHERE order_id = o.id AND saldo_masuk > 0 
                        ORDER BY tanggal_masuk_pembayaran ASC 
                        LIMIT 1
                    ), '-') as invoice_number
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                LEFT JOIN platforms p ON p.id = o.platform_id
                WHERE 1=1{$dateFilter}{$platformFilter}
            )
            SELECT 
                order_id,
                order_number,
                invoice_number,
                order_date,
                platform_id,
                platform_name,
                order_total_value,
                order_total_nominal,
                order_total_value_without_ppn,
                order_total_hpp,
                gross_profit_simple as gross_profit,
                profit_margin,
                order_total_volume
            FROM gross_profit_data
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
            {$baseCTE}
            SELECT COUNT(*) as total
            FROM order_totals ot
            INNER JOIN orders o ON ot.order_id = o.id
            WHERE 1=1{$dateFilter}{$platformFilter}";
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
                COUNT(DISTINCT ot.order_id) as total_orders,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                COALESCE(SUM(ot.order_total_value / 1.11), 0) as total_value_without_ppn,
                COALESCE(SUM((ot.order_total_value / 1.11) - ot.order_total_hpp), 0) as total_gross_profit,
                CASE 
                    WHEN COALESCE(SUM(ot.order_total_value / 1.11), 0) > 0 
                    THEN (COALESCE(SUM((ot.order_total_value / 1.11) - ot.order_total_hpp), 0) / COALESCE(SUM(ot.order_total_value / 1.11), 0)) * 100
                    ELSE 0
                END as avg_profit_margin,
                CASE 
                    WHEN COUNT(DISTINCT ot.order_id) > 0 
                    THEN COALESCE(SUM(ot.order_total_value), 0) / COUNT(DISTINCT ot.order_id)
                    ELSE 0
                END as avg_order_value
            FROM order_totals ot
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
                COUNT(DISTINCT ot.order_id) as order_count,
                COALESCE(SUM(ot.order_total_value), 0) as total_value,
                COALESCE(SUM(ot.order_total_nominal), 0) as total_nominal,
                COALESCE(SUM(ot.order_total_hpp), 0) as total_hpp,
                COALESCE(SUM(ot.order_total_volume), 0) as total_volume,
                COALESCE(SUM(ot.order_total_value / 1.11), 0) as total_value_without_ppn,
                COALESCE(SUM((ot.order_total_value / 1.11) - ot.order_total_hpp), 0) as total_gross_profit,
                CASE 
                    WHEN COALESCE(SUM(ot.order_total_value / 1.11), 0) > 0 
                    THEN (COALESCE(SUM((ot.order_total_value / 1.11) - ot.order_total_hpp), 0) / COALESCE(SUM(ot.order_total_value / 1.11), 0)) * 100
                    ELSE 0
                END as profit_margin
            FROM order_totals ot
            INNER JOIN orders o ON ot.order_id = o.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            WHERE 1=1{$dateFilter}{$platformFilter}
            GROUP BY o.platform_id, p.name
            ORDER BY total_gross_profit DESC";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        $sortColumn = 'order_date';
        $sortDirection = 'DESC';
        
        switch ($sortBy) {
            case 'date_oldest':
                $sortColumn = 'order_date';
                $sortDirection = 'ASC';
                break;
            case 'gross_profit_highest':
                $sortColumn = 'gross_profit';
                $sortDirection = 'DESC';
                break;
            case 'gross_profit_lowest':
                $sortColumn = 'gross_profit';
                $sortDirection = 'ASC';
                break;
            case 'margin_highest':
                $sortColumn = 'profit_margin';
                $sortDirection = 'DESC';
                break;
            case 'margin_lowest':
                $sortColumn = 'profit_margin';
                $sortDirection = 'ASC';
                break;
            case 'value_highest':
                $sortColumn = 'order_total_value';
                $sortDirection = 'DESC';
                break;
            case 'value_lowest':
                $sortColumn = 'order_total_value';
                $sortDirection = 'ASC';
                break;
            case 'date_newest':
            default:
                $sortColumn = 'order_date';
                $sortDirection = 'DESC';
                break;
        }
        
        return "ORDER BY {$sortColumn} {$sortDirection}";
    }
}

