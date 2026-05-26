<?php

namespace App\Services\Analytics;

use App\Queries\Analytics\Finance\FinanceShopeeQuery;
use App\Queries\Analytics\Finance\FinanceTokopediaQuery;
use App\Queries\Analytics\Finance\FinanceTiktokQuery;
use App\Queries\Analytics\Finance\FinanceBlibliQuery;
use Illuminate\Support\Facades\DB;

/**
 * FinanceAnalyticsService
 * 
 * Service layer untuk Finance Analytics
 * Hanya orchestrator, tidak ada perhitungan PHP
 */
class FinanceAnalyticsService
{
    /**
     * Get Shopee financial analytics
     */
    public function getShopeeAnalytics(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = FinanceShopeeQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = FinanceShopeeQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = FinanceShopeeQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $transactions = [];
        foreach ($results as $row) {
            $transactions[] = [
                'id' => (int)$row->id,
                'order_id' => (int)$row->order_id,
                'no_order' => $row->no_order,
                'no_invoice' => $row->no_invoice,
                'tanggal_order' => $row->tanggal_order,
                'tanggal_masuk_pembayaran' => $row->tanggal_masuk_pembayaran,
                'nominal_fix' => (float)$row->nominal_fix,
                'saldo_masuk' => (float)$row->saldo_masuk,
                'outstanding' => (float)$row->outstanding,
                'qty' => (float)($row->qty ?? 0),
            ];
        }
        
        $summary = [
            'total_count' => (int)($summaryResult->total_count ?? 0),
            'total_nominal_fix' => (float)($summaryResult->total_nominal_fix ?? 0),
            'total_saldo_masuk' => (float)($summaryResult->total_saldo_masuk ?? 0),
            'total_outstanding' => (float)($summaryResult->total_outstanding ?? 0),
        ];
        
        return [
            'transactions' => $transactions,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get Tokopedia financial analytics
     */
    public function getTokopediaAnalytics(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = FinanceTokopediaQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = FinanceTokopediaQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = FinanceTokopediaQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $transactions = [];
        foreach ($results as $row) {
            $transactions[] = [
                'id' => (int)$row->id,
                'order_id' => (int)$row->order_id,
                'no_order' => $row->no_order,
                'no_invoice' => $row->no_invoice,
                'tanggal_order' => $row->tanggal_order,
                'tanggal_masuk_pembayaran' => $row->tanggal_masuk_pembayaran,
                'nominal_fix' => (float)$row->nominal_fix,
                'saldo_masuk' => (float)$row->saldo_masuk,
                'outstanding' => (float)$row->outstanding,
                'qty' => (float)($row->qty ?? 0),
            ];
        }
        
        $summary = [
            'total_count' => (int)($summaryResult->total_count ?? 0),
            'total_nominal_fix' => (float)($summaryResult->total_nominal_fix ?? 0),
            'total_saldo_masuk' => (float)($summaryResult->total_saldo_masuk ?? 0),
            'total_outstanding' => (float)($summaryResult->total_outstanding ?? 0),
        ];
        
        return [
            'transactions' => $transactions,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get TikTok financial analytics
     */
    public function getTiktokAnalytics(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = FinanceTiktokQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = FinanceTiktokQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = FinanceTiktokQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $transactions = [];
        foreach ($results as $row) {
            $transactions[] = [
                'id' => (int)$row->id,
                'order_id' => (int)$row->order_id,
                'no_order' => $row->no_order,
                'no_invoice' => $row->no_invoice,
                'tanggal_order' => $row->tanggal_order,
                'tanggal_masuk_pembayaran' => $row->tanggal_masuk_pembayaran,
                'nominal_fix' => (float)$row->nominal_fix,
                'saldo_masuk' => (float)$row->saldo_masuk,
                'outstanding' => (float)$row->outstanding,
                'qty' => (float)($row->qty ?? 0),
            ];
        }
        
        $summary = [
            'total_count' => (int)($summaryResult->total_count ?? 0),
            'total_nominal_fix' => (float)($summaryResult->total_nominal_fix ?? 0),
            'total_saldo_masuk' => (float)($summaryResult->total_saldo_masuk ?? 0),
            'total_outstanding' => (float)($summaryResult->total_outstanding ?? 0),
        ];
        
        return [
            'transactions' => $transactions,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
    
    /**
     * Get Blibli financial analytics
     */
    public function getBlibliAnalytics(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = FinanceBlibliQuery::build($filters, $perPage, $page);
        $results = DB::select($query);
        
        $countQuery = FinanceBlibliQuery::buildCount($filters);
        $countResult = DB::selectOne($countQuery);
        $total = (int)($countResult->total ?? 0);
        
        $summaryQuery = FinanceBlibliQuery::buildSummary($filters);
        $summaryResult = DB::selectOne($summaryQuery);
        
        $transactions = [];
        foreach ($results as $row) {
            $transactions[] = [
                'id' => (int)$row->id,
                'order_id' => (int)$row->order_id,
                'no_order' => $row->no_order,
                'no_invoice' => $row->no_invoice,
                'tanggal_order' => $row->tanggal_order,
                'tanggal_masuk_pembayaran' => $row->tanggal_masuk_pembayaran,
                'nominal_fix' => (float)$row->nominal_fix,
                'saldo_masuk' => (float)$row->saldo_masuk,
                'outstanding' => (float)$row->outstanding,
                'qty' => 0, // Blibli doesn't have qty
            ];
        }
        
        $summary = [
            'total_count' => (int)($summaryResult->total_count ?? 0),
            'total_nominal_fix' => (float)($summaryResult->total_nominal_fix ?? 0),
            'total_saldo_masuk' => (float)($summaryResult->total_saldo_masuk ?? 0),
            'total_outstanding' => (float)($summaryResult->total_outstanding ?? 0),
        ];
        
        return [
            'transactions' => $transactions,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'summary' => $summary,
        ];
    }
}

