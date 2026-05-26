<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Services\Analytics\SalesAnalyticsService;
use Illuminate\Http\Request;

/**
 * SalesAnalyticsController - REFACTORED VERSION
 * 
 * Thin controller - hanya menerima input & return view
 * Semua logika ada di Service layer
 * Semua perhitungan ada di Query layer (SQL)
 */
class SalesAnalyticsController extends Controller
{
    protected $service;
    
    public function __construct(SalesAnalyticsService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Sales by Day of Week Report
     */
    public function salesByDayOfWeekReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'quick_range' => $request->input('quick_range'),
        ];
        
        $data = $this->service->getSalesByDayOfWeek($filters);
        
        return view('analytics.sales_by_day_of_week', [
            'dayOfWeekData' => collect($data['day_of_week_data']),
            'dayOfWeekSummary' => $this->buildDayOfWeekSummary($data['day_of_week_data']),
            'dayNames' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'platformSummary' => collect($data['platform_summary']),
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Sales by Status Day Report
     */
    public function salesByStatusAndDayReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'status_hari' => $request->input('status'),
            'quick_range' => $request->input('quick_range'),
        ];
        
        $data = $this->service->getSalesByStatusDay($filters);
        
        return view('analytics.sales_by_status_day', [
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'selectedStatus' => $filters['status_hari'],
            'allStatuses' => $data['all_statuses'],
            'statusDayMatrix' => $data['status_day_matrix'],
            'statusSummary' => $data['status_summary'],
            'dayOfWeekSummary' => $data['day_of_week_summary'],
            'summary' => $data['summary'],
            'platformSummary' => collect($data['platform_summary']),
        ]);
    }
    
    /**
     * Sales by Date Number Report
     */
    public function salesByDateNumberReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'quick_range' => $request->input('quick_range'),
        ];
        
        $data = $this->service->getSalesByDateNumber($filters);
        
        return view('analytics.sales_by_date_number', [
            'dateNumberData' => $data['date_number_data'],
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'summary' => $data['summary'],
            'platformSummary' => collect($data['platform_summary']),
        ]);
    }
    
    /**
     * Monthly Sales Summary Report
     */
    public function monthlySalesSummaryReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
        ];
        
        $data = $this->service->getMonthlySalesSummary($filters);
        
        return view('analytics.monthly_sales_summary', [
            'monthlyData' => $data['monthly_data'],
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'summary' => $data['summary'],
            'platformSummary' => collect($data['platform_summary']),
        ]);
    }
    
    /**
     * Sales by Platform Report
     */
    public function salesByPlatformReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'sort' => $request->input('sort', 'date_newest'),
        ];
        
        $perPage = 50;
        $page = $request->input('page', 1);
        
        $data = $this->service->getSalesByPlatform($filters, $perPage, $page);
        
        // Create paginator manually
        $orders = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['orders']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.sales_by_platform', [
            'orders' => $orders,
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Sales Detail Report
     */
    public function salesDetailReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'min_qty' => $request->input('min_qty'),
            'max_qty' => $request->input('max_qty'),
            'sort' => $request->input('sort', 'date_newest'),
        ];
        
        $perPage = 25;
        $page = $request->input('page', 1);
        
        $data = $this->service->getSalesDetail($filters, $perPage, $page);
        
        // Load full order data for view (with relationships)
        $orderIds = collect($data['orders'])->pluck('id')->toArray();
        $orders = \App\Models\Order::withoutGlobalScope('mainCategory')
            ->whereIn('id', $orderIds)
            ->with([
                'orderItems.platformProduct.mappingBarang' => function($query) {
                    $query->where('is_active', true);
                },
                'platform'
            ])
            ->get()
            ->sortBy(function($order) use ($orderIds) {
                return array_search($order->id, $orderIds);
            })
            ->values();
        
        // Create paginator
        $paginatedOrders = new \Illuminate\Pagination\LengthAwarePaginator(
            $orders,
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.sales_detail_report', [
            'orders' => $paginatedOrders,
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Internal Product Sales Report
     */
    public function internalProductSalesReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'sort' => $request->input('sort', 'qty_highest'),
        ];
        
        $perPage = 25;
        $page = $request->input('page', 1);
        
        $data = $this->service->getInternalProductSales($filters, $perPage, $page);
        
        // Create paginator
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['products']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.internal_product_sales', [
            'products' => $products,
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Helper: Build day of week summary for JavaScript charts
     */
    private function buildDayOfWeekSummary(array $dayOfWeekData): array
    {
        $summary = [];
        foreach ($dayOfWeekData as $day) {
            $summary[$day['day_of_week']] = [
                'day_name' => $day['day_name'],
                'total_value' => $day['total_value'],
                'total_nominal' => $day['total_nominal'],
                'total_hpp' => $day['total_hpp'],
                'total_gross_profit' => $day['total_gross_profit'],
                'total_volume' => $day['total_volume'],
                'order_count' => $day['total_orders'],
            ];
        }
        return $summary;
    }
}

