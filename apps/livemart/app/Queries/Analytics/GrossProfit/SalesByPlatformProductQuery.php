<?php

namespace App\Queries\Analytics\GrossProfit;

use App\Queries\Analytics\Core\BaseTransactionQuery;
use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * SalesByPlatformProductQuery
 * 
 * Query untuk analytics Sales by Platform Product (Gross Profit)
 * Uses BaseTransactionQuery for base CTEs, then adds platform product specific calculations
 */
class SalesByPlatformProductQuery
{
    /**
     * Build query untuk platform product sales
     */
    public static function build(array $filters = [], int $perPage = 20, int $page = 1): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $platformId = $filters['platform_id'] ?? null;
        $orderNumber = $filters['order_number'] ?? null;
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sort'] ?? 'revenue_highest';
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND o.order_number LIKE {$orderNumberQuoted}";
        }
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND pp.platform_product_name LIKE {$searchQuoted}";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        $platformProductCTE = "
            base_platform_data AS (
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
                    ot.order_total_value as total_saldo_masuk,
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
                    ), '-') as invoice_number,
                    -- Calculate total COGS for this order_item (sum of all barang_keluar)
                    COALESCE((
                        SELECT SUM(
                            GREATEST(0,
                                COALESCE(pd.harga_hpp, 0)
                                * (1 - COALESCE(pd.diskon_persen_1, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_2, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_3, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_4, 0) / 100.0)
                                * (1 - COALESCE(pd.diskon_persen_5, 0) / 100.0)
                                - COALESCE(pd.diskon_nominal_1, 0)
                                - COALESCE(pd.diskon_nominal_2, 0)
                                - COALESCE(pd.diskon_nominal_3, 0)
                                - COALESCE(pd.diskon_nominal_4, 0)
                                - COALESCE(pd.diskon_nominal_5, 0)
                            ) * bk.qty
                        )
                        FROM barang_keluar bk
                        INNER JOIN warehouse_stock ws ON ws.id = bk.warehouse_stock_id
                        LEFT JOIN penerimaan_detail pd ON pd.id = ws.penerimaan_detail_id
                        WHERE bk.order_item_id = oi.id
                    ), 0) as total_cogs_for_order_item,
                    (SELECT COUNT(DISTINCT oi2.id) FROM order_items oi2 WHERE oi2.order_id = o.id) > 1 as has_multiple_items
                FROM orders o
                INNER JOIN platforms pl ON pl.id = o.platform_id
                INNER JOIN order_totals ot ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
                WHERE 1=1{$dateFilter}{$platformFilter}{$orderNumberFilter}{$searchFilter}
            )";
        
        $orderBy = self::buildOrderBy($sortBy);
        $offset = ($page - 1) * $perPage;
        
        return "
            {$baseCTE},
            {$platformProductCTE}
            SELECT 
                order_number,
                invoice_number,
                order_date,
                platform_name as platform,
                platform_product_name,
                platform_product_variant as product_variant,
                platform_quantity as quantity,
                total_saldo_masuk as revenue,
                total_cogs_for_order_item as capital,
                (total_saldo_masuk / 1.11) - total_cogs_for_order_item as gross_profit,
                0 as price,
                has_multiple_items
            FROM base_platform_data
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
        
        $dateFilter = FilterBuilder::dateFilter($startDate, $endDate, 'o.tanggal');
        $platformFilter = FilterBuilder::platformFilter($platformId, 'o.platform_id');
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND o.order_number LIKE {$orderNumberQuoted}";
        }
        
        $searchFilter = '';
        if ($search) {
            $searchQuoted = DB::getPdo()->quote('%' . $search . '%');
            $searchFilter = " AND pp.platform_product_name LIKE {$searchQuoted}";
        }
        
        $baseCTE = BaseTransactionQuery::baseCTE($filters);
        
        $platformProductCTE = "
            base_platform_data AS (
                SELECT 
                    oi.id as order_item_id
                FROM orders o
                INNER JOIN order_totals ot ON ot.order_id = o.id
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN platform_products pp ON pp.id = oi.platform_product_id
                WHERE 1=1{$dateFilter}{$platformFilter}{$orderNumberFilter}{$searchFilter}
            )";
        
        return "
            {$baseCTE},
            {$platformProductCTE}
            SELECT COUNT(*) as total
            FROM base_platform_data";
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
                COUNT(*) as total_platform_products,
                COUNT(*) as total_platform_products_after_returns,
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
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        $sortColumn = 'revenue';
        $sortDirection = 'DESC';
        
        switch ($sortBy) {
            case 'revenue_lowest':
                $sortColumn = 'revenue';
                $sortDirection = 'ASC';
                break;
            case 'capital_highest':
                $sortColumn = 'capital';
                $sortDirection = 'DESC';
                break;
            case 'capital_lowest':
                $sortColumn = 'capital';
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
            case 'quantity_highest':
                $sortColumn = 'quantity';
                $sortDirection = 'DESC';
                break;
            case 'quantity_lowest':
                $sortColumn = 'quantity';
                $sortDirection = 'ASC';
                break;
            case 'revenue_highest':
            default:
                $sortColumn = 'revenue';
                $sortDirection = 'DESC';
                break;
        }
        
        return "ORDER BY {$sortColumn} {$sortDirection}";
    }
}

