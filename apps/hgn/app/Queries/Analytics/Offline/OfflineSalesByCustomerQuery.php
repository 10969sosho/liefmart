<?php

namespace App\Queries\Analytics\Offline;

use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * OfflineSalesByCustomerQuery
 * 
 * Query untuk analytics Offline Sales by Customer
 */
class OfflineSalesByCustomerQuery
{
    /**
     * Build query untuk sales by customer
     */
    public static function build(array $filters = [], string $sortBy = 'value_highest'): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        
        $dateFilter = FilterBuilder::saleDateFilter($startDate, $endDate, 'os');
        $customerFilter = FilterBuilder::customerFilter($customerId, 'os');
        
        $orderBy = self::buildOrderBy($sortBy);
        
        return "
            SELECT 
                os.customer_id,
                c.name as customer_name,
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
            LEFT JOIN customers c ON os.customer_id = c.id
            LEFT JOIN offline_sale_items osi ON os.id = osi.offline_sale_id
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}
            GROUP BY os.customer_id, c.name
            {$orderBy}";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        
        $dateFilter = FilterBuilder::saleDateFilter($startDate, $endDate, 'os');
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
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}";
    }
    
    /**
     * Build ORDER BY clause
     */
    private static function buildOrderBy(string $sortBy): string
    {
        switch ($sortBy) {
            case 'value_lowest':
                return 'ORDER BY total_value ASC';
            case 'volume_highest':
                return 'ORDER BY total_volume DESC';
            case 'volume_lowest':
                return 'ORDER BY total_volume ASC';
            case 'orders_highest':
                return 'ORDER BY total_orders DESC';
            case 'orders_lowest':
                return 'ORDER BY total_orders ASC';
            case 'name_asc':
                return 'ORDER BY customer_name ASC';
            case 'name_desc':
                return 'ORDER BY customer_name DESC';
            case 'value_highest':
            default:
                return 'ORDER BY total_value DESC';
        }
    }
}

