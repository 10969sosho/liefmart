<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArusKasTiktokImport;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\ImportTemp;

class ArusKasTiktokController extends Controller
{
    /**
     * Display a listing of the imported TikTok cash flow data
     */
    public function index(Request $request)
    {
        $query = ArusKasTiktokImport::query();

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
            
        return view('finance.aruskastiktok.index', compact('transactions', 'startDate', 'endDate'));
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('finance.aruskastiktok.import')->withErrors([]);
    }

    /**
     * Preview the imported data before saving
     */
    public function preview(Request $request)
    {
        Log::info('=== TIKTOK PREVIEW START ===');
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        Log::info('TikTok file uploaded: ' . $file->getClientOriginalName());
        
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get highest row and column
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            // Get headers first
            $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
            
            // Get data rows with special handling for No. Pesanan
            $rows = [$headerRow]; // Start with header
            
            for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
                $rowData = [];
                for ($colIndex = 1; $colIndex <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); $colIndex++) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
                    $cell = $worksheet->getCell($cellCoordinate);
                    
                    // Check if this column is No. Pesanan
                    $headerIndex = $colIndex - 1;
                    if (isset($headerRow[$headerIndex]) && strtolower($headerRow[$headerIndex]) === 'no. pesanan') {
                        // For No. Pesanan, get the formatted value to preserve exact format
                        $rowData[] = $cell->getFormattedValue();
                    } else {
                        // For other columns, use calculated value
                        $rowData[] = $cell->getCalculatedValue();
                    }
                }
                $rows[] = $rowData;
            }
            
            Log::info('TikTok Excel rows count: ' . count($rows));
            
            // Check if the file has any data
            if (count($rows) <= 1) {
                Log::warning('TikTok Excel file has no data rows');
                return redirect()->back()->with('error', 'File tidak memiliki data');
            }
            
            // Get headers (first row) and clean them
            $headers = array_shift($rows);
            Log::info('TikTok Excel headers (raw): ' . json_encode($headers));
            
            // Clean headers - trim whitespace and remove null values
            $headers = array_map(function($header) {
                return $header !== null ? trim($header) : $header;
            }, $headers);
            
            // Remove null headers
            $headers = array_filter($headers, function($header) {
                return $header !== null && $header !== '';
            });
            
            Log::info('TikTok Excel headers (cleaned): ' . json_encode($headers));
            
            // Required column headers - Essential columns
            $essentialHeaders = [
                'Tanggal Pembayaran', 
                'Deskripsi', 
                'No. Pesanan', 
                'Pembayaran', 
                'Saldo Akhir'
            ];
            
            // Supporting columns - Not strictly required but expected
            $supportingHeaders = [
                'Tanggal Pesanan'
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
                    // Find the original case version for error message
                    $originalHeader = $supportingHeaders[array_search($supportingHeader, $supportingHeadersLower)];
                    $missingSupportingHeaders[] = $originalHeader;
                }
            }
            
            if (!empty($missingSupportingHeaders)) {
                // Just a warning about missing supporting headers
                session()->flash('warning', 'Beberapa kolom pendukung tidak ditemukan: ' . implode(', ', $missingSupportingHeaders) . 
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
                
                // Normalize column names for case-insensitive access
                $normalizedData = [];
                foreach ($rowData as $key => $value) {
                    $normalizedData[strtolower($key)] = $value;
                }
                
                // Debug normalized data keys for first few rows
                if ($index < 3) {
                    Log::info('TikTok normalized keys for row ' . $rowNumber . ': ' . json_encode(array_keys($normalizedData)));
                }
                
                // Create a standardized data array with proper case
                $standardizedData = [];
                $standardizedData['Tanggal Pembayaran'] = $normalizedData['tanggal pembayaran'] ?? null;
                $standardizedData['Deskripsi'] = $normalizedData['deskripsi'] ?? null;
                
                // For No. Pesanan, find the original value to preserve exact format
                $noPesananValue = null;
                foreach ($rowData as $key => $value) {
                    if (strtolower($key) === 'no. pesanan') {
                        $noPesananValue = $value;
                        break;
                    }
                }
                $standardizedData['No. Pesanan'] = $noPesananValue;
                
                $standardizedData['Tanggal Pesanan'] = $normalizedData['tanggal pesanan'] ?? null;
                $standardizedData['Pembayaran'] = $normalizedData['pembayaran'] ?? null;
                $standardizedData['Saldo Akhir'] = $normalizedData['saldo akhir'] ?? null;
                
                // Use standardized data for processing
                $rowData = $standardizedData;
                
                // Debug first few rows
                if ($index < 3) {
                    Log::info('TikTok row ' . $rowNumber . ' data: ' . json_encode($rowData));
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
                if (empty($rowData['No. Pesanan'])) {
                    $rowData['No. Pesanan'] = '-';
                } else {
                    // No. Pesanan sudah dalam format yang tepat dari getFormattedValue()
                    // Hanya perlu konversi ke string dan hapus .00 jika ada
                    $noPesanan = (string)$rowData['No. Pesanan'];
                    
                    // Hapus .00 di akhir jika ada
                    $noPesanan = preg_replace('/\.00$/', '', $noPesanan);
                    
                    $rowData['No. Pesanan'] = $noPesanan;
                }
                
                if (empty($rowData['Pembayaran']) && $rowData['Pembayaran'] !== '0' && $rowData['Pembayaran'] !== 0) {
                    $rowIssues[] = 'Pembayaran kosong';
                }
                
                if (empty($rowData['Saldo Akhir']) && $rowData['Saldo Akhir'] !== '0' && $rowData['Saldo Akhir'] !== 0) {
                    $rowIssues[] = 'Saldo Akhir kosong';
                }
                
                // Handle Tanggal Pesanan - process date if present
                if (isset($rowData['Tanggal Pesanan']) && !empty($rowData['Tanggal Pesanan']) && $rowData['Tanggal Pesanan'] !== '-') {
                    // Store the original raw value
                    $rawTanggalPesanan = $rowData['Tanggal Pesanan'];
                    
                    // Try to parse the date
                    try {
                        $tanggalPesanan = \Carbon\Carbon::parse($rowData['Tanggal Pesanan'])->format('Y-m-d');
                        $rowData['Tanggal Pesanan'] = $tanggalPesanan;
                        $rowData['raw_tanggal_pesanan'] = $rawTanggalPesanan;
                    } catch (\Exception $e) {
                        $rowIssues[] = 'Format tanggal pesanan tidak valid: ' . $rowData['Tanggal Pesanan'];
                        $rowData['Tanggal Pesanan'] = null;
                        $rowData['raw_tanggal_pesanan'] = $rawTanggalPesanan;
                    }
                } else {
                    // Handle empty Tanggal Pesanan by replacing with "-"
                    $rowData['Tanggal Pesanan'] = null;
                    $rowData['raw_tanggal_pesanan'] = '-';
                }
                
                // Convert No. Pesanan to string to avoid float precision issues
                // (DIHAPUS: Jangan ubah apapun pada No. Pesanan, biarkan sesuai Excel)
                
                // Store raw tanggal_pembayaran for display
                if (isset($rowData['Tanggal Pembayaran'])) {
                    $rowData['raw_tanggal_pembayaran'] = $rowData['Tanggal Pembayaran'];
                }
                
                // Clean and parse numeric values (Pembayaran and Saldo Akhir)
                if (isset($rowData['Pembayaran'])) {
                    // Store the raw value for display, but also parse for validation
                    $rawPembayaran = $rowData['Pembayaran'];
                    $rowData['Pembayaran'] = $this->parseNumericValue($rowData['Pembayaran']);
                    $rowData['raw_pembayaran'] = $rawPembayaran;
                }
                if (isset($rowData['Saldo Akhir'])) {
                    // Store the raw value for display, but also parse for validation
                    $rawSaldoAkhir = $rowData['Saldo Akhir'];
                    $rowData['Saldo Akhir'] = $this->parseNumericValue($rowData['Saldo Akhir']);
                    $rowData['raw_saldo_akhir'] = $rawSaldoAkhir;
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
                'import_type' => 'tiktok',
                'session_id' => $sessionId,
                'data' => $data,
                'issues' => $issues,
                'summary' => $summary
            ]);
            
            Log::info('TikTok Preview - Stored to database with session_id: ' . $sessionId . '. Data count: ' . count($data) . ', Valid: ' . $summary['valid']);
            
            // Cleanup old records
            ImportTemp::cleanup();
            
            return view('finance.aruskastiktok.preview', compact('data', 'issues', 'summary'))->with('import_session_id', $sessionId);
            
        } catch (\Exception $e) {
            Log::error('Arus Kas TikTok import error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Parse numeric value from Excel - handles various formats including Indonesian currency format
     */
    private function parseNumericValue($value)
    {
        if (empty($value) || $value === null) {
            return 0;
        }
        $value = (string)$value;
        $cleaned = preg_replace('/[^\d,.-]/', '', $value);
        if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (preg_match('/^\d{1,3}(,\d{3})*\.\d{2}$/', $cleaned)) {
            $cleaned = str_replace(',', '', $cleaned);
        } else {
            $lastDot = strrpos($cleaned, '.');
            $lastComma = strrpos($cleaned, ',');
            if ($lastDot !== false && $lastComma !== false) {
                if ($lastDot > $lastComma) {
                    $cleaned = str_replace(',', '', $cleaned);
                } else {
                    $cleaned = str_replace('.', '', $cleaned);
                    $cleaned = str_replace(',', '.', $cleaned);
                }
            } elseif ($lastComma !== false) {
                if (strlen($cleaned) - $lastComma <= 3) {
                    $cleaned = str_replace(',', '.', $cleaned);
                } else {
                    $cleaned = str_replace(',', '', $cleaned);
                }
            }
        }
        $numericValue = (float)$cleaned;
        return $numericValue;
    }

    /**
     * Process and save the validated import data
     */
    public function process(Request $request)
    {
        Log::info('=== TIKTOK PROCESS START ===');
        
        // Get import_session_id from request
        $sessionId = $request->input('import_session_id');
        Log::info('TikTok Process - Looking for session_id: ' . $sessionId);
        
        if (!$sessionId) {
            Log::error('TikTok Process - No session ID provided');
            return redirect()->route('finance.aruskastiktok.import')->with('error', 'Session import tidak ditemukan. Silakan upload ulang file Excel.');
        }
        
        // Ambil data dari database
        $importTemp = ImportTemp::where('import_type', 'tiktok')
                                ->where('session_id', $sessionId)
                                ->first();
        
        if (!$importTemp) {
            Log::error('TikTok Process - No import data found for session_id: ' . $sessionId);
            return redirect()->route('finance.aruskastiktok.import')->with('error', 'Data import tidak ditemukan atau sudah expired. Silakan upload ulang file Excel.');
        }
        
        $data = $importTemp->data;
        $issues = $importTemp->issues;
        $summary = $importTemp->summary;
        
        Log::info('TikTok Process - Database data count: ' . ($data ? count($data) : 'null'));
        Log::info('TikTok Process - Summary: ' . json_encode($summary));
        
        if (!$data) {
            Log::error('TikTok Process - No data in database record');
            return redirect()->route('finance.aruskastiktok.import')->with('error', 'Tidak ada data untuk diproses. Silakan upload ulang file Excel.');
        }
        
        // Filter hanya data valid
        $validData = array_filter($data, function($row) {
            return $row['_valid'];
        });
        
        Log::info('TikTok Process - Valid data count: ' . count($validData));
        
        if (empty($validData)) {
            Log::error('TikTok Process - No valid data to process');
            return redirect()->route('finance.aruskastiktok.import')->with('error', 'Tidak ada data valid untuk diproses');
        }
        
        Log::info('TikTok Process - Starting database transaction');
        DB::beginTransaction();
        
        try {
            // Get TikTok platform ID
            $platform = Platform::where('name', 'TikTok')->first();
            
            if (!$platform) {
                $platform = Platform::create(['name' => 'TikTok']);
            }
            
            $processed = 0;
            
            foreach ($validData as $rowIndex => $row) {
                Log::info('TikTok Process - Processing row ' . ($rowIndex + 1) . ': ' . json_encode([
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
                        $dateValue = $row['Tanggal Pembayaran'];
                        
                        // Handle Excel serial date (numeric format)
                        if (is_numeric($dateValue)) {
                            // Excel serial date starts from 1900-01-01 (but Excel incorrectly treats 1900 as a leap year)
                            $tanggalPembayaran = \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                                ->addDays($dateValue)
                                ->format('Y-m-d');
                            Log::info('Parsed Excel serial date: ' . $dateValue . ' -> ' . $tanggalPembayaran);
                        } else {
                            // Try common date formats
                            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
                            $parsed = false;
                            
                            foreach ($formats as $format) {
                                try {
                                    $tanggalPembayaran = \Carbon\Carbon::createFromFormat($format, $dateValue)->format('Y-m-d');
                                    $parsed = true;
                                    Log::info('Parsed date with format ' . $format . ': ' . $dateValue . ' -> ' . $tanggalPembayaran);
                                    break;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            if (!$parsed) {
                                // Last resort: use Carbon::parse
                                $tanggalPembayaran = \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
                                Log::info('Parsed date with Carbon::parse: ' . $dateValue . ' -> ' . $tanggalPembayaran);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Format tanggal pembayaran tidak valid: ' . $row['Tanggal Pembayaran'] . ' - Error: ' . $e->getMessage());
                        continue; // Skip row with invalid date
                    }
                }
                
                // Parse tanggal pesanan - preserve original format if possible
                $tanggalPesanan = null;
                if (!empty($row['Tanggal Pesanan'] ?? null) && ($row['Tanggal Pesanan'] ?? null) !== '-' && $row['Tanggal Pesanan'] !== null) {
                    try {
                        $dateValue = $row['Tanggal Pesanan'];
                        
                        // Handle Excel serial date (numeric format)
                        if (is_numeric($dateValue)) {
                            $tanggalPesanan = \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                                ->addDays($dateValue)
                                ->format('Y-m-d');
                            Log::info('Parsed Excel serial date for pesanan: ' . $dateValue . ' -> ' . $tanggalPesanan);
                        } else {
                            // Try common date formats
                            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
                            $parsed = false;
                            
                            foreach ($formats as $format) {
                                try {
                                    $tanggalPesanan = \Carbon\Carbon::createFromFormat($format, $dateValue)->format('Y-m-d');
                                    $parsed = true;
                                    Log::info('Parsed pesanan date with format ' . $format . ': ' . $dateValue . ' -> ' . $tanggalPesanan);
                                    break;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                            
                            if (!$parsed) {
                                // Last resort: use Carbon::parse
                                $tanggalPesanan = \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
                                Log::info('Parsed pesanan date with Carbon::parse: ' . $dateValue . ' -> ' . $tanggalPesanan);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Format tanggal pesanan tidak valid: ' . ($row['Tanggal Pesanan'] ?? 'null') . ' - Error: ' . $e->getMessage());
                        // Don't skip, just set to null
                    }
                }
                
                // Log final parsed dates for debugging
                Log::info('Final parsed dates for row ' . ($rowIndex + 1) . ': ' . json_encode([
                    'original_tanggal_pembayaran' => $rawTanggalPembayaran,
                    'parsed_tanggal_pembayaran' => $tanggalPembayaran,
                    'original_tanggal_pesanan' => $rawTanggalPesanan,
                    'parsed_tanggal_pesanan' => $tanggalPesanan,
                    'no_pesanan' => $row['No. Pesanan'] ?? '-'
                ]));
                
                // Get Excel row number (add 2 because: +1 for 0-based index, +1 for Excel header row)
                $excelRowNumber = $row['_row_number'] ?? ($rowIndex + 2);
                
                // Buat instance ArusKasTiktokImport baru
                $transaction = new ArusKasTiktokImport([
                    'tanggal_pembayaran' => $tanggalPembayaran,
                    'deskripsi' => $row['Deskripsi'],
                    'no_pesanan' => isset($row['No. Pesanan']) ? (string)$row['No. Pesanan'] : '-',
                    'tanggal_pesanan' => $tanggalPesanan,
                    'pembayaran' => $row['Pembayaran'],
                    'saldo_akhir' => $row['Saldo Akhir'],
                    'platform_id' => $platform->id,
                    'raw_tanggal_pembayaran' => $rawTanggalPembayaran,
                    'raw_tanggal_pesanan' => $rawTanggalPesanan,
                    'raw_pembayaran' => $rawPembayaran,
                    'raw_saldo_akhir' => $rawSaldoAkhir,
                    'excel_row_number' => $excelRowNumber,
                ]);
                
                Log::info('TikTok Process - Created transaction object: ' . json_encode($transaction->toArray()));
                
                // Simpan transaksi
                $result = $transaction->save();
                Log::info('TikTok Process - Save result: ' . ($result ? 'SUCCESS' : 'FAILED'));
                
                if ($result) {
                    Log::info('TikTok Process - Transaction saved with ID: ' . $transaction->id);
                    $processed++;
                } else {
                    Log::error('TikTok Process - Failed to save transaction');
                }
            }
            
            Log::info('TikTok Process - Committing transaction. Processed: ' . $processed);
            DB::commit();
            
            // Clear database record
            $importTemp->delete();
            Log::info('TikTok Process - Cleaned up import temp record');
            
            $notProcessed = $summary['total'] - $processed;
            $processMessage = "Berhasil mengimpor {$processed} dari {$summary['total']} data arus kas TikTok.";
            
            if ($notProcessed > 0) {
                $processMessage .= " {$notProcessed} data tidak diproses karena data tidak valid.";
            }
            
            Log::info('TikTok Process - SUCCESS: ' . $processMessage);
            
            return redirect()->route('finance.aruskastiktok.index')
                ->with('success', $processMessage);
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TikTok Process - EXCEPTION: ' . $e->getMessage());
            Log::error('TikTok Process - Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('finance.aruskastiktok.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
        
        Log::info('=== TIKTOK PROCESS END ===');
    }
} 