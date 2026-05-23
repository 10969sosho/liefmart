<?php

namespace App\Queries\Analytics\Sales;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * InternalProductSalesQuery
 * 
 * Query untuk analytics Internal Product Sales
 * Handles mapping_barang untuk internal products
 */
class InternalProductSalesQuery
{
    /**
     * Build query untuk internal product sales
     */
    public static function build(array $filters = [], int $perPage = 25, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $sortBy = $filters['sort'] ?? 'qty_highest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        // Build retur CTE
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
                    oi.platform_product_id
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
            ),
            mapping_with_package_qty AS (
                SELECT 
                    mb.platform_product_id,
                    mb.product_id,
                    mb.quantity as mapping_qty,
                    (SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = mb.platform_product_id AND is_active = 1) as package_qty
                FROM mapping_barang mb
                WHERE mb.is_active = 1
            ),
            internal_product_sales AS (
                SELECT 
                    mwpq.product_id,
                    p.name as product_name,
                    p.sku as product_sku,
                    o.id as order_id,
                    (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) * mwpq.mapping_qty as internal_qty,
                    (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) * mwpq.mapping_qty * (oiwr.price_after_discount / COALESCE(mwpq.package_qty, 1)) as internal_value
                FROM orders o
                INNER JOIN order_items_with_retur oiwr ON o.id = oiwr.order_id
                INNER JOIN mapping_with_package_qty mwpq ON oiwr.platform_product_id = mwpq.platform_product_id
                INNER JOIN products p ON mwpq.product_id = p.id
                WHERE 1=1{$dateFilter}{$platformFilter}
                    AND (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) > 0
            ),
            product_aggregated AS (
                SELECT 
                    product_id,
                    product_name,
                    product_sku,
                    COUNT(DISTINCT order_id) as order_count,
                    SUM(internal_qty) as total_qty,
                    SUM(internal_value) as total_value
                FROM internal_product_sales
                GROUP BY product_id, product_name, product_sku
            )";
        
        // Build ORDER BY
        $orderBy = self::buildOrderBy($sortBy);
        
        $offset = ($page - 1) * $perPage;
        
        return "
            WITH {$returCTE}
            SELECT 
                product_id,
                product_name,
                product_sku,
                order_count,
                total_qty,
                total_value
            FROM product_aggregated
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
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
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
                    oi.platform_product_id
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
            ),
            mapping_with_package_qty AS (
                SELECT 
                    mb.platform_product_id,
                    mb.product_id,
                    mb.quantity as mapping_qty,
                    (SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = mb.platform_product_id AND is_active = 1) as package_qty
                FROM mapping_barang mb
                WHERE mb.is_active = 1
            ),
            internal_product_sales AS (
                SELECT 
                    mwpq.product_id,
                    o.id as order_id,
                    (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) * mwpq.mapping_qty as internal_qty
                FROM orders o
                INNER JOIN order_items_with_retur oiwr ON o.id = oiwr.order_id
                INNER JOIN mapping_with_package_qty mwpq ON oiwr.platform_product_id = mwpq.platform_product_id
                WHERE 1=1{$dateFilter}{$platformFilter}
                    AND (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) > 0
            ),
            product_aggregated AS (
                SELECT 
                    product_id
                FROM internal_product_sales
                GROUP BY product_id
            )";
        
        return "
            WITH {$returCTE}
            SELECT COUNT(*) as total
            FROM product_aggregated";
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
                    oi.platform_product_id
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
            ),
            mapping_with_package_qty AS (
                SELECT 
                    mb.platform_product_id,
                    mb.product_id,
                    mb.quantity as mapping_qty,
                    (SELECT SUM(quantity) FROM mapping_barang WHERE platform_product_id = mb.platform_product_id AND is_active = 1) as package_qty
                FROM mapping_barang mb
                WHERE mb.is_active = 1
            ),
            internal_product_sales AS (
                SELECT 
                    mwpq.product_id,
                    o.id as order_id,
                    (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) * mwpq.mapping_qty as internal_qty,
                    (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) * mwpq.mapping_qty * (oiwr.price_after_discount / COALESCE(mwpq.package_qty, 1)) as internal_value
                FROM orders o
                INNER JOIN order_items_with_retur oiwr ON o.id = oiwr.order_id
                INNER JOIN mapping_with_package_qty mwpq ON oiwr.platform_product_id = mwpq.platform_product_id
                WHERE 1=1{$dateFilter}{$platformFilter}
                    AND (oiwr.quantity - (oiwr.retur_qty / COALESCE(mwpq.package_qty, 1))) > 0
            ),
            product_aggregated AS (
                SELECT 
                    product_id,
                    COUNT(DISTINCT order_id) as order_count,
                    SUM(internal_qty) as total_qty,
                    SUM(internal_value) as total_value
                FROM internal_product_sales
                GROUP BY product_id
            )";
        
        return "
            WITH {$returCTE}
            SELECT 
                COUNT(DISTINCT product_id) as total_products,
                SUM(order_count) as total_orders,
                SUM(total_qty) as total_qty,
                SUM(total_value) as total_value
            FROM product_aggregated";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        switch ($sortBy) {
            case 'qty_lowest':
                return 'ORDER BY total_qty ASC';
            case 'value_highest':
                return 'ORDER BY total_value DESC';
            case 'value_lowest':
                return 'ORDER BY total_value ASC';
            case 'name_asc':
                return 'ORDER BY product_name ASC';
            case 'name_desc':
                return 'ORDER BY product_name DESC';
            case 'qty_highest':
            default:
                return 'ORDER BY total_qty DESC';
        }
    }
}

