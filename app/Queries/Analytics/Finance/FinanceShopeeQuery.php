<?php

namespace App\Queries\Analytics\Finance;

use App\Queries\Analytics\Core\FilterBuilder;
use Illuminate\Support\Facades\DB;

/**
 * FinanceShopeeQuery
 * 
 * Query untuk analytics Finance Shopee
 * Handles filtering financial transactions
 */
class FinanceShopeeQuery
{
    /**
     * Build query untuk shopee financial transactions
     */
    public static function build(array $filters = [], int $perPage = 15, int $page = 1): string
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $fromOrderDate = $filters['from_order_date'] ?? null;
        $toOrderDate = $filters['to_order_date'] ?? null;
        $orderNumber = $filters['order_number'] ?? null;
        $invoiceNumber = $filters['invoice_number'] ?? null;
        $taxId = $filters['tax_id'] ?? null;
        $paymentDate = $filters['payment_date'] ?? null;
        $minNominal = $filters['min_nominal'] ?? null;
        $maxNominal = $filters['max_nominal'] ?? null;
        $outstandingStatus = $filters['outstanding_status'] ?? null;
        
        $paymentDateFilter = FilterBuilder::paymentDateFilter($fromDate, $toDate, 'ft');
        $orderDateFilter = FilterBuilder::orderDateFilter($fromOrderDate, $toOrderDate, 'ft');
        $outstandingFilter = FilterBuilder::outstandingFilter($outstandingStatus, 'ft');
        $nominalFilter = FilterBuilder::numericRangeFilter($minNominal, $maxNominal, 'nominal_fix', 'ft');
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND ft.no_order LIKE {$orderNumberQuoted}";
        }
        
        $invoiceNumberFilter = '';
        if ($invoiceNumber) {
            $invoiceNumberQuoted = DB::getPdo()->quote('%' . $invoiceNumber . '%');
            $invoiceNumberFilter = " AND ft.no_invoice LIKE {$invoiceNumberQuoted}";
        }
        
        $taxIdFilter = '';
        if ($taxId && is_array($taxId)) {
            $conditions = [];
            foreach ($taxId as $id) {
                $idPadded = str_pad($id, 2, '0', STR_PAD_LEFT);
                $idQuoted = DB::getPdo()->quote('%/' . $idPadded);
                $conditions[] = "ft.no_invoice LIKE {$idQuoted}";
            }
            if (!empty($conditions)) {
                $taxIdFilter = " AND (" . implode(' OR ', $conditions) . ")";
            }
        }
        
        $paymentDateFilterExact = '';
        if ($paymentDate) {
            $paymentDateQuoted = DB::getPdo()->quote($paymentDate);
            $paymentDateFilterExact = " AND DATE(ft.tanggal_masuk_pembayaran) = {$paymentDateQuoted}";
        }
        
        $offset = ($page - 1) * $perPage;
        
        return "
            SELECT 
                ft.id,
                ft.order_id,
                ft.no_order,
                ft.no_invoice,
                ft.tanggal_order,
                ft.tanggal_masuk_pembayaran,
                ft.nominal_fix,
                ft.saldo_masuk,
                ft.outstanding,
                ft.qty,
                o.id as order_id_exists
            FROM shopee_financial_transactions ft
            LEFT JOIN orders o ON ft.order_id = o.id
            WHERE 1=1{$paymentDateFilter}{$orderDateFilter}{$orderNumberFilter}{$invoiceNumberFilter}{$taxIdFilter}{$paymentDateFilterExact}{$nominalFilter}{$outstandingFilter}
            ORDER BY ft.tanggal_order DESC
            LIMIT {$perPage} OFFSET {$offset}";
    }
    
    /**
     * Build query untuk count
     */
    public static function buildCount(array $filters = []): string
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $fromOrderDate = $filters['from_order_date'] ?? null;
        $toOrderDate = $filters['to_order_date'] ?? null;
        $orderNumber = $filters['order_number'] ?? null;
        $invoiceNumber = $filters['invoice_number'] ?? null;
        $taxId = $filters['tax_id'] ?? null;
        $paymentDate = $filters['payment_date'] ?? null;
        $minNominal = $filters['min_nominal'] ?? null;
        $maxNominal = $filters['max_nominal'] ?? null;
        $outstandingStatus = $filters['outstanding_status'] ?? null;
        
        $paymentDateFilter = FilterBuilder::paymentDateFilter($fromDate, $toDate, 'ft');
        $orderDateFilter = FilterBuilder::orderDateFilter($fromOrderDate, $toOrderDate, 'ft');
        $outstandingFilter = FilterBuilder::outstandingFilter($outstandingStatus, 'ft');
        $nominalFilter = FilterBuilder::numericRangeFilter($minNominal, $maxNominal, 'nominal_fix', 'ft');
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND ft.no_order LIKE {$orderNumberQuoted}";
        }
        
        $invoiceNumberFilter = '';
        if ($invoiceNumber) {
            $invoiceNumberQuoted = DB::getPdo()->quote('%' . $invoiceNumber . '%');
            $invoiceNumberFilter = " AND ft.no_invoice LIKE {$invoiceNumberQuoted}";
        }
        
        $taxIdFilter = '';
        if ($taxId && is_array($taxId)) {
            $conditions = [];
            foreach ($taxId as $id) {
                $idPadded = str_pad($id, 2, '0', STR_PAD_LEFT);
                $idQuoted = DB::getPdo()->quote('%/' . $idPadded);
                $conditions[] = "ft.no_invoice LIKE {$idQuoted}";
            }
            if (!empty($conditions)) {
                $taxIdFilter = " AND (" . implode(' OR ', $conditions) . ")";
            }
        }
        
        $paymentDateFilterExact = '';
        if ($paymentDate) {
            $paymentDateQuoted = DB::getPdo()->quote($paymentDate);
            $paymentDateFilterExact = " AND DATE(ft.tanggal_masuk_pembayaran) = {$paymentDateQuoted}";
        }
        
        return "
            SELECT COUNT(*) as total
            FROM shopee_financial_transactions ft
            WHERE 1=1{$paymentDateFilter}{$orderDateFilter}{$orderNumberFilter}{$invoiceNumberFilter}{$taxIdFilter}{$paymentDateFilterExact}{$nominalFilter}{$outstandingFilter}";
    }
    
    /**
     * Build query untuk summary
     */
    public static function buildSummary(array $filters = []): string
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $fromOrderDate = $filters['from_order_date'] ?? null;
        $toOrderDate = $filters['to_order_date'] ?? null;
        $orderNumber = $filters['order_number'] ?? null;
        $invoiceNumber = $filters['invoice_number'] ?? null;
        $taxId = $filters['tax_id'] ?? null;
        $paymentDate = $filters['payment_date'] ?? null;
        $minNominal = $filters['min_nominal'] ?? null;
        $maxNominal = $filters['max_nominal'] ?? null;
        $outstandingStatus = $filters['outstanding_status'] ?? null;
        
        $paymentDateFilter = FilterBuilder::paymentDateFilter($fromDate, $toDate, 'ft');
        $orderDateFilter = FilterBuilder::orderDateFilter($fromOrderDate, $toOrderDate, 'ft');
        $outstandingFilter = FilterBuilder::outstandingFilter($outstandingStatus, 'ft');
        $nominalFilter = FilterBuilder::numericRangeFilter($minNominal, $maxNominal, 'nominal_fix', 'ft');
        
        $orderNumberFilter = '';
        if ($orderNumber) {
            $orderNumberQuoted = DB::getPdo()->quote('%' . $orderNumber . '%');
            $orderNumberFilter = " AND ft.no_order LIKE {$orderNumberQuoted}";
        }
        
        $invoiceNumberFilter = '';
        if ($invoiceNumber) {
            $invoiceNumberQuoted = DB::getPdo()->quote('%' . $invoiceNumber . '%');
            $invoiceNumberFilter = " AND ft.no_invoice LIKE {$invoiceNumberQuoted}";
        }
        
        $taxIdFilter = '';
        if ($taxId && is_array($taxId)) {
            $conditions = [];
            foreach ($taxId as $id) {
                $idPadded = str_pad($id, 2, '0', STR_PAD_LEFT);
                $idQuoted = DB::getPdo()->quote('%/' . $idPadded);
                $conditions[] = "ft.no_invoice LIKE {$idQuoted}";
            }
            if (!empty($conditions)) {
                $taxIdFilter = " AND (" . implode(' OR ', $conditions) . ")";
            }
        }
        
        $paymentDateFilterExact = '';
        if ($paymentDate) {
            $paymentDateQuoted = DB::getPdo()->quote($paymentDate);
            $paymentDateFilterExact = " AND DATE(ft.tanggal_masuk_pembayaran) = {$paymentDateQuoted}";
        }
        
        return "
            SELECT 
                COUNT(*) as total_count,
                COALESCE(SUM(ft.nominal_fix), 0) as total_nominal_fix,
                COALESCE(SUM(ft.saldo_masuk), 0) as total_saldo_masuk,
                COALESCE(SUM(ft.outstanding), 0) as total_outstanding
            FROM shopee_financial_transactions ft
            WHERE 1=1{$paymentDateFilter}{$orderDateFilter}{$orderNumberFilter}{$invoiceNumberFilter}{$taxIdFilter}{$paymentDateFilterExact}{$nominalFilter}{$outstandingFilter}";
    }
}

