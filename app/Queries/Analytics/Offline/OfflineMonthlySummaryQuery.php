<?php

namespace App\Queries\Analytics\Offline;

use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * OfflineMonthlySummaryQuery
 * 
 * Query untuk analytics Offline Monthly Sales Summary
 */
class OfflineMonthlySummaryQuery
{
    /**
     * Build query untuk monthly summary
     */
    public static function build(array $filters = []): string
    {
        $year = $filters['year'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        
        $yearFilter = FilterBuilder::yearFilter($year, 'os');
        $customerFilter = FilterBuilder::customerFilter($customerId, 'os');
        
        return "
            SELECT 
                MONTH(os.sale_date) as month,
                DATE_FORMAT(os.sale_date, '%M') as month_name,
                COUNT(DISTINCT os.id) as total_orders,
                COALESCE(SUM(os.total_amount), 0) as total_value,
                COALESCE(SUM(osi.quantity), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT os.id) > 0 
                    THEN COALESCE(SUM(os.total_amount), 0) / COUNT(DISTINCT os.id)
                    ELSE 0
                END as avg_order_value,
                CASE 
                    WHEN COUNT(DISTINCT os.id) > 0 
                    THEN COALESCE(SUM(osi.quantity), 0) / COUNT(DISTINCT os.id)
                    ELSE 0
                END as avg_order_volume
            FROM offline_sales os
            LEFT JOIN offline_sale_items osi ON os.id = osi.offline_sale_id
            WHERE os.status = 'paid'{$yearFilter}{$customerFilter}
            GROUP BY MONTH(os.sale_date), DATE_FORMAT(os.sale_date, '%M')
            ORDER BY month";
    }
    
    /**
     * Build query untuk year summary
     */
    public static function buildYearSummary(array $filters = []): string
    {
        $year = $filters['year'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        
        $yearFilter = FilterBuilder::yearFilter($year, 'os');
        $customerFilter = FilterBuilder::customerFilter($customerId, 'os');
        
        return "
            SELECT 
                COUNT(DISTINCT os.id) as total_orders,
                COALESCE(SUM(os.total_amount), 0) as total_value,
                COALESCE(SUM(osi.quantity), 0) as total_volume,
                CASE 
                    WHEN COUNT(DISTINCT os.id) > 0 
                    THEN COALESCE(SUM(os.total_amount), 0) / COUNT(DISTINCT os.id)
                    ELSE 0
                END as avg_order_value,
                CASE 
                    WHEN COUNT(DISTINCT os.id) > 0 
                    THEN COALESCE(SUM(osi.quantity), 0) / COUNT(DISTINCT os.id)
                    ELSE 0
                END as avg_order_volume
            FROM offline_sales os
            LEFT JOIN offline_sale_items osi ON os.id = osi.offline_sale_id
            WHERE os.status = 'paid'{$yearFilter}{$customerFilter}";
    }
}

