<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArusKasShopee2Import;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ImportTemp;

class ArusKasShopee2Controller extends Controller
{
    /**
     * Display a listing of the imported Shopee Liefmarket cash flow data
     */
    public function index(Request $request)
    {
        $query = ArusKasShopee2Import::query();

        // Set default dates - today if no filters provided
        $startDate = $request->input('start_date', date('Y-m-d')); // Default to today
        $endDate = $request->input('end_date', date('Y-m-d')); // Default to today

        // Filter by date range
        $query->whereDate('tanggal_pemasukan', '>=', $startDate)
              ->whereDate('tanggal_pemasukan', '<=', $endDate);

        // Get all transactions ordered by date
        $transactions = $query->orderBy('tanggal_pemasukan', 'desc')->get();
            
        return view('finance.aruskasshopee2.index', compact('transactions', 'startDate', 'endDate'));
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('finance.aruskasshopee2.import');
    }

    /**
     * Preview the imported data before saving
     */
    public function preview(Request $request)
    {
        Log::info('=== SHOPEE LIEF MART PREVIEW START ===');
        
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
                if (in_array('Tanggal Pemasukan', $row) || in_array('TANGGAL PEMASUKAN', $row)) {
                    $headerRow = $index;
                    break;
                }
            }

            if ($headerRow === null) {
                return redirect()->back()->with('error', 'Header "Tanggal Pemasukan" tidak ditemukan dalam file Excel');
            }

            $headers = $rows[$headerRow];
            $dataRows = array_slice($rows, $headerRow + 1);

            // Map headers to expected columns
            $headerMap = [
                'tanggal_pemasukan' => null,
                'deskripsi' => null,
                'no_pesanan' => null,
                'pemasukan' => null,
                'saldo_akhir' => null
            ];

            foreach ($headers as $index => $header) {
                $header = trim(strtolower($header));
                if (strpos($header, 'tanggal') !== false && strpos($header, 'pemasukan') !== false) {
                    $headerMap['tanggal_pemasukan'] = $index;
                } elseif (strpos($header, 'deskripsi') !== false) {
                    $headerMap['deskripsi'] = $index;
                } elseif (strpos($header, 'pesanan') !== false) {
                    $headerMap['no_pesanan'] = $index;
                } elseif (strpos($header, 'pemasukan') !== false) {
                    $headerMap['pemasukan'] = $index;
                } elseif (strpos($header, 'saldo') !== false && strpos($header, 'akhir') !== false) {
                    $headerMap['saldo_akhir'] = $index;
                }
            }

            $previewData = [];
            foreach ($dataRows as $rowIndex => $row) {
                if (empty(array_filter($row))) continue; // Skip empty rows

                $previewData[] = [
                    'Tanggal Pemasukan' => $row[$headerMap['tanggal_pemasukan']] ?? '',
                    'Deskripsi' => $row[$headerMap['deskripsi']] ?? '',
                    'No. Pesanan' => $row[$headerMap['no_pesanan']] ?? '',
                    'Pemasukan' => $row[$headerMap['pemasukan']] ?? '',
                    'Saldo Akhir' => $row[$headerMap['saldo_akhir']] ?? '',
                    'raw_tanggal_pemasukan' => $row[$headerMap['tanggal_pemasukan']] ?? '',
                    'raw_pemasukan' => $row[$headerMap['pemasukan']] ?? '',
                    'raw_saldo_akhir' => $row[$headerMap['saldo_akhir']] ?? '',
                ];
            }

            return view('finance.aruskasshopee2.preview', compact('previewData'));

        } catch (\Exception $e) {
            Log::error('Shopee2 Preview Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error membaca file: ' . $e->getMessage());
        }
    }

    /**
     * Process the imported data
     */
    public function process(Request $request)
    {
        Log::info('=== SHOPEE2 PROCESS START ===');
        
        $validData = $request->input('valid_data');
        
        if (empty($validData)) {
            Log::error('Shopee2 Process - No valid data to process');
            return redirect()->route('finance.aruskasshopee2.import')->with('error', 'Tidak ada data valid untuk diproses');
        }
        
        Log::info('Shopee2 Process - Starting database transaction');
        DB::beginTransaction();
        
        try {
            // Get Shopee2 platform ID
            $platform = Platform::where('name', 'shopee2')->first();
            
            if (!$platform) {
                $platform = Platform::create(['name' => 'shopee2']);
            }
            
            $processed = 0;
            
            foreach ($validData as $rowIndex => $row) {
                Log::info('Shopee2 Process - Processing row ' . ($rowIndex + 1) . ': ' . json_encode([
                    'tanggal_pemasukan' => $row['Tanggal Pemasukan'],
                    'deskripsi' => $row['Deskripsi'],
                    'no_pesanan' => $row['No. Pesanan'],
                    'pemasukan' => $row['Pemasukan'],
                    'saldo_akhir' => $row['Saldo Akhir']
                ]));
                
                try {
                    // Parse tanggal pemasukan
                    $tanggalPemasukan = null;
                    if (!empty($row['Tanggal Pemasukan'])) {
                        try {
                            $tanggalPemasukan = \Carbon\Carbon::createFromFormat('d/m/Y', $row['Tanggal Pemasukan']);
                        } catch (\Exception $e) {
                            try {
                                $tanggalPemasukan = \Carbon\Carbon::parse($row['Tanggal Pemasukan']);
                            } catch (\Exception $e2) {
                                Log::error('Shopee2 Process - Invalid date format: ' . $row['Tanggal Pemasukan']);
                                continue;
                            }
                        }
                    }
                    
                    // Parse pemasukan
                    $pemasukan = 0;
                    if (!empty($row['Pemasukan'])) {
                        $pemasukan = (float) str_replace(',', '', str_replace('.', '', $row['Pemasukan']));
                    }
                    
                    // Parse saldo akhir
                    $saldoAkhir = 0;
                    if (!empty($row['Saldo Akhir'])) {
                        $saldoAkhir = (float) str_replace(',', '', str_replace('.', '', $row['Saldo Akhir']));
                    }
                    
                    // Create ArusKasShopee2Import record
                    ArusKasShopee2Import::create([
                        'tanggal_pemasukan' => $tanggalPemasukan,
                        'deskripsi' => $row['Deskripsi'] ?? '',
                        'no_pesanan' => $row['No. Pesanan'] ?? '',
                        'pemasukan' => $pemasukan,
                        'saldo_akhir' => $saldoAkhir,
                        'platform_id' => $platform->id,
                        'excel_row_number' => $rowIndex + 1,
                    ]);
                    
                    $processed++;
                    
                } catch (\Exception $e) {
                    Log::error('Shopee2 Process - Error processing row ' . ($rowIndex + 1) . ': ' . $e->getMessage());
                    continue;
                }
            }
            
            DB::commit();
            Log::info('Shopee2 Process - Successfully processed ' . $processed . ' records');
            
            return redirect()->route('finance.aruskasshopee2.index')->with('success', "Berhasil mengimport {$processed} data arus kas Shopee2");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Shopee2 Process - Database error: ' . $e->getMessage());
            return redirect()->route('finance.aruskasshopee2.import')->with('error', 'Error menyimpan data: ' . $e->getMessage());
        }
    }
}
