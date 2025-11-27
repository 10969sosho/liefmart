<?php

namespace App\Queries\Analytics\Core;

use Illuminate\Support\Facades\DB;

/**
 * BaseTransactionQuery - Single Source of Truth untuk semua CTE analytics
 * 
 * Semua analytics HARUS menggunakan CTE ini untuk konsistensi dan performa.
 * Tidak boleh ada duplikasi SQL untuk financial transactions.
 */
class BaseTransactionQuery
{
    /**
     * Generate base CTE untuk all_transactions
     * Menggabungkan semua financial transactions dari semua platform
     * 
     * @param array $filters ['start_date', 'end_date', 'platform_id', 'status_hari']
     * @return string SQL CTE
     */
    public static function allTransactionsCTE(array $filters = []): string
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
        
        return "
            all_transactions AS (
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    COALESCE(ft.qty, 0) as qty
                FROM orders o
                INNER JOIN shopee_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
                
                UNION ALL
                
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    0 as qty
                FROM orders o
                INNER JOIN shopee2_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
                
                UNION ALL
                
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    COALESCE(ft.qty, 0) as qty
                FROM orders o
                INNER JOIN tiktok_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
                
                UNION ALL
                
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    0 as qty
                FROM orders o
                INNER JOIN tiktok2_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
                
                UNION ALL
                
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    COALESCE(ft.qty, 0) as qty
                FROM orders o
                INNER JOIN tokopedia_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
                
                UNION ALL
                
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    0 as qty
                FROM orders o
                INNER JOIN blibli_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
                
                UNION ALL
                
                SELECT 
                    o.id as order_id,
                    o.tanggal,
                    o.platform_id,
                    o.status_hari,
                    COALESCE(ft.saldo_masuk, 0) as saldo_masuk,
                    0 as qty
                FROM orders o
                INNER JOIN lazada_financial_transactions ft ON ft.order_id = o.id
                WHERE ft.saldo_masuk > 0{$dateFilter}{$platformFilter}{$statusFilter}
            )";
    }
    
    /**
     * Generate CTE untuk order_items_nominal
     * Menghitung total nominal per order dari order_items
     * 
     * @return string SQL CTE
     */
    public static function orderItemsNominalCTE(): string
    {
        return "
            order_items_nominal AS (
                SELECT 
                    order_id,
                    SUM(price_after_discount * quantity) as total_nominal
                FROM order_items
                GROUP BY order_id
            )";
    }
    
    /**
     * Generate CTE untuk order_hpp
     * Menghitung HPP per order dari penerimaan_detail
     * 
     * @return string SQL CTE
     */
    public static function orderHppCTE(): string
    {
        return "
            order_hpp AS (
                SELECT 
                    oi.order_id,
                    SUM(
                        CASE 
                            WHEN oi.warehouse_stock_id IS NOT NULL 
                                AND ws.id IS NOT NULL 
                                AND pd.id IS NOT NULL 
                                AND pd.qty > 0 
                            THEN (pd.subtotal / pd.qty) * oi.quantity
                            ELSE 0
                        END
                    ) as total_hpp
                FROM order_items oi
                LEFT JOIN warehouse_stock ws ON oi.warehouse_stock_id = ws.id
                LEFT JOIN penerimaan_detail pd ON ws.penerimaan_detail_id = pd.id
                GROUP BY oi.order_id
            )";
    }
    
    /**
     * Generate CTE untuk order_totals
     * Menggabungkan semua metrics: saldo_masuk, nominal, HPP, volume, gross profit
     * 
     * @return string SQL CTE
     */
    public static function orderTotalsCTE(): string
    {
        return "
            order_totals AS (
                SELECT 
                    at.order_id,
                    SUM(at.saldo_masuk) as order_total_value,
                    SUM(at.qty) as order_total_volume,
                    COALESCE(oi.total_nominal, 0) as order_total_nominal,
                    COALESCE(hpp.total_hpp, 0) as order_total_hpp,
                    (SUM(at.saldo_masuk) - COALESCE(hpp.total_hpp, 0)) as order_total_gross_profit
                FROM all_transactions at
                LEFT JOIN order_items_nominal oi ON at.order_id = oi.order_id
                LEFT JOIN order_hpp hpp ON at.order_id = hpp.order_id
                GROUP BY at.order_id, oi.total_nominal, hpp.total_hpp
            )";
    }
    
    /**
     * Generate semua base CTE sekaligus
     * Ini adalah method utama yang harus dipanggil oleh semua query analytics
     * 
     * @param array $filters
     * @return string SQL dengan semua CTE
     */
    public static function baseCTE(array $filters = []): string
    {
        return "
            WITH " . self::allTransactionsCTE($filters) . ",
            " . self::orderItemsNominalCTE() . ",
            " . self::orderHppCTE() . ",
            " . self::orderTotalsCTE();
    }
    
    /**
     * Generate base CTE dengan filter retur penjualan
     * Exclude orders yang memiliki retur penjualan
     * 
     * @param array $filters
     * @return string SQL dengan semua CTE + retur filter
     */
    public static function baseCTEWithReturFilter(array $filters = []): string
    {
        $returFilterCTE = "
            orders_with_retur AS (
                SELECT DISTINCT order_id
                FROM retur_penjualan
                WHERE status IN ('draft', 'selesai')
            ),
            filtered_order_totals AS (
                SELECT ot.*
                FROM order_totals ot
                WHERE ot.order_id NOT IN (SELECT order_id FROM orders_with_retur)
            )";
        
        return "
            WITH " . self::allTransactionsCTE($filters) . ",
            " . self::orderItemsNominalCTE() . ",
            " . self::orderHppCTE() . ",
            " . self::orderTotalsCTE() . ",
            {$returFilterCTE}";
    }
}

