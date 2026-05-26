<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Services\Analytics\GrossProfitAnalyticsService;
use App\Services\Analytics\OfflineSalesAnalyticsService;
use Illuminate\Http\Request;

/**
 * GrossProfitAnalyticsController - REFACTORED VERSION
 * 
 * Thin controller - hanya menerima input & return view
 */
class GrossProfitAnalyticsController extends Controller
{
    protected $grossProfitService;
    protected $offlineService;
    
    public function __construct(
        GrossProfitAnalyticsService $grossProfitService,
        OfflineSalesAnalyticsService $offlineService
    ) {
        $this->grossProfitService = $grossProfitService;
        $this->offlineService = $offlineService;
    }
    
    /**
     * Sales by Platform Product Report (Gross Profit)
     */
    public function salesByPlatformProductReport(Request $request)
    {
        $platforms = Platform::all();
        $productCategories = \App\Models\ProductCategory::orderBy('name')->get();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'order_number' => $request->input('order_number'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'revenue_highest'),
        ];
        
        $perPage = 10;
        $page = $request->input('page', 1);
        
        $data = $this->grossProfitService->getSalesByPlatformProduct($filters, $perPage, $page);
        
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['products']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.sales_by_platform_product', [
            'platformProductRows' => $products,
            'platforms' => $platforms,
            'productCategories' => $productCategories,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
            'search' => $filters['search'],
            'orderNumber' => $filters['order_number'],
        ]);
    }
    
    /**
     * Gross Profit Report (Online)
     */
    public function grossProfitReport(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'sort' => $request->input('sort', 'date_newest'),
        ];
        
        $perPage = 25;
        $page = $request->input('page', 1);
        
        $data = $this->grossProfitService->getGrossProfitReport($filters, $perPage, $page);
        
        $orders = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['orders']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.gross_profit_report', [
            'orders' => $orders,
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
            'platformSummary' => collect($data['platform_summary']),
        ]);
    }
    
    /**
     * Gross Profit Offline Report
     */
    public function grossProfitOfflineReport(Request $request)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        $filters = [
            'start_date' => $request->input('start_date', date('Y-m-01')),
            'end_date' => $request->input('end_date', date('Y-m-d')),
            'customer_id' => $request->input('customer_id'),
            'invoice_number' => $request->input('invoice_number'),
            'po_number' => $request->input('po_number'),
            'sku' => $request->input('sku'),
        ];
        
        $data = $this->offlineService->getGrossProfit($filters);
        
        $profitData = collect($data['profit_data']);
        
        return view('analytics.gross_profit_offline', [
            'profitData' => $profitData,
            'customers' => $customers,
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'selectedInvoice' => $filters['invoice_number'],
            'selectedPO' => $filters['po_number'],
            'selectedSKU' => $filters['sku'],
            'selectedCustomer' => $filters['customer_id'],
            'totalSales' => $data['summary']['total_sales'],
            'totalRevenue' => $data['summary']['total_revenue'],
            'totalProfit' => $data['summary']['total_profit'],
            'averageMargin' => $data['summary']['average_margin'],
        ]);
    }
}

