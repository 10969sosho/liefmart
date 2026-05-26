<?php

namespace App\Queries\Analytics\Offline;

use Shared\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * OfflineSalesByProductQuery
 * 
 * Query untuk analytics Offline Sales by Product
 */
class OfflineSalesByProductQuery
{
    /**
     * Build query untuk sales by product
     */
    public static function build(array $filters = [], string $sortBy = 'quantity_highest'): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $customerId = $filters['customer_id'] ?? null;
        
        $dateFilter = FilterBuilder::saleDateFilter($startDate, $endDate, 'os');
        $customerFilter = FilterBuilder::customerFilter($customerId, 'os');
        
        $orderBy = self::buildOrderBy($sortBy);
        
        return "
            SELECT 
                p.id as product_id,
                p.name as product_name,
                p.sku as product_sku,
                COUNT(DISTINCT os.id) as total_orders,
                COALESCE(SUM(osi.quantity), 0) as total_quantity,
                COALESCE(SUM(osi.subtotal), 0) as total_value
            FROM products p
            INNER JOIN offline_sale_items osi ON p.id = osi.product_id
            INNER JOIN offline_sales os ON osi.offline_sale_id = os.id
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}
            GROUP BY p.id, p.name, p.sku
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
                COUNT(DISTINCT p.id) as total_products,
                COUNT(DISTINCT os.id) as total_orders,
                COALESCE(SUM(osi.quantity), 0) as total_quantity,
                COALESCE(SUM(osi.subtotal), 0) as total_value
            FROM products p
            INNER JOIN offline_sale_items osi ON p.id = osi.product_id
            INNER JOIN offline_sales os ON osi.offline_sale_id = os.id
            WHERE os.status = 'paid'{$dateFilter}{$customerFilter}";
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
            case 'orders_highest':
                return 'ORDER BY total_orders DESC';
            case 'orders_lowest':
                return 'ORDER BY total_orders ASC';
            case 'name_asc':
                return 'ORDER BY product_name ASC';
            case 'name_desc':
                return 'ORDER BY product_name DESC';
            case 'quantity_highest':
            default:
                return 'ORDER BY total_quantity DESC';
        }
    }
}

