<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LazadaFinancialTransaction;
use App\Models\Order;
use App\Imports\LazadaFinancialImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LazadaFinanceController extends Controller
{
    /**
     * Display a listing of the financial data for Lazada.
     */
    public function index()
    {
        $transactions = LazadaFinancialTransaction::with('order')
            ->orderBy('tanggal_pembayaran', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('financial.lazada.index', [
            'platform' => 'lazada',
            'transactions' => $transactions
        ]);
    }

    /**
     * Show the form for importing financial data.
     */
    public function import()
    {
        return view('financial.lazada.import');
    }

    /**
     * Preview the imported data before processing.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new LazadaFinancialImport();
            Excel::import($import, $request->file('file'));

            $data = $import->getData();
            $stats = $import->getStats();
            $invalidData = $import->getInvalidData();
            $headerIssues = $import->getHeaderIssues();

            // Simpan data ke session untuk proses import
            session([
                'lazada_financial_import_data' => $data,
                'lazada_financial_import_stats' => $stats,
                'lazada_financial_invalid_data' => $invalidData,
                'lazada_financial_header_issues' => $headerIssues,
            ]);

            return view('financial.lazada.preview', compact('data', 'stats', 'invalidData', 'headerIssues'));

        } catch (\Exception $e) {
            \Log::error('Error in Lazada financial preview: ' . $e->getMessage());
            return redirect()->route('finance.lazada.import')
                ->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Process the imported data.
     */
    public function process(Request $request)
    {
        try {
            // Ambil data dari session
            $data = session('lazada_financial_import_data', []);
            
            if (empty($data)) {
                return redirect()->route('finance.lazada.import')
                    ->with('error', 'Tidak ada data untuk diimport. Silakan upload file Excel terlebih dahulu.');
            }

            // Simpan data ke database
            $import = new LazadaFinancialImport();
            $import->data = $data;
            $result = $import->saveToDatabase();
            
            // Clear session data
            session()->forget([
                'lazada_financial_import_data',
                'lazada_financial_import_stats',
                'lazada_financial_invalid_data',
                'lazada_financial_header_issues'
            ]);
            
            if ($result['success']) {
                return redirect()->route('finance.lazada.index')->with('success', $result['message']);
            } else {
                return redirect()->route('finance.lazada.import')->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            \Log::error('Lazada financial import error: ' . $e->getMessage());
            return redirect()->route('finance.lazada.import')
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Show the specified financial transaction.
     */
    public function show(LazadaFinancialTransaction $transaction)
    {
        $transaction->load('order');
        return view('financial.lazada.show', compact('transaction'));
    }

    /**
     * Remove the specified financial transaction.
     */
    public function destroy(LazadaFinancialTransaction $transaction)
    {
        try {
            $transaction->delete();
            return redirect()->route('finance.lazada.index')
                ->with('success', 'Transaksi keuangan berhasil dihapus.');
        } catch (\Exception $e) {
            \Log::error('Error deleting Lazada financial transaction: ' . $e->getMessage());
            return redirect()->route('finance.lazada.index')
                ->with('error', 'Terjadi kesalahan saat menghapus transaksi: ' . $e->getMessage());
        }
    }
}
