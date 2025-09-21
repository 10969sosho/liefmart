<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ShopeeFinancialTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\InvoiceSequence;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ShopeeFinanceAnalyticsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AdjustmentHistory;

class PembayaranShopeeController extends Controller
{
    public function index(Request $request)
    {
        $platform = 'shopee'; // Tetapkan platform
        
        $query = ShopeeFinancialTransaction::with([
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
        $totalCount = \App\Models\ShopeeFinancialTransaction::count();
        $totalNominalFix = \App\Models\ShopeeFinancialTransaction::sum('nominal_fix');
        $totalSaldoMasuk = \App\Models\ShopeeFinancialTransaction::sum('saldo_masuk');
        $totalOutstanding = \App\Models\ShopeeFinancialTransaction::sum('outstanding');
        
        // Note: We'll filter out fully returned orders in the view/collection processing
        // as it's more efficient to check this in PHP rather than complex SQL queries

        // Get all transactions with orders to ensure no empty data (this will be filtered)
        $transactions = clone $query;
        $transactions = $transactions->orderBy('tanggal_order', 'desc')->paginate(15);
        
        // Filter out fully returned orders from the results
        $transactions->getCollection()->transform(function($transaction) {
            // Skip transactions whose orders are fully returned
            if ($transaction->order && $transaction->order->isFullyReturned()) {
                return null;
            }
            return $transaction;
        })->filter(); // Remove null values
        
        // Get all orders that don't have financial transactions
        $missingOrders = Order::with('orderItems')->whereDoesntHave('shopeeFinancialTransactions')
            ->whereHas('platform', function($query) {
                $query->where('name', 'shopee');
            })
            ->orderBy('tanggal', 'desc') // Use tanggal instead of order_date
            ->get();
            
        // Group transactions by order number for display
        $groupedTransactions = $transactions->groupBy('no_order');
        
        return view('financial.shopee.index', compact(
            'transactions', 
            'groupedTransactions', 
            'platform', 
            'missingOrders',
            'totalCount',
            'totalNominalFix',
            'totalSaldoMasuk',
            'totalOutstanding'
        ));
    }

    /**
     * Show the form for importing financial data.
     *
     * @return \Illuminate\Http\Response
     */
    public function importForm()
    {
        return view('financial.shopee.import');
    }

    public function import()
    {
        return view('financial.shopee.import');
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

    public function preview(Request $request)
    {
        // If this is a GET request, check if we have data in the session
        if ($request->isMethod('get')) {
            if (!session()->has('shopee_import_data')) {
                return redirect()->route('finance.shopee.import')
                    ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
            }
            
            // Get the data from the session
            $data = session('shopee_import_data');
            $previewData = session('shopee_preview_data');
            $previewHeaders = session('shopee_preview_headers');
            $headerLabels = session('shopee_header_labels');
            $issues = session('shopee_issues');
            $totalRows = session('shopee_total_rows');
            $validRows = session('shopee_valid_rows');
            $invalidRows = session('shopee_invalid_rows');
            
            \Log::info("preview GET: previewData is " . (is_array($previewData) ? "array with " . count($previewData) . " items" : "not an array"));
            
            // If any of the required data is missing, redirect to import
            if (!$previewData || !$previewHeaders || !$headerLabels) {
                return redirect()->route('finance.shopee.import')
                    ->with('error', 'Data preview tidak lengkap. Silakan upload file kembali.');
            }
            
            return view('financial.shopee.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
        }
        
        // For POST requests, validate and process the file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls', // No file size limit
        ]);
    
        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $data = [];
        $headers = [];
        $issues = [];
        
        try {
            // Log file processing start
            Log::info('Starting Shopee financial import preview', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize()
            ]);
            
            // Membaca file Excel tanpa batasan
            $reader = IOFactory::createReader(IOFactory::identify($path));
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            
            // Set additional reader options for better performance
            // No row limit - read all data
            
            $spreadsheet = $reader->load($path);
            
            // Keep reader for potential reuse
            
            // Mencari sheet Income (case-insensitive)
            $incomeSheet = null;
            $availableSheets = [];
            
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheetTitle = $sheet->getTitle();
                $availableSheets[] = $sheetTitle;
                
                // Check for Income sheet (case-insensitive)
                if (strtolower($sheetTitle) === 'income') {
                    $incomeSheet = $sheet;
                    break;
                }
            }
            
            // Log available sheets for debugging
            Log::info('Available sheets in Excel file: ' . implode(', ', $availableSheets));
            
            if (!$incomeSheet) {
                // Free memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                
                $availableSheetsText = implode(', ', $availableSheets);
                $errorMessage = 'Sheet "Income" tidak ditemukan dalam file Excel.';
                
                if (!empty($availableSheets)) {
                    $errorMessage .= ' Sheet yang tersedia: ' . $availableSheetsText . '.';
                } else {
                    $errorMessage .= ' File tidak memiliki sheet yang dapat dibaca.';
                }
                
                $errorMessage .= ' Pastikan file Excel memiliki sheet dengan nama "Income" (tidak case-sensitive).';
                
                return redirect()->back()->with('error', $errorMessage);
            }
            
            // Get highest row and column to limit processing
            $highestRow = $incomeSheet->getHighestRow();
            $highestColumn = $incomeSheet->getHighestColumn();
            
            Log::info("Excel file dimensions: {$highestRow} rows x {$highestColumn} columns");
            
            // No limit on file size - process all rows
            Log::info("Processing all {$highestRow} rows from Excel file");
            
            $worksheet = $incomeSheet;
            
            // Ambil header (baris pertama)
            $highestColumn = $worksheet->getHighestDataColumn();
            $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
            
            // Bersihkan header dari spasi berlebih
            $headers = array_map('trim', $headerRow);
            
            // Validasi header (hanya kolom esensial wajib)
            $requiredHeaders = [
                'NOMOR PESANAN',
                'TANGGAL MASUK PEMBAYARAN',
                'HARI MASUK PEMBAYARAN',
                'JUMLAH MASUK PEMBAYARAN'
            ];
            
            $missingHeaders = [];
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    $missingHeaders[] = $requiredHeader;
                }
            }
            
