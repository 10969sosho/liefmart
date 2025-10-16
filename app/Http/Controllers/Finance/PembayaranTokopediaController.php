<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TokopediaFinancialTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TokopediaFinanceAnalyticsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AdjustmentHistory;

class PembayaranTokopediaController extends Controller
{
    public function index(Request $request)
    {
        $platform = 'tokopedia'; // Tetapkan platform
        
        // Base query
        $query = TokopediaFinancialTransaction::with([
            'order.orderItems.platformProduct.mappingBarang', 
            'order.orderItems.warehouseStock.tax', 
            'order.mainCategory'
        ]);
        
        // Filter by payment date range
        if ($request->filled('from_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', '<=', $request->to_date);
        }
        
        // Filter by order date range
        if ($request->filled('from_order_date')) {
            $query->whereDate('tanggal_order', '>=', $request->from_order_date);
        }
        
        if ($request->filled('to_order_date')) {
            $query->whereDate('tanggal_order', '<=', $request->to_order_date);
        }
        
        // Filter by order number
        if ($request->filled('order_number')) {
            $query->where('no_order', 'like', '%' . $request->order_number . '%');
        }
        
        // Filter by invoice number
        if ($request->filled('invoice_number')) {
            $query->where('no_invoice', 'like', '%' . $request->invoice_number . '%');
        }
        
        // Filter by tax ID
        if ($request->filled('tax_id')) {
            $taxIds = (array) $request->tax_id;
            $query->where(function($q) use ($taxIds) {
                foreach ($taxIds as $taxId) {
                    $q->orWhere('no_invoice', 'like', '%/' . str_pad($taxId, 2, '0', STR_PAD_LEFT));
                }
            });
        }
        
        // Filter by payment date
        if ($request->filled('payment_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', $request->payment_date);
        }
        
        // Filter by nominal range
        if ($request->filled('min_nominal')) {
            $query->where('nominal_fix', '>=', $request->min_nominal);
        }
        
        if ($request->filled('max_nominal')) {
            $query->where('nominal_fix', '<=', $request->max_nominal);
        }
        
        // Filter by outstanding status
        if ($request->filled('outstanding_status')) {
            if ($request->outstanding_status === '0') {
                $query->where('outstanding', 0);
            } elseif ($request->outstanding_status === '1') {
                $query->where(function($q) {
                    $q->where('outstanding', '>', 0)
                      ->orWhere('outstanding', '<', 0);
                });
            }
        }
        
        // Calculate totals for cards from ALL data (not filtered)
        $totalCount = \App\Models\TokopediaFinancialTransaction::count();
        $totalNominalFix = \App\Models\TokopediaFinancialTransaction::sum('nominal_fix');
        $totalSaldoMasuk = \App\Models\TokopediaFinancialTransaction::sum('saldo_masuk');
        $totalOutstanding = \App\Models\TokopediaFinancialTransaction::sum('outstanding');
        
        // Get transactions with pagination (this will be filtered)
        $transactions = $query->orderBy('tanggal_order', 'desc')->paginate(15);
        
        // Filter out fully returned orders from the results
        $transactions->getCollection()->transform(function($transaction) {
            // Skip transactions whose orders are fully returned
            if ($transaction->order && $transaction->order->isFullyReturned()) {
                return null;
            }
            return $transaction;
        })->filter(); // Remove null values
        
        // Get all orders that don't have financial transactions
        $missingOrders = Order::with(['orderItems', 'orderItems.platformProduct.mappingBarang'])
            ->whereDoesntHave('tokopediaFinancialTransactions')
            ->whereHas('platform', function($query) {
                $query->where('name', 'tokopedia');
            })
            ->orderBy('tanggal', 'desc')
            ->get()
            ->filter(function($order) {
                // Filter out fully returned orders
                return !$order->isFullyReturned();
            });
        
        return view('financial.tokopedia.index', compact(
            'transactions', 
            'platform',
            'totalCount',
            'totalNominalFix',
            'totalSaldoMasuk',
            'totalOutstanding',
            'missingOrders'
        ));
    }

    /**
     * Show the form for importing financial data.
     *
     * @return \Illuminate\Http\Response
     */
    public function importForm()
    {
        return view('financial.tokopedia.import');
    }

    /**
     * Alias for preview method to match route name
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importPreview(Request $request)
    {
        return $this->preview($request);
    }

    /**
     * Alias for process method to match route name
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importProcess(Request $request)
    {
        return $this->process($request);
    }

    public function import()
    {
        return view('financial.tokopedia.import');
    }

    /**
     * Preview imported data with optimized performance
     */
    public function previewDuplicateRemoved(Request $request)
    {
        // If this is a GET request, check if we have data in the session
        if ($request->isMethod('get')) {
            if (!session()->has('tokopedia_import_data')) {
                return redirect()->route('finance.tokopedia.import')
                    ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
            }
            
            // Get the data from the session
            $data = session('tokopedia_import_data');
            $previewData = session('tokopedia_preview_data');
            $previewHeaders = session('tokopedia_preview_headers');
            $headerLabels = session('tokopedia_header_labels');
            $issues = session('tokopedia_import_issues');
            $totalRows = session('tokopedia_total_rows');
            $validRows = session('tokopedia_valid_rows');
            $invalidRows = session('tokopedia_invalid_rows');
            
            // If any of the required data is missing, redirect to import
            if (!$previewData || !$previewHeaders || !$headerLabels) {
                return redirect()->route('finance.tokopedia.import')
                    ->with('error', 'Data preview tidak lengkap. Silakan upload file kembali.');
            }
            
            return view('financial.tokopedia.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
        }
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
    
        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $data = [];
        $headers = [];
        $issues = [];
        
        try {
            // Log file processing start
            Log::info('Starting Tokopedia financial import preview', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);
            
            // Load Excel file with memory optimization
            $reader = IOFactory::createReader(IOFactory::identify($path));
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            
            // Set additional reader options for better performance
            if (method_exists($reader, 'setReadFilter')) {
                // Only read first 10000 rows to prevent memory issues
                $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                    private $maxRow = 10000;
                    
                    public function readCell($column, $row, $worksheetName = '') {
                        return $row <= $this->maxRow;
                    }
                });
            }
            
            $spreadsheet = $reader->load($path);
            
            // Free up memory immediately after loading
            unset($reader);
            
            // Look for the 'Order details' sheet
            $orderDetailsSheet = null;
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                \Log::info("Found sheet: " . $sheet->getTitle());
                if (strtolower($sheet->getTitle()) === 'order details') {
                    $orderDetailsSheet = $sheet;
                    break;
                }
            }
            
            if (!$orderDetailsSheet) {
                // Let's try to use the first sheet if Order details isn't found
                $orderDetailsSheet = $spreadsheet->getActiveSheet();
                \Log::info("Using active sheet: " . $orderDetailsSheet->getTitle());
            }
            
            $worksheet = $orderDetailsSheet;
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            \Log::info("Processing sheet: " . $worksheet->getTitle() . " (Rows: $highestRow, Columns: $highestColumn)");
            
            // Get headers from first row
            $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
            $headers = array_filter($headerRow, function($value) {
                return !empty(trim($value));
            });
            
            // Standardize header names for Tokopedia
            $headerMapping = [
                'Order ID' => 'ORDER_ID',
                'Order Number' => 'ORDER_NUMBER', 
                'Order Date' => 'ORDER_DATE',
                'Payment Date' => 'PAYMENT_DATE',
                'Payment Amount' => 'PAYMENT_AMOUNT',
                'Voucher Ditanggung Penjual' => 'VOUCHER_DITANGGUNG_PENJUAL',
                'KOMISI AMS/AFFILIATE' => 'KOMISI_AMS_AFFILIATE',
                'BIAYA ADMIN' => 'BIAYA_ADMIN',
                'BIAYA LAYANAN (GRATIS ONGKIR + CASHBACK)' => 'BIAYA_LAYANAN',
                'DISKON 5' => 'DISKON_5',
                'DISKON 6' => 'DISKON_6'
            ];
            
