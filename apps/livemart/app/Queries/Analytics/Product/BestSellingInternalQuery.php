<?php

namespace App\Queries\Analytics\Product;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * BestSellingInternalQuery
 * 
 * Query untuk analytics Produk Internal Terlaris
 * Handles mapping_barang untuk internal products
 */
class BestSellingInternalQuery
{
    /**
     * Build query untuk best selling internal products
     */
    public static function build(array $filters = [], int $perPage = 100, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sort'] ?? 'quantity_highest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND (p.name LIKE {$searchQuoted} OR p.sku LIKE {$searchQuoted})";
        }
        
        // Build retur CTE
        $returCTE = "
            retur_details AS (
                SELECT 
                    rpd.order_item_id,
                    SUM(rpd.qty) as retur_qty_individual
                FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualan rp ON rpd.retur_penjualan_id = rp.id
                WHERE rp.status IN ('draft', 'selesai')
                GROUP BY rpd.order_item_id
            ),
            package_quantities AS (
                SELECT 
                    pp.id as platform_product_id,
                    COALESCE(SUM(mb.quantity), 1) as package_quantity
                FROM platform_products pp
                LEFT JOIN mapping_barang mb ON pp.id = mb.platform_product_id AND mb.is_active = 1
                GROUP BY pp.id
            ),
            order_items_with_retur AS (
                SELECT 
                    oi.id as order_item_id,
                    oi.order_id,
                    oi.platform_product_id,
                    oi.quantity,
                    COALESCE(rd.retur_qty_individual, 0) as retur_qty_individual,
                    COALESCE(pq.package_quantity, 1) as package_quantity,
                    (oi.quantity - COALESCE(rd.retur_qty_individual, 0) / COALESCE(pq.package_quantity, 1)) as net_quantity
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
                LEFT JOIN package_quantities pq ON oi.platform_product_id = pq.platform_product_id
            ),
            valid_orders AS (
                SELECT DISTINCT o.id as order_id
                FROM orders o
                WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}
                    AND NOT EXISTS (
                        SELECT 1 FROM retur_penjualan rp 
                        WHERE rp.order_id = o.id 
                        AND rp.status IN ('draft', 'selesai')
                    )
            ),
            internal_product_sales AS (
                SELECT 
                    p.id as product_id,
                    p.name as product_name,
                    p.sku as product_sku,
                    o.id as order_id,
                    o.platform_id,
                    SUM(oiwr.net_quantity * mb.quantity) as internal_qty
                FROM products p
                INNER JOIN mapping_barang mb ON p.id = mb.product_id AND mb.is_active = 1
                INNER JOIN order_items_with_retur oiwr ON mb.platform_product_id = oiwr.platform_product_id
                INNER JOIN valid_orders vo ON oiwr.order_id = vo.order_id
                INNER JOIN orders o ON oiwr.order_id = o.id
                WHERE 1=1{$searchFilter}
                GROUP BY p.id, p.name, p.sku, o.id, o.platform_id
            ),
            product_aggregated AS (
                SELECT 
                    product_id,
                    product_name,
                    product_sku,
                    COUNT(DISTINCT order_id) as order_count,
                    GROUP_CONCAT(DISTINCT platform_id ORDER BY platform_id SEPARATOR ',') as platform_ids,
                    SUM(internal_qty) as total_quantity
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
                platform_ids,
                total_quantity
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
        $search = $filters['search'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND (p.name LIKE {$searchQuoted} OR p.sku LIKE {$searchQuoted})";
        }
        
        $returCTE = "
            retur_details AS (
                SELECT 
                    rpd.order_item_id,
                    SUM(rpd.qty) as retur_qty_individual
                FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualan rp ON rpd.retur_penjualan_id = rp.id
                WHERE rp.status IN ('draft', 'selesai')
                GROUP BY rpd.order_item_id
            ),
            package_quantities AS (
                SELECT 
                    pp.id as platform_product_id,
                    COALESCE(SUM(mb.quantity), 1) as package_quantity
                FROM platform_products pp
                LEFT JOIN mapping_barang mb ON pp.id = mb.platform_product_id AND mb.is_active = 1
                GROUP BY pp.id
            ),
            order_items_with_retur AS (
                SELECT 
                    oi.id as order_item_id,
                    oi.order_id,
                    oi.platform_product_id,
                    oi.quantity,
                    COALESCE(rd.retur_qty_individual, 0) as retur_qty_individual,
                    COALESCE(pq.package_quantity, 1) as package_quantity,
                    (oi.quantity - COALESCE(rd.retur_qty_individual, 0) / COALESCE(pq.package_quantity, 1)) as net_quantity
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
                LEFT JOIN package_quantities pq ON oi.platform_product_id = pq.platform_product_id
            ),
            valid_orders AS (
                SELECT DISTINCT o.id as order_id
                FROM orders o
                WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}
                    AND NOT EXISTS (
                        SELECT 1 FROM retur_penjualan rp 
                        WHERE rp.order_id = o.id 
                        AND rp.status IN ('draft', 'selesai')
                    )
            ),
            internal_product_sales AS (
                SELECT 
                    p.id as product_id,
                    o.id as order_id,
                    SUM(oiwr.net_quantity * mb.quantity) as internal_qty
                FROM products p
                INNER JOIN mapping_barang mb ON p.id = mb.product_id AND mb.is_active = 1
                INNER JOIN order_items_with_retur oiwr ON mb.platform_product_id = oiwr.platform_product_id
                INNER JOIN valid_orders vo ON oiwr.order_id = vo.order_id
                INNER JOIN orders o ON oiwr.order_id = o.id
                WHERE 1=1{$searchFilter}
                GROUP BY p.id, o.id
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
        $search = $filters['search'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND (p.name LIKE {$searchQuoted} OR p.sku LIKE {$searchQuoted})";
        }
        
        $returCTE = "
            retur_details AS (
                SELECT 
                    rpd.order_item_id,
                    SUM(rpd.qty) as retur_qty_individual
                FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualan rp ON rpd.retur_penjualan_id = rp.id
                WHERE rp.status IN ('draft', 'selesai')
                GROUP BY rpd.order_item_id
            ),
            package_quantities AS (
                SELECT 
                    pp.id as platform_product_id,
                    COALESCE(SUM(mb.quantity), 1) as package_quantity
                FROM platform_products pp
                LEFT JOIN mapping_barang mb ON pp.id = mb.platform_product_id AND mb.is_active = 1
                GROUP BY pp.id
            ),
            order_items_with_retur AS (
                SELECT 
                    oi.id as order_item_id,
                    oi.order_id,
                    oi.platform_product_id,
                    oi.quantity,
                    COALESCE(rd.retur_qty_individual, 0) as retur_qty_individual,
                    COALESCE(pq.package_quantity, 1) as package_quantity,
                    (oi.quantity - COALESCE(rd.retur_qty_individual, 0) / COALESCE(pq.package_quantity, 1)) as net_quantity
                FROM order_items oi
                LEFT JOIN retur_details rd ON oi.id = rd.order_item_id
                LEFT JOIN package_quantities pq ON oi.platform_product_id = pq.platform_product_id
            ),
            valid_orders AS (
                SELECT DISTINCT o.id as order_id
                FROM orders o
                WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}
                    AND NOT EXISTS (
                        SELECT 1 FROM retur_penjualan rp 
                        WHERE rp.order_id = o.id 
                        AND rp.status IN ('draft', 'selesai')
                    )
            ),
            internal_product_sales AS (
                SELECT 
                    p.id as product_id,
                    o.id as order_id,
                    SUM(oiwr.net_quantity * mb.quantity) as internal_qty
                FROM products p
                INNER JOIN mapping_barang mb ON p.id = mb.product_id AND mb.is_active = 1
                INNER JOIN order_items_with_retur oiwr ON mb.platform_product_id = oiwr.platform_product_id
                INNER JOIN valid_orders vo ON oiwr.order_id = vo.order_id
                INNER JOIN orders o ON oiwr.order_id = o.id
                WHERE 1=1{$searchFilter}
                GROUP BY p.id, o.id
            ),
            product_aggregated AS (
                SELECT 
                    product_id,
                    COUNT(DISTINCT order_id) as order_count,
                    SUM(internal_qty) as total_quantity
                FROM internal_product_sales
                GROUP BY product_id
            )";
        
        return "
            WITH {$returCTE}
            SELECT 
                COUNT(DISTINCT pa.product_id) as total_products,
                SUM(pa.order_count) as total_orders,
                SUM(pa.total_quantity) as total_qty
            FROM product_aggregated pa";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        switch ($sortBy) {
            case 'quantity_lowest':
                return 'ORDER BY total_quantity ASC';
            case 'order_count_highest':
                return 'ORDER BY order_count DESC';
            case 'order_count_lowest':
                return 'ORDER BY order_count ASC';
            case 'quantity_highest':
            default:
                return 'ORDER BY total_quantity DESC';
        }
    }
}

