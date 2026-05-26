<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShopeeFinanceController extends Controller
{
    /**
     * Display a listing of the financial data for Shopee.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get transactions data - this is a placeholder, implement actual data retrieval
        // Using Paginator::make for empty collections
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            collect([]), // items
            0, // total
            15, // per page
            1, // current page
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return view('financial.shopee.index', [
            'platform' => 'shopee',
            'transactions' => $transactions
        ]);
    }

    /**
     * Show the form for importing financial data.
     *
     * @return \Illuminate\Http\Response
     */
    public function import()
    {
        return view('financial.shopee.import');
    }

    /**
     * Preview the imported data before processing.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        // Logic for previewing the data will be implemented here
        // For now, we'll just set placeholder data to fix the view error
        
        // Define the variables needed by the view
        $totalRows = 0;
        $validRows = 0;
        $invalidRows = 0;
        $issues = [];
        $previewData = [];
        $previewHeaders = [
            'no_order', 'tanggal_order', 'hari_order', 'no_invoice',
            'nominal_harga', 'nominal_diskon1', 'nominal_diskon2', 'nominal_diskon3',
            'nominal_diskon4', 'nominal_diskon5', 'nominal_diskon6', 'adjustment',
            'nominal_fix', 'saldo_masuk', 'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran',
            'outstanding'
        ];
        $headerLabels = [
            'no_order' => 'No. Order',
            'tanggal_order' => 'Tanggal Order',
            'hari_order' => 'Hari Order',
            'no_invoice' => 'No. Invoice',
            'nominal_harga' => 'Nominal Harga',
            'nominal_diskon1' => 'Voucher Ditanggung Penjual',
            'nominal_diskon2' => 'Komisi AMS/Affiliate',
            'nominal_diskon3' => 'Biaya Admin',
            'nominal_diskon4' => 'Biaya Layanan',
            'nominal_diskon5' => 'Diskon 5',
            'nominal_diskon6' => 'Diskon 6',
            'adjustment' => 'Adjustment',
            'nominal_fix' => 'Nominal Fix',
            'saldo_masuk' => 'Saldo Masuk',
            'tanggal_masuk_pembayaran' => 'Tanggal Masuk Pembayaran',
            'hari_masuk_pembayaran' => 'Hari Masuk Pembayaran',
            'outstanding' => 'Outstanding'
        ];
        
        // Actual implementation would process the file and populate these variables
        // For now, we're just passing empty/placeholder data to prevent view errors
        
        return view('financial.shopee.preview', compact(
            'totalRows', 'validRows', 'invalidRows', 'issues',
            'previewData', 'previewHeaders', 'headerLabels'
        ));
    }

    /**
     * Process the imported data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request)
    {
        // Logic for processing the data will be implemented here
        
        return redirect()->route('finance.shopee.index')
            ->with('success', 'Data keuangan Shopee berhasil diimpor.');
    }
}