            $standardizedHeaders = [];
            foreach ($headers as $header) {
                $standardizedHeaders[] = $headerMapping[$header] ?? $header;
            }
            
            \Log::info("Mapped headers: " . json_encode($standardizedHeaders));
            
            // Check for missing required headers
            $requiredHeaders = ['ORDER_ID', 'ORDER_NUMBER', 'PAYMENT_DATE', 'PAYMENT_AMOUNT'];
            $foundRequiredHeaders = [];
            foreach ($requiredHeaders as $requiredHeader) {
                if (in_array($requiredHeader, $standardizedHeaders)) {
                    $foundRequiredHeaders[] = $requiredHeader;
                }
            }
            $missingHeaders = array_diff($requiredHeaders, $foundRequiredHeaders);
            if (!empty($missingHeaders)) {
                \Log::warning("Missing required headers: " . json_encode($missingHeaders));
                return redirect()->back()->with('error', 'Format file tidak sesuai. Kolom yang tidak ditemukan: ' . implode(', ', $missingHeaders));
            }
            
            // Get column indexes for mapped headers
            $columnIndices = [];
            foreach ($standardizedHeaders as $index => $header) {
                $columnIndices[$header] = $index;
            }
            
            // Helper function to get value from row using standardized header name
            $getValue = function($row, $standardHeader) use ($columnIndices, $standardizedHeaders) {
                // Find the index for this standard header
                $index = array_search($standardHeader, $standardizedHeaders);
                if ($index !== false && isset($row[$index])) {
                    return $row[$index];
                }
                
                // Try alternate method using column indices
                if (isset($columnIndices[$standardHeader]) && isset($row[$columnIndices[$standardHeader]])) {
                    return $row[$columnIndices[$standardHeader]];
                }
                
                // Default to empty string if not found
                return '';
            };
            
            // Get data from Excel with row limit
            $dataRange = 'A2:' . $highestColumn . min($highestRow, 10000);
            $rows = $worksheet->rangeToArray($dataRange, null, true, false);
            
