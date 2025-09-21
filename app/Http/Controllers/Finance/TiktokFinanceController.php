<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TiktokFinanceController extends Controller
{
    /**
     * Display a listing of the financial data for TikTok.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get transactions data - this is a placeholder, implement actual data retrieval
        // Using LengthAwarePaginator for empty collections
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            collect([]), // items
            0, // total
            15, // per page
            1, // current page
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        return view('financial.tiktok.index', [
            'platform' => 'tiktok',
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
        return view('financial.tiktok.import');
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
        
        return view('financial.tiktok.preview');
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
        
        return redirect()->route('finance.tiktok.index')
            ->with('success', 'Data keuangan TikTok berhasil diimpor.');
    }
}
