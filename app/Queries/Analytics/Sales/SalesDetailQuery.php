<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * SalesDetailQuery
 * 
 * Query untuk analytics Sales Detail Report
 * Handles retur penjualan exclusion and price/qty filters
 */
class SalesDetailQuery
{
    /**
     * Build query untuk sales detail with pagination
     */
    public static function build(array $filters = [], int $perPage = 25, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $minQty = $filters['min_qty'] ?? null;
        $maxQty = $filters['max_qty'] ?? null;
        $sortBy = $filters['sort'] ?? 'date_newest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        // Build price and qty filters
        $priceFilter = '';
        if ($minPrice !== null) {
            $priceFilter .= " AND order_total_value >= " . floatval($minPrice);
        }
        if ($maxPrice !== null) {
            $priceFilter .= " AND order_total_value <= " . floatval($maxPrice);
        }
        
        $qtyFilter = '';
        if ($minQty !== null) {
            $qtyFilter .= " AND order_total_volume >= " . floatval($minQty);
        }
        if ($maxQty !== null) {
            $qtyFilter .= " AND order_total_volume <= " . floatval($maxQty);
        }
        
        // Build retur calculation CTE
        $returCTE = "
            retur_details AS (
                SELECT 
                    rpd.order_item_id,
                    SUM(rpd.qty) as retur_qty
                FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualan rp ON rpd.retur_penjualan_id = rp.id
                WHERE rp.status IN ('draft', 'selesai')
                GROUP BY rpd.order_item_id
            ),
            order_items_with_retur AS (
                SELECT 
                    oi.order_id,
                    oi.id as order_item_id,
                    oi.quantity,
                    oi.price_after_discount,
                    COALESCE(rd.retur_qty, 0) as retur_qty,
                    CASE 
                        WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                            COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                        ELSE 1
                    END as package_qty,
                    (oi.quantity - COALESCE(rd.retur_qty, 0) / 
                        CASE 
                            WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                                COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                            ELSE 1
                        END
                    ) as remaining_qty,
                    (oi.price_after_discount * (oi.quantity - COALESCE(rd.retur_qty, 0) / 
                        CASE 
                            WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                                COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                            ELSE 1
                        END
                    )) as remaining_value
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
                LEFT JOIN platform_products pp ON oi.platform_product_id = pp.id
                LEFT JOIN mapping_barang mb ON pp.id = mb.platform_product_id AND mb.is_active = 1
            ),
            orders_with_totals AS (
                SELECT 
                    o.id as order_id,
                    o.order_number,
                    o.tanggal,
                    o.platform_id,
                    SUM(oiwr.remaining_qty) as order_total_volume,
                    SUM(oiwr.remaining_value) as order_total_value
                FROM orders o
                INNER JOIN order_items_with_retur oiwr ON o.id = oiwr.order_id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY o.id, o.order_number, o.tanggal, o.platform_id
                HAVING SUM(oiwr.remaining_qty) > 0{$priceFilter}{$qtyFilter}
            )";
        
        // Build ORDER BY
        $orderBy = self::buildOrderBy($sortBy);
        
        $offset = ($page - 1) * $perPage;
        
        return "
            WITH {$returCTE}
            SELECT 
                o.id,
                o.order_number,
                o.tanggal,
                o.platform_id,
                p.name as platform_name,
                owt.order_total_value as total_value,
                owt.order_total_volume as total_volume
            FROM orders o
            INNER JOIN orders_with_totals owt ON o.id = owt.order_id
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
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $minQty = $filters['min_qty'] ?? null;
        $maxQty = $filters['max_qty'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $priceFilter = '';
        if ($minPrice !== null) {
            $priceFilter .= " AND order_total_value >= " . floatval($minPrice);
        }
        if ($maxPrice !== null) {
            $priceFilter .= " AND order_total_value <= " . floatval($maxPrice);
        }
        
        $qtyFilter = '';
        if ($minQty !== null) {
            $qtyFilter .= " AND order_total_volume >= " . floatval($minQty);
        }
        if ($maxQty !== null) {
            $qtyFilter .= " AND order_total_volume <= " . floatval($maxQty);
        }
        
        $returCTE = "
            retur_details AS (
                SELECT 
                    rpd.order_item_id,
                    SUM(rpd.qty) as retur_qty
                FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualan rp ON rpd.retur_penjualan_id = rp.id
                WHERE rp.status IN ('draft', 'selesai')
                GROUP BY rpd.order_item_id
            ),
            order_items_with_retur AS (
                SELECT 
                    oi.order_id,
                    oi.id as order_item_id,
                    oi.quantity,
                    oi.price_after_discount,
                    COALESCE(rd.retur_qty, 0) as retur_qty,
                    CASE 
                        WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                            COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                        ELSE 1
                    END as package_qty,
                    (oi.quantity - COALESCE(rd.retur_qty, 0) / 
                        CASE 
                            WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                                COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                            ELSE 1
                        END
                    ) as remaining_qty,
                    (oi.price_after_discount * (oi.quantity - COALESCE(rd.retur_qty, 0) / 
                        CASE 
                            WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                                COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                            ELSE 1
                        END
                    )) as remaining_value
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
                LEFT JOIN platform_products pp ON oi.platform_product_id = pp.id
                LEFT JOIN mapping_barang mb ON pp.id = mb.platform_product_id AND mb.is_active = 1
            ),
            orders_with_totals AS (
                SELECT 
                    o.id as order_id,
                    SUM(oiwr.remaining_qty) as order_total_volume,
                    SUM(oiwr.remaining_value) as order_total_value
                FROM orders o
                INNER JOIN order_items_with_retur oiwr ON o.id = oiwr.order_id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY o.id
                HAVING SUM(oiwr.remaining_qty) > 0{$priceFilter}{$qtyFilter}
            )";
        
        return "
            WITH {$returCTE}
            SELECT COUNT(*) as total
            FROM orders_with_totals";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;
        $minQty = $filters['min_qty'] ?? null;
        $maxQty = $filters['max_qty'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $priceFilter = '';
        if ($minPrice !== null) {
            $priceFilter .= " AND order_total_value >= " . floatval($minPrice);
        }
        if ($maxPrice !== null) {
            $priceFilter .= " AND order_total_value <= " . floatval($maxPrice);
        }
        
        $qtyFilter = '';
        if ($minQty !== null) {
            $qtyFilter .= " AND order_total_volume >= " . floatval($minQty);
        }
        if ($maxQty !== null) {
            $qtyFilter .= " AND order_total_volume <= " . floatval($maxQty);
        }
        
        $returCTE = "
            retur_details AS (
                SELECT 
                    rpd.order_item_id,
                    SUM(rpd.qty) as retur_qty
                FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualan rp ON rpd.retur_penjualan_id = rp.id
                WHERE rp.status IN ('draft', 'selesai')
                GROUP BY rpd.order_item_id
            ),
            order_items_with_retur AS (
                SELECT 
                    oi.order_id,
                    oi.id as order_item_id,
                    oi.quantity,
                    oi.price_after_discount,
                    COALESCE(rd.retur_qty, 0) as retur_qty,
                    CASE 
                        WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                            COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                        ELSE 1
                    END as package_qty,
                    (oi.quantity - COALESCE(rd.retur_qty, 0) / 
                        CASE 
                            WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                                COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                            ELSE 1
                        END
                    ) as remaining_qty,
                    (oi.price_after_discount * (oi.quantity - COALESCE(rd.retur_qty, 0) / 
                        CASE 
                            WHEN pp.id IS NOT NULL AND mb.id IS NOT NULL THEN
                                COALESCE((SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = pp.id AND is_active = 1), 1)
                            ELSE 1
                        END
                    )) as remaining_value
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
                LEFT JOIN platform_products pp ON oi.platform_product_id = pp.id
                LEFT JOIN mapping_barang mb ON pp.id = mb.platform_product_id AND mb.is_active = 1
            ),
            orders_with_totals AS (
                SELECT 
                    o.id as order_id,
                    SUM(oiwr.remaining_qty) as order_total_volume,
                    SUM(oiwr.remaining_value) as order_total_value
                FROM orders o
                INNER JOIN order_items_with_retur oiwr ON o.id = oiwr.order_id
                WHERE 1=1{$dateFilter}{$platformFilter}
                GROUP BY o.id
                HAVING SUM(oiwr.remaining_qty) > 0{$priceFilter}{$qtyFilter}
            ),
            all_orders_count AS (
                SELECT COUNT(*) as total
                FROM orders o
                WHERE 1=1{$dateFilter}{$platformFilter}
            ),
            orders_with_retur_count AS (
                SELECT COUNT(DISTINCT rp.order_id) as total
                FROM retur_penjualan rp
                INNER JOIN orders o ON rp.order_id = o.id
                WHERE rp.status IN ('draft', 'selesai')
                {$dateFilter}{$platformFilter}
            )";
        
        return "
            WITH {$returCTE}
            SELECT 
                COUNT(DISTINCT owt.order_id) as total_orders,
                COALESCE(SUM(owt.order_total_value), 0) as total_value,
                COALESCE(SUM(owt.order_total_volume), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT owt.order_id) > 0 
                    THEN COALESCE(SUM(owt.order_total_value), 0) / COUNT(DISTINCT owt.order_id)
                    ELSE 0
                END as avg_order_value,
                CASE 
                    WHEN COUNT(DISTINCT owt.order_id) > 0 
                    THEN COALESCE(SUM(owt.order_total_volume), 0) / COUNT(DISTINCT owt.order_id)
                    ELSE 0
                END as avg_order_volume,
                (SELECT total FROM all_orders_count) as total_orders_all,
                (SELECT total FROM orders_with_retur_count) as total_returns,
                (SELECT total FROM orders_with_retur_count) as orders_with_returns
            FROM orders_with_totals owt";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        switch ($sortBy) {
            case 'date_oldest':
                return 'ORDER BY o.tanggal ASC';
            case 'value_highest':
                return 'ORDER BY owt.order_total_value DESC, o.tanggal DESC';
            case 'value_lowest':
                return 'ORDER BY owt.order_total_value ASC, o.tanggal DESC';
            case 'date_newest':
            default:
                return 'ORDER BY o.tanggal DESC';
        }
    }
}