            // Free up memory from spreadsheet
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $worksheet);
            
            // Read data
            $rowNumber = 1;
            $previewData = [];
            
            $totalRows = count($rows);
            
            // Collect all order numbers first for batch query
            $orderNumbers = [];
            $processedRows = [];
            
            // First pass: collect order numbers and process basic data
            foreach ($rows as $row) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Create data array based on headers
                $rowData = [];
                $rowData['ORDER_ID'] = $getValue($row, 'ORDER_ID');
                $rowData['ORDER_NUMBER'] = $getValue($row, 'ORDER_NUMBER');
                $rowData['ORDER_DATE'] = $getValue($row, 'ORDER_DATE');
                $rowData['PAYMENT_DATE'] = $getValue($row, 'PAYMENT_DATE');
                $rowData['PAYMENT_AMOUNT'] = $getValue($row, 'PAYMENT_AMOUNT');
                $rowData['VOUCHER_DITANGGUNG_PENJUAL'] = $getValue($row, 'VOUCHER_DITANGGUNG_PENJUAL');
                $rowData['KOMISI_AMS_AFFILIATE'] = $getValue($row, 'KOMISI_AMS_AFFILIATE');
                $rowData['BIAYA_ADMIN'] = $getValue($row, 'BIAYA_ADMIN');
                $rowData['BIAYA_LAYANAN'] = $getValue($row, 'BIAYA_LAYANAN');
                $rowData['DISKON_5'] = $getValue($row, 'DISKON_5');
                $rowData['DISKON_6'] = $getValue($row, 'DISKON_6');
                
                // Collect order number for batch query
                if (!empty($rowData['ORDER_NUMBER'])) {
                    $orderNumbers[] = $rowData['ORDER_NUMBER'];
                }
                
                $processedRows[] = [
                    'rowNumber' => $rowNumber,
                    'rowData' => $rowData
                ];
            }
            
            // Batch query: Get all orders and their items in one query
            $orders = [];
            $existingTransactions = [];
            
            if (!empty($orderNumbers)) {
                // Remove duplicates to avoid unnecessary queries
                $orderNumbers = array_unique($orderNumbers);
                
                // Get all orders with their items in one query
                $orders = Order::whereIn('order_number', $orderNumbers)
                    ->with(['orderItems.warehouseStock'])
                    ->get()
                    ->keyBy('order_number');
                
                // Get all existing transactions in one query
                $existingTransactions = TokopediaFinancialTransaction::whereIn('no_order', $orderNumbers)
                    ->pluck('no_order')
                    ->toArray();
                
                \Log::info("Batch loaded " . count($orders) . " orders and " . count($existingTransactions) . " existing transactions");
            }
            
            // Second pass: process rows with pre-loaded data
            foreach ($processedRows as $processedRow) {
                $rowNumber = $processedRow['rowNumber'];
                $rowData = $processedRow['rowData'];
                
                // Validate row data
                $rowIssues = [];
                
                // 1. Validate order number
                if (empty($rowData['ORDER_NUMBER'])) {
                    $rowIssues[] = 'Nomor pesanan kosong';
                }
                
                // 2. Validate date format
                if (!empty($rowData['PAYMENT_DATE'])) {
                    // If date is an Excel date object
                    if ($rowData['PAYMENT_DATE'] instanceof \DateTime) {
                        $rowData['PAYMENT_DATE'] = $rowData['PAYMENT_DATE']->format('Y-m-d');
                    } else {
                        // Try to parse date in various formats
                        $date = null;
                        $dateValue = $rowData['PAYMENT_DATE'];
                        
                        // If it's a numeric value (Excel serial date)
                        if (is_numeric($dateValue)) {
                            try {
                                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                                $rowData['PAYMENT_DATE'] = $excelDate->format('Y-m-d');
                                $date = true;
                            } catch (\Exception $e) {
                                \Log::warning("Failed to convert Excel date: " . $e->getMessage());
                            }
                        } else {
                            // Try multiple formats
                            $formats = [
                                'Y-m-d', 'Y/m/d', 'd/m/Y', 'Y-m-d H:i:s', 'Y/m/d H:i:s',
                                'd-m-Y', 'm/d/Y', 'Y.m.d', 'd.m.Y', 'm.d.Y'
                            ];
                            
                            foreach ($formats as $format) {
                                $parsedDate = \DateTime::createFromFormat($format, $dateValue);
                                if ($parsedDate && $parsedDate->format($format) == $dateValue) {
                                    $rowData['PAYMENT_DATE'] = $parsedDate->format('Y-m-d');
                                    $date = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$date) {
                            \Log::warning("Invalid date format: " . $dateValue);
                            $rowIssues[] = 'Format tanggal tidak valid. Format yang didukung: YYYY-MM-DD, YYYY/MM/DD, DD/MM/YYYY';
                        }
                    }
                } else {
                    $rowIssues[] = 'Tanggal pembayaran kosong';
                }
                
                // 3. Validate payment amount
                if (!isset($rowData['PAYMENT_AMOUNT'])) {
                    $rowIssues[] = 'Jumlah pembayaran tidak ditemukan';
                } elseif ($rowData['PAYMENT_AMOUNT'] === '') {
                    $rowIssues[] = 'Jumlah pembayaran kosong';
                }
                
                // 4. Check if order exists in database (using pre-loaded data)
                $order = $orders[$rowData['ORDER_NUMBER']] ?? null;
                if (!$order) {
                    \Log::warning("Order tidak ditemukan: " . $rowData['ORDER_NUMBER']);
                    $rowIssues[] = 'Nomor order tidak ditemukan di database';
                    
                    // Skip this transaction instead of creating placeholder data
                    $rowData['_valid'] = false;
                    $rowData['_issues'] = $rowIssues;
                    $rowData['_row'] = $rowNumber;
                    
                    $data[] = $rowData;
                    $issues[$rowNumber] = $rowIssues;
                    continue;
                }
                
                // 5. Check if transaction already exists (using pre-loaded data)
                $transactionExists = in_array($rowData['ORDER_NUMBER'], $existingTransactions);
                if ($transactionExists) {
                    \Log::warning("Transaksi sudah ada untuk order: " . $rowData['ORDER_NUMBER']);
                    $rowIssues[] = 'Transaksi untuk order ini sudah ada';
                }
                
                // Create preview data even if transaction exists to show in the preview
                // Calculate total price from order items considering quantity (using pre-loaded data)
                $nominal_harga = 0;
                foreach ($order->orderItems as $item) {
                    $nominal_harga += $item->price_after_discount * $item->quantity;
                }
                
                // Calculate total quantity across all order items (using pre-loaded data)
                $totalQty = $order->orderItems->sum('quantity');
                
                // Preview data
                $previewData[] = [
                    'tanggal_order' => $order->tanggal,
                    'hari_order' => $order->hari,
                    'no_order' => $order->order_number,
                    'no_invoice' => 'PREVIEW-' . $order->order_number,
                    'qty' => $totalQty,
                    'nominal_harga' => $nominal_harga,
                    'nominal_diskon1' => !empty($rowData['VOUCHER_DITANGGUNG_PENJUAL']) ? -abs((float) $rowData['VOUCHER_DITANGGUNG_PENJUAL']) : 0,
                    'nominal_diskon2' => !empty($rowData['KOMISI_AMS_AFFILIATE']) ? -abs((float) $rowData['KOMISI_AMS_AFFILIATE']) : 0,
                    'nominal_diskon3' => !empty($rowData['BIAYA_ADMIN']) ? -abs((float) $rowData['BIAYA_ADMIN']) : 0,
                    'nominal_diskon4' => !empty($rowData['BIAYA_LAYANAN']) ? -abs((float) $rowData['BIAYA_LAYANAN']) : 0,
                    'nominal_diskon5' => !empty($rowData['DISKON_5']) ? -abs((float) $rowData['DISKON_5']) : 0,
                    'nominal_diskon6' => !empty($rowData['DISKON_6']) ? -abs((float) $rowData['DISKON_6']) : 0,
                    'saldo_masuk' => !empty($rowData['PAYMENT_AMOUNT']) ? (float) $rowData['PAYMENT_AMOUNT'] : 0,
                    'tanggal_masuk_pembayaran' => $rowData['PAYMENT_DATE'],
                    'hari_masuk_pembayaran' => \Carbon\Carbon::parse($rowData['PAYMENT_DATE'])->format('l'),
                    'nominal_fix' => $nominal_harga + 
                        (!empty($rowData['VOUCHER_DITANGGUNG_PENJUAL']) ? -abs((float) $rowData['VOUCHER_DITANGGUNG_PENJUAL']) : 0) +
                        (!empty($rowData['KOMISI_AMS_AFFILIATE']) ? -abs((float) $rowData['KOMISI_AMS_AFFILIATE']) : 0) +
                        (!empty($rowData['BIAYA_ADMIN']) ? -abs((float) $rowData['BIAYA_ADMIN']) : 0) +
                        (!empty($rowData['BIAYA_LAYANAN']) ? -abs((float) $rowData['BIAYA_LAYANAN']) : 0) +
                        (!empty($rowData['DISKON_5']) ? -abs((float) $rowData['DISKON_5']) : 0) +
                        (!empty($rowData['DISKON_6']) ? -abs((float) $rowData['DISKON_6']) : 0),
                    'outstanding' => 0,
                    'status' => 'preview'
                ];
                
                // Store row data with validation info
                $rowData['_valid'] = empty($rowIssues);
                $rowData['_issues'] = $rowIssues;
                $rowData['_row'] = $rowNumber;
                
                $data[] = $rowData;
                
                if (!empty($rowIssues)) {
                    $issues[$rowNumber] = $rowIssues;
                }
            }
            
            // Store data in session
            session([
                'tokopedia_import_data' => $data,
                'tokopedia_preview_data' => $previewData,
                'tokopedia_preview_headers' => $standardizedHeaders,
                'tokopedia_header_labels' => $headers,
                'tokopedia_import_issues' => $issues,
                'tokopedia_total_rows' => $totalRows,
                'tokopedia_valid_rows' => count(array_filter($data, function($row) { return $row['_valid']; })),
                'tokopedia_invalid_rows' => count(array_filter($data, function($row) { return !$row['_valid']; }))
            ]);
            
            // Generate and store process token for secure processing
            $processToken = uniqid('tokopedia_', true);
            session(['tokopedia_process_token' => $processToken]);
            
            return view('financial.tokopedia.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
            
        } catch (\Exception $e) {
            Log::error("Error during Tokopedia financial import preview: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error memproses data: ' . $e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        // If this is a GET request, check if we have data in the session
        if ($request->isMethod('get')) {
            if (!session()->has('tokopedia_import_data')) {
                return redirect()->route('finance.tokopedia.import')
                    ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
            }
            
            // Get the data from the session
            $data = session('tokopedia_import_data');
            $previewData = session('tokopedia_preview_data');
            $previewHeaders = session('tokopedia_preview_headers');
            $headerLabels = session('tokopedia_header_labels');
            $issues = session('tokopedia_issues');
            $totalRows = session('tokopedia_total_rows');
            $validRows = session('tokopedia_valid_rows');
            $invalidRows = session('tokopedia_invalid_rows');
            $transactionSummary = session('tokopedia_transaction_summary');
            
            // If any of the required data is missing, redirect to import
            if (!$previewData || !$previewHeaders || !$headerLabels) {
                return redirect()->route('finance.tokopedia.import')
                    ->with('error', 'Data preview tidak lengkap. Silakan upload file kembali.');
            }
            
            return view('financial.tokopedia.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows', 'transactionSummary'));
        }
        
        // For POST requests, validate and process the file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);
    
        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $data = [];
        $headers = [];
        $issues = [];
        $previewData = [];
        
        try {
            // Membaca file Excel
            $spreadsheet = IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Ambil header (baris pertama)
            $highestColumn = $worksheet->getHighestDataColumn();
            $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
            
            // Bersihkan header dari spasi berlebih
            $headers = array_map('trim', $headerRow);
            
            // Validasi header
            $requiredHeaders = [
                'Description',
                'Nominal (Rp)'
            ];
            
            // Cek apakah header lain tersedia
            $hasPaymentDate = in_array('TANGGAL MASUK PEMBAYARAN', $headers);
            $hasPaymentDay = in_array('HARI MASUK PEMBAYARAN', $headers);
            
            $missingHeaders = [];
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    $missingHeaders[] = $requiredHeader;
                }
            }
            
            if (!empty($missingHeaders)) {
                return redirect()->back()->with('error', 'Header yang diperlukan tidak ditemukan: ' . implode(', ', $missingHeaders));
            }
            
            // Proses data per baris dengan logic yang diperbaiki
            $rows = $worksheet->rangeToArray('A2:' . $highestColumn . $worksheet->getHighestRow(), null, true, false);
            
            // Use the improved processing method
            $processResult = $this->processExcelRowsImproved($rows, $headers, $hasPaymentDate, $hasPaymentDay);
            $transactionData = $processResult['transaction_data'];
            $transactionSummary = $processResult['summary'];
            
            // Sekarang proses transaksi dengan structure yang baru
            $data = [];
            $issues = [];
            $previewData = [];
            
            foreach ($transactionData as $orderNumber => $transactionInfo) {
                $rowData = [];
                $rowIssues = [];
                
                // Set order number
                $rowData['no_order'] = $orderNumber;
                
                // Validasi order di database
                $order = Order::where('order_number', $orderNumber)->first();
                if (!$order) {
                    $rowIssues[] = 'Nomor order tidak ditemukan di database';
                } else {
                    // Cek jika transaksi sudah ada
                    $transactionExists = TokopediaFinancialTransaction::where('no_order', $orderNumber)->exists();
                    if ($transactionExists) {
                        $rowIssues[] = 'Transaksi untuk order ini sudah ada';
                    }
                    
                    // Set data dari order
                    $rowData['tanggal_order'] = $order->tanggal;
                    $rowData['hari_order'] = $order->hari;
                    
                    // Use the main transaction amount (gross amount before deductions)
                    $rowData['nominal_harga'] = $transactionInfo['main_transaction'];
                    
                    // Map costs to biaya fields
                    $biayaFields = $this->mapCostsToBiayaFields($transactionInfo['costs']);
                    foreach ($biayaFields as $field => $value) {
                        $rowData[$field] = $value;
                    }
                    
                    // Use withdrawal amount as saldo_masuk if available
                    if ($transactionInfo['withdrawal_amount'] > 0) {
                        $rowData['saldo_masuk'] = $transactionInfo['withdrawal_amount'];
                    } else {
                        // No withdrawal amount available, set saldo_masuk to 0
                        $rowData['saldo_masuk'] = 0;
                    }
                    
                    // Calculate nominal_fix properly (will be recalculated later with method)
                    $rowData['nominal_fix'] = $rowData['saldo_masuk']; // Temporary value, will be recalculated
                    
                    // Set payment date and day
                    $rowData['tanggal_masuk_pembayaran'] = date('Y-m-d');
                    $rowData['hari_masuk_pembayaran'] = date('l');
                    
                    // Use payment date from Excel if available
                    if ($transactionInfo['payment_date']) {
                        $rowData['tanggal_masuk_pembayaran'] = $transactionInfo['payment_date'];
                    }
                    
                    if ($transactionInfo['payment_day']) {
                        $rowData['hari_masuk_pembayaran'] = $transactionInfo['payment_day'];
                    }
                    
                    // Calculate outstanding
                    $rowData['outstanding'] = $rowData['nominal_fix'] - $rowData['saldo_masuk'];
                    
                    // Set quantity from order items
                    if ($order) {
                        $rowData['qty'] = $order->orderItems->sum('quantity');
                    } else {
                        $rowData['qty'] = 0;
                    }
                }
                
                // Tambahkan informasi validasi
                $rowData['_valid'] = empty($rowIssues);
                $rowData['_issues'] = $rowIssues;
                $rowData['_row'] = $transactionInfo['row'];
                
                // Jika ada masalah, tambahkan ke array issues
                if (!empty($rowIssues)) {
                    $issues[$transactionInfo['row']] = $rowIssues;
                }
                
                // Tambahkan ke data utama
                $data[] = $rowData;
                
                // Jika valid, tambahkan ke preview data
                if (empty($rowIssues)) {
                    $previewData[] = $rowData;
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
        
        // Update transaction summary dengan data transaksi yang valid/invalid
        $transactionSummary['processed_transactions'] = count($data);
        $transactionSummary['valid_transactions'] = count($previewData);
        $transactionSummary['invalid_transactions'] = count($data) - count($previewData);
        
        // Simpan data preview di session
        session(['tokopedia_import_data' => $data]);
        session(['tokopedia_import_issues' => $issues]);
        session(['tokopedia_preview_data' => $previewData]);
        session(['tokopedia_transaction_summary' => $transactionSummary]);
        
        // Hitung statistik
        $totalRows = count($data);
        $validRows = count(array_filter($data, function($row) { return $row['_valid']; }));
        $invalidRows = $totalRows - $validRows;
        
        // Simpan statistik di session juga
        session(['tokopedia_total_rows' => $totalRows]);
        session(['tokopedia_valid_rows' => $validRows]);
        session(['tokopedia_invalid_rows' => $invalidRows]);
        
        // Definisi kolom tampilan untuk preview
        $previewHeaders = [
            'tanggal_order', 'hari_order', 'no_order', 'no_invoice', 
            'nominal_harga', 'nominal_diskon1', 'nominal_diskon2', 
            'nominal_diskon3', 'nominal_diskon4', 'nominal_diskon5', 
            'nominal_diskon6', 'nominal_diskon7', 'nominal_diskon8', 
            'nominal_diskon9', 'nominal_diskon10', 'nominal_diskon11', 
            'nominal_diskon12', 'adjustment', 'nominal_fix', 'qty', 'saldo_masuk', 
            'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran', 
            'outstanding'
        ];
        
        // Label header yang lebih user-friendly
        $headerLabels = [
            'tanggal_order' => 'Tanggal Order',
            'hari_order' => 'Hari Order',
            'no_order' => 'No. Order',
            'no_invoice' => 'No. Invoice',
            'nominal_harga' => 'Nominal Harga',
            'nominal_diskon1' => 'Komisi',
            'nominal_diskon2' => 'Biaya Layanan',
            'nominal_diskon3' => 'Biaya Admin',
            'nominal_diskon4' => 'Ongkir',
            'nominal_diskon5' => 'Cashback',
            'nominal_diskon6' => 'Voucher',
            'nominal_diskon7' => 'Biaya Lain 1',
            'nominal_diskon8' => 'Biaya Lain 2',
            'nominal_diskon9' => 'Biaya Lain 3',
            'nominal_diskon10' => 'Biaya Lain 4',
            'nominal_diskon11' => 'Biaya Lain 5',
            'nominal_diskon12' => 'Biaya Lain 6',
            'adjustment' => 'Adjustment',
            'nominal_fix' => 'Nominal Fix',
            'qty' => 'Qty',
            'saldo_masuk' => 'Saldo Masuk',
            'tanggal_masuk_pembayaran' => 'Tanggal Masuk Pembayaran',
            'hari_masuk_pembayaran' => 'Hari Masuk Pembayaran',
            'outstanding' => 'Outstanding'
        ];
        
        // Simpan header di session
        session(['tokopedia_preview_headers' => $previewHeaders]);
        session(['tokopedia_header_labels' => $headerLabels]);
        
        return view('financial.tokopedia.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows', 'transactionSummary'));
    }

    public function process(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Get the processed data from session instead of form data
            $sessionData = session('tokopedia_import_data');
            if (!$sessionData) {
                return redirect()->route('finance.tokopedia.import')
                    ->with('error', 'Data import tidak ditemukan. Silakan upload ulang file.');
            }
            
            // Filter only valid data
            $validData = array_filter($sessionData, function($row) {
                return $row['_valid'] === true || $row['_valid'] === 'true';
            });
            
            $importCount = 0;
            
            foreach ($validData as $row) {
                // Find the order
                $order = Order::where('order_number', $row['no_order'])->first();
                    
                    if ($order) {
                    // Check if transaction already exists for this order
                    $exists = TokopediaFinancialTransaction::where('order_id', $order->id)->exists();
                    
                    if (!$exists) {
                        // Create transaction data from the processed row
                        $transactionData = [
                            'DESCRIPTION' => $row['description'] ?? 'Tokopedia Transaction',
                            'TANGGAL' => $row['tanggal_masuk_pembayaran'] ?? date('Y-m-d'),
                            'AMOUNT' => $row['saldo_masuk'] ?? 0,
                            'nominal_diskon1' => $row['nominal_diskon1'] ?? 0,
                            'nominal_diskon2' => $row['nominal_diskon2'] ?? 0,
                            'nominal_diskon3' => $row['nominal_diskon3'] ?? 0,
                            'nominal_diskon4' => $row['nominal_diskon4'] ?? 0,
                            'nominal_diskon5' => $row['nominal_diskon5'] ?? 0,
                            'nominal_diskon6' => $row['nominal_diskon6'] ?? 0,
                            'nominal_diskon7' => $row['nominal_diskon7'] ?? 0,
                            'nominal_diskon8' => $row['nominal_diskon8'] ?? 0,
                            'nominal_diskon9' => $row['nominal_diskon9'] ?? 0,
                            'nominal_diskon10' => $row['nominal_diskon10'] ?? 0,
                            'nominal_diskon11' => $row['nominal_diskon11'] ?? 0,
                            'nominal_diskon12' => $row['nominal_diskon12'] ?? 0,
                            'group_value' => $row['nominal_harga'] ?? 0,
                            'group_qty' => $row['qty'] ?? 0
                        ];
                        
                        // Use default tax_id 3 for online PKP
                        $this->createTransaction($order, $transactionData, 3);
                        $importCount++;
                    } else {
                        \Log::info("Transaction already exists for order: " . $row['no_order']);
                    }
                } else {
                    \Log::warning("Order not found: " . $row['no_order']);
                }
            }
            
            DB::commit();
            
            return redirect()->route('finance.tokopedia.index')
                ->with('success', "Successfully imported $importCount financial transactions.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error during Tokopedia financial import: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error processing data: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a transaction from imported data
     */
    protected function createTransaction($order, $data, $taxId)
    {
        $transaction = new TokopediaFinancialTransaction();
        
        // Set order relationship
        $transaction->order_id = $order->id;
        
        // Set basic info from order
        $transaction->tanggal_order = $order->tanggal;
        $transaction->hari_order = $order->hari;
        $transaction->no_order = $order->order_number;
        
        // Generate invoice number based on tax_id
        $transaction->no_invoice = TokopediaFinancialTransaction::generateInvoiceNumber($order, $taxId);
        
        // Use group values if available (from proportion calculation), otherwise use full order values
        if (isset($data['group_qty']) && isset($data['group_value'])) {
            // Use proportional values
            $transaction->qty = $data['group_qty'];
            $transaction->nominal_harga = round($data['group_value'], 2);
        } else {
            // Calculate total qty from order items (fallback for single tax group)
            $totalQty = 0;
            foreach ($order->orderItems as $item) {
                $totalQty += $item->quantity;
            }
            $transaction->qty = $totalQty;
            
            // Calculate total price from order items considering quantity (fallback)
            $totalPrice = 0;
            foreach ($order->orderItems as $item) {
                $totalPrice += $item->price_after_discount * $item->quantity;
            }
            $transaction->nominal_harga = $totalPrice;
        }
        
        // Set discount values from data
        $transaction->nominal_diskon1 = $data['nominal_diskon1'] ?? 0;
        $transaction->nominal_diskon2 = $data['nominal_diskon2'] ?? 0;
        $transaction->nominal_diskon3 = $data['nominal_diskon3'] ?? 0;
        $transaction->nominal_diskon4 = $data['nominal_diskon4'] ?? 0;
        $transaction->nominal_diskon5 = $data['nominal_diskon5'] ?? 0;
        $transaction->nominal_diskon6 = $data['nominal_diskon6'] ?? 0;
        
        // Set payment info - use current date if not provided
        $paymentDate = isset($data['TANGGAL']) ? Carbon::parse($data['TANGGAL']) : Carbon::now();
        $transaction->tanggal_masuk_pembayaran = $paymentDate->format('Y-m-d');
        $transaction->hari_masuk_pembayaran = $paymentDate->format('l');
        $transaction->saldo_masuk = isset($data['AMOUNT']) ? (float) $data['AMOUNT'] : 0;
                
        // Calculate nominal_fix and other values
        $transaction->calculateNominalFix()
                   ->calculateOutstanding()
                   ->calculatePercentages();
        
        // Add validation for potential data inconsistencies
        $expectedOrderTotal = 0;
        foreach ($order->orderItems as $item) {
            $expectedOrderTotal += $item->price_after_discount * $item->quantity;
        }
        
        // Check if nominal_harga is significantly higher than expected
        if ($transaction->nominal_harga > $expectedOrderTotal * 1.5) {
            \Log::warning("Potential data inconsistency detected for order {$order->order_number}: nominal_harga={$transaction->nominal_harga}, expected_order_total={$expectedOrderTotal}");
        }
        
        // Add validation for potential overpayment scenarios
        if ($transaction->saldo_masuk > 0 && $transaction->nominal_fix > 0) {
            $ratio = $transaction->saldo_masuk / $transaction->nominal_fix;
            if ($ratio > 3.0) { // If payment is more than 3x the expected amount
                \Log::warning("Potential overpayment detected for order {$order->order_number}: saldo_masuk={$transaction->saldo_masuk}, nominal_fix={$transaction->nominal_fix}");
            }
        }
        
        $transaction->save();
        return $transaction;
    }

    public function manual()
    {
        // Implementasi halaman manual input jika diperlukan
        return view('financial.tokopedia.manual');
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'saldo_masuk' => 'required|numeric',
            'tanggal_masuk_pembayaran' => 'required|date',
            'hari_masuk_pembayaran' => 'required|string',
            'nominal_diskon1' => 'nullable|numeric',
            'nominal_diskon2' => 'nullable|numeric',
            'nominal_diskon3' => 'nullable|numeric',
            'nominal_diskon4' => 'nullable|numeric',
            'nominal_diskon5' => 'nullable|numeric',
            'nominal_diskon6' => 'nullable|numeric',
        ]);
        
        try {
            DB::beginTransaction();
            
            $order = Order::with(['orderItems.warehouseStock.tax', 'mainCategory'])->findOrFail($request->order_id);
            
            // Check if transaction already exists for this order
            $exists = TokopediaFinancialTransaction::where('order_id', $order->id)->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'Transaksi untuk order ini sudah ada.');
            }
            
            // Get order items with their warehouse stocks
            $orderItems = $order->orderItems()->with('warehouseStock')->get();
            
            // Get BarangKeluar items for this order for proportional calculations
            $barangKeluarItems = \App\Models\BarangKeluar::whereHas('orderItem', function($query) use ($order) {
                $query->where('order_id', $order->id);
            })->with('warehouseStock', 'orderItem')->get();
            
            // Group BarangKeluar by tax_id
            $itemsByTaxId = [];
            $totalQty = 0;
            
            foreach ($barangKeluarItems as $item) {
                if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                    $taxId = $item->warehouseStock->tax_id;
                    if (!isset($itemsByTaxId[$taxId])) {
                        $itemsByTaxId[$taxId] = [];
                    }
                    $itemsByTaxId[$taxId][] = $item;
                    $totalQty += $item->qty;
                }
            }
            
            // If no BarangKeluar records found, fall back to using order items
            if (empty($itemsByTaxId)) {
                foreach ($orderItems as $item) {
                    if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                        $taxId = $item->warehouseStock->tax_id;
                        if (!isset($itemsByTaxId[$taxId])) {
                            $itemsByTaxId[$taxId] = [];
                        }
                        $itemsByTaxId[$taxId][] = $item;
                        $totalQty += $item->quantity;
                    }
                }
            }
            
            // If still no items found, use default tax_id
            if (empty($itemsByTaxId)) {
                $itemsByTaxId[3] = $orderItems->toArray(); // Default to PKP Skincare (tax_id 3)
            }
            
            // Calculate total order price
            $totalOrderPrice = 0;
            foreach ($order->orderItems as $item) {
                $totalOrderPrice += $item->price_after_discount * $item->quantity;
            }
            
            // Get input values
            $nominal_diskon1 = $request->nominal_diskon1 ?? 0;
            $nominal_diskon2 = $request->nominal_diskon2 ?? 0;
            $nominal_diskon3 = $request->nominal_diskon3 ?? 0;
            $nominal_diskon4 = $request->nominal_diskon4 ?? 0;
            $nominal_diskon5 = $request->nominal_diskon5 ?? 0;
            $nominal_diskon6 = $request->nominal_diskon6 ?? 0;
            
            // Process each tax group and create a transaction for each
            foreach ($itemsByTaxId as $taxId => $items) {
                // Calculate value-based proportion for this tax group
                $groupQty = 0;
                $groupValue = 0;
                
                foreach ($items as $item) {
                    $itemQty = $item->qty ?? $item->quantity ?? 0;
                    $groupQty += $itemQty;
                    
                    // Calculate value based on actual BarangKeluar item
                    if ($item instanceof \App\Models\BarangKeluar && $item->orderItem) {
                        // Get the unit price from order item
                        $unitPrice = $item->orderItem->price_after_discount;
                        $groupValue += $unitPrice * $itemQty;
                    } else {
                        // Fallback: use price_after_discount from order item
                        $unitPrice = $item->price_after_discount ?? 0;
                        $groupValue += $unitPrice * $itemQty;
                    }
                }
                
                // Calculate proportion based on VALUE, not quantity
                $proportion = ($totalOrderPrice > 0) ? $groupValue / $totalOrderPrice : 1;
                
                // Prepare data for createTransaction with proportional values
                $transactionData = [
                    'DESCRIPTION' => '',
                    'TANGGAL' => $request->tanggal_masuk_pembayaran,
                    'AMOUNT' => (float)$request->saldo_masuk * $proportion,
                    'nominal_diskon1' => (float)$nominal_diskon1 * $proportion,
                    'nominal_diskon2' => (float)$nominal_diskon2 * $proportion,
                    'nominal_diskon3' => (float)$nominal_diskon3 * $proportion,
                    'nominal_diskon4' => (float)$nominal_diskon4 * $proportion,
                    'nominal_diskon5' => (float)$nominal_diskon5 * $proportion,
                    'nominal_diskon6' => (float)$nominal_diskon6 * $proportion,
                    'group_value' => $groupValue,
                    'group_qty' => $groupQty
                ];
                
                $this->createTransaction($order, $transactionData, $taxId);
            }
            
            DB::commit();
            
            return redirect()->route('finance.tokopedia.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menambahkan transaksi Tokopedia manual: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Adjust transaction value
     */
    public function adjust(Request $request, $id)
    {
        $request->validate([
            'adjustment' => 'required|numeric',
            'adjustment_description' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction = TokopediaFinancialTransaction::findOrFail($id);
            
            // Check if the transaction is locked
            if ($transaction->isLocked()) {
                return redirect()->back()->with('error', 'Transaksi ini telah dikunci dan tidak dapat diubah. Hubungi administrator untuk membuka kunci.');
            }
            
            // Save previous values for history
            $oldValues = [
                'adjustment' => $transaction->adjustment,
                'adjustment_description' => $transaction->adjustment_description,
                'nominal_fix' => $transaction->nominal_fix,
                'outstanding' => $transaction->outstanding
            ];
            
            $oldAdjustment = $transaction->adjustment;
            $transaction->adjustment = $request->adjustment;
            $transaction->adjustment_description = $request->adjustment_description;
            
            // Calculate total discount from all discount fields
            $totalDiskon = 
                ($transaction->nominal_diskon1 ?? 0) + 
                ($transaction->nominal_diskon2 ?? 0) + 
                ($transaction->nominal_diskon3 ?? 0) + 
                ($transaction->nominal_diskon4 ?? 0) + 
                ($transaction->nominal_diskon5 ?? 0) + 
                ($transaction->nominal_diskon6 ?? 0) + 
                ($transaction->nominal_diskon7 ?? 0) + 
                ($transaction->nominal_diskon8 ?? 0) + 
                ($transaction->nominal_diskon9 ?? 0) + 
                ($transaction->nominal_diskon10 ?? 0) + 
                ($transaction->nominal_diskon11 ?? 0) + 
                ($transaction->nominal_diskon12 ?? 0);
                
            // Recalculate nominal_fix including adjustment
            $transaction->nominal_fix = $transaction->nominal_harga - $totalDiskon + $transaction->adjustment;
            $transaction->outstanding = $transaction->nominal_fix - $transaction->saldo_masuk;
            $transaction->calculatePercentages();
            
            // Simpan ke adjustment_histories
            AdjustmentHistory::create([
                'transaction_id' => $transaction->id,
                'platform' => 'tokopedia',
                'old_value' => $oldAdjustment,
                'new_value' => $transaction->adjustment,
                'description' => $request->adjustment_description,
                'user_id' => auth()->id(),
            ]);
            
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.tokopedia.index')
                ->with('success', 'Adjustment berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat adjust transaksi Tokopedia: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $transaction = TokopediaFinancialTransaction::findOrFail($id);
            
            // Log the deletion for audit purposes
            Log::info('Deleting Tokopedia transaction', [
                'transaction_id' => $transaction->id,
                'order_number' => $transaction->no_order,
                'invoice_number' => $transaction->no_invoice,
                'amount' => $transaction->nominal_fix,
                'deleted_by' => auth()->id() ?? 'system'
            ]);
            
            // Delete the transaction
            $transaction->delete();
            
            DB::commit();
            
            return redirect()->route('finance.tokopedia.index')
                ->with('success', 'Transaksi berhasil dihapus.');
                
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error("Transaksi tidak ditemukan: " . $e->getMessage());
            return redirect()->back()->with('error', 'Transaksi tidak ditemukan.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menghapus transaksi Tokopedia: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Print invoice for a transaction
     * 
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($id)
    {
        try {
            $transaction = TokopediaFinancialTransaction::with([
                    'order.orderItems.platformProduct', // Removed .products to avoid potential errors
                    'order.orderItems.warehouseStock.tax'
                ])
                ->findOrFail($id);
                
            // Determine the tax_id from the invoice number and set logo accordingly
            $taxId = null;
            $logoFile = 'HGN.jpeg'; // Default logo file
            $isPKP = true; // Default to PKP
            
            if (strpos($transaction->no_invoice, 'HPNSDA-OLK/01') !== false) {
                $taxId = 1; // PKP - Coffee
                $logoFile = 'HGN.jpeg';
                $isPKP = true;
            } elseif (strpos($transaction->no_invoice, 'HPNSDA-OLK/02') !== false) {
                $taxId = 2; // Non PKP - Coffee
                $logoFile = 'LM.jpeg';
                $isPKP = false;
            } elseif (strpos($transaction->no_invoice, 'HGNSDA-OL/01') !== false) {
                $taxId = 3; // PKP - Skincare
                $logoFile = 'HGN.jpeg';
                $isPKP = true;
            } elseif (strpos($transaction->no_invoice, 'HGNSDA-OL/02') !== false) {
                $taxId = 4; // Non PKP - Skincare
                $logoFile = 'LM.jpeg';
                $isPKP = false;
            } else {
                // If we can't determine from pattern, extract the last 2 digits
                preg_match('/(\d{2})$/', $transaction->no_invoice, $matches);
                if (!empty($matches[1])) {
                    $taxId = (int) $matches[1];
                    $isPKP = in_array($taxId, [1, 3, 5, 7]);
                    $logoFile = $isPKP ? 'HGN.jpeg' : 'LM.jpeg';
                }
            }
            
            return view('financial.tokopedia.print-invoice', compact('transaction', 'logoFile', 'isPKP'));
        } catch (\Exception $e) {
            Log::error("Error saat print invoice Tokopedia: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencetak invoice: ' . $e->getMessage());
        }
    }

    /**
     * Lock a transaction
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function lock($id)
    {
        try {
            $transaction = TokopediaFinancialTransaction::findOrFail($id);
            $transaction->lock(auth()->id());
            
            return redirect()->back()->with('success', 'Transaksi berhasil dikunci.');
        } catch (\Exception $e) {
            Log::error("Error saat mengunci transaksi Tokopedia: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Unlock a transaction
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function unlock($id)
    {
        try {
            $transaction = TokopediaFinancialTransaction::findOrFail($id);
            
            // Only admin or the person who locked it can unlock
            if (auth()->user()->role != 'admin' && $transaction->locked_by != auth()->id()) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk membuka kunci transaksi ini.');
            }
            
            $transaction->unlock();
            
            return redirect()->back()->with('success', 'Kunci transaksi berhasil dibuka.');
        } catch (\Exception $e) {
            Log::error("Error saat membuka kunci transaksi Tokopedia: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * View transaction history
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function history($id)
    {
        try {
            $transaction = TokopediaFinancialTransaction::with('lockedByUser')->findOrFail($id);
            $adjustmentHistories = \App\Models\AdjustmentHistory::where('transaction_id', $transaction->id)
                ->where('platform', 'tokopedia')
                ->orderBy('created_at', 'desc')
                ->with('user')
                ->get();
            return view('financial.tokopedia.history', compact('transaction', 'adjustmentHistories'));
        } catch (\Exception $e) {
            Log::error("Error saat melihat history transaksi Tokopedia: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Export data to Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $filename = 'tokopedia_finance_analytics_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new TokopediaFinanceAnalyticsExport($request->all()), $filename);
    }

    /**
     * Export data to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $query = TokopediaFinancialTransaction::query();
        
        // Apply the same filters as in index method
        // Filter by payment date range
        if ($request->filled('from_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', '<=', $request->to_date);
        }
        
        // Filter by order date range
        if ($request->filled('from_order_date')) {
            $query->whereDate('tanggal_order', '>=', $request->from_order_date);
        }
        
        if ($request->filled('to_order_date')) {
            $query->whereDate('tanggal_order', '<=', $request->to_order_date);
        }
        
        // Filter by order number
        if ($request->filled('order_number')) {
            $query->where('no_order', 'like', '%' . $request->order_number . '%');
        }
        
        // Filter by invoice number
        if ($request->filled('invoice_number')) {
            $query->where('no_invoice', 'like', '%' . $request->invoice_number . '%');
        }
        
        // Filter by tax ID
        if ($request->filled('tax_id')) {
            $taxIds = (array) $request->tax_id;
            $query->where(function($q) use ($taxIds) {
                foreach ($taxIds as $taxId) {
                    $q->orWhere('no_invoice', 'like', '%/' . str_pad($taxId, 2, '0', STR_PAD_LEFT));
                }
            });
        }
        
        // Filter by payment date
        if ($request->filled('payment_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', $request->payment_date);
        }
        
        // Filter by nominal range
        if ($request->filled('min_nominal')) {
            $query->where('nominal_fix', '>=', $request->min_nominal);
        }
        
        if ($request->filled('max_nominal')) {
            $query->where('nominal_fix', '<=', $request->max_nominal);
        }
        
        // Filter by outstanding status
        if ($request->filled('outstanding_status')) {
            if ($request->outstanding_status === '0') {
                $query->where('outstanding', 0);
            } elseif ($request->outstanding_status === '1') {
                $query->where(function($q) {
                    $q->where('outstanding', '>', 0)
                      ->orWhere('outstanding', '<', 0);
                });
            }
        }
        
        $transactions = $query->orderBy('tanggal_order', 'desc')->get();
        
        $pdf = Pdf::loadView('exports.financial.tokopedia', compact('transactions'))
                  ->setPaper('a4', 'landscape');
        
        return $pdf->download('tokopedia_finance_analytics_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    private function mapDescriptionToCostCategory($description)
    {
        // CORRECTED mapping based on screenshot analysis
        // Proper separation of costs into correct fields
        // ORDER MATTERS! More specific patterns must come FIRST
        $mappings = [
            // Main transaction - this is the gross amount before any deductions
            'Transaksi Penjualan Berhasil' => 'main_transaction',
            'HARGA SETELAH DISKON' => 'main_transaction',
            
            // Cost categories - these will be mapped to specific nominal_diskon fields
            'PENARIKAN' => 'withdrawal', // This is usually the final amount received
            
            // REVERTED: Back to original mapping, will fix headers instead
            // Biaya Admin -> nominal_diskon2 (These contain "Bebas Ongkir")
            'Pemotongan Biaya Layanan Bebas Ongkir Power Merchant' => 'biaya_admin',
            'Biaya Layanan Bebas Ongkir Power Merchant' => 'biaya_admin',
            'Biaya Layanan Bebas Ongkir' => 'biaya_admin',
            'Bebas Ongkir Power Merchant' => 'biaya_admin',
            'Bebas Ongkir' => 'biaya_admin',
            'BIAYA ADMIN' => 'biaya_admin',
            'Biaya Admin' => 'biaya_admin',
            'ADMIN' => 'biaya_admin',
            'Admin' => 'biaya_admin',
            
            // Biaya Layanan -> nominal_diskon3 (Standard service fees WITHOUT "Bebas Ongkir")
            'Pemotongan Biaya Layanan Power Merchant' => 'biaya_layanan',
            'Biaya Layanan Power Merchant' => 'biaya_layanan', 
            'Power Merchant' => 'biaya_layanan',
            'BIAYA LAYANAN' => 'biaya_layanan',
            'Biaya Layanan' => 'biaya_layanan',
            
            // All other costs get their own categories (will map to nominal_diskon3, 4, 5, etc.)
            'Pemotongan Komisi' => 'komisi',
            'KOMISI' => 'komisi',
            'ONGKIR' => 'ongkir',
            'CASHBACK' => 'cashback',
            'VOUCHER' => 'voucher',
            'SUBSIDI' => 'subsidi',
            'INSENTIF' => 'insentif',
            'PAJAK' => 'pajak',
            'PPH' => 'pph',
            'PPN' => 'ppn',
        ];
        
        // Log the description being processed for debugging
        \Log::info('Processing description for cost category mapping', [
            'description' => $description,
            'cleaned' => trim($description)
        ]);
        
        // Check for exact matches first (case-insensitive)
        // IMPORTANT: Order matters - more specific patterns checked first
        $description = trim($description);
        
        // Special priority check for "Bebas Ongkir" - always admin fee (REVERTED)
        if (stripos($description, 'Bebas Ongkir') !== false) {
            \Log::info('Matched description to ADMIN (Bebas Ongkir pattern)', [
                'description' => $description,
                'category' => 'biaya_admin'
            ]);
            return 'biaya_admin';
        }
        
        foreach ($mappings as $keyword => $category) {
            if (stripos($description, $keyword) !== false) {
                \Log::info('Matched description to category', [
                    'description' => $description,
                    'keyword' => $keyword,
                    'category' => $category
                ]);
                return $category;
            }
        }
        
        // If no match found, check if it's a withdrawal (final payment)
        if (stripos($description, 'Withdrawal') !== false || 
            stripos($description, 'TBK') !== false || 
            stripos($description, 'BANK') !== false) {
            \Log::info('Matched description as withdrawal', ['description' => $description]);
            return 'withdrawal';
        }
        
        \Log::warning('No category match found for description', ['description' => $description]);
        return 'unknown';
    }
    
    private function processExcelRowsImproved($rows, $headers, $hasPaymentDate, $hasPaymentDay)
    {
        $transactionData = [];
        $totalRowsScanned = 0;
        $transactionRows = 0;
        $discountRows = 0;
        $ignoredRows = 0;
        $withdrawalRows = 0;
        
        foreach ($rows as $row) {
            $totalRowsScanned++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                $ignoredRows++;
                continue;
            }
            
            // Ensure we have enough columns
            if (count($row) < count($headers)) {
                $ignoredRows++;
                continue;
            }
            
            // Create data array based on headers
            $rowData = [];
            foreach ($headers as $index => $header) {
                if (isset($row[$index])) {
                    $rowData[$header] = $row[$index];
                }
            }
            
            // Extract order number from description
            if (!empty($rowData['Description'])) {
                if (preg_match('/INV\/\d{8}\/MPL\/\d+/', $rowData['Description'], $matches)) {
                    $orderNumber = $matches[0];
                    
                    // Determine the category of this row
                    $category = $this->mapDescriptionToCostCategory($rowData['Description']);
                    
                    \Log::info('Processing Excel row for order', [
                        'order_number' => $orderNumber,
                        'description' => $rowData['Description'],
                        'category' => $category,
                        'nominal' => $rowData['Nominal (Rp)'] ?? 'N/A'
                    ]);
                    
                    // Initialize transaction data if not exists
                    if (!isset($transactionData[$orderNumber])) {
                        $transactionData[$orderNumber] = [
                            'row' => $totalRowsScanned,
                            'order_number' => $orderNumber,
                            'main_transaction' => 0,
                            'costs' => [],
                            'withdrawal_amount' => 0,
                            'payment_date' => null,
                            'payment_day' => null,
                        ];
                    }
                    
                    // Process based on category
                    switch ($category) {
                        case 'main_transaction':
                            $transactionRows++;
                            // This is the gross transaction amount (before any deductions)
                            $nominal = $this->parseNominal($rowData['Nominal (Rp)']);
                            $transactionData[$orderNumber]['main_transaction'] = $nominal;
                            $transactionData[$orderNumber]['description'] = $rowData['Description'];
                            
                            // Save payment date and day if available
                            if ($hasPaymentDate && isset($rowData['TANGGAL MASUK PEMBAYARAN'])) {
                                $transactionData[$orderNumber]['payment_date'] = $rowData['TANGGAL MASUK PEMBAYARAN'];
                            }
                            if ($hasPaymentDay && isset($rowData['HARI MASUK PEMBAYARAN'])) {
                                $transactionData[$orderNumber]['payment_day'] = $rowData['HARI MASUK PEMBAYARAN'];
                            }
                            break;
                            
                        case 'withdrawal':
                            $withdrawalRows++;
                            // This is the final amount received (after all deductions)
                            $nominal = $this->parseNominal($rowData['Nominal (Rp)']);
                            $transactionData[$orderNumber]['withdrawal_amount'] = abs($nominal); // Ensure positive
                            break;
                            
                        case 'biaya_admin':
                        case 'biaya_layanan':
                        case 'komisi':
                        case 'ongkir':
                        case 'cashback':
                        case 'voucher':
                        case 'subsidi':
                        case 'insentif':
                        case 'pajak':
                        case 'pph':
                        case 'ppn':
                            $discountRows++;
                            // These are cost deductions
                            $nominal = $this->parseNominal($rowData['Nominal (Rp)']);
                            $costItem = [
                                'category' => $category,
                                'description' => $rowData['Description'],
                                'amount' => abs($nominal) // Store as positive, we'll make it negative when mapping
                            ];
                            $transactionData[$orderNumber]['costs'][] = $costItem;
                            
                            \Log::info('Added cost item to transaction', [
                                'order_number' => $orderNumber,
                                'cost_item' => $costItem
                            ]);
                            break;
                            
                        default:
                            $ignoredRows++;
                            break;
                    }
                } else {
                    $ignoredRows++;
                }
            } else {
                $ignoredRows++;
            }
        }
        
        return [
            'transaction_data' => $transactionData,
            'summary' => [
                'total_rows_scanned' => $totalRowsScanned,
                'transaction_rows' => $transactionRows,
                'discount_rows' => $discountRows,
                'withdrawal_rows' => $withdrawalRows,
                'ignored_rows' => $ignoredRows,
                'orders_found' => count($transactionData)
            ]
        ];
    }
    
    private function parseNominal($nominalString)
    {
        // Remove currency symbols and formatting
        $cleaned = str_replace(['Rp', '.', ',', ' '], '', $nominalString);
        
        // Handle negative values
        $isNegative = strpos($cleaned, '-') !== false;
        $cleaned = str_replace('-', '', $cleaned);
        
        $value = (float) $cleaned;
        return $isNegative ? -$value : $value;
    }
    
    private function mapCostsToBiayaFields($costs)
    {
        $biayaFields = [
            'nominal_diskon1' => 0, // Komisi (CORRECTED)
            'nominal_diskon2' => 0, // Biaya Admin (CORRECTED)
            'nominal_diskon3' => 0, // Biaya Layanan (CORRECTED)
            'nominal_diskon4' => 0, // Ongkir
            'nominal_diskon5' => 0, // Cashback
            'nominal_diskon6' => 0, // Voucher
            'nominal_diskon7' => 0, // Additional cost 1
            'nominal_diskon8' => 0, // Additional cost 2
            'nominal_diskon9' => 0, // Additional cost 3
            'nominal_diskon10' => 0, // Additional cost 4
            'nominal_diskon11' => 0, // Additional cost 5
            'nominal_diskon12' => 0, // Additional cost 6
        ];
        
        // Map costs to appropriate fields
        $additionalCostIndex = 7; // Start from nominal_diskon7 for unmapped costs
        
        \Log::info('Mapping costs to biaya fields', ['total_costs' => count($costs)]);
        
        foreach ($costs as $cost) {
            $amount = -abs($cost['amount']); // Make negative (cost deduction)
            
            \Log::info('Processing cost item', [
                'category' => $cost['category'],
                'description' => $cost['description'],
                'amount' => $amount
            ]);
            
            switch ($cost['category']) {
                // Komisi -> nominal_diskon1 (CORRECTED: Komisi should be first)
                case 'komisi':
                    $biayaFields['nominal_diskon1'] += $amount;
                    \Log::info('Mapped to nominal_diskon1 (Komisi)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Biaya Admin -> nominal_diskon2 (CORRECTED: Fixed mapping)
                case 'biaya_admin':
                    $biayaFields['nominal_diskon2'] += $amount;
                    \Log::info('Mapped to nominal_diskon2 (Biaya Admin)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Biaya Layanan -> nominal_diskon3 (CORRECTED: Fixed mapping)
                case 'biaya_layanan':
                    $biayaFields['nominal_diskon3'] += $amount;
                    \Log::info('Mapped to nominal_diskon3 (Biaya Layanan)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Ongkir -> nominal_diskon4
                case 'ongkir':
                    $biayaFields['nominal_diskon4'] += $amount;
                    \Log::info('Mapped to nominal_diskon4 (Ongkir)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Cashback -> nominal_diskon5
                case 'cashback':
                    $biayaFields['nominal_diskon5'] += $amount;
                    \Log::info('Mapped to nominal_diskon5 (Cashback)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Voucher -> nominal_diskon6
                case 'voucher':
                    $biayaFields['nominal_diskon6'] += $amount;
                    \Log::info('Mapped to nominal_diskon6 (Voucher)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Subsidi -> nominal_diskon7
                case 'subsidi':
                    $biayaFields['nominal_diskon7'] += $amount;
                    \Log::info('Mapped to nominal_diskon7 (Subsidi)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Insentif -> nominal_diskon8
                case 'insentif':
                    $biayaFields['nominal_diskon8'] += $amount;
                    \Log::info('Mapped to nominal_diskon8 (Insentif)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // Pajak -> nominal_diskon9
                case 'pajak':
                    $biayaFields['nominal_diskon9'] += $amount;
                    \Log::info('Mapped to nominal_diskon9 (Pajak)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // PPH -> nominal_diskon10
                case 'pph':
                    $biayaFields['nominal_diskon10'] += $amount;
                    \Log::info('Mapped to nominal_diskon10 (PPH)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                // PPN -> nominal_diskon11
                case 'ppn':
                    $biayaFields['nominal_diskon11'] += $amount;
                    \Log::info('Mapped to nominal_diskon11 (PPN)', ['amount' => $amount, 'type' => $cost['category']]);
                    break;
                    
                default:
                    // Map to additional cost fields for unrecognized categories
                    if ($additionalCostIndex <= 12) {
                        $biayaFields['nominal_diskon' . $additionalCostIndex] += $amount;
                        \Log::info('Mapped to additional cost field', [
                            'field' => 'nominal_diskon' . $additionalCostIndex,
                            'amount' => $amount,
                            'category' => $cost['category']
                        ]);
                        $additionalCostIndex++;
                    } else {
                        \Log::warning('Too many cost categories, cannot map to additional field', [
                            'category' => $cost['category'],
                            'amount' => $amount
                        ]);
                    }
                    break;
            }
        }
        
        \Log::info('Final biaya fields mapping', $biayaFields);
        
        return $biayaFields;
    }
} 