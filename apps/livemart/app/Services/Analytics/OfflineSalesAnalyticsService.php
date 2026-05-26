<?php

namespace App\Services\Analytics;

use App\Queries\Analytics\Offline\OfflineMonthlySummaryQuery;
use App\Queries\Analytics\Offline\OfflineSalesByCustomerQuery;
use App\Queries\Analytics\Offline\OfflineSalesDetailQuery;
use App\Queries\Analytics\Offline\OfflineSalesByProductQuery;
use App\Queries\Analytics\Offline\OfflineGrossProfitQuery;
use Illuminate\Support\Facades\DB;

/**
 * OfflineSalesAnalyticsService
 * 
 * Service layer untuk Offline Sales Analytics
 * Hanya orchestrator, tidak ada perhitungan PHP
 */
class OfflineSalesAnalyticsService
{
    /**
     * Get offline monthly sales summary
     */
    public function getMonthlySummary(array $filters = []): array
    {
        $query = OfflineMonthlySummaryQuery::build($filters);
        $results = DB::select($query);
        
        $summaryQuery = OfflineMonthlySummaryQuery::buildYearSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthName = date('F', mktime(0, 0, 0, $month, 1, $filters['year'] ?? date('Y')));
            $monthlyData[$month] = [
                'month' => $month,
                'month_name' => $monthName,
                'total_orders' => 0,
                'total_value' => 0,
                'total_volume' => 0,
                'avg_order_value' => 0,
                'avg_order_volume' => 0,
            ];
        }
        
        foreach ($results as $row) {
            $month = (int)$row->month;
            if (isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'month' => $month,
                    'month_name' => $row->month_name,
                    'total_orders' => (int)$row->total_orders,
                    'total_value' => (float)$row->total_value,
                    'total_volume' => (float)$row->total_volume,
                    'avg_order_value' => (float)$row->avg_order_value,
                    'avg_order_volume' => (float)$row->avg_order_volume,
                ];
            }
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
            'avg_order_volume' => (float)($summaryResult->avg_order_volume ?? 0),
        ];
        
        return [
            'monthly_data' => array_values($monthlyData),
            'summary' => $summary,
        ];
    }
    
    /**
     * Get offline sales by customer
     */
    public function getSalesByCustomer(array $filters = [], string $sortBy = 'value_highest'): array
    {
        $query = OfflineSalesByCustomerQuery::build($filters, $sortBy);
        $results = DB::select($query);
        
        $summaryQuery = OfflineSalesByCustomerQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $customers = [];
        foreach ($results as $row) {
            $customers[] = [
                'customer_id' => (int)$row->customer_id,
                'customer_name' => $row->customer_name ?? 'Unknown',
                'total_orders' => (int)$row->total_orders,
                'total_value' => (float)$row->total_value,
                'total_volume' => (float)$row->total_volume,
                'avg_order_value' => (float)$row->avg_order_value,
                'avg_order_volume' => (float)$row->avg_order_volume,
            ];
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
            'avg_order_volume' => (float)($summaryResult->avg_order_volume ?? 0),
        ];
        
        return [
            'customers' => $customers,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get offline sales detail
     */
    public function getSalesDetail(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $query = OfflineSalesDetailQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = OfflineSalesDetailQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $sales = [];
        foreach ($results as $row) {
            $sales[] = [
                'id' => (int)$row->id,
                'surat_jalan_number' => $row->surat_jalan_number,
                'sale_date' => $row->sale_date,
                'customer_id' => (int)$row->customer_id,
                'customer_name' => $row->customer_name ?? 'Unknown',
                'total_amount' => (float)$row->total_amount,
                'status' => $row->status,
            ];
        }
        
        return [
            'sales' => $sales,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
        ];
    }
    
    /**
     * Get offline sales by product
     */
    public function getSalesByProduct(array $filters = [], string $sortBy = 'quantity_highest'): array
    {
        $query = OfflineSalesByProductQuery::build($filters, $sortBy);
        $results = DB::select($query);
        
        $summaryQuery = OfflineSalesByProductQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $products = [];
        foreach ($results as $row) {
            $products[] = [
                'product_id' => (int)$row->product_id,
                'product_name' => $row->product_name,
                'product_sku' => $row->product_sku,
                'total_orders' => (int)$row->total_orders,
                'total_quantity' => (float)$row->total_quantity,
                'total_value' => (float)$row->total_value,
            ];
        }
        
        $summary = [
            'total_products' => (int)($summaryResult->total_products ?? 0),
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_quantity' => (float)($summaryResult->total_quantity ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
        ];
        
        return [
            'products' => $products,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get offline gross profit
     */
    public function getGrossProfit(array $filters = []): array
    {
        $query = OfflineGrossProfitQuery::build($filters);
        $results = DB::select($query);
        
        $summaryQuery = OfflineGrossProfitQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $profitData = [];
        foreach ($results as $row) {
            $profitData[] = [
                'sale_id' => (int)$row->sale_id,
                'payment_date' => $row->payment_date,
                'po_number' => $row->po_number,
                'invoice_number' => $row->invoice_number,
                'product_name' => $row->product_name,
                'sku' => $row->sku,
                'quantity' => (float)$row->quantity,
                'payment_per_product' => (float)$row->payment_per_product,
                'payment_per_invoice' => (float)$row->payment_per_invoice,
                'cost_price' => (float)$row->cost_price,
                'total_cost_price' => (float)$row->total_cost_price,
                'profit_per_unit' => (float)$row->profit_per_unit,
                'profit_per_invoice' => (float)$row->profit_per_invoice,
                'margin_per_unit' => (float)$row->margin_per_unit,
                'margin_per_invoice' => (float)$row->margin_per_invoice,
            ];
        }
        
        $summary = [
            'total_sales' => (int)($summaryResult->total_sales ?? 0),
            'total_revenue' => (float)($summaryResult->total_revenue ?? 0),
            'total_cost' => (float)($summaryResult->total_cost ?? 0),
            'total_profit' => (float)($summaryResult->total_profit ?? 0),
            'average_margin' => (float)($summaryResult->average_margin ?? 0),
        ];
        
        return [
            'profit_data' => $profitData,
            'summary' => $summary,
        ];
    }
}

