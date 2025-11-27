<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\FinanceAnalyticsService;
use Illuminate\Http\Request;

/**
 * FinanceAnalyticsController - REFACTORED VERSION
 * 
 * Thin controller - hanya menerima input & return view
 */
class FinanceAnalyticsController extends Controller
{
    protected $service;
    
    public function __construct(FinanceAnalyticsService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Shopee Analytics
     */
    public function shopeeAnalytics(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'from_order_date' => $request->input('from_order_date'),
            'to_order_date' => $request->input('to_order_date'),
            'order_number' => $request->input('order_number'),
            'invoice_number' => $request->input('invoice_number'),
            'tax_id' => $request->input('tax_id'),
            'payment_date' => $request->input('payment_date'),
            'min_nominal' => $request->input('min_nominal'),
            'max_nominal' => $request->input('max_nominal'),
            'outstanding_status' => $request->input('outstanding_status'),
        ];
        
        $perPage = 15;
        $page = $request->input('page', 1);
        
        $data = $this->service->getShopeeAnalytics($filters, $perPage, $page);
        
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['transactions']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $groupedTransactions = $transactions->groupBy('no_order');
        
        return view('analytics.finance.shopee', [
            'transactions' => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'platform' => 'shopee',
            'totalCount' => $data['summary']['total_count'],
            'totalNominalFix' => $data['summary']['total_nominal_fix'],
            'totalSaldoMasuk' => $data['summary']['total_saldo_masuk'],
            'totalOutstanding' => $data['summary']['total_outstanding'],
        ]);
    }
    
    /**
     * Tokopedia Analytics
     */
    public function tokopediaAnalytics(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'from_order_date' => $request->input('from_order_date'),
            'to_order_date' => $request->input('to_order_date'),
            'order_number' => $request->input('order_number'),
            'invoice_number' => $request->input('invoice_number'),
            'tax_id' => $request->input('tax_id'),
            'payment_date' => $request->input('payment_date'),
            'min_nominal' => $request->input('min_nominal'),
            'max_nominal' => $request->input('max_nominal'),
            'outstanding_status' => $request->input('outstanding_status'),
        ];
        
        $perPage = 15;
        $page = $request->input('page', 1);
        
        $data = $this->service->getTokopediaAnalytics($filters, $perPage, $page);
        
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['transactions']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $groupedTransactions = $transactions->groupBy('no_order');
        
        return view('analytics.finance.tokopedia', [
            'transactions' => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'platform' => 'tokopedia',
            'totalCount' => $data['summary']['total_count'],
            'totalNominalFix' => $data['summary']['total_nominal_fix'],
            'totalSaldoMasuk' => $data['summary']['total_saldo_masuk'],
            'totalOutstanding' => $data['summary']['total_outstanding'],
        ]);
    }
    
    /**
     * TikTok Analytics
     */
    public function tiktokAnalytics(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'from_order_date' => $request->input('from_order_date'),
            'to_order_date' => $request->input('to_order_date'),
            'order_number' => $request->input('order_number'),
            'invoice_number' => $request->input('invoice_number'),
            'tax_id' => $request->input('tax_id'),
            'payment_date' => $request->input('payment_date'),
            'min_nominal' => $request->input('min_nominal'),
            'max_nominal' => $request->input('max_nominal'),
            'outstanding_status' => $request->input('outstanding_status'),
        ];
        
        $perPage = 15;
        $page = $request->input('page', 1);
        
        $data = $this->service->getTiktokAnalytics($filters, $perPage, $page);
        
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['transactions']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $groupedTransactions = $transactions->groupBy('no_order');
        
        return view('analytics.finance.tiktok', [
            'transactions' => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'platform' => 'tiktok',
            'totalCount' => $data['summary']['total_count'],
            'totalNominalFix' => $data['summary']['total_nominal_fix'],
            'totalSaldoMasuk' => $data['summary']['total_saldo_masuk'],
            'totalOutstanding' => $data['summary']['total_outstanding'],
        ]);
    }
    
    /**
     * Blibli Analytics
     */
    public function blibliAnalytics(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'from_order_date' => $request->input('from_order_date'),
            'to_order_date' => $request->input('to_order_date'),
            'order_number' => $request->input('order_number'),
            'invoice_number' => $request->input('invoice_number'),
            'tax_id' => $request->input('tax_id'),
            'payment_date' => $request->input('payment_date'),
            'min_nominal' => $request->input('min_nominal'),
            'max_nominal' => $request->input('max_nominal'),
            'outstanding_status' => $request->input('outstanding_status'),
        ];
        
        $perPage = 15;
        $page = $request->input('page', 1);
        
        $data = $this->service->getBlibliAnalytics($filters, $perPage, $page);
        
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['transactions']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $groupedTransactions = $transactions->groupBy('no_order');
        
        return view('analytics.finance.blibli', [
            'transactions' => $transactions,
            'groupedTransactions' => $groupedTransactions,
            'platform' => 'blibli',
            'totalCount' => $data['summary']['total_count'],
            'totalNominalFix' => $data['summary']['total_nominal_fix'],
            'totalSaldoMasuk' => $data['summary']['total_saldo_masuk'],
            'totalOutstanding' => $data['summary']['total_outstanding'],
        ]);
    }
}

