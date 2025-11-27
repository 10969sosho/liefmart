<?php

namespace App\Services\Analytics;

use App\Queries\Analytics\Sales\SalesByDayOfWeekQuery;
use App\Queries\Analytics\Sales\SalesByStatusDayQuery;
use App\Queries\Analytics\Sales\SalesByDateNumberQuery;
use App\Queries\Analytics\Sales\MonthlySalesSummaryQuery;
use App\Queries\Analytics\Sales\SalesByPlatformQuery;
use App\Queries\Analytics\Sales\SalesDetailQuery;
use App\Queries\Analytics\Sales\InternalProductSalesQuery;
use App\Queries\Analytics\Sales\SalesValueQuery;
use App\Queries\Analytics\Sales\SalesVolumeQuery;
use App\Queries\Analytics\Sales\SingleItemQuery;
use App\Queries\Analytics\Sales\MultipleItemQuery;
use App\Queries\Analytics\Sales\DiscountAnalysisQuery;
use App\Queries\Analytics\Sales\DailySalesQuery;
use App\Queries\Analytics\Sales\SalesValueQuery;
use App\Queries\Analytics\Sales\SalesVolumeQuery;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * SalesAnalyticsService
 * 
 * Service layer untuk Sales Analytics
 * Hanya orchestrator, tidak ada perhitungan PHP
 */
class SalesAnalyticsService
{
    /**
     * Get sales by day of week data
     * 
     * @param array $filters
     * @return array
     */
    public function getSalesByDayOfWeek(array $filters = []): array
    {
        // Validate and prepare filters
        $filters = $this->prepareFilters($filters);
        
        // Get day of week data
        $dayOfWeekQuery = SalesByDayOfWeekQuery::build($filters);
        $dayOfWeekResults = DB::select($dayOfWeekQuery);
        
        // Get summary
        $summaryQuery = SalesByDayOfWeekQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        // Get platform summary
        $platformSummaryQuery = SalesByDayOfWeekQuery::buildPlatformSummary($filters);
        $platformSummaryResults = DB::select($platformSummaryQuery);
        
        // Transform results to array (no calculation, just mapping)
        // Saturday business logic sudah di-handle di SQL (SalesByDayOfWeekQuery)
        $dayOfWeekData = [];
        foreach ($dayOfWeekResults as $row) {
            $dayOfWeekData[(int)$row->day_of_week] = [
                'day_of_week' => (int)$row->day_of_week,
                'total_orders' => (int)$row->total_orders,
                'total_value' => (float)$row->total_value,
                'total_nominal' => (float)$row->total_nominal,
                'total_hpp' => (float)$row->total_hpp,
                'total_gross_profit' => (float)$row->total_gross_profit,
                'total_volume' => (float)$row->total_volume,
                'avg_order_value' => (float)$row->avg_order_value,
                'avg_order_volume' => (float)$row->avg_order_volume,
            ];
        }
        
        // Fill missing days with zeros
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $completeDayOfWeekData = [];
        for ($i = 0; $i < 7; $i++) {
            $data = $dayOfWeekData[$i] ?? [];
            $completeDayOfWeekData[] = [
                'day_name' => $dayNames[$i],
                'day_of_week' => $i,
                'total_orders' => $data['total_orders'] ?? 0,
                'total_value' => $data['total_value'] ?? 0,
                'total_nominal' => $data['total_nominal'] ?? 0,
                'total_hpp' => $data['total_hpp'] ?? 0,
                'total_gross_profit' => $data['total_gross_profit'] ?? 0,
                'total_volume' => $data['total_volume'] ?? 0,
                'avg_order_value' => $data['avg_order_value'] ?? 0,
                'avg_order_volume' => $data['avg_order_volume'] ?? 0,
            ];
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
            'avg_order_volume' => (float)($summaryResult->avg_order_volume ?? 0),
        ];
        
        $platformSummary = [];
        foreach ($platformSummaryResults as $row) {
            $platformSummary[] = [
                'platform' => trim($row->platform_name ?? 'Unknown'),
                'order_count' => (int)$row->order_count,
                'total_value' => (float)$row->total_value,
                'total_nominal' => (float)$row->total_nominal,
                'total_hpp' => (float)$row->total_hpp,
                'total_gross_profit' => (float)$row->total_gross_profit,
                'total_volume' => (float)$row->total_volume,
            ];
        }
        
        return [
            'day_of_week_data' => $completeDayOfWeekData,
            'summary' => $summary,
            'platform_summary' => $platformSummary,
        ];
    }
    
