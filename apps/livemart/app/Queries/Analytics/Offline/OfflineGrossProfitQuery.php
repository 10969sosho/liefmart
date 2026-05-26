<?php

namespace App\Queries\Analytics\Offline;

use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * OfflineGrossProfitQuery
 * 
 * Query untuk analytics Offline Gross Profit
 * Calculates profit from offline sales with HPP from penerimaan_detail
 */
class OfflineGrossProfitQuery
{
    /**
     * Build query untuk gross profit detail
     */
    public static function build(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        $invoiceNumber = $filters['invoice_number'] ?? null;
        $poNumber = $filters['po_number'] ?? null;
        $sku = $filters['sku'] ?? null;
        
        $dateFilter = FilterBuilder::saleDateFilter($startDate, $endDate, 'os');
        $customerFilter = FilterBuilder::customerFilter($customerId, 'os');
        
        $invoiceFilter = '';
        if ($invoiceNumber) {
            $invoiceQuoted = DB::getPdo()->quote('%' . $invoiceNumber . '%');
            $invoiceFilter = " AND EXISTS (
                SELECT 1 FROM finance_offline fo 
                WHERE fo.offline_sale_id = os.id 
                AND fo.invoice_number LIKE {$invoiceQuoted}
            )";
        }
        
        $poFilter = '';
        if ($poNumber) {
            $poQuoted = DB::getPdo()->quote('%' . $poNumber . '%');
            $poFilter = " AND os.surat_jalan_number LIKE {$poQuoted}";
        }
        
        $skuFilter = '';
        if ($sku) {
            $skuQuoted = DB::getPdo()->quote('%' . $sku . '%');
            $skuFilter = " AND p.sku LIKE {$skuQuoted}";
        }
        
        return "
            SELECT 
                os.id as sale_id,
                os.sale_date as payment_date,
                os.surat_jalan_number as po_number,
                COALESCE(fo.invoice_number, '-') as invoice_number,
                p.name as product_name,
                p.sku,
                osi.quantity,
                osi.unit_price as payment_per_product,
                os.total_amount as payment_per_invoice,
                CASE 
                    WHEN pd.qty > 0 
                    THEN (pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty
                    ELSE 0
                END as cost_price,
                CASE 
                    WHEN pd.qty > 0 
                    THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                    ELSE 0
                END as total_cost_price,
                CASE 
                    WHEN os.total_amount > 0 
                    THEN (osi.subtotal / os.total_amount) * os.total_amount
                    ELSE osi.subtotal
                END as proportional_payment,
                CASE 
                    WHEN os.total_amount > 0 
                    THEN (osi.subtotal / os.total_amount) * os.total_amount - 
                         CASE 
                             WHEN pd.qty > 0 
                             THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                             ELSE 0
                         END
                    ELSE osi.subtotal - 
                         CASE 
                             WHEN pd.qty > 0 
                             THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                             ELSE 0
                         END
                END as profit_per_invoice,
                osi.unit_price - 
                CASE 
                    WHEN pd.qty > 0 
                    THEN (pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty
                    ELSE 0
                END as profit_per_unit,
                CASE 
                    WHEN osi.unit_price > 0 
                    THEN ((osi.unit_price - 
                        CASE 
                            WHEN pd.qty > 0 
                            THEN (pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty
                            ELSE 0
                        END) / osi.unit_price) * 100
                    ELSE 0
                END as margin_per_unit,
                CASE 
                    WHEN osi.unit_price > 0 
                    THEN ((CASE 
                        WHEN os.total_amount > 0 
                        THEN (osi.subtotal / os.total_amount) * os.total_amount - 
                             CASE 
                                 WHEN pd.qty > 0 
                                 THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                                 ELSE 0
                             END
                        ELSE osi.subtotal - 
                             CASE 
                                 WHEN pd.qty > 0 
                                 THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                                 ELSE 0
                             END
                    END) / osi.unit_price) * 100
                    ELSE 0
                END as margin_per_invoice
            FROM offline_sales os
            INNER JOIN offline_sale_items osi ON os.id = osi.offline_sale_id
            INNER JOIN products p ON osi.product_id = p.id
            LEFT JOIN warehouse_stock ws ON osi.warehouse_stock_id = ws.id
            LEFT JOIN penerimaan_detail pd ON ws.penerimaan_detail_id = pd.id
            LEFT JOIN finance_offline fo ON os.id = fo.offline_sale_id
            LEFT JOIN customers c ON os.customer_id = c.id
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}{$invoiceFilter}{$poFilter}{$skuFilter}
            ORDER BY os.sale_date DESC, os.id DESC";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        $invoiceNumber = $filters['invoice_number'] ?? null;
        $poNumber = $filters['po_number'] ?? null;
        $sku = $filters['sku'] ?? null;
        
        $dateFilter = FilterBuilder::saleDateFilter($startDate, $endDate, 'os');
        $customerFilter = FilterBuilder::customerFilter($customerId, 'os');
        
        $invoiceFilter = '';
        if ($invoiceNumber) {
            $invoiceQuoted = DB::getPdo()->quote('%' . $invoiceNumber . '%');
            $invoiceFilter = " AND EXISTS (
                SELECT 1 FROM finance_offline fo 
                WHERE fo.offline_sale_id = os.id 
                AND fo.invoice_number LIKE {$invoiceQuoted}
            )";
        }
        
        $poFilter = '';
        if ($poNumber) {
            $poQuoted = DB::getPdo()->quote('%' . $poNumber . '%');
            $poFilter = " AND os.surat_jalan_number LIKE {$poQuoted}";
        }
        
        $skuFilter = '';
        if ($sku) {
            $skuQuoted = DB::getPdo()->quote('%' . $sku . '%');
            $skuFilter = " AND p.sku LIKE {$skuQuoted}";
        }
        
        return "
            SELECT 
                COUNT(DISTINCT os.id) as total_sales,
                COALESCE(SUM(os.total_amount), 0) as total_revenue,
                COALESCE(SUM(
                    CASE 
                        WHEN pd.qty > 0 
                        THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                        ELSE 0
                    END
                ), 0) as total_cost,
                COALESCE(SUM(os.total_amount), 0) - COALESCE(SUM(
                    CASE 
                        WHEN pd.qty > 0 
                        THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                        ELSE 0
                    END
                ), 0) as total_profit,
                CASE 
                    WHEN COALESCE(SUM(os.total_amount), 0) > 0 
                    THEN ((COALESCE(SUM(os.total_amount), 0) - COALESCE(SUM(
                        CASE 
                            WHEN pd.qty > 0 
                            THEN ((pd.subtotal - COALESCE(pd.diskon, 0)) / pd.qty) * osi.quantity
                            ELSE 0
                        END
                    ), 0)) / COALESCE(SUM(os.total_amount), 0)) * 100
                    ELSE 0
                END as average_margin
            FROM offline_sales os
            INNER JOIN offline_sale_items osi ON os.id = osi.offline_sale_id
            INNER JOIN products p ON osi.product_id = p.id
            LEFT JOIN warehouse_stock ws ON osi.warehouse_stock_id = ws.id
            LEFT JOIN penerimaan_detail pd ON ws.penerimaan_detail_id = pd.id
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}{$invoiceFilter}{$poFilter}{$skuFilter}";
    }
}