            if (!empty($missingHeaders)) {
                return redirect()->back()->with('error', 'Format file tidak sesuai. Kolom yang tidak ditemukan: ' . implode(', ', $missingHeaders));
            }
            
            // Dapatkan kolom opsional
            $hasDiskon5 = in_array('DISKON 5', $headers);
            $hasDiskon6 = in_array('DISKON 6', $headers);
            
            // Dapatkan index kolom
            $columnIndices = [];
            foreach ($headers as $index => $header) {
                $columnIndices[$header] = $index;
            }
            
            // Dapatkan data dari Excel
            $rows = $worksheet->toArray();
            
            // Lewati baris header
            array_shift($rows);
            
            // Baca data
            $rowNumber = 1;
            $previewData = [];
            
            foreach ($rows as $row) {
                $rowNumber++;
                
                // Skip baris kosong
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Pastikan jumlah kolom sesuai
                if (count($row) >= count($requiredHeaders)) {
                    // Buat array data berdasarkan header
                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        if (isset($row[$index])) {
                            $rowData[$header] = $row[$index];
                        }
                    }
                    
                    // Validasi data per baris
                    $rowIssues = [];
                    
                    // 1. Validasi nomor pesanan
                    if (empty($rowData['NOMOR PESANAN'])) {
                        $rowIssues[] = 'Nomor pesanan kosong';
                    }
                    
                    // 2. Validasi format tanggal
                    if (!empty($rowData['TANGGAL MASUK PEMBAYARAN'])) {
                        // Jika tanggal dalam format objek Excel
                        if ($rowData['TANGGAL MASUK PEMBAYARAN'] instanceof \DateTime) {
                            $rowData['TANGGAL MASUK PEMBAYARAN'] = $rowData['TANGGAL MASUK PEMBAYARAN']->format('Y-m-d');
                        } else {
                            // Coba parse string tanggal
                            $date = \DateTime::createFromFormat('Y-m-d', $rowData['TANGGAL MASUK PEMBAYARAN']);
                            if (!$date) {
                                $rowIssues[] = 'Format tanggal tidak valid (Format yang benar: YYYY-MM-DD)';
                            }
                        }
                    } else {
                        $rowIssues[] = 'Tanggal pembayaran kosong';
                    }
                    
                    // 3. Validasi hari pembayaran
                    if (empty($rowData['HARI MASUK PEMBAYARAN'])) {
                        $rowIssues[] = 'Hari pembayaran kosong';
                    }
                    
                    // 4. Validasi jumlah pembayaran - allow 0 values
                    if (!isset($rowData['JUMLAH MASUK PEMBAYARAN'])) {
                        $rowIssues[] = 'Jumlah pembayaran tidak ditemukan';
                    } elseif ($rowData['JUMLAH MASUK PEMBAYARAN'] === '') {
                        $rowIssues[] = 'Jumlah pembayaran kosong';
                    }
                    
                    // 5. Cek order di database
                    $order = Order::where('order_number', $rowData['NOMOR PESANAN'])->first();
                    if (!$order) {
                        $rowIssues[] = 'Nomor order tidak ditemukan di database';
                    } else {
                        // 6. Cek jika transaksi sudah ada
                        $transactionExists = ShopeeFinancialTransaction::where('no_order', $rowData['NOMOR PESANAN'])->exists();
                        if ($transactionExists) {
                            $rowIssues[] = 'Transaksi untuk order ini sudah ada';
                        } else {
                            // Get order items with their warehouse stocks
                            $orderItems = $order->orderItems()->with('warehouseStock')->get();
                            
                            if ($orderItems->isEmpty()) {
                                $rowIssues[] = 'Order tidak memiliki item';
                            } else {
                                // Order is valid, create a simple data structure for preview
                                $orderData = [
                                    'tanggal_order' => $order->tanggal ? $order->tanggal->format('Y-m-d') : 'N/A',
                                    'hari_order' => $order->hari ?? 'N/A',
                                    'no_order' => $rowData['NOMOR PESANAN'],
                                    'saldo_masuk' => (float) $rowData['JUMLAH MASUK PEMBAYARAN'],
                                    'tanggal_masuk_pembayaran' => $rowData['TANGGAL MASUK PEMBAYARAN'],
                                    'hari_masuk_pembayaran' => $rowData['HARI MASUK PEMBAYARAN'],
                                    'invoices' => [],
                                ];
                                
                                // Create invoice entries in the preview data
                                $invoice = [
                                    'no_invoice' => 'PREVIEW-' . $rowData['NOMOR PESANAN'],
                                    'tax_id' => 'AUTO',
                                    'is_pkp' => true,
                                ];
                                
                                // Calculate total quantity across all order items
                                $totalQty = $order->orderItems->sum('quantity');
                                $invoice['qty'] = $totalQty;
                                
                                // Calculate total invoice value (price_after_discount × quantity)
                                $totalInvoiceValue = 0;
                                foreach ($order->orderItems as $item) {
                                    $totalInvoiceValue += $item->price_after_discount * $item->quantity;
                                }
                                $invoice['nominal_harga'] = $totalInvoiceValue;
                                
                                // Kolom biaya opsional default 0 jika tidak tersedia
                                $invoice['nominal_diskon1'] = !empty($rowData['Voucher Ditanggung Penjual']) ? -abs((float) $rowData['Voucher Ditanggung Penjual']) : 0;
                                $invoice['nominal_diskon2'] = !empty($rowData['KOMISI AMS/AFFILIATE']) ? -abs((float) $rowData['KOMISI AMS/AFFILIATE']) : 0;
                                $invoice['nominal_diskon3'] = !empty($rowData['BIAYA ADMIN']) ? -abs((float) $rowData['BIAYA ADMIN']) : 0;
                                $invoice['nominal_diskon4'] = !empty($rowData['BIAYA LAYANAN (GRATIS ONGKIR + CASHBACK)']) ? -abs((float) $rowData['BIAYA LAYANAN (GRATIS ONGKIR + CASHBACK)']) : 0;
                                $invoice['nominal_diskon5'] = !empty($rowData['DISKON 5']) ? -abs((float) $rowData['DISKON 5']) : 0;
                                $invoice['nominal_diskon6'] = !empty($rowData['DISKON 6']) ? -abs((float) $rowData['DISKON 6']) : 0;
                                $invoice['adjustment'] = 0;
                                
                                // Calculate nominal_fix for display
                                $invoice['nominal_fix'] = $invoice['nominal_harga'] + 
                                    $invoice['nominal_diskon1'] + 
                                    $invoice['nominal_diskon2'] + 
                                    $invoice['nominal_diskon3'] + 
                                    $invoice['nominal_diskon4'] + 
                                    $invoice['nominal_diskon5'] + 
                                    $invoice['nominal_diskon6'] + 
                                    $invoice['adjustment'];
                                
                                $invoice['saldo_masuk'] = (float) $rowData['JUMLAH MASUK PEMBAYARAN'];
                                $invoice['outstanding'] = $invoice['nominal_fix'] - $invoice['saldo_masuk'];
                                
                                $orderData['invoices'][] = $invoice;
                                
                                // Add the order data to the preview data
                                $previewData[] = $orderData;
                                \Log::info("Added order data for order {$orderData['no_order']} with " . count($orderData['invoices']) . " invoices");
                            }
                        }
                    }
                    
                    // Tambahkan issues jika ada
                    if (!empty($rowIssues)) {
                        $issues[$rowNumber] = $rowIssues;
                    }
                    
                    // Tambahkan status validasi ke data
                    $rowData['_valid'] = empty($rowIssues);
                    $rowData['_issues'] = $rowIssues;
                    $rowData['_row'] = $rowNumber;
                    
                    $data[] = $rowData;
                } else {
                    $issues[$rowNumber] = ['Jumlah kolom tidak sesuai dengan header'];
                }
            }
        } catch (\Exception $e) {
            Log::error('Shopee import error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage() . ' (Check log for details)');
        }
        
        // Hitung statistik
        $totalRows = count($data);
        $validRows = count(array_filter($data, function($row) { return $row['_valid']; }));
        $invalidRows = $totalRows - $validRows;
        
        // Define kolom tampilan untuk preview
        $previewHeaders = [
            'tanggal_order', 'hari_order', 'no_order', 'invoices',
            'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran'
        ];
        
        // Label header yang lebih user-friendly
        $headerLabels = [
            'tanggal_order' => 'Tanggal Order',
            'hari_order' => 'Hari Order',
            'no_order' => 'No. Order',
            'invoices' => 'Invoices',
            'tanggal_masuk_pembayaran' => 'Tanggal Masuk Pembayaran',
            'hari_masuk_pembayaran' => 'Hari Masuk Pembayaran'
        ];
        
        // Save all data to session
        session(['shopee_import_data' => $data]);
        session(['shopee_import_issues' => $issues]);
        session(['shopee_preview_data' => $previewData]);
        session(['shopee_preview_headers' => $previewHeaders]);
        session(['shopee_header_labels' => $headerLabels]);
        session(['shopee_total_rows' => $totalRows]);
        session(['shopee_valid_rows' => $validRows]);
        session(['shopee_invalid_rows' => $invalidRows]);
        
        // Generate and store process token for secure processing
        $processToken = uniqid('shopee_', true);
        session(['shopee_process_token' => $processToken]);
        
        return view('financial.shopee.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
    }

    /**
     * Show the preview page for GET requests
     * This method checks if there's preview data in the session and displays it
     * If no data is found, it redirects to the import page
     */
    public function showPreview()
    {
        // Check if there's preview data in the session
        if (!session()->has('shopee_import_data')) {
            return redirect()->route('finance.shopee.import')
                ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
        }
        
        // Get the data from the session
        $data = session('shopee_import_data');
        $previewData = session('shopee_preview_data');
        $previewHeaders = session('shopee_preview_headers');
        $headerLabels = session('shopee_header_labels');
        $issues = session('shopee_issues');
        $totalRows = session('shopee_total_rows');
        $validRows = session('shopee_valid_rows');
        $invalidRows = session('shopee_invalid_rows');
        
        \Log::info("showPreview: previewData is " . (is_array($previewData) ? "array with " . count($previewData) . " items" : "not an array"));
        
        // If any of the required data is missing, redirect to import
        if (!$previewData || !$previewHeaders || !$headerLabels) {
            return redirect()->route('finance.shopee.import')
                ->with('error', 'Data preview tidak lengkap. Silakan upload file kembali.');
        }
        
        return view('financial.shopee.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
    }

    public function process(Request $request)
    {
        try {
            Log::info('Starting Shopee financial import process');
            
            // Validate process token
            $processToken = $request->input('process_token');
            if (!$processToken || $processToken !== session('shopee_process_token')) {
                return redirect()->route('finance.shopee.import')
                    ->with('error', 'Token proses tidak valid. Silakan upload ulang file.');
            }
            
            // Get data from session instead of POST data
            $importData = session('shopee_import_data');
            if (!$importData) {
                return redirect()->route('finance.shopee.import')
                    ->with('error', 'Data import tidak ditemukan. Silakan upload ulang file.');
            }
            
            // Filter only valid data
            $validData = array_filter($importData, function($rowData) {
                return isset($rowData['_valid']) && $rowData['_valid'] === true;
            });
            
            if (empty($validData)) {
                return redirect()->route('finance.shopee.import')
                    ->with('error', 'Tidak ada data valid untuk diproses.');
            }
            
            DB::beginTransaction();
            $importCount = 0;
            $skippedCount = 0;
            $skippedReasons = [];
            
            // Process in batches for better performance
            $batchSize = 100; // Increased batch size for faster processing
            $batches = array_chunk($validData, $batchSize);
            
            Log::info('Processing ' . count($validData) . ' valid records in ' . count($batches) . ' batches');
            
            // Pertama, pra-proses semua data untuk mengetahui berapa banyak transaksi valid yang akan diimpor
            $validOrders = [];
            
            foreach ($batches as $batchIndex => $batch) {
                Log::info('Processing batch ' . ($batchIndex + 1) . ' of ' . count($batches));
                
                foreach ($batch as $index => $rowData) {
                    try {
                        // Skip if no order number provided
                        if (!isset($rowData['NOMOR PESANAN']) || empty($rowData['NOMOR PESANAN'])) {
                            $skippedCount++;
                            $skippedReasons[] = "Baris #" . ($index + 1) . ": Nomor order kosong";
                            Log::warning("Skipping row - missing order number");
                            continue;
                        }
                        
                        // Find order by the order number from the imported data
                        $order = Order::where('order_number', $rowData['NOMOR PESANAN'])->first();
                        
                        if (!$order) {
                            $skippedCount++;
                            $skippedReasons[] = "Baris #" . ($index + 1) . ": Order {$rowData['NOMOR PESANAN']} tidak ditemukan di database";
                            Log::warning("Skipping order {$rowData['NOMOR PESANAN']} - order not found in database");
                            continue;
                        }
                        
                        // Check if a transaction with this order number already exists
                        $existingTransaction = ShopeeFinancialTransaction::where('no_order', $order->order_number)->first();
                        
                        if ($existingTransaction) {
                            // Skip this order since a transaction already exists
                            $skippedCount++;
                            $skippedReasons[] = "Baris #" . ($index + 1) . ": Order {$order->order_number} sudah memiliki transaksi";
                            Log::warning("Skipping order {$order->order_number} - transaction already exists");
                            continue;
                        }
                        
                        // Check for required data
                        if (!isset($rowData['TANGGAL MASUK PEMBAYARAN']) || empty($rowData['TANGGAL MASUK PEMBAYARAN'])) {
                            $skippedCount++;
                            $skippedReasons[] = "Baris #" . ($index + 1) . ": Order {$order->order_number} - tanggal masuk pembayaran kosong";
                            Log::warning("Skipping order {$order->order_number} - missing payment date");
                            continue;
                        }
                        
                        if (!isset($rowData['HARI MASUK PEMBAYARAN']) || empty($rowData['HARI MASUK PEMBAYARAN'])) {
                            $skippedCount++;
                            $skippedReasons[] = "Baris #" . ($index + 1) . ": Order {$order->order_number} - hari masuk pembayaran kosong";
                            Log::warning("Skipping order {$order->order_number} - missing payment day");
                            continue;
                        }
                        
                        // Get order items with their warehouse stocks
                        $orderItems = $order->orderItems()->with('warehouseStock')->get();
                        
                        if ($orderItems->isEmpty()) {
                            $skippedCount++;
                            $skippedReasons[] = "Baris #" . ($index + 1) . ": Order {$order->order_number} tidak memiliki item";
                            Log::warning("Skipping order {$order->order_number} - no order items found");
                            continue;
                        }
                        
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
                        if (empty($itemsByTaxId) || $totalQty == 0) {
                            // For testing purposes, create two tax groups
                            // This ensures we have multiple invoices for each order
                            $halfwayPoint = intdiv(count($orderItems), 2);
                            $taxId1 = 3; // PKP Online
                            $taxId2 = 4; // Non-PKP Online
                            
                            // Reset the total quantity counter
                            $totalQty = 0;
                            
                            // Distribute items between two tax groups for testing
                            for ($i = 0; $i < count($orderItems); $i++) {
                                $taxId = $i < $halfwayPoint ? $taxId1 : $taxId2;
                                if (!isset($itemsByTaxId[$taxId])) {
                                    $itemsByTaxId[$taxId] = [];
                                }
                                $itemsByTaxId[$taxId][] = $orderItems[$i];
                                $totalQty += $orderItems[$i]->quantity;
                            }
                            
                            // If no items or only one item, use default
                            if (count($itemsByTaxId) <= 1 && count($orderItems) > 0) {
                                // Force creating two tax groups for testing
                                $itemsByTaxId = [
                                    $taxId1 => array_slice($orderItems->toArray(), 0, $halfwayPoint),
                                    $taxId2 => array_slice($orderItems->toArray(), $halfwayPoint)
                                ];
                            }
                        }
                        
                        // Order is valid, add to the list of valid orders
                        $validOrders[$order->id] = [
                            'order' => $order,
                            'rowData' => $rowData,
                        ];
                        
                    } catch (\Exception $e) {
                        $skippedCount++;
                        $skippedReasons[] = "Baris #" . ($index + 1) . ": Error - " . $e->getMessage();
                        Log::error("Error processing row " . ($index + 1) . ": " . $e->getMessage());
                        Log::error($e->getTraceAsString());
                        continue;
                    }
                }
            }
            
            // Selanjutnya, proses setiap order yang valid
            foreach ($validOrders as $orderId => $orderData) {
                $order = $orderData['order'];
                $rowData = $orderData['rowData'];
                
                try {
                    // Create transaction for this order
                    $transaction = new ShopeeFinancialTransaction();
                    $transaction->tanggal_order = $order->tanggal;
                    $transaction->hari_order = $order->hari;
                    $transaction->no_order = $order->order_number;
                    $transaction->no_invoice = $this->generateInvoiceForOrder($order);
                    $transaction->order_id = $order->id;
                    
                    // Calculate total quantity across all order items
                    $totalQty = $order->orderItems->sum('quantity');
                    $transaction->qty = $totalQty;
                    
                    // Calculate total invoice value (price_after_discount × quantity)
                    $totalInvoiceValue = 0;
                    foreach ($order->orderItems as $item) {
                        $totalInvoiceValue += $item->price_after_discount * $item->quantity;
                    }
                    $transaction->nominal_harga = $totalInvoiceValue;
                    
                    // Process discount values from the import data
                    $transaction->nominal_diskon1 = !empty($rowData['Voucher Ditanggung Penjual']) ? -abs((float)$rowData['Voucher Ditanggung Penjual']) : 0;
                    $transaction->nominal_diskon2 = !empty($rowData['KOMISI AMS/AFFILIATE']) ? -abs((float)$rowData['KOMISI AMS/AFFILIATE']) : 0;
                    $transaction->nominal_diskon3 = !empty($rowData['BIAYA ADMIN']) ? -abs((float)$rowData['BIAYA ADMIN']) : 0;
                    $transaction->nominal_diskon4 = !empty($rowData['BIAYA LAYANAN (GRATIS ONGKIR + CASHBACK)']) ? -abs((float)$rowData['BIAYA LAYANAN (GRATIS ONGKIR + CASHBACK)']) : 0;
                    $transaction->nominal_diskon5 = !empty($rowData['DISKON 5']) ? -abs((float)$rowData['DISKON 5']) : 0;
                    $transaction->nominal_diskon6 = !empty($rowData['DISKON 6']) ? -abs((float)$rowData['DISKON 6']) : 0;
                    
                    // Set payment info
                    $transaction->tanggal_masuk_pembayaran = $rowData['TANGGAL MASUK PEMBAYARAN'];
                    $transaction->hari_masuk_pembayaran = $rowData['HARI MASUK PEMBAYARAN'];
                    $transaction->saldo_masuk = (float)$rowData['JUMLAH MASUK PEMBAYARAN'];
                    
                    // Calculate nominal_fix and outstanding
                    $transaction->calculateNominalFix()
                              ->calculateOutstanding()
                              ->calculatePercentages();
                    
                    // Add validation for potential overpayment scenarios
                    if ($transaction->saldo_masuk > 0 && $transaction->nominal_fix > 0) {
                        $ratio = $transaction->saldo_masuk / $transaction->nominal_fix;
                        if ($ratio > 3.0) { // If payment is more than 3x the expected amount
                            \Log::warning("Potential overpayment detected for order {$order->order_number}: saldo_masuk={$transaction->saldo_masuk}, nominal_fix={$transaction->nominal_fix}");
                        }
                    }
                    
                    $transaction->save();
                    $importCount++;
                } catch (\Exception $e) {
                    $skippedCount++;
                    $skippedReasons[] = "Order {$order->order_number}: Error - " . $e->getMessage();
                    Log::error("Error creating transaction for order {$order->order_number}: " . $e->getMessage());
                    Log::error($e->getTraceAsString());
                    continue;
                }
            }
            
            DB::commit();
            
            // Clean up session data after successful processing
            session()->forget([
                'shopee_import_data',
                'shopee_import_issues', 
                'shopee_preview_data',
                'shopee_preview_headers',
                'shopee_header_labels',
                'shopee_total_rows',
                'shopee_valid_rows', 
                'shopee_invalid_rows',
                'shopee_process_token'
            ]);
            
            // Store skipped reasons in session if any
            if (!empty($skippedReasons)) {
                session(['skipped_reasons' => $skippedReasons]);
            }
            
            return redirect()->route('finance.shopee.index')
                ->with('success', "Berhasil mengimpor $importCount transaksi finansial. $skippedCount transaksi dilewati.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during Shopee financial import: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error memproses data: ' . $e->getMessage());
        }
    }

    /**
     * Generate invoice number for an order based on warehouse stock tax_id
     * 
     * @param Order $order
     * @return string
     */
    protected function generateInvoiceForOrder($order)
    {
        // First, let's check BarangKeluar records associated with this order
        $barangKeluarItems = \App\Models\BarangKeluar::whereHas('orderItem', function($query) use ($order) {
            $query->where('order_id', $order->id);
        })->with('warehouseStock')->get();
        
        // Group by tax_id and calculate quantity for each tax_id
        $taxGroupsFromBarangKeluar = [];
        $taxQty = [];
        
        foreach ($barangKeluarItems as $item) {
            if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                $taxId = $item->warehouseStock->tax_id;
                if (!isset($taxGroupsFromBarangKeluar[$taxId])) {
                    $taxGroupsFromBarangKeluar[$taxId] = [];
                    $taxQty[$taxId] = 0;
                }
                $taxGroupsFromBarangKeluar[$taxId][] = $item;
                $taxQty[$taxId] += $item->qty;
            }
        }
        
        // If no BarangKeluar items, fall back to order items
        if (empty($taxGroupsFromBarangKeluar)) {
            $orderItems = $order->orderItems()->with('warehouseStock')->get();
            $taxGroups = [];
            
            foreach ($orderItems as $item) {
                if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                    $taxId = $item->warehouseStock->tax_id;
                    if (!isset($taxGroups[$taxId])) {
                        $taxGroups[$taxId] = [];
                        $taxQty[$taxId] = 0;
                    }
                    $taxGroups[$taxId][] = $item;
                    $taxQty[$taxId] += $item->quantity;
                }
            }
            
            $taxGroupsFromBarangKeluar = $taxGroups;
        }
        
        // Find the tax_id with the highest quantity
        arsort($taxQty);
        $dominantTaxId = key($taxQty);
        
        // Generate invoice number based on tax_id
        return ShopeeFinancialTransaction::generateInvoiceNumber($order, $dominantTaxId);
    }
    
    public function manual()
    {
        // Implementasi halaman manual input jika diperlukan
        return view('financial.shopee.manual');
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
            'nominal_diskon7' => 'nullable|numeric',
            'nominal_diskon8' => 'nullable|numeric',
            'nominal_diskon9' => 'nullable|numeric',
            'nominal_diskon10' => 'nullable|numeric',
            'nominal_diskon11' => 'nullable|numeric',
            'nominal_diskon12' => 'nullable|numeric',
            'adjustment' => 'nullable|numeric',
        ]);
        
        try {
            DB::beginTransaction();
            
            $order = Order::findOrFail($request->order_id);
            
            // Cek jika sudah ada transaksi untuk order ini
            $exists = ShopeeFinancialTransaction::where('order_id', $order->id)->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'Transaksi untuk order ini sudah ada.');
            }
            
            // Create a single transaction for the order
            $transaction = new ShopeeFinancialTransaction();
            $transaction->tanggal_order = $order->tanggal;
            $transaction->hari_order = $order->hari;
            $transaction->no_order = $order->order_number;
            
            // Calculate total quantity across all order items
            $totalQty = $order->orderItems->sum('quantity');
            $transaction->qty = $totalQty;
            
            // Calculate total invoice value (price_after_discount × quantity)
            $totalInvoiceValue = 0;
            foreach ($order->orderItems as $item) {
                $totalInvoiceValue += $item->price_after_discount * $item->quantity;
            }
            $transaction->nominal_harga = $totalInvoiceValue;
            
            $transaction->nominal_diskon1 = $request->nominal_diskon1 ? -abs((float)$request->nominal_diskon1) : 0;
            $transaction->nominal_diskon2 = $request->nominal_diskon2 ? -abs((float)$request->nominal_diskon2) : 0;
            $transaction->nominal_diskon3 = $request->nominal_diskon3 ? -abs((float)$request->nominal_diskon3) : 0;
            $transaction->nominal_diskon4 = $request->nominal_diskon4 ? -abs((float)$request->nominal_diskon4) : 0;
            $transaction->nominal_diskon5 = $request->nominal_diskon5 ? -abs((float)$request->nominal_diskon5) : 0;
            $transaction->nominal_diskon6 = $request->nominal_diskon6 ? -abs((float)$request->nominal_diskon6) : 0;
            $transaction->nominal_diskon7 = $request->nominal_diskon7 ? -abs((float)$request->nominal_diskon7) : 0;
            $transaction->nominal_diskon8 = $request->nominal_diskon8 ? -abs((float)$request->nominal_diskon8) : 0;
            $transaction->nominal_diskon9 = $request->nominal_diskon9 ? -abs((float)$request->nominal_diskon9) : 0;
            $transaction->nominal_diskon10 = $request->nominal_diskon10 ? -abs((float)$request->nominal_diskon10) : 0;
            $transaction->nominal_diskon11 = $request->nominal_diskon11 ? -abs((float)$request->nominal_diskon11) : 0;
            $transaction->nominal_diskon12 = $request->nominal_diskon12 ? -abs((float)$request->nominal_diskon12) : 0;
            $transaction->adjustment = $request->adjustment ?? 0;
            $transaction->saldo_masuk = $request->saldo_masuk;
            $transaction->tanggal_masuk_pembayaran = $request->tanggal_masuk_pembayaran;
            $transaction->hari_masuk_pembayaran = $request->hari_masuk_pembayaran;
            $transaction->order_id = $order->id;
            
            // Generate invoice number
            $transaction->no_invoice = $this->generateInvoiceForOrder($order);
            
            // Use the model's helper methods to calculate values
            $transaction->calculateNominalFix()
                        ->calculateOutstanding()
                        ->calculatePercentages();
            
            // Add validation for potential overpayment scenarios
            if ($transaction->saldo_masuk > 0 && $transaction->nominal_fix > 0) {
                $ratio = $transaction->saldo_masuk / $transaction->nominal_fix;
                if ($ratio > 3.0) { // If payment is more than 3x the expected amount
                    \Log::warning("Potential overpayment detected for order {$order->order_number}: saldo_masuk={$transaction->saldo_masuk}, nominal_fix={$transaction->nominal_fix}");
                }
            }
            
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.shopee.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menambahkan transaksi Shopee manual: " . $e->getMessage());
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
            
            $transaction = ShopeeFinancialTransaction::findOrFail($id);
            
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
            
            // Use the model's helper methods to recalculate all values
            $transaction->calculateNominalFix()
                       ->calculateOutstanding()
                       ->calculatePercentages();
            
            // Simpan ke adjustment_histories
            AdjustmentHistory::create([
                'transaction_id' => $transaction->id,
                'platform' => 'shopee',
                'old_value' => $oldAdjustment,
                'new_value' => $transaction->adjustment,
                'description' => $request->adjustment_description,
                'user_id' => auth()->id(),
            ]);
            
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.shopee.index')
                ->with('success', 'Adjustment berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat adjust transaksi Shopee: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a transaction
     */
    public function delete($id)
    {
        try {
            $transaction = ShopeeFinancialTransaction::findOrFail($id);
            $transaction->delete();
            
            return redirect()->route('finance.shopee.index')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error("Error saat menghapus transaksi Shopee: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
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
            // Only fetch the transaction without complex relationships
            $transaction = ShopeeFinancialTransaction::findOrFail($id);
            
            // Determine the tax_id from the invoice number
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
                preg_match('/\/(\d{2})$/', $transaction->no_invoice, $matches);
                if (!empty($matches[1])) {
                    $taxId = (int) $matches[1];
                    $isPKP = in_array($taxId, [1, 3, 5, 7]);
                    $logoFile = $isPKP ? 'HGN.jpeg' : 'LM.jpeg';
                }
            }
            
            // Fetch minimal order data for display purposes only
            $orderNumber = $transaction->no_order;
            
            // Get simple product name from order if available, otherwise use generic name
            $productName = "Produk Shopee";
            
            try {
                if ($transaction->order_id) {
                    // Just get basic order info without detailed relationships
                    $order = \App\Models\Order::select('id', 'order_number')->find($transaction->order_id);
                    if ($order) {
                        // Try to get at least the first product name for display purposes
                        $firstItem = $order->orderItems()
                            ->select('id', 'platform_product_id')
                            ->with(['platformProduct:id,platform_product_name'])
                            ->first();
                        
                        if ($firstItem && $firstItem->platformProduct) {
                            $productName = $firstItem->platformProduct->platform_product_name;
                        }
                    }
                }
            } catch (\Exception $e) {
                // If there's any error getting product info, just use the default
                Log::warning("Couldn't fetch product name for invoice {$transaction->no_invoice}: " . $e->getMessage());
            }
            
            // Log transaction details for monitoring
            Log::info("Printing simplified invoice {$transaction->no_invoice}, QTY: {$transaction->qty}, HPP: {$transaction->nominal_harga}");
            
            return view('financial.shopee.print-invoice', compact('transaction', 'logoFile', 'isPKP', 'productName'));
        } catch (\Exception $e) {
            Log::error("Error saat print invoice Shopee: " . $e->getMessage());
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
            $transaction = ShopeeFinancialTransaction::findOrFail($id);
            $transaction->lock(auth()->id());
            
            return redirect()->back()->with('success', 'Transaksi berhasil dikunci.');
        } catch (\Exception $e) {
            Log::error("Error saat mengunci transaksi Shopee: " . $e->getMessage());
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
            $transaction = ShopeeFinancialTransaction::findOrFail($id);
            
            // Only admin or the person who locked it can unlock
            if (auth()->user()->role != 'admin' && $transaction->locked_by != auth()->id()) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk membuka kunci transaksi ini.');
            }
            
            $transaction->unlock();
            
            return redirect()->back()->with('success', 'Kunci transaksi berhasil dibuka.');
        } catch (\Exception $e) {
            Log::error("Error saat membuka kunci transaksi Shopee: " . $e->getMessage());
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
            $transaction = ShopeeFinancialTransaction::with('lockedByUser')->findOrFail($id);
            $adjustmentHistories = \App\Models\AdjustmentHistory::where('transaction_id', $transaction->id)
                ->where('platform', 'shopee')
                ->orderBy('created_at', 'desc')
                ->with('user')
                ->get();
            return view('financial.shopee.history', compact('transaction', 'adjustmentHistories'));
        } catch (\Exception $e) {
            Log::error("Error saat melihat history transaksi Shopee: " . $e->getMessage());
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
        $filename = 'shopee_finance_analytics_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new ShopeeFinanceAnalyticsExport($request->all()), $filename);
    }

    /**
     * Export data to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $query = ShopeeFinancialTransaction::with(['order.orderItems.warehouseStock.tax', 'order.mainCategory']);
        
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
        
        $pdf = Pdf::loadView('exports.financial.shopee', compact('transactions'))
                  ->setPaper('a4', 'landscape');
        
        return $pdf->download('shopee_finance_analytics_' . date('Y-m-d_H-i-s') . '.pdf');
    }
}