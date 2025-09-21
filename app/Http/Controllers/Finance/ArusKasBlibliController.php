<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArusKasBlibli;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ImportTemp;

class ArusKasBlibliController extends Controller
{
    public function index(Request $request)
    {
        $query = ArusKasBlibli::query();

        // Set default dates - today if no filters provided
        $startDate = $request->input('start_date', date('Y-m-d')); // Default to today
        $endDate = $request->input('end_date', date('Y-m-d')); // Default to today

        // Filter by date range
        $query->whereDate('tanggal_pembayaran', '>=', $startDate)
              ->whereDate('tanggal_pembayaran', '<=', $endDate);

        // Get all transactions ordered by date
        $transactions = $query->orderBy('tanggal_pembayaran', 'desc')->get();
            
        return view('finance.aruskasblibli.index', compact('transactions', 'startDate', 'endDate'));
    }

    public function import()
    {
        return view('finance.aruskasblibli.import')->withErrors([]);
    }
    
    public function preview(Request $request)
    {
        Log::info('Blibli preview method called');
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        Log::info('File uploaded: ' . $file->getClientOriginalName());
        
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get all data as array (including headings)
            $rows = $worksheet->toArray();
            
            Log::info('Blibli Excel rows count: ' . count($rows));
            
            // Check if the file has any data
            if (count($rows) <= 1) {
                Log::warning('Blibli Excel file has no data rows');
                return redirect()->back()->with('error', 'File tidak memiliki data');
            }
            
            // Get headers (first row) and clean them
            $headers = array_shift($rows);
            Log::info('Blibli Excel headers (raw): ' . json_encode($headers));
            
            // Clean headers - trim whitespace and remove null values
            $headers = array_map(function($header) {
                return $header !== null ? trim($header) : $header;
            }, $headers);
            
            // Remove null headers
            $headers = array_filter($headers, function($header) {
                return $header !== null && $header !== '';
            });
            
            Log::info('Blibli Excel headers (cleaned): ' . json_encode($headers));
            
            // Required column headers - Essential columns
            $essentialHeaders = [
                'Tanggal Pembayaran', 
                'Deskripsi', 
                'Pembayaran', 
                'Saldo Akhir'
            ];
            
            // Supporting columns - Not strictly required but expected
            $supportingHeaders = [
                'No. Pesanan',
                'Tanggal pesanan'  // Note: lowercase 'p' as shown in the image
            ];
            
            // All required headers
            $requiredHeaders = array_merge($essentialHeaders, $supportingHeaders);
            
            // Check if all required headers exist
            $missingHeaders = array_diff($requiredHeaders, $headers);
            
            // Essential missing headers - those that must be present
            $missingEssentialHeaders = array_diff($essentialHeaders, $headers);
            
            if (!empty($missingEssentialHeaders)) {
                return redirect()->back()->with('error', 'Format file tidak valid. Kolom utama yang hilang: ' . implode(', ', $missingEssentialHeaders));
            }
            
            if (!empty($missingHeaders)) {
                // Just a warning about missing supporting headers
                session()->flash('warning', 'Beberapa kolom pendukung tidak ditemukan: ' . implode(', ', array_diff($supportingHeaders, $headers)) . 
                    '. Proses akan tetap dilanjutkan.');
            }
            
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
                
                // Debug first few rows
                if ($index < 3) {
                    Log::info('Blibli row ' . $rowNumber . ' data: ' . json_encode($rowData));
                }
                
                $rowIssues = [];
                
                // Validate essential fields - these must have values
                if (empty($rowData['Tanggal Pembayaran'])) {
                    $rowIssues[] = 'Tanggal Pembayaran kosong';
                }
                
                if (empty($rowData['Deskripsi'])) {
                    $rowIssues[] = 'Deskripsi kosong';
                }
                
                // Handle empty No. Pesanan by replacing with "-"
                if (!isset($rowData['No. Pesanan']) || empty($rowData['No. Pesanan'])) {
                    $rowData['No. Pesanan'] = '-';
                }
                
                if (empty($rowData['Pembayaran']) && $rowData['Pembayaran'] !== '0' && $rowData['Pembayaran'] !== 0) {
                    $rowIssues[] = 'Pembayaran kosong';
                }
                
                if (empty($rowData['Saldo Akhir']) && $rowData['Saldo Akhir'] !== '0' && $rowData['Saldo Akhir'] !== 0) {
                    $rowIssues[] = 'Saldo Akhir kosong';
                }
                
                // Convert No. Pesanan to string to avoid float precision issues
                if (isset($rowData['No. Pesanan']) && $rowData['No. Pesanan'] !== null) {
                    $rowData['No. Pesanan'] = (string)$rowData['No. Pesanan'];
                }
                
                // Clean and parse numeric values (Pembayaran and Saldo Akhir)
                if (isset($rowData['Pembayaran'])) {
                    $rowData['Pembayaran'] = $this->parseNumericValue($rowData['Pembayaran']);
                }
                
                if (isset($rowData['Saldo Akhir'])) {
                    $rowData['Saldo Akhir'] = $this->parseNumericValue($rowData['Saldo Akhir']);
                }
                
                // Add validation results to the data
                $rowData['_row_number'] = $rowNumber;
                $rowData['_valid'] = empty($rowIssues);
                $rowData['_issues'] = $rowIssues;
                
                $data[] = $rowData;
                
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
                'import_type' => 'blibli',
                'session_id' => $sessionId,
                'data' => $data,
                'issues' => $issues,
                'summary' => $summary
            ]);
            
            Log::info('Blibli Preview - Stored to database with session_id: ' . $sessionId . '. Data count: ' . count($data) . ', Valid: ' . $summary['valid']);
            
            // Cleanup old records
            ImportTemp::cleanup();
            
            return view('finance.aruskasblibli.preview', compact('data', 'issues', 'summary'))->with('import_session_id', $sessionId);
            
        } catch (\Exception $e) {
            Log::error('Arus Kas Blibli import error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function process(Request $request)
    {
        Log::info('=== BLIBLI PROCESS START ===');
        
        // Get import_session_id from request
        $sessionId = $request->input('import_session_id');
        Log::info('Blibli Process - Looking for session_id: ' . $sessionId);
        
        if (!$sessionId) {
            Log::error('Blibli Process - No session ID provided');
            return redirect()->route('finance.aruskasblibli.import')->with('error', 'Session import tidak ditemukan. Silakan upload ulang file Excel.');
        }
        
        // Ambil data dari database
        $importTemp = ImportTemp::where('import_type', 'blibli')
                                ->where('session_id', $sessionId)
                                ->first();
        
        if (!$importTemp) {
            Log::error('Blibli Process - No import data found for session_id: ' . $sessionId);
            return redirect()->route('finance.aruskasblibli.import')->with('error', 'Data import tidak ditemukan atau sudah expired. Silakan upload ulang file Excel.');
        }
        
        $data = $importTemp->data;
        $issues = $importTemp->issues;
        $summary = $importTemp->summary;
        
        Log::info('Blibli Process - Database data count: ' . ($data ? count($data) : 'null'));
        Log::info('Blibli Process - Summary: ' . json_encode($summary));
        
        if (!$data) {
            Log::error('Blibli Process - No data in database record');
            return redirect()->route('finance.aruskasblibli.import')->with('error', 'Tidak ada data untuk diproses. Silakan upload ulang file Excel.');
        }
        
        // Filter hanya data valid
        $validData = array_filter($data, function($row) {
            return $row['_valid'];
        });
        
        Log::info('Blibli Process - Valid data count: ' . count($validData));
        
        if (empty($validData)) {
            Log::error('Blibli Process - No valid data to process');
            return redirect()->route('finance.aruskasblibli.import')->with('error', 'Tidak ada data valid untuk diproses');
        }
        
        Log::info('Blibli Process - Starting database transaction');
        DB::beginTransaction();
        
        try {
            $processed = 0;
            
            foreach ($validData as $rowIndex => $row) {
                Log::info('Blibli Process - Processing row ' . ($rowIndex + 1) . ': ' . json_encode([
                    'tanggal_pembayaran' => $row['Tanggal Pembayaran'],
                    'deskripsi' => $row['Deskripsi'],
                    'pembayaran' => $row['Pembayaran'],
                    'saldo_akhir' => $row['Saldo Akhir']
                ]));
                // Parse tanggal pembayaran
                $tanggalPembayaran = null;
                if (!empty($row['Tanggal Pembayaran'])) {
                    try {
                        $tanggalPembayaran = \Carbon\Carbon::parse($row['Tanggal Pembayaran'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning('Format tanggal pembayaran tidak valid: ' . $row['Tanggal Pembayaran']);
                        continue; // Skip row with invalid date
                    }
                }
                
                // Parse tanggal pesanan
                $tanggalPesanan = null;
                if (!empty($row['Tanggal pesanan'] ?? null)) {
                    try {
                        $tanggalPesanan = \Carbon\Carbon::parse($row['Tanggal pesanan'] ?? null)->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning('Format tanggal pesanan tidak valid: ' . ($row['Tanggal pesanan'] ?? 'null'));
                        // Don't skip, just set to null
                    }
                }
                
                // Buat instance ArusKasBlibli baru
                $transaction = new ArusKasBlibli([
                    'tanggal_pembayaran' => $tanggalPembayaran,
                    'deskripsi' => $row['Deskripsi'],
                    'no_pesanan' => $row['No. Pesanan'],
                    'tanggal_pesanan' => $tanggalPesanan,
                    'pembayaran' => $row['Pembayaran'],
                    'saldo_akhir' => $row['Saldo Akhir'],
                ]);
                
                Log::info('Blibli Process - Created transaction object: ' . json_encode($transaction->toArray()));
                
                // Simpan transaksi
                $result = $transaction->save();
                Log::info('Blibli Process - Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
                
                if ($result) {
                    Log::info('Blibli Process - Transaction saved with ID: ' . $transaction->id);
                    $processed++;
                } else {
                    Log::error('Blibli Process - Failed to save transaction');
                }
            }
            
            Log::info('Blibli Process - Committing transaction. Processed: ' . $processed);
            DB::commit();
            
            // Clear database record
            $importTemp->delete();
            Log::info('Blibli Process - Cleaned up import temp record');
            
            $notProcessed = $summary['total'] - $processed;
            $processMessage = "Berhasil mengimpor {$processed} dari {$summary['total']} data arus kas Blibli.";
            
            if ($notProcessed > 0) {
                $processMessage .= " {$notProcessed} data tidak diproses karena data tidak valid.";
            }
            
            Log::info('Blibli Process - SUCCESS: ' . $processMessage);
            
            return redirect()->route('finance.aruskasblibli.index')
                ->with('success', $processMessage);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Blibli Process - EXCEPTION: ' . $e->getMessage());
            Log::error('Blibli Process - Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('finance.aruskasblibli.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
        
        Log::info('=== BLIBLI PROCESS END ===');
    }
    
    /**
     * Parse numeric value from Excel - handles various formats including Indonesian currency format
     */
    private function parseNumericValue($value)
    {
        if (empty($value) || $value === null) {
            return 0;
        }
        
        // Convert to string first
        $value = (string)$value;
        
        // Remove currency symbols, spaces, and common separators
        $cleaned = preg_replace('/[^\d,.-]/', '', $value);
        
        // Handle Indonesian format: 46.780,00 -> 46780.00
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $cleaned)) {
            // Indonesian format with thousands separator (.) and decimal comma (,)
            $cleaned = str_replace('.', '', $cleaned); // Remove thousands separators
            $cleaned = str_replace(',', '.', $cleaned); // Replace decimal comma with dot
        }
        // Handle format: 46,780.00 -> 46780.00  
        elseif (preg_match('/^\d{1,3}(,\d{3})*\.\d{2}$/', $cleaned)) {
            // US format with thousands separator (,) and decimal dot (.)
            $cleaned = str_replace(',', '', $cleaned); // Remove thousands separators
        }
        // Handle simple format: just remove any remaining separators except last decimal
        else {
            // Find last decimal point or comma
            $lastDot = strrpos($cleaned, '.');
            $lastComma = strrpos($cleaned, ',');
            
            if ($lastDot !== false && $lastComma !== false) {
                // Both exist, use the later one as decimal separator
                if ($lastDot > $lastComma) {
                    // Dot is decimal separator
                    $cleaned = str_replace(',', '', $cleaned);
                } else {
                    // Comma is decimal separator
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                }
            } elseif ($lastComma !== false) {
                // Only comma exists - treat as decimal separator if in last 3 positions
                if (strlen($cleaned) - $lastComma <= 3) {
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    $cleaned = str_replace(',', '', $cleaned);
                }
            }
        }
        
        // Convert to float
        $numericValue = (float)$cleaned;
        
        return $numericValue;
    }
} 