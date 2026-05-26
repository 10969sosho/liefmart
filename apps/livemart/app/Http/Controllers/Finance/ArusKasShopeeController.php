<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArusKasShopeeImport;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ImportTemp;

class ArusKasShopeeController extends Controller
{
    private function normalizeNumber($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $v = trim(str_replace(['Rp', 'rp', ' '], '', (string) $value));
        $isNegative = false;
        if (preg_match('/^\(.*\)$/', $v)) {
            $isNegative = true;
            $v = trim($v, '()');
        }
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $v)) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '', $v);
        }
        if (is_numeric($v)) {
            $num = (float) $v;
            return $isNegative ? -$num : $num;
        }
        return null;
    }
    /**
     * Display a listing of the imported Shopee Lamourad cash flow data
     */
    public function index(Request $request)
    {
        $query = ArusKasShopeeImport::query();

        // Set default dates - today if no filters provided
        $startDate = $request->input('start_date', date('Y-m-d')); // Default to today
        $endDate = $request->input('end_date', date('Y-m-d')); // Default to today

        // Filter by date range
        $query->whereDate('tanggal_pemasukan', '>=', $startDate)
              ->whereDate('tanggal_pemasukan', '<=', $endDate);

        // Get all transactions ordered by date
        $transactions = $query->orderBy('tanggal_pemasukan', 'desc')->get();
            
        return view('finance.aruskasshopee.index', compact('transactions', 'startDate', 'endDate'));
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('finance.aruskasshopee.import');
    }

    /**
     * Preview the imported data before saving
     */
    public function preview(Request $request)
    {
        Log::info('=== SHOPEE LAMOURAD PREVIEW START ===');
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        Log::info('Shopee Lamourad file uploaded: ' . $file->getClientOriginalName());
        
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get all data as array (including headings)
            $rows = $worksheet->toArray();
            
            Log::info('Shopee Excel rows count: ' . count($rows));
            
            // Check if the file has any data
            if (count($rows) <= 1) {
                Log::warning('Shopee Excel file has no data rows');
                return redirect()->back()->with('error', 'File tidak memiliki data');
            }
            
            // Get headers (first row) and clean them
            $headers = array_shift($rows);
            Log::info('Shopee Excel headers (raw): ' . json_encode($headers));
            
            // Clean headers - trim whitespace and remove null values
            $headers = array_map(function($header) {
                return $header !== null ? trim($header) : $header;
            }, $headers);
            
            // Remove null headers
            $headers = array_filter($headers, function($header) {
                return $header !== null && $header !== '';
            });
            
            Log::info('Shopee Excel headers (cleaned): ' . json_encode($headers));
            
            // Required column headers - Essential columns
            $essentialHeaders = [
                'Tanggal Pemasukan', 
                'Deskripsi', 
                'No. Pesanan', 
                'Tanggal Pesanan',
                'Pemasukan', 
                'Saldo Akhir'
            ];
            
            // Supporting columns - Not strictly required but expected
            $supportingHeaders = [
                'Tipe Transaksi',
                'Jenis Transaksi',
                'Status'
            ];
            
            // All required headers
            $requiredHeaders = array_merge($essentialHeaders, $supportingHeaders);
            
            // Check if all required headers exist (case-insensitive)
            $headersLower = array_map('strtolower', $headers);
            $essentialHeadersLower = array_map('strtolower', $essentialHeaders);
            $missingEssentialHeaders = [];
            
            foreach ($essentialHeadersLower as $essentialHeader) {
                $found = false;
                foreach ($headersLower as $header) {
                    if ($header === $essentialHeader) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // Find the original case version for error message
                    $originalHeader = $essentialHeaders[array_search($essentialHeader, $essentialHeadersLower)];
                    $missingEssentialHeaders[] = $originalHeader;
                }
            }
            
            if (!empty($missingEssentialHeaders)) {
                return redirect()->back()->with('error', 'Format file tidak valid. Kolom utama yang hilang: ' . implode(', ', $missingEssentialHeaders));
            }
            
            // Check for missing supporting headers (case-insensitive)
            $supportingHeadersLower = array_map('strtolower', $supportingHeaders);
            $missingSupportingHeaders = [];
            
            foreach ($supportingHeadersLower as $supportingHeader) {
                $found = false;
                foreach ($headersLower as $header) {
                    if ($header === $supportingHeader) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $originalHeader = $supportingHeaders[array_search($supportingHeader, $supportingHeadersLower)];
                    $missingSupportingHeaders[] = $originalHeader;
                }
            }
            
            if (!empty($missingSupportingHeaders)) {
                // Just a warning about missing supporting headers
                session()->flash('warning', 'Beberapa kolom pendukung tidak ditemukan: ' . implode(', ', $missingSupportingHeaders) . 
                    '. Proses akan tetap dilanjutkan.');
            }
            
            // To track duplicate order numbers within the current import
            $importedOrderNumbers = [];
            
            // Process each row
            $data = [];
            $issues = [];
            $duplicateInImport = 0;
            $alreadyExists = 0;
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Add 2 because 1-indexed and we removed header row
                
                // Only take the same number of columns as headers (to handle extra null columns)
                $row = array_slice($row, 0, count($headers));
                
                // Skip if row is completely empty
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map the columns to our data structure
                $rowData = array_combine($headers, $row);
                
                // Normalize column names for case-insensitive access
                $normalizedData = [];
                foreach ($rowData as $key => $value) {
                    $normalizedData[strtolower($key)] = $value;
                }
                
                // Debug first few rows
                if ($index < 3) {
                    Log::info('Shopee row ' . $rowNumber . ' data: ' . json_encode($rowData));
                }
                $rowIssues = [];
                
                // Validate essential fields - these must have values (case-insensitive)
                // Handle Tanggal Pemasukan with #N/A validation
                if (empty($normalizedData['tanggal pemasukan']) || 
                    $normalizedData['tanggal pemasukan'] === '#N/A' || 
                    strtoupper(trim($normalizedData['tanggal pemasukan'])) === '#N/A') {
                    $rowIssues[] = 'Tanggal Pemasukan kosong atau tidak valid (#N/A)';
                } else {
                    // Validate date format
                    $tanggalPemasukan = $normalizedData['tanggal pemasukan'];
                    try {
                        // Try to parse the date
                        $parsedDate = \Carbon\Carbon::parse($tanggalPemasukan);
                        $normalizedData['tanggal pemasukan'] = $parsedDate->format('Y-m-d');
                    } catch (\Exception $e) {
                        $rowIssues[] = 'Format Tanggal Pemasukan tidak valid: ' . $tanggalPemasukan;
                    }
                }
                
                if (empty($normalizedData['deskripsi'])) {
                    $rowIssues[] = 'Deskripsi kosong';
                }
                
                // Handle empty No. Pesanan by replacing with "-"
                if (empty($normalizedData['no. pesanan'])) {
                    $normalizedData['no. pesanan'] = '-';
                }
                
                // Handle Tanggal Pesanan with #N/A validation
                if (empty($normalizedData['tanggal pesanan']) || 
                    $normalizedData['tanggal pesanan'] === '#N/A' || 
                    strtoupper(trim($normalizedData['tanggal pesanan'])) === '#N/A') {
                    $normalizedData['tanggal pesanan'] = null;
                } else {
                    // Validate date format for Tanggal Pesanan
                    $tanggalPesanan = $normalizedData['tanggal pesanan'];
                    try {
                        // Try to parse the date
                        $parsedDate = \Carbon\Carbon::parse($tanggalPesanan);
                        $normalizedData['tanggal pesanan'] = $parsedDate->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If date parsing fails, set to null and add warning
                        $normalizedData['tanggal pesanan'] = null;
                        $rowIssues[] = 'Format Tanggal Pesanan tidak valid: ' . $tanggalPesanan . ' (diabaikan)';
                    }
                }
                
                // Handle Pemasukan with #N/A validation
                if (empty($normalizedData['pemasukan']) || 
                    $normalizedData['pemasukan'] === '#N/A' || 
                    strtoupper(trim($normalizedData['pemasukan'])) === '#N/A') {
                    $rowIssues[] = 'Pemasukan kosong atau tidak valid (#N/A)';
                } else {
                    // Validate numeric format
                    $pemasukan = $normalizedData['pemasukan'];
                    $pemasukanNormalized = $this->normalizeNumber($pemasukan);
                    if ($pemasukanNormalized === null) {
                        $rowIssues[] = 'Format Pemasukan tidak valid: ' . $pemasukan;
                    } else {
                        $normalizedData['pemasukan'] = $pemasukanNormalized;
                    }
                }
                
                // Handle Saldo Akhir with #N/A validation
                // For fund withdrawals, be more lenient with saldo akhir validation
                $isWithdrawal = stripos($normalizedData['deskripsi'], 'penarikan') !== false || 
                               stripos($normalizedData['deskripsi'], 'withdrawal') !== false;
                
                if (empty($normalizedData['saldo akhir']) || 
                    $normalizedData['saldo akhir'] === '#N/A' || 
                    strtoupper(trim($normalizedData['saldo akhir'])) === '#N/A') {
                    if (!$isWithdrawal) {
                        $rowIssues[] = 'Saldo Akhir kosong atau tidak valid (#N/A)';
                    } else {
                        // For withdrawals, set saldo akhir to 0 if invalid
                        $normalizedData['saldo akhir'] = 0;
                    }
                } else {
                    // Validate numeric format
                    $saldoAkhir = $normalizedData['saldo akhir'];
                    $saldoNormalized = $this->normalizeNumber($saldoAkhir);
                    if ($saldoNormalized === null) {
                        if (!$isWithdrawal) {
                            $rowIssues[] = 'Format Saldo Akhir tidak valid: ' . $saldoAkhir;
                        } else {
                            $normalizedData['saldo akhir'] = 0;
                        }
                    } else {
                        $normalizedData['saldo akhir'] = $saldoNormalized;
                    }
                }
                
                // Check for duplicate validation - always check regardless of order number
                // For fund withdrawals, be more lenient with duplicate checking
                $isWithdrawal = stripos($normalizedData['deskripsi'], 'penarikan') !== false || 
                               stripos($normalizedData['deskripsi'], 'withdrawal') !== false;
                
                // Create unique key combining order number and description
                $uniqueKey = $normalizedData['no. pesanan'] . '|' . $normalizedData['deskripsi'];
                
                // Check for duplicate in the current import
                if (in_array($uniqueKey, $importedOrderNumbers)) {
                    if (!$isWithdrawal) {
                        $rowIssues[] = 'Duplikasi No. Pesanan dengan Deskripsi yang sama dalam data yang diimpor';
                        $duplicateInImport++;
                    }
                    // For withdrawals, allow duplicates as they might be different transactions
                } else {
                    $importedOrderNumbers[] = $uniqueKey;
                }
                
                // Check duplicate entry in existing database (same order number AND same description)
                // For withdrawals, be more lenient - only check if it's not a withdrawal
                if (!$isWithdrawal) {
                    $exists = ArusKasShopeeImport::where('no_pesanan', $normalizedData['no. pesanan'])
                        ->where('deskripsi', $normalizedData['deskripsi'])
                        ->exists();
                        
                    if ($exists) {
                        $rowIssues[] = 'Data dengan No. Pesanan dan Deskripsi yang sama sudah ada di database';
                        $alreadyExists++;
                    }
                }
                
                // Fill in supporting fields with default values if they're missing or #N/A
                if (!isset($normalizedData['tipe transaksi']) || 
                    $normalizedData['tipe transaksi'] === '#N/A' || 
                    strtoupper(trim($normalizedData['tipe transaksi'])) === '#N/A') {
                    $normalizedData['tipe transaksi'] = '-';
                }
                
                if (!isset($normalizedData['jenis transaksi']) || 
                    $normalizedData['jenis transaksi'] === '#N/A' || 
                    strtoupper(trim($normalizedData['jenis transaksi'])) === '#N/A') {
                    $normalizedData['jenis transaksi'] = '-';
                }
                
                if (!isset($normalizedData['status']) || 
                    $normalizedData['status'] === '#N/A' || 
                    strtoupper(trim($normalizedData['status'])) === '#N/A') {
                    $normalizedData['status'] = '-';
                }
                
                // Convert back to original case for consistency
                $processedData = [
                    'Tanggal Pemasukan' => $normalizedData['tanggal pemasukan'],
                    'Deskripsi' => $normalizedData['deskripsi'],
                    'No. Pesanan' => $normalizedData['no. pesanan'],
                    'Tanggal Pesanan' => $normalizedData['tanggal pesanan'],
                    'Pemasukan' => $normalizedData['pemasukan'],
                    'Saldo Akhir' => $normalizedData['saldo akhir'],
                    'Tipe Transaksi' => $normalizedData['tipe transaksi'],
                    'Jenis Transaksi' => $normalizedData['jenis transaksi'],
                    'Status' => $normalizedData['status'],
                ];
                
                // Add validation results to the data
                $processedData['_row_number'] = $rowNumber;
                $processedData['_valid'] = empty($rowIssues);
                $processedData['_issues'] = $rowIssues;
                
                $data[] = $processedData;
                
                // Add to issues list if there are problems
                if (!empty($rowIssues)) {
                    $issues[$rowNumber] = $rowIssues;
                }
            }
            
            // Prepare summary
            $totalRows = count($data);
            $validRows = count(array_filter($data, fn($row) => $row['_valid']));
            $summary = [
                'total' => $totalRows,
                'valid' => $validRows,
                'invalid' => $totalRows - $validRows,
                'duplicate_in_import' => $duplicateInImport,
                'already_exists' => $alreadyExists
            ];
            
            // Generate unique session ID for this import
            $sessionId = session()->getId() . '_' . time();
            
            // Store in database instead of session
            ImportTemp::create([
                'import_type' => 'shopee',
                'session_id' => $sessionId,
                'data' => $data,
                'issues' => $issues,
                'summary' => $summary
            ]);
            
            Log::info('Shopee Preview - Stored to database with session_id: ' . $sessionId . '. Data count: ' . count($data) . ', Valid: ' . $summary['valid']);
            
            // Cleanup old records
            ImportTemp::cleanup();
            
            return view('finance.aruskasshopee.preview', compact('data', 'issues', 'summary'))->with('import_session_id', $sessionId);
            
        } catch (\Exception $e) {
            Log::error('Arus Kas Shopee import error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Process and save the validated import data
     */
    public function process(Request $request)
    {
        Log::info('=== SHOPEE PROCESS START ===');
        
        // Get import_session_id from request
        $sessionId = $request->input('import_session_id');
        Log::info('Shopee Process - Looking for session_id: ' . $sessionId);
        
        if (!$sessionId) {
            Log::error('Shopee Process - No session ID provided');
            return redirect()->route('finance.aruskasshopee.import')->with('error', 'Session import tidak ditemukan. Silakan upload ulang file Excel.');
        }
        
        // Ambil data dari database
        $importTemp = ImportTemp::where('import_type', 'shopee')
                                ->where('session_id', $sessionId)
                                ->first();
        
        if (!$importTemp) {
            Log::error('Shopee Process - No import data found for session_id: ' . $sessionId);
            return redirect()->route('finance.aruskasshopee.import')->with('error', 'Data import tidak ditemukan atau sudah expired. Silakan upload ulang file Excel.');
        }
        
        $data = $importTemp->data;
        $issues = $importTemp->issues;
        $summary = $importTemp->summary;
        
        Log::info('Shopee Process - Database data count: ' . ($data ? count($data) : 'null'));
        Log::info('Shopee Process - Summary: ' . json_encode($summary));
        
        if (!$data) {
            Log::error('Shopee Process - No data in database record');
            return redirect()->route('finance.aruskasshopee.import')->with('error', 'Tidak ada data untuk diproses. Silakan upload ulang file Excel.');
        }
        
        // Filter hanya data valid
        $validData = array_filter($data, function($row) {
            return $row['_valid'];
        });
        
        Log::info('Shopee Process - Valid data count: ' . count($validData));
        
        if (empty($validData)) {
            Log::error('Shopee Process - No valid data to process');
            return redirect()->route('finance.aruskasshopee.import')->with('error', 'Tidak ada data valid untuk diproses');
        }
        
        Log::info('Shopee Process - Starting database transaction');
        DB::beginTransaction();
        
        try {
            // Get Shopee platform ID
            $platform = Platform::where('name', 'Shopee')->first();
            
            if (!$platform) {
                $platform = Platform::create(['name' => 'Shopee']);
            }
            
            $processed = 0;
            
            foreach ($validData as $rowIndex => $row) {
                Log::info('Shopee Process - Processing row ' . ($rowIndex + 1) . ': ' . json_encode([
                    'tanggal_pemasukan' => $row['Tanggal Pemasukan'],
                    'deskripsi' => $row['Deskripsi'],
                    'no_pesanan' => $row['No. Pesanan'],
                    'pemasukan' => $row['Pemasukan'],
                    'saldo_akhir' => $row['Saldo Akhir']
                ]));
                
                try {
                    // Buat instance ArusKasShopeeImport baru
                    $transaction = new ArusKasShopeeImport([
                        'tanggal_pemasukan' => $row['Tanggal Pemasukan'],
                        'tipe_transaksi' => $row['Tipe Transaksi'],
                        'deskripsi' => $row['Deskripsi'],
                        'no_pesanan' => $row['No. Pesanan'],
                        'tanggal_pesanan' => $row['Tanggal Pesanan'],
                        'jenis_transaksi' => $row['Jenis Transaksi'],
                        'pemasukan' => $row['Pemasukan'],
                        'status' => $row['Status'],
                        'saldo_akhir' => $row['Saldo Akhir'],
                        'platform_id' => $platform->id,
                    ]);
                    
                    Log::info('Shopee Process - Created transaction object: ' . json_encode($transaction->toArray()));
                    
                    // Simpan transaksi
                    $result = $transaction->save();
                    Log::info('Shopee Process - Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
                    
                    if ($result) {
                        Log::info('Shopee Process - Transaction saved with ID: ' . $transaction->id);
                        $processed++;
                    } else {
                        Log::error('Shopee Process - Failed to save transaction');
                    }
                } catch (\Exception $e) {
                    Log::error('Shopee Process - Error processing row ' . ($rowIndex + 1) . ': ' . $e->getMessage());
                    Log::error('Shopee Process - Row data: ' . json_encode($row));
                    // Continue processing other rows even if one fails
                    continue;
                }
            }
            
            Log::info('Shopee Process - Committing transaction. Processed: ' . $processed);
            DB::commit();
            
            // Clear database record
            $importTemp->delete();
            Log::info('Shopee Process - Cleaned up import temp record');
            
            $notProcessed = $summary['total'] - $processed;
            $processMessage = "Berhasil mengimpor {$processed} dari {$summary['total']} data arus kas Shopee.";
            
            if ($notProcessed > 0) {
                $processMessage .= " {$notProcessed} data tidak diproses karena:";
                if ($summary['duplicate_in_import'] > 0) {
                    $processMessage .= " {$summary['duplicate_in_import']} duplikasi dalam file,";
                }
                if ($summary['already_exists'] > 0) {
                    $processMessage .= " {$summary['already_exists']} sudah ada di database,";
                }
                $processMessage = rtrim($processMessage, ',') . '.';
            }
            
            Log::info('Shopee Process - SUCCESS: ' . $processMessage);
            
            return redirect()->route('finance.aruskasshopee.index')
                ->with('success', $processMessage);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Shopee Process - EXCEPTION: ' . $e->getMessage());
            Log::error('Shopee Process - Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('finance.aruskasshopee.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
        
        Log::info('=== SHOPEE PROCESS END ===');
    }
} 
