<?php

namespace App\Queries\Analytics\Product;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * MasterProductSpecialQuery
 * 
 * Query untuk analytics Sales by Master Product Special
 * Similar to MasterProductQuery but uses AVERAGE COST instead of specific penerimaan_detail
 */
class MasterProductSpecialQuery
{
    /**
     * Build query untuk master product special sales
     */
    public static function build(array $filters = [], int $perPage = 20, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $orderNumber = $filters['order_number'] ?? null;
        $search = $filters['search'] ?? null;
        $brands = $filters['brands'] ?? [];
        $subBrands = $filters['sub_brands'] ?? [];
        $productCategories = $filters['product_categories'] ?? [];
        $productTypes = $filters['product_types'] ?? [];
        $productSizes = $filters['product_sizes'] ?? [];
        $productVariants = $filters['product_variants'] ?? [];
        $sortBy = $filters['sort'] ?? 'revenue_highest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        $productFilters = MasterProductQuery::buildProductFilters($brands, $subBrands, $productCategories, $productTypes, $productSizes, $productVariants);
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND o.order_number LIKE {$orderNumberQuoted}";
        }
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND (pp.platform_product_name LIKE {$searchQuoted} OR p.name LIKE {$searchQuoted} OR p.sku LIKE {$searchQuoted})";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        $masterProductCTE = "
            base_master_data AS (
                SELECT 
                    o.id as order_id,
                    o.order_number,
                    o.tanggal as order_date,
                    o.platform_id,
                    pl.name as platform_name,
                    oi.id as order_item_id,
                    oi.quantity as platform_quantity,
                    pp.id as platform_product_id,
                    pp.platform_product_name,
                    COALESCE(pp.variant, 'N/A') as platform_product_variant,
                    bk.id as barang_keluar_id,
                    bk.qty as master_qty,
                    p.id as product_id,
                    p.name as product_name,
                    COALESCE(p.sku, 'N/A') as sku,
                    COALESCE(pipv.initial_price, 0) as price,
                    ot.order_total_value as total_saldo_masuk,
                    COALESCE((
                        SELECT SUM(COALESCE(pipv2.initial_price, 0) * bk2.qty)
                        FROM order_items oi2
                        INNER JOIN barang_keluar bk2 ON bk2.order_item_id = oi2.id
                        INNER JOIN warehouse_stock ws2 ON ws2.id = bk2.warehouse_stock_id
                        INNER JOIN products p2 ON p2.id = ws2.product_id
                        LEFT JOIN product_initial_price_versions pipv2 ON pipv2.product_id = p2.id
                            AND pipv2.valid_from <= o.created_at
                            AND (pipv2.valid_until IS NULL OR pipv2.valid_until > o.created_at)
                        WHERE oi2.order_id = o.id
                    ), 0) as total_order_value_from_products,
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
                    ), '-') as invoice_number
                FROM orders o
                INNER JOIN platforms pl ON pl.id = o.platform_id
                INNER JOIN order_totals ot ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
                INNER JOIN mapping_barang mb ON mb.platform_product_id = pp.id AND mb.is_active = 1
                INNER JOIN products p ON p.id = mb.product_id
                LEFT JOIN product_initial_price_versions pipv ON pipv.product_id = p.id
                    AND pipv.valid_from <= o.created_at
                    AND (pipv.valid_until IS NULL OR pipv.valid_until > o.created_at)
                INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
                INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id AND ws.product_id = p.id
                WHERE 1=1{$dateFilter}{$platformFilter}{$orderNumberFilter}{$searchFilter}{$productFilters}
            ),
            calculated_master_data AS (
                SELECT 
                    *,
                    -- Calculate AVERAGE COST per unit (SPECIAL REPORT)
                    COALESCE((
                        SELECT 
                            SUM(
                                GREATEST(0,
                                    COALESCE(pd2.harga_hpp, 0)
                                    * (1 - COALESCE(pd2.diskon_persen_1, 0) / 100.0)
                                    * (1 - COALESCE(pd2.diskon_persen_2, 0) / 100.0)
                                    * (1 - COALESCE(pd2.diskon_persen_3, 0) / 100.0)
                                    * (1 - COALESCE(pd2.diskon_persen_4, 0) / 100.0)
                                    * (1 - COALESCE(pd2.diskon_persen_5, 0) / 100.0)
                                    - COALESCE(pd2.diskon_nominal_1, 0)
                                    - COALESCE(pd2.diskon_nominal_2, 0)
                                    - COALESCE(pd2.diskon_nominal_3, 0)
                                    - COALESCE(pd2.diskon_nominal_4, 0)
                                    - COALESCE(pd2.diskon_nominal_5, 0)
                                ) * pd2.qty
                            ) / NULLIF(SUM(pd2.qty), 0)
                        FROM penerimaan_detail pd2
                        WHERE pd2.product_id = base_master_data.product_id
                        AND pd2.qty > 0
                    ), 0) as cogs_per_unit,
                    COALESCE(price, 0) * COALESCE(master_qty, 0) as pricelist_total,
                    CASE 
                        WHEN total_order_value_from_products > 0 THEN
                            (COALESCE(price, 0) * COALESCE(master_qty, 0) / total_order_value_from_products) * 100
                        ELSE 0
                    END as proportion_percent
                FROM base_master_data
            )";
        
        $orderBy = MasterProductQuery::buildOrderBy($sortBy);
        $offset = ($page - 1) * $perPage;
        
        return "
            {$baseCTE},
            {$masterProductCTE}
            SELECT 
                order_number,
                invoice_number,
                order_date,
                platform_name as platform,
                platform_product_name,
                platform_product_variant,
                platform_quantity,
                sku,
                product_name,
                master_qty as quantity,
                price,
                pricelist_total,
                proportion_percent,
                total_saldo_masuk as order_total_payment,
                total_order_value_from_products,
                (total_saldo_masuk * proportion_percent / 100) as revenue,
                (cogs_per_unit * master_qty) as capital,
                cogs_per_unit as modal_per_pcs,
                ((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) as payment_per_product_per_pcs,
                (((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) as payment_per_product_without_ppn,
                ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0)) as unit_cost,
                ((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) - ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0))) as profit_per_pcs,
                ((((total_saldo_masuk * proportion_percent / 100) / NULLIF(master_qty, 0)) / 1.11) - ((cogs_per_unit * master_qty) / NULLIF(master_qty, 0))) * master_qty as gross_profit
            FROM calculated_master_data
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
        $orderNumber = $filters['order_number'] ?? null;
        $search = $filters['search'] ?? null;
        $brands = $filters['brands'] ?? [];
        $subBrands = $filters['sub_brands'] ?? [];
        $productCategories = $filters['product_categories'] ?? [];
        $productTypes = $filters['product_types'] ?? [];
        $productSizes = $filters['product_sizes'] ?? [];
        $productVariants = $filters['product_variants'] ?? [];
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        $productFilters = MasterProductQuery::buildProductFilters($brands, $subBrands, $productCategories, $productTypes, $productSizes, $productVariants);
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND o.order_number LIKE {$orderNumberQuoted}";
        }
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND (pp.platform_product_name LIKE {$searchQuoted} OR p.name LIKE {$searchQuoted} OR p.sku LIKE {$searchQuoted})";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        $masterProductCTE = "
            base_master_data AS (
                SELECT 
                    o.id as order_id,
                    oi.id as order_item_id,
                    bk.id as barang_keluar_id
                FROM orders o
                INNER JOIN order_totals ot ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
                INNER JOIN mapping_barang mb ON mb.platform_product_id = pp.id AND mb.is_active = 1
                INNER JOIN products p ON p.id = mb.product_id
                INNER JOIN barang_keluar bk ON bk.order_item_id = oi.id
                INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id AND ws.product_id = p.id
                WHERE 1=1{$dateFilter}{$platformFilter}{$orderNumberFilter}{$searchFilter}{$productFilters}
            )";
        
        return "
            {$baseCTE},
            {$masterProductCTE}
            SELECT COUNT(*) as total
            FROM base_master_data";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $query = self::build($filters, 999999, 1);
        
        return "
            WITH summary_data AS ({$query})
            SELECT 
                COUNT(*) as total_products,
                COUNT(*) as total_rows,
                COALESCE(SUM(revenue), 0) as total_revenue,
                COALESCE(SUM(revenue) / 1.11, 0) as total_revenue_without_ppn,
                COALESCE(SUM(capital), 0) as total_capital,
                COALESCE(SUM(revenue) / 1.11 - SUM(capital), 0) as total_gross_profit,
                COALESCE(SUM(quantity), 0) as total_quantity,
                CASE 
                    WHEN COALESCE(SUM(revenue) / 1.11, 0) > 0 
                    THEN ((COALESCE(SUM(revenue) / 1.11 - SUM(capital), 0)) / COALESCE(SUM(revenue) / 1.11, 0)) * 100
                    ELSE 0
                END as profit_margin
            FROM summary_data";
    }
}
