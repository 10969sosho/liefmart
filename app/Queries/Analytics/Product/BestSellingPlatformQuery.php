<?php

namespace App\Queries\Analytics\Product;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * BestSellingPlatformQuery
 * 
 * Query untuk analytics Produk Platform Terlaris
 * Handles retur penjualan and mapping_barang
 */
class BestSellingPlatformQuery
{
    /**
     * Build query untuk best selling platform products
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
            $searchFilter = " AND (pp.platform_product_name LIKE {$searchQuoted} OR pp.variant LIKE {$searchQuoted})";
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
                    oi.price_after_discount,
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
            product_aggregated AS (
                SELECT 
                    pp.id as platform_product_id,
                    pp.platform_product_name,
                    pp.variant,
                    p.id as platform_id,
                    p.name as platform_name,
                    SUM(oiwr.net_quantity) as total_quantity,
                    COUNT(DISTINCT oiwr.order_id) as order_count,
                    SUM(oiwr.price_after_discount * oiwr.net_quantity) as total_value
                FROM platform_products pp
                INNER JOIN order_items_with_retur oiwr ON pp.id = oiwr.platform_product_id
                INNER JOIN valid_orders vo ON oiwr.order_id = vo.order_id
                INNER JOIN orders o ON oiwr.order_id = o.id
                INNER JOIN platforms p ON o.platform_id = p.id
                WHERE 1=1{$searchFilter}{$platformFilter}
                GROUP BY pp.id, pp.platform_product_name, pp.variant, p.id, p.name
                HAVING SUM(oiwr.net_quantity) > 0
            )";
        
        // Build ORDER BY
        $orderBy = self::buildOrderBy($sortBy);
        
        $offset = ($page - 1) * $perPage;
        
        return "
            WITH {$returCTE}
            SELECT 
                platform_product_id,
                platform_product_name,
                variant,
                platform_id,
                platform_name,
                total_quantity,
                order_count,
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
        $search = $filters['search'] ?? null;
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate);
        $platformFilter = FilterBuilder::platformFilter($platformId);
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND (pp.platform_product_name LIKE {$searchQuoted} OR pp.variant LIKE {$searchQuoted})";
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
            product_aggregated AS (
                SELECT 
                    pp.id as platform_product_id
                FROM platform_products pp
                INNER JOIN order_items_with_retur oiwr ON pp.id = oiwr.platform_product_id
                INNER JOIN valid_orders vo ON oiwr.order_id = vo.order_id
                INNER JOIN orders o ON oiwr.order_id = o.id
                WHERE 1=1{$searchFilter}{$platformFilter}
                GROUP BY pp.id
                HAVING SUM(oiwr.net_quantity) > 0
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
            $searchFilter = " AND (pp.platform_product_name LIKE {$searchQuoted} OR pp.variant LIKE {$searchQuoted})";
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
                    oi.price_after_discount,
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
            all_orders_count AS (
                SELECT COUNT(*) as total
                FROM orders o
                WHERE o.platform_id IS NOT NULL{$dateFilter}{$platformFilter}
            ),
            returns_count AS (
                SELECT COUNT(DISTINCT rp.order_id) as total
                FROM retur_penjualan rp
                INNER JOIN orders o ON rp.order_id = o.id
                WHERE rp.status IN ('draft', 'selesai'){$dateFilter}{$platformFilter}
            ),
            product_aggregated AS (
                SELECT 
                    pp.id as platform_product_id,
                    SUM(oiwr.net_quantity) as total_quantity,
                    SUM(oiwr.price_after_discount * oiwr.net_quantity) as total_value
                FROM platform_products pp
                INNER JOIN order_items_with_retur oiwr ON pp.id = oiwr.platform_product_id
                INNER JOIN valid_orders vo ON oiwr.order_id = vo.order_id
                INNER JOIN orders o ON oiwr.order_id = o.id
                WHERE 1=1{$searchFilter}{$platformFilter}
                GROUP BY pp.id
                HAVING SUM(oiwr.net_quantity) > 0
            )";
        
        return "
            WITH {$returCTE}
            SELECT 
                COUNT(DISTINCT pa.platform_product_id) as total_products,
                COALESCE(SUM(pa.total_quantity), 0) as total_quantity,
                COALESCE(SUM(pa.total_quantity), 0) as total_quantity_with_returns,
                COALESCE(SUM(pa.total_value), 0) as total_value,
                (SELECT total FROM all_orders_count) as total_orders,
                (SELECT total FROM returns_count) as total_returns_count
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
            case 'value_highest':
                return 'ORDER BY total_value DESC';
            case 'value_lowest':
                return 'ORDER BY total_value ASC';
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

