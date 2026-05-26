<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * DiscountAnalysisQuery
 * 
 * Query untuk analytics Discount Analysis Report
 * Menganalisis discount yang diberikan per order/item
 * Discount = (platform_product.initial_price - order_items.price_after_discount) * quantity
 */
class DiscountAnalysisQuery
{
    /**
     * Build query untuk discount analysis
     */
    public static function build(array $filters = [], int $perPage = 25, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $minDiscount = $filters['min_discount'] ?? null;
        $maxDiscount = $filters['max_discount'] ?? null;
        $sortBy = $filters['sort'] ?? 'discount_highest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $discountFilter = '';
        if ($minDiscount !== null) {
            $discountFilter .= " AND item_discount >= " . floatval($minDiscount);
        }
        if ($maxDiscount !== null) {
            $discountFilter .= " AND item_discount <= " . floatval($maxDiscount);
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        $orderBy = self::buildOrderBy($sortBy);
        $offset = ($page - 1) * $perPage;
        
        return "
            {$baseCTE},
            discount_analysis AS (
                SELECT 
                    o.id as order_id,
                    o.order_number,
                    o.tanggal,
                    o.platform_id,
                    p.name as platform_name,
                    oi.id as order_item_id,
                    pp.id as platform_product_id,
                    pp.platform_product_name,
                    COALESCE(pp.initial_price, 0) as original_price,
                    oi.price_after_discount,
                    oi.quantity,
                    (COALESCE(pp.initial_price, 0) * oi.quantity) as total_before_discount,
                    (oi.price_after_discount * oi.quantity) as total_after_discount,
                    ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity) as item_discount,
                    CASE 
                        WHEN COALESCE(pp.initial_price, 0) > 0 
                        THEN ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) / COALESCE(pp.initial_price, 0)) * 100
                        ELSE 0
                    END as discount_percentage
                FROM order_totals ot
                INNER JOIN orders o ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON oi.platform_product_id = pp.id
                LEFT JOIN platforms p ON p.id = o.platform_id
                WHERE 1=1{$dateFilter}{$platformFilter}{$discountFilter}
            )
            SELECT 
                order_id,
                order_number,
                tanggal,
                platform_id,
                platform_name,
                order_item_id,
                platform_product_id,
                platform_product_name,
                original_price,
                price_after_discount,
                quantity,
                total_before_discount,
                total_after_discount,
                item_discount,
                discount_percentage
            FROM discount_analysis
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
        $minDiscount = $filters['min_discount'] ?? null;
        $maxDiscount = $filters['max_discount'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $discountFilter = '';
        if ($minDiscount !== null) {
            $discountFilter .= " AND ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity) >= " . floatval($minDiscount);
        }
        if ($maxDiscount !== null) {
            $discountFilter .= " AND ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity) <= " . floatval($maxDiscount);
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT COUNT(*) as total
            FROM order_totals ot
            INNER JOIN orders o ON ot.order_id = o.id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON oi.platform_product_id = pp.id
            WHERE 1=1{$dateFilter}{$platformFilter}{$discountFilter}";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $minDiscount = $filters['min_discount'] ?? null;
        $maxDiscount = $filters['max_discount'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $discountFilter = '';
        if ($minDiscount !== null) {
            $discountFilter .= " AND ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity) >= " . floatval($minDiscount);
        }
        if ($maxDiscount !== null) {
            $discountFilter .= " AND ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity) <= " . floatval($maxDiscount);
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        return "
            {$baseCTE}
            SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT oi.id) as total_items,
                COALESCE(SUM((COALESCE(pp.initial_price, 0) * oi.quantity)), 0) as total_before_discount,
                COALESCE(SUM((oi.price_after_discount * oi.quantity)), 0) as total_after_discount,
                COALESCE(SUM(((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity)), 0) as total_discount,
                CASE 
                    WHEN COALESCE(SUM((COALESCE(pp.initial_price, 0) * oi.quantity)), 0) > 0 
                    THEN (COALESCE(SUM(((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity)), 0) / COALESCE(SUM((COALESCE(pp.initial_price, 0) * oi.quantity)), 0)) * 100
                    ELSE 0
                END as avg_discount_percentage,
                COALESCE(AVG(CASE 
                    WHEN COALESCE(pp.initial_price, 0) > 0 
                    THEN ((COALESCE(pp.initial_price, 0) - oi.price_after_discount) / COALESCE(pp.initial_price, 0)) * 100
                    ELSE 0
                END), 0) as avg_item_discount_percentage
            FROM order_totals ot
            INNER JOIN orders o ON ot.order_id = o.id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON oi.platform_product_id = pp.id
            WHERE 1=1{$dateFilter}{$platformFilter}{$discountFilter}";
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
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT oi.id) as total_items,
                COALESCE(SUM((COALESCE(pp.initial_price, 0) * oi.quantity)), 0) as total_before_discount,
                COALESCE(SUM((oi.price_after_discount * oi.quantity)), 0) as total_after_discount,
                COALESCE(SUM(((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity)), 0) as total_discount,
                CASE 
                    WHEN COALESCE(SUM((COALESCE(pp.initial_price, 0) * oi.quantity)), 0) > 0 
                    THEN (COALESCE(SUM(((COALESCE(pp.initial_price, 0) - oi.price_after_discount) * oi.quantity)), 0) / COALESCE(SUM((COALESCE(pp.initial_price, 0) * oi.quantity)), 0)) * 100
                    ELSE 0
                END as avg_discount_percentage
            FROM order_totals ot
            INNER JOIN orders o ON ot.order_id = o.id
            INNER JOIN order_items oi ON oi.order_id = o.id
            INNER JOIN platform_products pp ON oi.platform_product_id = pp.id
            LEFT JOIN platforms p ON p.id = o.platform_id
            WHERE 1=1{$dateFilter}{$platformFilter}
            GROUP BY o.platform_id, p.name
            ORDER BY total_discount DESC";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        $sortColumn = 'item_discount';
        $sortDirection = 'DESC';
        
        switch ($sortBy) {
            case 'discount_lowest':
                $sortColumn = 'item_discount';
                $sortDirection = 'ASC';
                break;
            case 'discount_percentage_highest':
                $sortColumn = 'discount_percentage';
                $sortDirection = 'DESC';
                break;
            case 'discount_percentage_lowest':
                $sortColumn = 'discount_percentage';
                $sortDirection = 'ASC';
                break;
            case 'date_newest':
                $sortColumn = 'tanggal';
                $sortDirection = 'DESC';
                break;
            case 'date_oldest':
                $sortColumn = 'tanggal';
                $sortDirection = 'ASC';
                break;
            case 'discount_highest':
            default:
                $sortColumn = 'item_discount';
                $sortDirection = 'DESC';
                break;
        }
        
        return "ORDER BY {$sortColumn} {$sortDirection}";
    }
}

