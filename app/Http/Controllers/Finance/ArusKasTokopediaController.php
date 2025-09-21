<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ArusKasTokopediaImport;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ArusKasTokopediaController extends Controller
{
    /**
     * Display a listing of the imported Tokopedia cash flow data
     */
    public function index(Request $request)
    {
        $query = ArusKasTokopediaImport::query();

        // Set default dates - today if no filters provided
        $startDate = $request->input('start_date', date('Y-m-d')); // Default to today
        $endDate = $request->input('end_date', date('Y-m-d')); // Default to today

        // Filter by date range
        $query->whereDate('tanggal_masuk_pembayaran', '>=', $startDate)
              ->whereDate('tanggal_masuk_pembayaran', '<=', $endDate);

        $transactions = $query->orderBy('tanggal_masuk_pembayaran', 'desc')->get();

        return view('finance.aruskastokopedia.index', compact('transactions', 'startDate', 'endDate'));
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('finance.aruskastokopedia.import');
    }

    /**
     * Preview the imported data before saving
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        
        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get all data as array (including headings)
            $rows = $worksheet->toArray();
            
            // Check if the file has any data
            if (count($rows) <= 1) {
                return redirect()->back()->with('error', 'File tidak memiliki data');
            }
            
            // Get headers from the first row and map them
            $headers = array_map('trim', $rows[0]);
            
            // Define our expected headers exactly as they appear in the file
            $expectedHeaders = [
                'Date',
                'Mutation (Debit/Credit)',
                'Description',
                'Nominal (Rp)',
                'Balance (Rp)'
            ];
            
            // Find column indexes for each expected header
            $columnIndexes = [];
            foreach ($expectedHeaders as $expectedHeader) {
                $index = array_search($expectedHeader, $headers);
                if ($index !== false) {
                    $columnIndexes[$expectedHeader] = $index;
                }
            }
            
            // Skip the header row
            array_shift($rows);
            
            // To track duplicate descriptions within the current import
            $importedDescriptions = [];
            
            // Process each row
            $data = [];
            $issues = [];
            $duplicateInImport = 0;
            $alreadyExists = 0;
            $invalidData = 0;
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Add 2 because 1-indexed and we removed header row
                
                // Create an array with our expected headers
                $rowData = [];
                foreach ($expectedHeaders as $header) {
                    if (isset($columnIndexes[$header]) && isset($row[$columnIndexes[$header]])) {
                        $rowData[$header] = $row[$columnIndexes[$header]];
                    } else {
                        $rowData[$header] = null;
                    }
                }
                
                // Special handling for date
                if (isset($rowData['Date']) && $rowData['Date'] !== null) {
                    // If the date is a number (Excel date format), convert it
                    if (is_numeric($rowData['Date'])) {
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rowData['Date']);
                        $rowData['Date'] = $date->format('Y-m-d H:i:s');
                    }
                }
                
                // Special handling for numeric fields - convert to float
                if (isset($rowData['Nominal (Rp)']) && $rowData['Nominal (Rp)'] !== null) {
                    $value = $rowData['Nominal (Rp)'];
                    if (is_string($value)) {
                        $value = preg_replace('/[^\d,.-]/', '', $value);
                        $value = str_replace(',', '', $value);
                    }
                    $rowData['Nominal (Rp)'] = (float) $value;
                }
                
                if (isset($rowData['Balance (Rp)']) && $rowData['Balance (Rp)'] !== null) {
                    $value = $rowData['Balance (Rp)'];
                    if (is_string($value)) {
                        $value = preg_replace('/[^\d,.-]/', '', $value);
                        $value = str_replace(',', '', $value);
                    }
                    $rowData['Balance (Rp)'] = (float) $value;
                }
                
                $rowIssues = [];
                
                // Validate essential fields - these must have values
                $tanggal = $rowData['Date'];
                if (empty($tanggal) || !strtotime($tanggal)) {
                    $rowIssues[] = 'Tanggal Masuk Pembayaran kosong';
                }
                
                if (empty($rowData['Description'])) {
                    $rowIssues[] = 'Description kosong';
                }
                
                if (empty($rowData['Nominal (Rp)']) && $rowData['Nominal (Rp)'] !== '0' && $rowData['Nominal (Rp)'] !== 0) {
                    $rowIssues[] = 'Nominal kosong';
                }
                
                // Skip empty rows
                if (empty($rowData['Date']) && 
                    empty($rowData['Mutation (Debit/Credit)']) && 
                    empty($rowData['Description']) && 
                    empty($rowData['Nominal (Rp)']) && 
                    empty($rowData['Balance (Rp)'])) {
                    continue;
                }
                
                // Check for duplicate descriptions (since Tokopedia doesn't have order numbers)
                if (!empty($rowData['Description'])) {
                    // Create unique key combining date, description and nominal
                    $uniqueKey = $rowData['Date'] . '|' . $rowData['Description'] . '|' . $rowData['Nominal (Rp)'];
                    
                    // Check for duplicate in the current import
                    if (in_array($uniqueKey, $importedDescriptions)) {
                        $rowIssues[] = 'Duplikasi transaksi dengan Tanggal, Deskripsi dan Nominal yang sama dalam data yang diimpor';
                        $duplicateInImport++;
                    } else {
                        $importedDescriptions[] = $uniqueKey;
                    }
                    
                    // Check duplicate entry in existing database (same date, description and nominal)
                    $exists = ArusKasTokopediaImport::where('tanggal_masuk_pembayaran', $rowData['Date'])
                        ->where('description', $rowData['Description'])
                        ->where('nominal', $rowData['Nominal (Rp)'])
                        ->exists();
                        
                    if ($exists) {
                        $rowIssues[] = 'Data dengan Tanggal, Deskripsi dan Nominal yang sama sudah ada di database';
                        $alreadyExists++;
                    }
                }
                
                // Add validation results to the data
                $rowData['_row_number'] = $rowNumber;
                $rowData['_valid'] = empty($rowIssues);
                $rowData['_issues'] = $rowIssues;
                
                $data[] = $rowData;
                
                // Add to issues list if there are problems
                if (!empty($rowIssues)) {
                    $issues[$rowNumber] = $rowIssues;
                    $invalidData++;
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
                'already_exists' => $alreadyExists,
                'invalid_data' => $invalidData,
            ];
            
            // Store in session for processing
            session(['aruskastokopedia_import_data' => $data]);
            session(['aruskastokopedia_import_issues' => $issues]);
            session(['aruskastokopedia_import_summary' => $summary]);
            
            return view('finance.aruskastokopedia.preview', compact('data', 'issues', 'summary'));
            
        } catch (\Exception $e) {
            Log::error('Arus Kas Tokopedia import error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Process and save the validated import data
     */
    public function process()
    {
        // Ambil data dari session
        $data = session('aruskastokopedia_import_data');
        $issues = session('aruskastokopedia_import_issues');
        $summary = session('aruskastokopedia_import_summary');
        
        if (!$data) {
            return redirect()->route('finance.aruskastokopedia.import')->with('error', 'Tidak ada data untuk diproses');
        }
        
        // Filter hanya data valid
        $validData = array_filter($data, function($row) {
            return $row['_valid'];
        });
        
        if (empty($validData)) {
            return redirect()->route('finance.aruskastokopedia.import')->with('error', 'Tidak ada data valid untuk diproses');
        }
        
        DB::beginTransaction();
        
        try {
            // Get Tokopedia platform ID
            $platform = Platform::where('name', 'Tokopedia')->first();
            
            if (!$platform) {
                $platform = Platform::create(['name' => 'Tokopedia']);
            }
            
            $processed = 0;
            
            foreach ($validData as $row) {
                try {
                    // Convert numeric values to float properly for Indonesian format
                    $nominal = $row['Nominal (Rp)'];
                    if (is_string($nominal)) {
                        $nominal = preg_replace('/[^\d,.-]/', '', $nominal);
                        $nominal = str_replace(',', '', $nominal);
                        $nominal = (float) $nominal;
                    } else if (is_numeric($nominal)) {
                        $nominal = (float) $nominal;
                    } else {
                        $nominal = 0;
                    }
                    $balance = $row['Balance (Rp)'];
                    if (is_string($balance)) {
                        $balance = preg_replace('/[^\d,.-]/', '', $balance);
                        $balance = str_replace(',', '', $balance);
                        $balance = (float) $balance;
                    } else if (is_numeric($balance)) {
                        $balance = (float) $balance;
                    } else {
                        $balance = 0;
                    }
                    // Log the date value before saving
                    \Log::info('Saving Tokopedia transaction', [
                        'date_raw' => $row['Date'],
                        'description' => $row['Description'],
                        'nominal' => $nominal,
                        'balance' => $balance
                    ]);
                    // Create the transaction record
                    $transaction = new ArusKasTokopediaImport([
                        'tanggal_masuk_pembayaran' => $row['Date'],
                        'mutation_type' => $row['Mutation (Debit/Credit)'],
                        'description' => $row['Description'],
                        'nominal' => $nominal,
                        'balance' => $balance,
                        'platform_id' => $platform->id,
                    ]);
                    $transaction->save();
                    $processed++;
                } catch (\Exception $e) {
                    \Log::error('Error saving Tokopedia transaction', [
                        'row' => $row,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }
            }
            
            DB::commit();
            
            // Clear session data
            session()->forget(['aruskastokopedia_import_data', 'aruskastokopedia_import_issues', 'aruskastokopedia_import_summary']);
            
            $notProcessed = $summary['total'] - $processed;
            $processMessage = "Berhasil mengimpor {$processed} dari {$summary['total']} data arus kas Tokopedia.";
            
            if ($notProcessed > 0) {
                $processMessage .= " {$notProcessed} data tidak diproses karena:";
                if ($summary['duplicate_in_import'] > 0) {
                    $processMessage .= " {$summary['duplicate_in_import']} duplikasi dalam file,";
                }
                if ($summary['already_exists'] > 0) {
                    $processMessage .= " {$summary['already_exists']} sudah ada di database,";
                }
                if ($summary['invalid_data'] > 0) {
                    $processMessage .= " {$summary['invalid_data']} data tidak valid,";
                }
                $processMessage = rtrim($processMessage, ',') . '.';
            }
            
            return redirect()->route('finance.aruskastokopedia.index')->with('success', $processMessage);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Arus Kas Tokopedia process error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('finance.aruskastokopedia.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
