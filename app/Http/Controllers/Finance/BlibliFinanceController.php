<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BlibliFinanceController extends Controller
{
    /**
     * Display a listing of the financial data for Blibli.
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
        
        return view('financial.blibli.index', [
            'platform' => 'blibli',
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
        return view('financial.blibli.import');
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
        
        return view('financial.blibli.preview');
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
        
        return redirect()->route('finance.blibli.index')
            ->with('success', 'Data keuangan Blibli berhasil diimpor.');
    }
}
