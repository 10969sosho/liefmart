<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArusKasTiktok2Import;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ImportTemp;

class ArusKasTiktok2Controller extends Controller
{
    /**
     * Display a listing of the imported TikTok2 cash flow data
     */
    public function index(Request $request)
    {
        $query = ArusKasTiktok2Import::query();

        // Set default dates - today if no filters provided
        $startDate = $request->input('start_date', date('Y-m-d')); // Default to today
        $endDate = $request->input('end_date', date('Y-m-d')); // Default to today

        // Filter by date range
        $query->whereDate('tanggal_pembayaran', '>=', $startDate)
              ->whereDate('tanggal_pembayaran', '<=', $endDate);

        // Get all transactions ordered by date and excel row number (2-layer sorting)
        // Primary: Tanggal Pembayaran (descending - newest first)
        // Secondary: Excel Row Number (ascending - maintain Excel order for same date)
        $transactions = $query->orderBy('tanggal_pembayaran', 'desc')
                             ->orderBy('excel_row_number', 'asc')
                             ->get();
            
        return view('finance.aruskastiktok2.index', compact('transactions', 'startDate', 'endDate'));
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('finance.aruskastiktok2.import')->withErrors([]);
    }

    /**
     * Preview the imported data before saving
     */
    public function preview(Request $request)
    {
        Log::info('=== TIKTOK2 PREVIEW START ===');
        
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Find header row
            $headerRow = null;
            foreach ($rows as $index => $row) {
                if (in_array('Tanggal Pembayaran', $row) || in_array('TANGGAL PEMBAYARAN', $row)) {
                    $headerRow = $index;
                    break;
                }
            }

            if ($headerRow === null) {
                return redirect()->back()->with('error', 'Header "Tanggal Pembayaran" tidak ditemukan dalam file Excel');
            }

            $headers = $rows[$headerRow];
            $dataRows = array_slice($rows, $headerRow + 1);

            // Map headers to expected columns
            $headerMap = [
                'tanggal_pembayaran' => null,
                'deskripsi' => null,
                'no_pesanan' => null,
                'pembayaran' => null,
                'saldo_akhir' => null
            ];

            foreach ($headers as $index => $header) {
                $header = trim(strtolower($header));
                if (strpos($header, 'tanggal') !== false && strpos($header, 'pembayaran') !== false) {
                    $headerMap['tanggal_pembayaran'] = $index;
                } elseif (strpos($header, 'deskripsi') !== false) {
                    $headerMap['deskripsi'] = $index;
                } elseif (strpos($header, 'pesanan') !== false) {
                    $headerMap['no_pesanan'] = $index;
                } elseif (strpos($header, 'pembayaran') !== false) {
                    $headerMap['pembayaran'] = $index;
                } elseif (strpos($header, 'saldo') !== false && strpos($header, 'akhir') !== false) {
                    $headerMap['saldo_akhir'] = $index;
                }
            }

            $previewData = [];
            foreach ($dataRows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows

                $previewData[] = [
                    'Tanggal Pembayaran' => $row[$headerMap['tanggal_pembayaran']] ?? '',
                    'Deskripsi' => $row[$headerMap['deskripsi']] ?? '',
                    'No. Pesanan' => $row[$headerMap['no_pesanan']] ?? '',
                    'Pembayaran' => $row[$headerMap['pembayaran']] ?? '',
                    'Saldo Akhir' => $row[$headerMap['saldo_akhir']] ?? '',
                    'raw_tanggal_pembayaran' => $row[$headerMap['tanggal_pembayaran']] ?? '',
                    'raw_pembayaran' => $row[$headerMap['pembayaran']] ?? '',
                    'raw_saldo_akhir' => $row[$headerMap['saldo_akhir']] ?? '',
                ];
            }

            return view('finance.aruskastiktok2.preview', compact('previewData'));

        } catch (\Exception $e) {
            Log::error('TikTok2 Preview Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error membaca file: ' . $e->getMessage());
        }
    }

    /**
     * Process the imported data
     */
    public function process(Request $request)
    {
        Log::info('=== TIKTOK2 PROCESS START ===');
        
        $validData = $request->input('valid_data');
        
        if (empty($validData)) {
            Log::error('TikTok2 Process - No valid data to process');
            return redirect()->route('finance.aruskastiktok2.import')->with('error', 'Tidak ada data valid untuk diproses');
        }
        
        Log::info('TikTok2 Process - Starting database transaction');
        DB::beginTransaction();
        
        try {
            // Get TikTok2 platform ID
            $platform = Platform::where('name', 'tiktok2')->first();
            
            if (!$platform) {
                $platform = Platform::create(['name' => 'tiktok2']);
            }
            
            $processed = 0;
            
            foreach ($validData as $rowIndex => $row) {
                Log::info('TikTok2 Process - Processing row ' . ($rowIndex + 1) . ': ' . json_encode([
                    'tanggal_pembayaran' => $row['Tanggal Pembayaran'],
                    'deskripsi' => $row['Deskripsi'],
                    'no_pesanan' => $row['No. Pesanan'],
                    'pembayaran' => $row['Pembayaran'],
                    'saldo_akhir' => $row['Saldo Akhir']
                ]));
                
                // Store original Excel values
                $rawTanggalPembayaran = $row['raw_tanggal_pembayaran'] ?? $row['Tanggal Pembayaran'];
                $rawTanggalPesanan = $row['raw_tanggal_pesanan'] ?? '-';
                $rawPembayaran = $row['raw_pembayaran'] ?? $row['Pembayaran'];
                $rawSaldoAkhir = $row['raw_saldo_akhir'] ?? $row['Saldo Akhir'];
                
                // Parse tanggal pembayaran - preserve original format if possible
                $tanggalPembayaran = null;
                if (!empty($row['Tanggal Pembayaran'])) {
                    try {
                        $tanggalPembayaran = \Carbon\Carbon::createFromFormat('d/m/Y', $row['Tanggal Pembayaran']);
                    } catch (\Exception $e) {
                        try {
                            $tanggalPembayaran = \Carbon\Carbon::parse($row['Tanggal Pembayaran']);
                        } catch (\Exception $e2) {
                            Log::error('TikTok2 Process - Invalid date format: ' . $row['Tanggal Pembayaran']);
                            continue;
                        }
                    }
                }
                
                // Parse pembayaran
                $pembayaran = 0;
                if (!empty($row['Pembayaran'])) {
                    $pembayaran = (float) str_replace(',', '', str_replace('.', '', $row['Pembayaran']));
                }
                
                // Parse saldo akhir
                $saldoAkhir = 0;
                if (!empty($row['Saldo Akhir'])) {
                    $saldoAkhir = (float) str_replace(',', '', str_replace('.', '', $row['Saldo Akhir']));
                }
                
                // Create ArusKasTiktok2Import record
                ArusKasTiktok2Import::create([
                    'tanggal_pembayaran' => $tanggalPembayaran,
                    'deskripsi' => $row['Deskripsi'] ?? '',
                    'no_pesanan' => $row['No. Pesanan'] ?? '',
                    'pembayaran' => $pembayaran,
                    'saldo_akhir' => $saldoAkhir,
                    'platform_id' => $platform->id,
                    'excel_row_number' => $rowIndex + 1,
                ]);
                
                $processed++;
            }
            
            DB::commit();
            Log::info('TikTok2 Process - Successfully processed ' . $processed . ' records');
            
            return redirect()->route('finance.aruskastiktok2.index')->with('success', "Berhasil mengimport {$processed} data arus kas TikTok2");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('TikTok2 Process - Database error: ' . $e->getMessage());
            return redirect()->route('finance.aruskastiktok2.import')->with('error', 'Error menyimpan data: ' . $e->getMessage());
        }
    }
}
