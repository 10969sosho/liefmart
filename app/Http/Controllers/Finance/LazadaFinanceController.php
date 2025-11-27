<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LazadaFinancialTransaction;
use App\Models\Platform;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LazadaFinanceController extends Controller
{
    protected $platform;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Dapatkan platform Lazada dari database
        $this->platform = Platform::where('name', 'lazada')->first();
        
        // Jika platform tidak ditemukan, throw exception
        if (!$this->platform) {
            throw new \Exception('Platform Lazada tidak ditemukan di database.');
        }
    }
    /**
     * Tampilkan halaman import financial data Lazada
     */
    public function import()
    {
        return view('financial.lazada.import');
    }

    /**
     * Preview data financial sebelum import
     */
    public function preview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $file = $request->file('excel_file');
            $tempPath = $file->store('temp');
            $fullPath = storage_path('app/' . $tempPath);

            // Baca data Excel
            $data = Excel::toArray(new \App\Imports\LazadaImport($this->platform->id), $fullPath);
            
            // Hapus file sementara
            \Storage::delete($tempPath);

            if (empty($data) || empty($data[0])) {
                return redirect()->route('finance.lazada.import')
                    ->with('error', 'File Excel kosong atau tidak dapat dibaca.');
            }

            $previewData = $data[0];
            $headers = array_shift($previewData);

            // Simpan data ke session
            session(['lazada_finance_preview' => $previewData]);
            session(['lazada_finance_headers' => $headers]);

            return view('financial.lazada.preview', compact('previewData', 'headers'));

        } catch (\Exception $e) {
            return redirect()->route('finance.lazada.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Proses import financial data
     */
    public function process(Request $request)
    {
        try {
            $data = session('lazada_finance_preview');
            $headers = session('lazada_finance_headers');

            if (!$data || !$headers) {
                return redirect()->route('finance.lazada.import')
                    ->with('error', 'Data preview tidak ditemukan.');
            }

            $successCount = 0;
            $errorCount = 0;

            foreach ($data as $row) {
                try {
                    $rowData = array_combine($headers, $row);
                    
                    LazadaFinancialTransaction::create([
                        'transaction_id' => $rowData['transaction_id'] ?? '',
                        'order_id' => $rowData['order_id'] ?? '',
                        'amount' => $rowData['amount'] ?? 0,
                        'fee' => $rowData['fee'] ?? 0,
                        'net_amount' => $rowData['net_amount'] ?? 0,
                        'transaction_date' => $rowData['transaction_date'] ?? now(),
                        'status' => $rowData['status'] ?? 'pending',
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                }
            }

            // Hapus data dari session
            session()->forget(['lazada_finance_preview', 'lazada_finance_headers']);

            return redirect()->route('finance.lazada.index')
                ->with('success', "Import selesai! Berhasil: {$successCount}, Gagal: {$errorCount}");

        } catch (\Exception $e) {
            return redirect()->route('finance.lazada.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail transaction
     */
    public function show(LazadaFinancialTransaction $transaction)
    {
        return view('financial.lazada.show', compact('transaction'));
    }

    /**
     * Hapus transaction
     */
    public function destroy(LazadaFinancialTransaction $transaction)
    {
        try {
            $transaction->delete();
            return redirect()->route('finance.lazada.index')
                ->with('success', 'Transaction berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('finance.lazada.index')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
