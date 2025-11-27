<?php

namespace App\Queries\Analytics\Offline;

use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * OfflineSalesDetailQuery
 * 
 * Query untuk analytics Offline Sales Detail
 */
class OfflineSalesDetailQuery
{
    /**
     * Build query untuk sales detail
     */
    public static function build(array $filters = [], int $perPage = 25, int $page = 1): string
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
            $skuFilter = " AND EXISTS (
                SELECT 1 FROM offline_sale_items osi 
                INNER JOIN products p ON osi.product_id = p.id
                WHERE osi.offline_sale_id = os.id 
                AND p.sku LIKE {$skuQuoted}
            )";
        }
        
        $offset = ($page - 1) * $perPage;
        
        return "
            SELECT 
                os.id,
                os.surat_jalan_number,
                os.sale_date,
                os.customer_id,
                c.name as customer_name,
                os.total_amount,
                os.status
            FROM offline_sales os
            LEFT JOIN customers c ON os.customer_id = c.id
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}{$invoiceFilter}{$poFilter}{$skuFilter}
            ORDER BY os.sale_date DESC
            LIMIT {$perPage} OFFSET {$offset}";
    }
    
    /**
     * Build query untuk count
     */
    public static function buildCount(array $filters = []): string
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
            $skuFilter = " AND EXISTS (
                SELECT 1 FROM offline_sale_items osi 
                INNER JOIN products p ON osi.product_id = p.id
                WHERE osi.offline_sale_id = os.id 
                AND p.sku LIKE {$skuQuoted}
            )";
        }
        
        return "
            SELECT COUNT(*) as total
            FROM offline_sales os
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}{$invoiceFilter}{$poFilter}{$skuFilter}";
    }
}

