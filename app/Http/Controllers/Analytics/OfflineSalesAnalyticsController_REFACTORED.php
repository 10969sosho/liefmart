<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\OfflineSalesAnalyticsService;
use Illuminate\Http\Request;

/**
 * OfflineSalesAnalyticsController - REFACTORED VERSION
 * 
 * Thin controller - hanya menerima input & return view
 */
class OfflineSalesAnalyticsController extends Controller
{
    protected $service;
    
    public function __construct(OfflineSalesAnalyticsService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Offline Monthly Sales Summary Report
     */
    public function offlineMonthlySalesSummaryReport(Request $request)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        $filters = [
            'year' => $request->input('year', date('Y')),
            'customer_id' => $request->input('customer_id'),
        ];
        
        $data = $this->service->getMonthlySummary($filters);
        
        $currentYear = date('Y');
        $availableYears = [];
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $availableYears[$year] = $year;
        }
        
        return view('analytics.offline_monthly_sales_summary', [
            'monthlySummary' => collect($data['monthly_data']),
            'yearSummary' => $data['summary'],
            'selectedYear' => $filters['year'],
            'selectedCustomer' => $filters['customer_id'],
            'availableYears' => $availableYears,
            'customers' => $customers,
        ]);
    }
    
    /**
     * Offline Sales by Customer Report
     */
    public function offlineSalesByCustomerReport(Request $request)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        $filters = [
            'start_date' => $request->input('start_date', date('Y-m-01')),
            'end_date' => $request->input('end_date', date('Y-m-d')),
            'customer_id' => $request->input('customer_id'),
        ];
        
        $sortBy = $request->input('sort', 'value_highest');
        
        $data = $this->service->getSalesByCustomer($filters, $sortBy);
        
        return view('analytics.offline_sales_by_customer', [
            'customerSummary' => collect($data['customers']),
            'customers' => $customers,
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'selectedCustomer' => $filters['customer_id'],
            'sortBy' => $sortBy,
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Offline Sales Detail Report
     */
    public function offlineSalesDetailReport(Request $request)
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
        
        $perPage = 25;
        $page = $request->input('page', 1);
        
        $data = $this->service->getSalesDetail($filters, $perPage, $page);
        
        $saleIds = collect($data['sales'])->pluck('id')->toArray();
        $sales = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->whereIn('id', $saleIds)
            ->with(['items', 'items.product', 'customerInfo'])
            ->get()
            ->sortBy(function($sale) use ($saleIds) {
                return array_search($sale->id, $saleIds);
            })
            ->values();
        
        $paginatedSales = new \Illuminate\Pagination\LengthAwarePaginator(
            $sales,
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.offline_sales_detail_report', [
            'sales' => $paginatedSales,
            'customers' => $customers,
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'selectedCustomer' => $filters['customer_id'],
            'selectedInvoice' => $filters['invoice_number'],
            'selectedPO' => $filters['po_number'],
            'selectedSKU' => $filters['sku'],
        ]);
    }
    
    /**
     * Offline Sales by Product Report
     */
    public function offlineSalesByProductReport(Request $request)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        $filters = [
            'start_date' => $request->input('start_date', date('Y-m-01')),
            'end_date' => $request->input('end_date', date('Y-m-d')),
            'customer_id' => $request->input('customer_id'),
        ];
        
        $sortBy = $request->input('sort', 'quantity_highest');
        
        $data = $this->service->getSalesByProduct($filters, $sortBy);
        
        return view('analytics.offline_sales_by_product', [
            'products' => collect($data['products']),
            'customers' => $customers,
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'selectedCustomer' => $filters['customer_id'],
            'sortBy' => $sortBy,
            'summary' => $data['summary'],
        ]);
    }
}