    /**
     * Get sales by status day data
     * 
     * @param array $filters
     * @return array
     */
    public function getSalesByStatusDay(array $filters = []): array
    {
        // Validate and prepare filters
        $filters = $this->prepareFilters($filters);
        
        // Get all unique statuses
        $allStatusesQuery = SalesByStatusDayQuery::buildAllStatuses($filters);
        $allStatusesResults = DB::select($allStatusesQuery);
        $allStatuses = collect($allStatusesResults)->pluck('status')->filter()->unique()->sort()->values()->toArray();
        
        // Get status day matrix
        $statusDayMatrixQuery = SalesByStatusDayQuery::buildStatusDayMatrix($filters);
        $statusDayMatrixResults = DB::select($statusDayMatrixQuery);
        
        // Get status summary
        $statusSummaryQuery = SalesByStatusDayQuery::buildStatusSummary($filters);
        $statusSummaryResults = DB::select($statusSummaryQuery);
        
        // Get day of week summary
        $dayOfWeekSummaryQuery = SalesByStatusDayQuery::buildDayOfWeekSummary($filters);
        $dayOfWeekSummaryResults = DB::select($dayOfWeekSummaryQuery);
        
        // Get overall summary
        $overallSummaryQuery = SalesByStatusDayQuery::buildOverallSummary($filters);
        $overallSummaryResult = DB::selectOne($overallSummaryQuery);
        
        // Get platform summary
        $platformSummaryQuery = SalesByStatusDayQuery::buildPlatformSummary($filters);
        $platformSummaryResults = DB::select($platformSummaryQuery);
        
        // Transform status day matrix
        $statusDayMatrix = [];
        foreach ($allStatuses as $status) {
            $statusDayMatrix[$status] = [];
            for ($i = 0; $i < 7; $i++) {
                $statusDayMatrix[$status][$i] = [
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_nominal' => 0,
                    'total_hpp' => 0,
                    'total_gross_profit' => 0,
                    'total_volume' => 0,
                ];
            }
        }
        
        foreach ($statusDayMatrixResults as $row) {
            $status = trim($row->status ?? '');
            $dayOfWeek = (int)($row->day_of_week ?? 0);
            if (!empty($status) && isset($statusDayMatrix[$status][$dayOfWeek])) {
                $statusDayMatrix[$status][$dayOfWeek] = [
                    'order_count' => (int)($row->order_count ?? 0),
                    'total_value' => (float)($row->total_value ?? 0),
                    'total_nominal' => (float)($row->total_nominal ?? 0),
                    'total_hpp' => (float)($row->total_hpp ?? 0),
                    'total_gross_profit' => (float)($row->total_gross_profit ?? 0),
                    'total_volume' => (float)($row->total_volume ?? 0),
                ];
            }
        }
        
        // Transform status summary
        $statusSummary = [];
        foreach ($statusSummaryResults as $row) {
            $status = trim($row->status ?? '');
            if (!empty($status)) {
                $statusSummary[$status] = [
                    'total_orders' => (int)($row->total_orders ?? 0),
                    'total_value' => (float)($row->total_value ?? 0),
                    'total_nominal' => (float)($row->total_nominal ?? 0),
                    'total_hpp' => (float)($row->total_hpp ?? 0),
                    'total_gross_profit' => (float)($row->total_gross_profit ?? 0),
                    'total_volume' => (float)($row->total_volume ?? 0),
                ];
            }
        }
        
        // Fill missing statuses
        foreach ($allStatuses as $status) {
            if (!isset($statusSummary[$status])) {
                $statusSummary[$status] = [
                    'total_orders' => 0,
                    'total_value' => 0,
                    'total_nominal' => 0,
                    'total_hpp' => 0,
                    'total_gross_profit' => 0,
                    'total_volume' => 0,
                ];
            }
        }
        
        // Transform day of week summary
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dayOfWeekSummary = [];
        for ($i = 0; $i < 7; $i++) {
            $dayOfWeekSummary[$i] = [
                'day_name' => $dayNames[$i],
                'order_count' => 0,
                'total_value' => 0,
                'total_nominal' => 0,
                'total_hpp' => 0,
                'total_gross_profit' => 0,
                'total_volume' => 0,
            ];
        }
        
        foreach ($dayOfWeekSummaryResults as $row) {
            $dayOfWeek = (int)($row->day_of_week ?? 0);
            if (isset($dayOfWeekSummary[$dayOfWeek])) {
                $dayOfWeekSummary[$dayOfWeek] = [
                    'day_name' => $dayNames[$dayOfWeek] ?? 'Unknown',
                    'order_count' => (int)($row->order_count ?? 0),
                    'total_value' => (float)($row->total_value ?? 0),
                    'total_nominal' => (float)($row->total_nominal ?? 0),
                    'total_hpp' => (float)($row->total_hpp ?? 0),
                    'total_gross_profit' => (float)($row->total_gross_profit ?? 0),
                    'total_volume' => (float)($row->total_volume ?? 0),
                ];
            }
        }
        
        // Transform overall summary
        $summary = [
            'total_orders' => (int)($overallSummaryResult->total_filtered_orders ?? 0),
            'total_value' => (float)($overallSummaryResult->total_value ?? 0),
            'total_nominal' => (float)($overallSummaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($overallSummaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($overallSummaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($overallSummaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($overallSummaryResult->avg_order_value ?? 0),
            'avg_order_volume' => (float)($overallSummaryResult->avg_order_volume ?? 0),
            'total_all_orders' => (int)($overallSummaryResult->total_all_orders ?? 0),
            'total_filtered_orders' => (int)($overallSummaryResult->total_filtered_orders ?? 0),
            'percent_filtered' => (int)($overallSummaryResult->total_all_orders ?? 0) > 0 
                ? round((($overallSummaryResult->total_filtered_orders ?? 0) / ($overallSummaryResult->total_all_orders ?? 1)) * 100, 1) 
                : 0,
        ];
        
        // Transform platform summary
        $platformSummary = [];
        foreach ($platformSummaryResults as $row) {
            $totalValue = (float)($row->total_value ?? 0);
            $totalHpp = (float)($row->total_hpp ?? 0);
            $platformSummary[] = [
                'platform' => trim($row->platform ?? 'Unknown'),
                'order_count' => (int)($row->order_count ?? 0),
                'total_value' => $totalValue,
                'total_nominal' => (float)($row->total_nominal ?? 0),
                'total_hpp' => $totalHpp,
                'total_gross_profit' => (float)($row->total_gross_profit ?? 0),
                'total_volume' => (float)($row->total_volume ?? 0),
            ];
        }
        
        return [
            'all_statuses' => $allStatuses,
            'status_day_matrix' => $statusDayMatrix,
            'status_summary' => $statusSummary,
            'day_of_week_summary' => $dayOfWeekSummary,
            'summary' => $summary,
            'platform_summary' => $platformSummary,
        ];
    }
    
    /**
     * Get sales by date number data
     */
    public function getSalesByDateNumber(array $filters = []): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = SalesByDateNumberQuery::build($filters);
        $results = DB::select($query);
        
        $summaryQuery = SalesByDateNumberQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $platformSummaryQuery = SalesByDateNumberQuery::buildPlatformSummary($filters);
        $platformSummaryResults = DB::select($platformSummaryQuery);
        
        // Transform results
        $dateNumberData = [];
        for ($i = 1; $i <= 31; $i++) {
            $dateKey = sprintf('%02d', $i);
            $dateNumberData[$dateKey] = [
                'date_number' => $dateKey,
                'total_orders' => 0,
                'total_value' => 0,
                'total_nominal' => 0,
                'total_hpp' => 0,
                'total_gross_profit' => 0,
                'total_volume' => 0,
                'avg_order_value' => 0,
            ];
        }
        
        foreach ($results as $row) {
            $dateKey = sprintf('%02d', (int)$row->date_number);
            if (isset($dateNumberData[$dateKey])) {
                $dateNumberData[$dateKey] = [
                    'date_number' => $dateKey,
                    'total_orders' => (int)$row->total_orders,
                    'total_value' => (float)$row->total_value,
                    'total_nominal' => (float)$row->total_nominal,
                    'total_hpp' => (float)$row->total_hpp,
                    'total_gross_profit' => (float)$row->total_gross_profit,
                    'total_volume' => (float)$row->total_volume,
                    'avg_order_value' => (float)$row->avg_order_value,
                ];
            }
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
        ];
        
        $platformSummary = [];
        foreach ($platformSummaryResults as $row) {
            $platformSummary[] = [
                'platform' => trim($row->platform_name ?? 'Unknown'),
                'order_count' => (int)$row->order_count,
                'total_value' => (float)$row->total_value,
                'total_nominal' => (float)$row->total_nominal,
                'total_hpp' => (float)$row->total_hpp,
                'total_gross_profit' => (float)$row->total_gross_profit,
                'total_volume' => (float)$row->total_volume,
            ];
        }
        
        return [
            'date_number_data' => array_values($dateNumberData),
            'summary' => $summary,
            'platform_summary' => $platformSummary,
        ];
    }
    
    /**
     * Get monthly sales summary data
     */
    public function getMonthlySalesSummary(array $filters = []): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = MonthlySalesSummaryQuery::build($filters);
        $results = DB::select($query);
        
        $summaryQuery = MonthlySalesSummaryQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $platformSummaryQuery = MonthlySalesSummaryQuery::buildPlatformSummary($filters);
        $platformSummaryResults = DB::select($platformSummaryQuery);
        
        $monthlyData = [];
        foreach ($results as $row) {
            $monthlyData[] = [
                'month_key' => $row->month_key,
                'month_name' => $row->month_name,
                'year' => (int)$row->year,
                'month' => (int)$row->month,
                'total_orders' => (int)$row->total_orders,
                'total_value' => (float)$row->total_value,
                'total_nominal' => (float)$row->total_nominal,
                'total_hpp' => (float)$row->total_hpp,
                'total_gross_profit' => (float)$row->total_gross_profit,
                'total_volume' => (float)$row->total_volume,
                'avg_order_value' => (float)$row->avg_order_value,
                'avg_order_volume' => (float)$row->avg_order_volume,
            ];
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
            'avg_order_volume' => (float)($summaryResult->avg_order_volume ?? 0),
        ];
        
        $platformSummary = [];
        foreach ($platformSummaryResults as $row) {
            $platformSummary[] = [
                'platform' => trim($row->platform_name ?? 'Unknown'),
                'order_count' => (int)$row->order_count,
                'total_value' => (float)$row->total_value,
                'total_nominal' => (float)$row->total_nominal,
                'total_hpp' => (float)$row->total_hpp,
                'total_gross_profit' => (float)$row->total_gross_profit,
                'total_volume' => (float)$row->total_volume,
            ];
        }
        
        return [
            'monthly_data' => $monthlyData,
            'summary' => $summary,
            'platform_summary' => $platformSummary,
        ];
    }
    
    /**
     * Get sales by platform data
     */
    public function getSalesByPlatform(array $filters = [], int $perPage = 50, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = SalesByPlatformQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = SalesByPlatformQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = SalesByPlatformQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $orders = [];
        foreach ($results as $row) {
            $orders[] = [
                'id' => (int)$row->id,
                'order_number' => $row->order_number,
                'tanggal' => $row->tanggal,
                'platform_id' => (int)$row->platform_id,
                'platform_name' => $row->platform_name,
                'total_value' => (float)$row->total_value,
                'total_nominal' => (float)$row->total_nominal,
                'total_hpp' => (float)$row->total_hpp,
                'total_gross_profit' => (float)$row->total_gross_profit,
                'total_volume' => (float)$row->total_volume,
            ];
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'orders_with_transactions' => (int)($summaryResult->orders_with_transactions ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'total_returns' => (int)($summaryResult->total_returns ?? 0),
            'orders_with_returns' => (int)($summaryResult->orders_with_returns ?? 0),
        ];
        
        return [
            'orders' => $orders,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get sales detail data
     */
    public function getSalesDetail(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = SalesDetailQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = SalesDetailQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = SalesDetailQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $orders = [];
        foreach ($results as $row) {
            $orders[] = [
                'id' => (int)$row->id,
                'order_number' => $row->order_number,
                'tanggal' => $row->tanggal,
                'platform_id' => (int)$row->platform_id,
                'platform_name' => $row->platform_name,
                'total_value' => (float)$row->total_value,
                'total_volume' => (float)$row->total_volume,
            ];
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
            'avg_order_volume' => (float)($summaryResult->avg_order_volume ?? 0),
            'total_orders_all' => (int)($summaryResult->total_orders_all ?? 0),
            'total_returns' => (int)($summaryResult->total_returns ?? 0),
            'orders_with_returns' => (int)($summaryResult->orders_with_returns ?? 0),
        ];
        
        return [
            'orders' => $orders,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get internal product sales data
     */
    public function getInternalProductSales(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = InternalProductSalesQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = InternalProductSalesQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = InternalProductSalesQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $products = [];
        foreach ($results as $row) {
            $products[] = [
                'product_id' => (int)$row->product_id,
                'product_name' => $row->product_name,
                'product_sku' => $row->product_sku,
                'order_count' => (int)$row->order_count,
                'total_qty' => (float)$row->total_qty,
                'total_value' => (float)$row->total_value,
            ];
        }
        
        $summary = [
            'total_products' => (int)($summaryResult->total_products ?? 0),
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_qty' => (float)($summaryResult->total_qty ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
        ];
        
        return [
            'products' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Prepare and validate filters
     * 
     * @param array $filters
     * @return array
     */
    private function prepareFilters(array $filters): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        
        // Set default to today if not provided
        if (!$startDate) {
            $startDate = Carbon::today()->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = Carbon::today()->format('Y-m-d');
        }
        
        // Handle quick_range
        if (isset($filters['quick_range'])) {
            $endDate = Carbon::today()->format('Y-m-d');
            switch ($filters['quick_range']) {
                case '7days':
                    $startDate = Carbon::today()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = Carbon::today()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = Carbon::today()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = Carbon::today()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }
        
        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $filters['platform_id'] ?? null,
            'status_hari' => $filters['status_hari'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'min_qty' => $filters['min_qty'] ?? null,
            'max_qty' => $filters['max_qty'] ?? null,
            'sort' => $filters['sort'] ?? null,
            'min_discount' => $filters['min_discount'] ?? null,
            'max_discount' => $filters['max_discount'] ?? null,
        ];
    }
    
    /**
     * Get single item orders
     */
    public function getSingleItemOrders(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = SingleItemQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = SingleItemQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = SingleItemQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $orders = [];
        foreach ($results as $row) {
            $orders[] = (array)$row;
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
        ];
        
        return [
            'orders' => $orders,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get multiple item orders
     */
    public function getMultipleItemOrders(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = MultipleItemQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = MultipleItemQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = MultipleItemQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $orders = [];
        foreach ($results as $row) {
            $orders[] = (array)$row;
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_value' => (float)($summaryResult->total_value ?? 0),
            'total_nominal' => (float)($summaryResult->total_nominal ?? 0),
            'total_hpp' => (float)($summaryResult->total_hpp ?? 0),
            'total_gross_profit' => (float)($summaryResult->total_gross_profit ?? 0),
            'total_volume' => (float)($summaryResult->total_volume ?? 0),
            'avg_items_per_order' => (float)($summaryResult->avg_items_per_order ?? 0),
            'avg_order_value' => (float)($summaryResult->avg_order_value ?? 0),
        ];
        
        return [
            'orders' => $orders,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get discount analysis
     */
    public function getDiscountAnalysis(array $filters = [], int $perPage = 25, int $page = 1): array
    {
        $filters = $this->prepareFilters($filters);
        
        $query = DiscountAnalysisQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = DiscountAnalysisQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = DiscountAnalysisQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $platformSummaryQuery = DiscountAnalysisQuery::buildPlatformSummary($filters);
        $platformSummaryResults = DB::select($platformSummaryQuery);
        
        $items = [];
        foreach ($results as $row) {
            $items[] = (array)$row;
        }
        
        $platformSummary = [];
        foreach ($platformSummaryResults as $row) {
            $platformSummary[] = (array)$row;
        }
        
        $summary = [
            'total_orders' => (int)($summaryResult->total_orders ?? 0),
            'total_items' => (int)($summaryResult->total_items ?? 0),
            'total_before_discount' => (float)($summaryResult->total_before_discount ?? 0),
            'total_after_discount' => (float)($summaryResult->total_after_discount ?? 0),
            'total_discount' => (float)($summaryResult->total_discount ?? 0),
            'avg_discount_percentage' => (float)($summaryResult->avg_discount_percentage ?? 0),
            'avg_item_discount_percentage' => (float)($summaryResult->avg_item_discount_percentage ?? 0),
        ];
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
            'platform_summary' => $platformSummary,
        ];
    }
}

