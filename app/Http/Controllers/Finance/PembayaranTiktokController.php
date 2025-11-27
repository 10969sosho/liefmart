<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TiktokFinancialTransaction;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\InvoiceSequence;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TiktokFinanceAnalyticsExport;
use App\Exports\TiktokCashFlowExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AdjustmentHistory;

class PembayaranTiktokController extends Controller
{
    public function index(Request $request)
    {
        $platform = 'tiktok'; // Set platform
        
        $query = TiktokFinancialTransaction::with([
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
        
        // Exclude transactions with fully returned orders at query level
        $query->whereHas('order', function($q) {
            // Filter out orders that are fully returned
            $q->where(function($subQ) {
                $subQ->whereDoesntHave('returPenjualan', function($rq) {
                    $rq->whereIn('status', ['draft', 'selesai']);
                });
            });
        });
        
        // Calculate totals for cards from FILTERED data
        $totalCount = $query->count();
        $totalNominalFix = $query->sum('nominal_fix');
        $totalSaldoMasuk = $query->sum('saldo_masuk');
        $totalOutstanding = $query->sum('outstanding');
        
        // Clone query for pagination (this will be filtered)
        $transactions = clone $query;
        $transactions = $transactions->orderBy('tanggal_order', 'desc')
            ->paginate(15)
            ->withQueryString(); // Preserves query parameters in pagination links
            
        // Group transactions by order number for display
        $groupedTransactions = $transactions->groupBy('no_order');
        
        // Get all orders that don't have financial transactions
        $missingOrders = Order::with(['orderItems', 'orderItems.platformProduct.mappingBarang'])
            ->whereDoesntHave('tiktokFinancialTransactions')
            ->whereHas('platform', function($query) {
                $query->where('name', 'tiktok');
            })
            ->orderBy('tanggal', 'desc') // Use tanggal instead of order_date
            ->get()
            ->filter(function($order) {
                // Filter out fully returned orders
                return !$order->isFullyReturned();
            });
            
        return view('financial.tiktok.index', compact(
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
        return view('financial.tiktok.import');
    }

    public function import()
    {
        return view('financial.tiktok.import');
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
            if (!session()->has('tiktok_import_data')) {
                return redirect()->route('finance.tiktok.import')
                    ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
            }
            
            // Get the data from the session
            $data = session('tiktok_import_data');
            $previewData = session('tiktok_preview_data');
            $previewHeaders = session('tiktok_preview_headers');
            $headerLabels = session('tiktok_header_labels');
            $issues = session('tiktok_import_issues');
            $totalRows = session('tiktok_total_rows');
            $validRows = session('tiktok_valid_rows');
            $invalidRows = session('tiktok_invalid_rows');
            
            // If any of the required data is missing, redirect to import
            if (!$previewData || !$previewHeaders || !$headerLabels) {
                return redirect()->route('finance.tiktok.import')
                    ->with('error', 'Data preview tidak lengkap. Silakan upload file kembali.');
            }
            
            return view('financial.tiktok.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
        }
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls', // Remove max file size limit to use hosting settings
        ]);
    
        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $data = [];
        $headers = [];
        $issues = [];
        
        try {
            // Log file processing start
            Log::info('Starting TikTok financial import preview', [
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
                \Log::warning("Sheet 'Order details' not found, attempting to use first sheet");
                $orderDetailsSheet = $spreadsheet->getSheet(0);
                if (!$orderDetailsSheet) {
                    // Free memory
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                    
                    return redirect()->back()->with('error', 'Sheet "Order details" tidak ditemukan dalam file Excel.');
                }
            }
            
            $worksheet = $orderDetailsSheet;
            \Log::info("Using sheet: " . $worksheet->getTitle());
            
            // Get highest row and column to limit processing
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestDataColumn();
            
            Log::info("Excel file dimensions: {$highestRow} rows x {$highestColumn} columns");
            
            // Limit processing to prevent memory issues
            if ($highestRow > 10000) {
                Log::warning("File too large, limiting to first 10000 rows");
                $highestRow = 10000;
            }
            
            // Get the header (first row)
            $headerRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
            
            // Clean headers from extra spaces
            $headers = array_map('trim', $headerRow);
            \Log::info("Headers found: " . json_encode($headers));
            
            // Validate required headers (only essential fields)
            $requiredHeaders = [
                'NOMOR PESANAN',
                'TANGGAL MASUK PEMBAYARAN',
                'HARI MASUK PEMBAYARAN',
                'JUMLAH MASUK PEMBAYARAN'
            ];
            
            // Map for alternate header names (lowercase for case insensitive comparison)
            $headerMapping = [
                'nomor pesanan' => 'NOMOR PESANAN',
                'no pesanan' => 'NOMOR PESANAN',
                'no. pesanan' => 'NOMOR PESANAN',
                'no order' => 'NOMOR PESANAN',
                'no. order' => 'NOMOR PESANAN',
                'order number' => 'NOMOR PESANAN',
                
                'tanggal masuk pembayaran' => 'TANGGAL MASUK PEMBAYARAN',
                'tanggal pembayaran' => 'TANGGAL MASUK PEMBAYARAN',
                'payment date' => 'TANGGAL MASUK PEMBAYARAN',
                'tgl masuk pembayaran' => 'TANGGAL MASUK PEMBAYARAN',
                'tgl pembayaran' => 'TANGGAL MASUK PEMBAYARAN',
                'tanggal' => 'TANGGAL MASUK PEMBAYARAN',
                
                'hari masuk pembayaran' => 'HARI MASUK PEMBAYARAN',
                'hari pembayaran' => 'HARI MASUK PEMBAYARAN',
                'payment day' => 'HARI MASUK PEMBAYARAN',
                
                'jumlah masuk pembayaran' => 'JUMLAH MASUK PEMBAYARAN',
                'jumlah pembayaran' => 'JUMLAH MASUK PEMBAYARAN',
                'amount' => 'JUMLAH MASUK PEMBAYARAN',
                'jumlah' => 'JUMLAH MASUK PEMBAYARAN',
                'nominal pembayaran' => 'JUMLAH MASUK PEMBAYARAN',
                
                'biaya admin' => 'BIAYA ADMIN',
                'admin fee' => 'BIAYA ADMIN',
                
                'affiliate commission' => 'AFFILIATE COMMISSION',
                'komisi affiliate' => 'AFFILIATE COMMISSION',
                'affiliasi' => 'AFFILIATE COMMISSION',
                
                'seller shipping fee + sfp service fee' => 'SELLER SHIPPING FEE + SFP SERVICE FEE',
                'seller shipping fee + sfp' => 'SELLER SHIPPING FEE + SFP SERVICE FEE',
                'shipping fee' => 'SELLER SHIPPING FEE + SFP SERVICE FEE',
                'biaya pengiriman' => 'SELLER SHIPPING FEE + SFP SERVICE FEE',
                
                'voucher xtra service fee' => 'VOUCHER XTRA SERVICE FEE',
                'voucher service fee' => 'VOUCHER XTRA SERVICE FEE',
                'biaya voucher' => 'VOUCHER XTRA SERVICE FEE',
                
                'cashback fee' => 'CASHBACK FEE',
                'biaya cashback' => 'CASHBACK FEE',
                
                'biaya6' => 'BIAYA6',
                'biaya 6' => 'BIAYA6',
                'biaya_6' => 'BIAYA6',
                'cost6' => 'BIAYA6',
                'cost 6' => 'BIAYA6',
                
                'biaya7' => 'BIAYA7',
                'biaya 7' => 'BIAYA7',
                'biaya_7' => 'BIAYA7',
                'cost7' => 'BIAYA7',
                'cost 7' => 'BIAYA7',
                
                'biaya8' => 'BIAYA8',
                'biaya 8' => 'BIAYA8',
                'biaya_8' => 'BIAYA8',
                'cost8' => 'BIAYA8',
                'cost 8' => 'BIAYA8',
                
                'biaya9' => 'BIAYA9',
                'biaya 9' => 'BIAYA9',
                'biaya_9' => 'BIAYA9',
                'cost9' => 'BIAYA9',
                'cost 9' => 'BIAYA9',
                
                'biaya10' => 'BIAYA10',
                'biaya 10' => 'BIAYA10',
                'biaya_10' => 'BIAYA10',
                'cost10' => 'BIAYA10',
                'cost 10' => 'BIAYA10',
                
                'biaya11' => 'BIAYA11',
                'biaya 11' => 'BIAYA11',
                'biaya_11' => 'BIAYA11',
                'cost11' => 'BIAYA11',
                'cost 11' => 'BIAYA11',
                
                'biaya12' => 'BIAYA12',
                'biaya 12' => 'BIAYA12',
                'biaya_12' => 'BIAYA12',
                'cost12' => 'BIAYA12',
                'cost 12' => 'BIAYA12'
            ];
            
            // Map the headers to standard names
            $mappedHeaders = [];
            foreach ($headers as $index => $header) {
                // Convert to lowercase for case-insensitive matching
                $lowerHeader = strtolower($header);
                if (isset($headerMapping[$lowerHeader])) {
                    $mappedHeaders[$index] = $headerMapping[$lowerHeader];
                } else {
                    $mappedHeaders[$index] = $header;
                }
            }
            
            // Replace original headers with mapped ones for further processing
            $headers = $mappedHeaders;
            \Log::info("Mapped headers: " . json_encode($headers));
            
            // Check for missing required headers
            $foundRequiredHeaders = [];
            foreach ($requiredHeaders as $requiredHeader) {
                if (in_array($requiredHeader, $headers)) {
                    $foundRequiredHeaders[] = $requiredHeader;
                }
            }
            $missingHeaders = array_diff($requiredHeaders, $foundRequiredHeaders);
            if (!empty($missingHeaders)) {
                \Log::warning("Missing required headers: " . json_encode($missingHeaders));
                return redirect()->back()->with('error', 'Format file tidak sesuai. Kolom yang tidak ditemukan: ' . implode(', ', $missingHeaders));
            }
            
            // Check for optional headers
            $hasDiskon6 = in_array('DISKON6', $headers);
            
            // Get column indexes for mapped headers
            $columnIndices = [];
            foreach ($headers as $index => $header) {
                $columnIndices[$header] = $index;
            }
            
            // Helper function to get value from row using standardized header name
            $getValue = function($row, $standardHeader) use ($columnIndices, $headers) {
                // Find the index for this standard header
                $index = array_search($standardHeader, $headers);
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
                // Use standard header names for consistency
                $rowData['NOMOR PESANAN'] = $getValue($row, 'NOMOR PESANAN');
                $rowData['TANGGAL MASUK PEMBAYARAN'] = $getValue($row, 'TANGGAL MASUK PEMBAYARAN');
                $rowData['HARI MASUK PEMBAYARAN'] = $getValue($row, 'HARI MASUK PEMBAYARAN');
                $rowData['JUMLAH MASUK PEMBAYARAN'] = $getValue($row, 'JUMLAH MASUK PEMBAYARAN');
                $rowData['BIAYA ADMIN'] = $getValue($row, 'BIAYA ADMIN');
                $rowData['AFFILIATE COMMISSION'] = $getValue($row, 'AFFILIATE COMMISSION');
                $rowData['SELLER SHIPPING FEE + SFP SERVICE FEE'] = $getValue($row, 'SELLER SHIPPING FEE + SFP SERVICE FEE');
                $rowData['VOUCHER XTRA SERVICE FEE'] = $getValue($row, 'VOUCHER XTRA SERVICE FEE');
                $rowData['CASHBACK FEE'] = $getValue($row, 'CASHBACK FEE');
                // Optional fee columns default to 0 if header/value missing
                $rowData['BIAYA6'] = $getValue($row, 'BIAYA6') !== '' ? $getValue($row, 'BIAYA6') : 0;
                $rowData['BIAYA7'] = $getValue($row, 'BIAYA7') !== '' ? $getValue($row, 'BIAYA7') : 0;
                $rowData['BIAYA8'] = $getValue($row, 'BIAYA8') !== '' ? $getValue($row, 'BIAYA8') : 0;
                $rowData['BIAYA9'] = $getValue($row, 'BIAYA9') !== '' ? $getValue($row, 'BIAYA9') : 0;
                $rowData['BIAYA10'] = $getValue($row, 'BIAYA10') !== '' ? $getValue($row, 'BIAYA10') : 0;
                $rowData['BIAYA11'] = $getValue($row, 'BIAYA11') !== '' ? $getValue($row, 'BIAYA11') : 0;
                $rowData['BIAYA12'] = $getValue($row, 'BIAYA12') !== '' ? $getValue($row, 'BIAYA12') : 0;
                
                // Collect order number for batch query - gunakan trim()
                if (!empty($rowData['NOMOR PESANAN'])) {
                    $orderNumbers[] = trim($rowData['NOMOR PESANAN']);
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
                    ->with(['orderItems'])
                    ->get()
                    ->keyBy('order_number');
                
                // Get all existing transactions in one query
                $existingTransactions = TiktokFinancialTransaction::whereIn('no_order', $orderNumbers)
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
                if (empty($rowData['NOMOR PESANAN'])) {
                    $rowIssues[] = 'Nomor pesanan kosong';
                }
                
                // 2. Validate date format
                if (!empty($rowData['TANGGAL MASUK PEMBAYARAN'])) {
                    // If date is an Excel date object
                    if ($rowData['TANGGAL MASUK PEMBAYARAN'] instanceof \DateTime) {
                        $rowData['TANGGAL MASUK PEMBAYARAN'] = $rowData['TANGGAL MASUK PEMBAYARAN']->format('Y-m-d');
                    } else {
                        // Try to parse date in various formats
                        $date = null;
                        $dateValue = $rowData['TANGGAL MASUK PEMBAYARAN'];
                        
                        // If it's a numeric value (Excel serial date)
                        if (is_numeric($dateValue)) {
                            try {
                                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue);
                                $rowData['TANGGAL MASUK PEMBAYARAN'] = $excelDate->format('Y-m-d');
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
                                    $rowData['TANGGAL MASUK PEMBAYARAN'] = $parsedDate->format('Y-m-d');
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
                
                // 3. Validate payment day
                if (empty($rowData['HARI MASUK PEMBAYARAN'])) {
                    $rowIssues[] = 'Hari pembayaran kosong';
                }
                
                // 4. Validate payment amount - allow 0 values
                if (!isset($rowData['JUMLAH MASUK PEMBAYARAN'])) {
                    $rowIssues[] = 'Jumlah pembayaran tidak ditemukan';
                } elseif ($rowData['JUMLAH MASUK PEMBAYARAN'] === '') {
                    $rowIssues[] = 'Jumlah pembayaran kosong';
                }
                
                // 5. Check if order exists in database (using pre-loaded data)
                // Gunakan trim() untuk menghindari masalah spasi
                $orderNumber = trim($rowData['NOMOR PESANAN']);
                $order = $orders[$orderNumber] ?? null;
                
                if (!$order) {
                    \Log::warning("Order tidak ditemukan untuk nomor pesanan: {$orderNumber}");
                    $rowIssues[] = 'Nomor order tidak ditemukan di database';
                    
                    // Skip this transaction instead of creating placeholder data
                    // This prevents incorrect nominal_fix calculations
                    $rowData['_valid'] = false;
                    $rowData['_issues'] = $rowIssues;
                    $rowData['_row'] = $rowNumber;
                    
                    $data[] = $rowData;
                    $issues[$rowNumber] = $rowIssues;
                    continue; // Skip to next transaction
                }
                
                // 6. Check if transaction already exists (using pre-loaded data)
                $transactionExists = in_array($orderNumber, $existingTransactions);
                if ($transactionExists) {
                    \Log::warning("Transaksi sudah ada untuk order: {$orderNumber}");
                    $rowIssues[] = 'Transaksi untuk order ini sudah ada';
                }
                
                // Create preview data even if transaction exists to show in the preview
                // Ensure order date is always valid - ambil dari database berdasarkan order_number
                $orderDate = $order->tanggal 
                    ?? Order::where('order_number', $orderNumber)->value('tanggal')
                    ?? throw new \Exception("Tanggal order tidak ditemukan untuk No Order {$orderNumber}");
                
                // Calculate total price from order items considering quantity (using pre-loaded data)
                $nominal_harga = 0;
                foreach ($order->orderItems as $item) {
                    $nominal_harga += $item->price_after_discount * $item->quantity;
                }
                
                // Calculate total quantity across all order items (using pre-loaded data)
                $totalQty = $order->orderItems->sum('quantity');
                
                // Preview data
                $previewData[] = [
                    'tanggal_order' => $orderDate,
                    'hari_order' => $order->hari,
                    'no_order' => $order->order_number,
                    'no_invoice' => 'PREVIEW-' . $order->order_number,
                    'qty' => $totalQty,
                    'nominal_harga' => $nominal_harga,
                    'nominal_diskon1' => !empty($rowData['BIAYA ADMIN']) ? -abs((float) $rowData['BIAYA ADMIN']) : 0,
                    'nominal_diskon2' => !empty($rowData['AFFILIATE COMMISSION']) ? -abs((float) $rowData['AFFILIATE COMMISSION']) : 0,
                    'nominal_diskon3' => !empty($rowData['SELLER SHIPPING FEE + SFP SERVICE FEE']) ? -abs((float) $rowData['SELLER SHIPPING FEE + SFP SERVICE FEE']) : 0,
                    'nominal_diskon4' => !empty($rowData['VOUCHER XTRA SERVICE FEE']) ? -abs((float) $rowData['VOUCHER XTRA SERVICE FEE']) : 0,
                    'nominal_diskon5' => !empty($rowData['CASHBACK FEE']) ? -abs((float) $rowData['CASHBACK FEE']) : 0,
                    'nominal_diskon6' => !empty($rowData['BIAYA6']) ? -abs((float) $rowData['BIAYA6']) : 0,
                    'nominal_diskon7' => !empty($rowData['BIAYA7']) ? -abs((float) $rowData['BIAYA7']) : 0,
                    'nominal_diskon8' => !empty($rowData['BIAYA8']) ? -abs((float) $rowData['BIAYA8']) : 0,
                    'nominal_diskon9' => !empty($rowData['BIAYA9']) ? -abs((float) $rowData['BIAYA9']) : 0,
                    'nominal_diskon10' => !empty($rowData['BIAYA10']) ? -abs((float) $rowData['BIAYA10']) : 0,
                    'nominal_diskon11' => !empty($rowData['BIAYA11']) ? -abs((float) $rowData['BIAYA11']) : 0,
                    'nominal_diskon12' => !empty($rowData['BIAYA12']) ? -abs((float) $rowData['BIAYA12']) : 0,
                    'adjustment' => 0,
                    'nominal_fix' => 0,
                    'saldo_masuk' => isset($rowData['JUMLAH MASUK PEMBAYARAN']) ? (float) $rowData['JUMLAH MASUK PEMBAYARAN'] : 0,
                    'tanggal_masuk_pembayaran' => $rowData['TANGGAL MASUK PEMBAYARAN'],
                    'hari_masuk_pembayaran' => $rowData['HARI MASUK PEMBAYARAN'],
                    'outstanding' => 0,
                    'persentase_diskon1' => 0,
                    'persentase_diskon2' => 0,
                    'persentase_diskon3' => 0,
                    'persentase_diskon4' => 0,
                    'persentase_diskon5' => 0,
                    'persentase_diskon6' => 0,
                    'persentase_diskon7' => 0,
                    'persentase_diskon8' => 0,
                    'persentase_diskon9' => 0,
                    'persentase_diskon10' => 0,
                    'persentase_diskon11' => 0,
                    'persentase_diskon12' => 0,
                    'total_persentase' => 0,
                ];
                
                // Add issues if any
                if (!empty($rowIssues)) {
                    $issues[$rowNumber] = $rowIssues;
                }
                
                // Add validation status to data
                $rowData['_valid'] = empty($rowIssues);
                $rowData['_issues'] = $rowIssues;
                $rowData['_row'] = $rowNumber;
                
                $data[] = $rowData;
            }
        } catch (\Exception $e) {
            \Log::error("Excel file processing error: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
        
        // Define display columns for preview
        $previewHeaders = [
            'tanggal_order', 'hari_order', 'no_order', 'no_invoice', 
            'nominal_harga', 'qty', 'nominal_diskon1', 'nominal_diskon2', 
            'nominal_diskon3', 'nominal_diskon4', 'nominal_diskon5', 
            'nominal_diskon6', 'nominal_diskon7', 'nominal_diskon8', 
            'nominal_diskon9', 'nominal_diskon10', 'nominal_diskon11', 
            'nominal_diskon12', 'adjustment', 'nominal_fix', 'saldo_masuk', 
            'tanggal_masuk_pembayaran', 'hari_masuk_pembayaran', 
            'outstanding', 'persentase_diskon1', 'persentase_diskon2', 
            'persentase_diskon3', 'persentase_diskon4', 'persentase_diskon5',
            'persentase_diskon6', 'persentase_diskon7', 'persentase_diskon8',
            'persentase_diskon9', 'persentase_diskon10', 'persentase_diskon11',
            'persentase_diskon12', 'total_persentase'
        ];
        
        // More user-friendly header labels
        $headerLabels = [
            'tanggal_order' => 'Tanggal Order',
            'hari_order' => 'Hari Order',
            'no_order' => 'No. Order',
            'no_invoice' => 'No. Invoice',
            'nominal_harga' => 'Nominal Harga',
            'qty' => 'Quantity',
            'nominal_diskon1' => 'Biaya Admin',
            'nominal_diskon2' => 'Affiliate Commission',
            'nominal_diskon3' => 'Seller Shipping Fee + SFP',
            'nominal_diskon4' => 'Voucher Xtra Service Fee',
            'nominal_diskon5' => 'Cashback Fee',
            'nominal_diskon6' => 'Biaya 6',
            'nominal_diskon7' => 'Biaya 7',
            'nominal_diskon8' => 'Biaya 8',
            'nominal_diskon9' => 'Biaya 9',
            'nominal_diskon10' => 'Biaya 10',
            'nominal_diskon11' => 'Biaya 11',
            'nominal_diskon12' => 'Biaya 12',
            'adjustment' => 'Adjustment',
            'nominal_fix' => 'Nominal Fix',
            'saldo_masuk' => 'Saldo Masuk',
            'tanggal_masuk_pembayaran' => 'Tanggal Masuk Pembayaran',
            'hari_masuk_pembayaran' => 'Hari Masuk Pembayaran',
            'outstanding' => 'Outstanding',
            'persentase_diskon1' => '% Biaya 1',
            'persentase_diskon2' => '% Biaya 2',
            'persentase_diskon3' => '% Biaya 3',
            'persentase_diskon4' => '% Biaya 4',
            'persentase_diskon5' => '% Biaya 5',
            'persentase_diskon6' => '% Biaya 6',
            'persentase_diskon7' => '% Biaya 7',
            'persentase_diskon8' => '% Biaya 8',
            'persentase_diskon9' => '% Biaya 9',
            'persentase_diskon10' => '% Biaya 10',
            'persentase_diskon11' => '% Biaya 11',
            'persentase_diskon12' => '% Biaya 12',
            'total_persentase' => 'Total %'
        ];
        
        // Save preview data in session
        session(['tiktok_import_data' => $data]);
        session(['tiktok_import_issues' => $issues]);
        session(['tiktok_preview_data' => $previewData]);
        session(['tiktok_preview_headers' => $previewHeaders]);
        session(['tiktok_header_labels' => $headerLabels]);
        
        // Calculate statistics
        $totalRows = count($data);
        $validRows = count(array_filter($data, function($row) { return $row['_valid']; }));
        $invalidRows = $totalRows - $validRows;
        
        session(['tiktok_total_rows' => $totalRows]);
        session(['tiktok_valid_rows' => $validRows]);
        session(['tiktok_invalid_rows' => $invalidRows]);
        
        // Generate and store process token for secure processing
        $processToken = uniqid('tiktok_', true);
        session(['tiktok_process_token' => $processToken]);
        
        return view('financial.tiktok.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows'));
    }

    /**
     * Process the imported data
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Request $request)
    {
        try {
            // Increase execution time limit for large imports
            set_time_limit(300); // 5 minutes
            ini_set('memory_limit', '512M');
            
            Log::info('Starting TikTok financial import process');
            
            // Validate process token
            $processToken = $request->input('process_token');
            if (!$processToken || $processToken !== session('tiktok_process_token')) {
                return redirect()->route('finance.tiktok.import')
                    ->with('error', 'Token proses tidak valid. Silakan upload ulang file.');
            }
            
            // Get data from session instead of POST data
            $importData = session('tiktok_import_data');
            if (!$importData) {
                return redirect()->route('finance.tiktok.import')
                    ->with('error', 'Data import tidak ditemukan. Silakan upload ulang file.');
            }
            
            // Filter only valid data
            $validData = array_filter($importData, function($rowData) {
                return isset($rowData['_valid']) && ($rowData['_valid'] === true || $rowData['_valid'] === 'true');
            });
            
            if (empty($validData)) {
                return redirect()->route('finance.tiktok.import')
                    ->with('error', 'Tidak ada data valid untuk diproses.');
            }
            
            DB::beginTransaction();
            $importCount = 0;
            $skippedCount = 0;
            $skippedReasons = [];
            
            // Process in smaller batches to avoid memory issues
            $batchSize = 50; // Reduced batch size for better performance
            $batches = array_chunk($validData, $batchSize);
            
            Log::info('Processing ' . count($validData) . ' valid records in ' . count($batches) . ' batches');
            
            // Collect all order numbers first for batch queries
            $allOrderNumbers = [];
            $processedRows = [];
            
            foreach ($batches as $batchIndex => $batch) {
                Log::info('Processing batch ' . ($batchIndex + 1) . ' of ' . count($batches));
                
                foreach ($batch as $index => $rowData) {
                    // Skip if no order number provided
                    if (empty($rowData['NOMOR PESANAN'])) {
                        $skippedCount++;
                        $skippedReasons[] = "Row #$index has no order number";
                        continue;
                    }
                    
                    $allOrderNumbers[] = $rowData['NOMOR PESANAN'];
                    $processedRows[] = [
                        'index' => $index,
                        'rowData' => $rowData
                    ];
                }
            }
            
            // Batch query: Get all orders and existing transactions
            $orders = [];
            $existingTransactions = [];
            
            if (!empty($allOrderNumbers)) {
                // Remove duplicates
                $allOrderNumbers = array_unique($allOrderNumbers);
                
                // Get all orders with their items in one query
                $orders = Order::whereIn('order_number', $allOrderNumbers)
                    ->with(['orderItems.warehouseStock'])
                    ->get()
                    ->keyBy('order_number');
                
                // Get all existing transactions in one query
                $existingTransactions = TiktokFinancialTransaction::whereIn('no_order', $allOrderNumbers)
                    ->pluck('no_order')
                    ->toArray();
                
                Log::info("Batch loaded " . count($orders) . " orders and " . count($existingTransactions) . " existing transactions");
            }
            
            // Process rows with pre-loaded data
            $validOrders = [];
            $validTaxGroups = [];
            
            foreach ($processedRows as $processedRow) {
                $index = $processedRow['index'];
                $rowData = $processedRow['rowData'];
                
                try {
                    // Check if order exists (using pre-loaded data)
                    $order = $orders[$rowData['NOMOR PESANAN']] ?? null;
                    if (!$order) {
                        $skippedCount++;
                        $skippedReasons[] = "Row #$index: Order {$rowData['NOMOR PESANAN']} not found";
                        continue;
                    }
                    
                    // Check if transaction already exists (using pre-loaded data)
                    if (in_array($rowData['NOMOR PESANAN'], $existingTransactions)) {
                        $skippedCount++;
                        $skippedReasons[] = "Row #$index: Transaction for order {$rowData['NOMOR PESANAN']} already exists";
                        continue;
                    }
                    
                    // Use pre-loaded order items
                    $orderItems = $order->orderItems;
                    
                    // Group OrderItems by tax_id
                    $itemsByTaxId = [];
                    $totalQty = 0;
                    
                    foreach ($orderItems as $item) {
                        // Get tax_id from warehouse_stock, or use a default tax_id if not available
                        $taxId = null;
                        if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                            $taxId = $item->warehouseStock->tax_id;
                        } else {
                            // Default to tax_id 4 (Non PKP - Skincare) for items without tax info
                            // This ensures all order items are included in financial transactions
                            $taxId = 4;
                        }
                        
                        if (!isset($itemsByTaxId[$taxId])) {
                            $itemsByTaxId[$taxId] = [];
                        }
                        $itemsByTaxId[$taxId][] = $item;
                        $totalQty += $item->quantity;
                    }
                    
                    // Order is valid, add to the list of valid orders
                    $validOrders[$order->id] = [
                        'order' => $order,
                        'rowData' => $rowData,
                        'taxGroups' => $itemsByTaxId,
                        'totalQty' => $totalQty
                    ];
                    
                    // Track all tax groups needed
                    foreach ($itemsByTaxId as $taxId => $items) {
                        if (!isset($validTaxGroups[$taxId])) {
                            $validTaxGroups[$taxId] = 0;
                        }
                        $validTaxGroups[$taxId]++;
                    }
                    
                } catch (\Exception $e) {
                    $skippedCount++;
                    $skippedReasons[] = "Row #$index: Error - " . $e->getMessage();
                    Log::error("Error processing row $index: " . $e->getMessage());
                    Log::error($e->getTraceAsString());
                    continue;
                }
            }
            
            // Sekarang, dapatkan nomor invoice untuk setiap kelompok pajak secara batch
            $invoiceNumbersByTaxId = [];
            foreach ($validTaxGroups as $taxId => $count) {
                // Mendapatkan kategori berdasarkan tax_id
                $category = ($taxId == 1 || $taxId == 2 || $taxId == 5 || $taxId == 6) 
                    ? InvoiceSequence::CATEGORY_KOPI 
                    : InvoiceSequence::CATEGORY_SKINCARE;
                    
                // Mendapatkan jenis penjualan (untuk Tiktok selalu ONLINE)
                $salesType = InvoiceSequence::SALES_ONLINE;
                
                // Mendapatkan status pajak
                $taxStatus = in_array($taxId, [1, 3, 5, 7]) 
                    ? InvoiceSequence::TAX_PKP 
                    : InvoiceSequence::TAX_NON_PKP;
                
                // Cari order pertama untuk mendapatkan tanggal order
                $firstOrder = null;
                foreach ($validOrders as $orderData) {
                    if (isset($orderData['taxGroups'][$taxId])) {
                        $firstOrder = $orderData['order'];
                        break;
                    }
                }
                
                $orderDate = $firstOrder ? $firstOrder->tanggal : null;
                
                // Mendapatkan batch nomor invoice dengan tanggal order
                $invoiceNumbersByTaxId[$taxId] = InvoiceSequence::getBatchInvoiceNumbers(
                    $category, 
                    $salesType, 
                    $taxStatus, 
                    $count,
                    $orderDate
                );
            }
            
            // Selanjutnya, proses setiap order yang valid
            $processedCount = 0;
            $totalValidOrders = count($validOrders);
            Log::info("Processing $totalValidOrders valid orders");
            
            foreach ($validOrders as $orderId => $orderData) {
                $processedCount++;
                if ($processedCount % 100 == 0) {
                    Log::info("Processed $processedCount of $totalValidOrders orders");
                }
                $order = $orderData['order'];
                $rowData = $orderData['rowData'];
                $itemsByTaxId = $orderData['taxGroups'];
                $totalQty = $orderData['totalQty'];
                
                try {
                    // Calculate total order price
                    $totalOrderPrice = 0;
                    foreach ($order->orderItems as $item) {
                        $totalOrderPrice += $item->price_after_discount * $item->quantity;
                    }
                    
                    // Process discount values from the import data
                    $nominal_diskon1 = !empty($rowData['BIAYA ADMIN']) ? -abs((float) $rowData['BIAYA ADMIN']) : 0;
                    $nominal_diskon2 = !empty($rowData['AFFILIATE COMMISSION']) ? -abs((float) $rowData['AFFILIATE COMMISSION']) : 0;
                    $nominal_diskon3 = !empty($rowData['SELLER SHIPPING FEE + SFP SERVICE FEE']) ? -abs((float) $rowData['SELLER SHIPPING FEE + SFP SERVICE FEE']) : 0;
                    $nominal_diskon4 = !empty($rowData['VOUCHER XTRA SERVICE FEE']) ? -abs((float) $rowData['VOUCHER XTRA SERVICE FEE']) : 0;
                    $nominal_diskon5 = !empty($rowData['CASHBACK FEE']) ? -abs((float) $rowData['CASHBACK FEE']) : 0;
                    $nominal_diskon6 = !empty($rowData['BIAYA6']) ? -abs((float) $rowData['BIAYA6']) : 0;
                    $nominal_diskon7 = !empty($rowData['BIAYA7']) ? -abs((float) $rowData['BIAYA7']) : 0;
                    $nominal_diskon8 = !empty($rowData['BIAYA8']) ? -abs((float) $rowData['BIAYA8']) : 0;
                    $nominal_diskon9 = !empty($rowData['BIAYA9']) ? -abs((float) $rowData['BIAYA9']) : 0;
                    $nominal_diskon10 = !empty($rowData['BIAYA10']) ? -abs((float) $rowData['BIAYA10']) : 0;
                    $nominal_diskon11 = !empty($rowData['BIAYA11']) ? -abs((float) $rowData['BIAYA11']) : 0;
                    $nominal_diskon12 = !empty($rowData['BIAYA12']) ? -abs((float) $rowData['BIAYA12']) : 0;
                    
                    // Sort tax groups to ensure consistent processing order: PKP first (1,3,5,7), then Non-PKP (2,4,6)
                    ksort($itemsByTaxId);
                    
                    // Process each tax group and create a transaction for each
                    foreach ($itemsByTaxId as $taxId => $items) {
                        // Get the next invoice number from the pre-generated batch
                        $invoiceData = array_shift($invoiceNumbersByTaxId[$taxId]);
                        if (!$invoiceData) {
                            throw new \Exception("No invoice number available for tax ID $taxId");
                        }
                        
                        // Create transaction for this tax group
                        $transaction = new TiktokFinancialTransaction();
                        // Use the actual order's tanggal directly, not the derived orderDate
                        $transaction->tanggal_order = $order->tanggal;
                        $transaction->hari_order = $order->hari;
                        $transaction->no_order = $order->order_number;
                        $transaction->no_invoice = $invoiceData['invoice_number'];
                        $transaction->order_id = $order->id;
                        
                        // Calculate value-based proportion for this tax group
                        $groupQty = 0;
                        $groupValue = 0;
                        
                        foreach ($items as $item) {
                            $itemQty = $item->quantity;
                            $groupQty += $itemQty;
                            
                            // Calculate value using OrderItem (price_after_discount is per unit)
                            $unitPrice = $item->price_after_discount;
                            $groupValue += $unitPrice * $itemQty;
                        }
                        
                        // Calculate proportion based on VALUE, not quantity
                        $proportion = ($totalOrderPrice > 0) ? $groupValue / $totalOrderPrice : 1;
                        
                        // Set values based on value-based proportion
                        $transaction->nominal_harga = round($groupValue, 2); // Use actual group value
                        $transaction->qty = $groupQty; // Set the group quantity
                        $transaction->nominal_diskon1 = round($nominal_diskon1 * $proportion, 2);
                        $transaction->nominal_diskon2 = round($nominal_diskon2 * $proportion, 2);
                        $transaction->nominal_diskon3 = round($nominal_diskon3 * $proportion, 2);
                        $transaction->nominal_diskon4 = round($nominal_diskon4 * $proportion, 2);
                        $transaction->nominal_diskon5 = round($nominal_diskon5 * $proportion, 2);
                        $transaction->nominal_diskon6 = round($nominal_diskon6 * $proportion, 2);
                        $transaction->nominal_diskon7 = round($nominal_diskon7 * $proportion, 2);
                        $transaction->nominal_diskon8 = round($nominal_diskon8 * $proportion, 2);
                        $transaction->nominal_diskon9 = round($nominal_diskon9 * $proportion, 2);
                        $transaction->nominal_diskon10 = round($nominal_diskon10 * $proportion, 2);
                        $transaction->nominal_diskon11 = round($nominal_diskon11 * $proportion, 2);
                        $transaction->nominal_diskon12 = round($nominal_diskon12 * $proportion, 2);
                        
                        // Set payment info
                        $transaction->tanggal_masuk_pembayaran = $rowData['TANGGAL MASUK PEMBAYARAN'];
                        $transaction->hari_masuk_pembayaran = $rowData['HARI MASUK PEMBAYARAN'];
                        
                        // Set adjustment to 0 by default
                        $transaction->adjustment = 0;
                        
                        // Calculate nominal_fix
                        $transaction->nominal_fix = $transaction->nominal_harga + 
                            $transaction->nominal_diskon1 + 
                            $transaction->nominal_diskon2 + 
                            $transaction->nominal_diskon3 + 
                            $transaction->nominal_diskon4 + 
                            $transaction->nominal_diskon5 + 
                            $transaction->nominal_diskon6 + 
                            $transaction->nominal_diskon7 + 
                            $transaction->nominal_diskon8 + 
                            $transaction->nominal_diskon9 + 
                            $transaction->nominal_diskon10 + 
                            $transaction->nominal_diskon11 + 
                            $transaction->nominal_diskon12 + 
                            $transaction->adjustment;
                        
                        // Set saldo_masuk from Excel data - use exact value, no fallback
                        // If JUMLAH MASUK PEMBAYARAN is 0 or empty, keep it as 0
                        $totalSaldoMasuk = isset($rowData['JUMLAH MASUK PEMBAYARAN']) ? 
                            (float) $rowData['JUMLAH MASUK PEMBAYARAN'] : 0;
                        
                        // Apply proportion to saldo_masuk like other values
                        $transaction->saldo_masuk = round($totalSaldoMasuk * $proportion, 2);
                        
                        // Calculate outstanding based on actual saldo_masuk
                        $transaction->outstanding = $transaction->nominal_fix - $transaction->saldo_masuk;
                        
                        // Add validation for potential overpayment scenarios
                        if ($transaction->saldo_masuk > 0 && $transaction->nominal_fix > 0) {
                            $ratio = $transaction->saldo_masuk / $transaction->nominal_fix;
                            if ($ratio > 3.0) { // If payment is more than 3x the expected amount
                                \Log::warning("Potential overpayment detected for order {$order->order_number}: saldo_masuk={$transaction->saldo_masuk}, nominal_fix={$transaction->nominal_fix}");
                            }
                        }
                        
                        // Calculate percentages
                        if ($transaction->nominal_harga > 0) {
                            $transaction->persentase_diskon1 = ($transaction->nominal_diskon1 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon2 = ($transaction->nominal_diskon2 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon3 = ($transaction->nominal_diskon3 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon4 = ($transaction->nominal_diskon4 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon5 = ($transaction->nominal_diskon5 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon6 = ($transaction->nominal_diskon6 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon7 = ($transaction->nominal_diskon7 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon8 = ($transaction->nominal_diskon8 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon9 = ($transaction->nominal_diskon9 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon10 = ($transaction->nominal_diskon10 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon11 = ($transaction->nominal_diskon11 / $transaction->nominal_harga) * 100;
                            $transaction->persentase_diskon12 = ($transaction->nominal_diskon12 / $transaction->nominal_harga) * 100;
                            $transaction->total_persentase = $transaction->persentase_diskon1 + $transaction->persentase_diskon2 + 
                                                         $transaction->persentase_diskon3 + $transaction->persentase_diskon4 +
                                                         $transaction->persentase_diskon5 + $transaction->persentase_diskon6 +
                                                         $transaction->persentase_diskon7 + $transaction->persentase_diskon8 +
                                                         $transaction->persentase_diskon9 + $transaction->persentase_diskon10 +
                                                         $transaction->persentase_diskon11 + $transaction->persentase_diskon12;
                        }
                        
                        $transaction->save();
                        $importCount++;
                    }
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
                'tiktok_import_data',
                'tiktok_import_issues',
                'tiktok_preview_data', 
                'tiktok_preview_headers',
                'tiktok_header_labels',
                'tiktok_total_rows',
                'tiktok_valid_rows',
                'tiktok_invalid_rows',
                'tiktok_process_token'
            ]);
            
            // Store skipped reasons in session if any
            if (!empty($skippedReasons)) {
                session(['skipped_reasons' => $skippedReasons]);
            }
            
            return redirect()->route('finance.tiktok.index')
                ->with('success', "Berhasil mengimpor $importCount transaksi finansial. $skippedCount transaksi dilewati.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during Tiktok financial import: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error memproses data: ' . $e->getMessage());
        }
    }

    public function manual()
    {
        // Implementation of manual input page if needed
        return view('financial.tiktok.manual');
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
            
            $order = Order::with(['orderItems.warehouseStock.tax', 'mainCategory'])->findOrFail($request->order_id);
            
            // Check if transaction already exists for this order
            $exists = TiktokFinancialTransaction::where('order_id', $order->id)->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'Transaksi untuk order ini sudah ada.');
            }
            
            // Get order items with their warehouse stocks
            $orderItems = $order->orderItems()->with('warehouseStock')->get();
            
            // Use OrderItems for calculation to avoid BarangKeluar duplication issues
            // Group OrderItems by tax_id
            $itemsByTaxId = [];
            $totalQty = 0;
            
            foreach ($orderItems as $item) {
                // Get tax_id from warehouse_stock, or use a default tax_id if not available
                $taxId = null;
                if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                    $taxId = $item->warehouseStock->tax_id;
                } else {
                    // Default to tax_id 4 (Non PKP - Skincare) for items without tax info
                    // This ensures all order items are included in financial transactions
                    $taxId = 4;
                }
                
                if (!isset($itemsByTaxId[$taxId])) {
                    $itemsByTaxId[$taxId] = [];
                }
                $itemsByTaxId[$taxId][] = $item;
                $totalQty += $item->quantity;
            }
            
            // Calculate total order price
            $totalOrderPrice = 0;
            foreach ($order->orderItems as $item) {
                $totalOrderPrice += $item->price_after_discount * $item->quantity;
            }
            
            // Process discount values from the form
            $nominal_diskon1 = !empty($request->nominal_diskon1) ? -abs((float) $request->nominal_diskon1) : 0;
            $nominal_diskon2 = !empty($request->nominal_diskon2) ? -abs((float) $request->nominal_diskon2) : 0;
            $nominal_diskon3 = !empty($request->nominal_diskon3) ? -abs((float) $request->nominal_diskon3) : 0;
            $nominal_diskon4 = !empty($request->nominal_diskon4) ? -abs((float) $request->nominal_diskon4) : 0;
            $nominal_diskon5 = !empty($request->nominal_diskon5) ? -abs((float) $request->nominal_diskon5) : 0;
            $nominal_diskon6 = !empty($request->nominal_diskon6) ? -abs((float) $request->nominal_diskon6) : 0;
            $nominal_diskon7 = !empty($request->nominal_diskon7) ? -abs((float) $request->nominal_diskon7) : 0;
            $nominal_diskon8 = !empty($request->nominal_diskon8) ? -abs((float) $request->nominal_diskon8) : 0;
            $nominal_diskon9 = !empty($request->nominal_diskon9) ? -abs((float) $request->nominal_diskon9) : 0;
            $nominal_diskon10 = !empty($request->nominal_diskon10) ? -abs((float) $request->nominal_diskon10) : 0;
            $nominal_diskon11 = !empty($request->nominal_diskon11) ? -abs((float) $request->nominal_diskon11) : 0;
            $nominal_diskon12 = !empty($request->nominal_diskon12) ? -abs((float) $request->nominal_diskon12) : 0;
            
            // Get invoice numbers for each tax group
            $invoiceNumbersByTaxId = [];
            foreach ($itemsByTaxId as $taxId => $items) {
                // Get category based on tax_id
                $category = ($taxId == 1 || $taxId == 2 || $taxId == 5 || $taxId == 6) 
                    ? InvoiceSequence::CATEGORY_KOPI 
                    : InvoiceSequence::CATEGORY_SKINCARE;
                    
                // Get sales type (always ONLINE for Tiktok)
                $salesType = InvoiceSequence::SALES_ONLINE;
                
                // Get tax status
                $taxStatus = in_array($taxId, [1, 3, 5, 7]) 
                    ? InvoiceSequence::TAX_PKP 
                    : InvoiceSequence::TAX_NON_PKP;
                
                // Get invoice number with order date - WAJIB dari tabel orders
                // Ensure we have the order date from the loaded order object
                if (!$order->tanggal) {
                    throw new \Exception("Tanggal order tidak ditemukan untuk Order {$order->order_number}");
                }
                $orderDate = $order->tanggal;
                
                $invoiceData = InvoiceSequence::getNextInvoiceNumber($category, $salesType, $taxStatus, $orderDate);
                $invoiceNumbersByTaxId[$taxId] = $invoiceData['invoice_number'];
            }
            
            // Process each tax group and create a transaction for each
            foreach ($itemsByTaxId as $taxId => $items) {
                // Get the invoice number for this tax group
                $invoiceNumber = $invoiceNumbersByTaxId[$taxId] ?? $this->generateInvoiceForOrder($order);
                
                // Create transaction for this tax group
                $transaction = new TiktokFinancialTransaction();
                // Use the actual order's tanggal directly, ensuring consistency
                $transaction->tanggal_order = $order->tanggal;
                $transaction->hari_order = $order->hari;
                $transaction->no_order = $order->order_number;
                $transaction->no_invoice = $invoiceNumber;
                $transaction->order_id = $order->id;
                
                // Calculate value-based proportion for this tax group
                $groupQty = 0;
                $groupValue = 0;
                
                foreach ($items as $item) {
                    $itemQty = $item->quantity;
                    $groupQty += $itemQty;
                    
                    // Calculate value using OrderItem (price_after_discount is per unit)
                    $unitPrice = $item->price_after_discount;
                    $groupValue += $unitPrice * $itemQty;
                }
                
                // Calculate proportion based on VALUE, not quantity
                $proportion = ($totalOrderPrice > 0) ? $groupValue / $totalOrderPrice : 1;
                
                // Set values based on value-based proportion
                $transaction->nominal_harga = round($groupValue, 2); // Use actual group value
                $transaction->qty = $groupQty; // Set the group quantity
                $transaction->nominal_diskon1 = round($nominal_diskon1 * $proportion, 2);
                $transaction->nominal_diskon2 = round($nominal_diskon2 * $proportion, 2);
                $transaction->nominal_diskon3 = round($nominal_diskon3 * $proportion, 2);
                $transaction->nominal_diskon4 = round($nominal_diskon4 * $proportion, 2);
                $transaction->nominal_diskon5 = round($nominal_diskon5 * $proportion, 2);
                $transaction->nominal_diskon6 = round($nominal_diskon6 * $proportion, 2);
                $transaction->nominal_diskon7 = round($nominal_diskon7 * $proportion, 2);
                $transaction->nominal_diskon8 = round($nominal_diskon8 * $proportion, 2);
                $transaction->nominal_diskon9 = round($nominal_diskon9 * $proportion, 2);
                $transaction->nominal_diskon10 = round($nominal_diskon10 * $proportion, 2);
                $transaction->nominal_diskon11 = round($nominal_diskon11 * $proportion, 2);
                $transaction->nominal_diskon12 = round($nominal_diskon12 * $proportion, 2);
                
                // Set payment info
                $transaction->tanggal_masuk_pembayaran = $request->tanggal_masuk_pembayaran;
                $transaction->hari_masuk_pembayaran = $request->hari_masuk_pembayaran;
                $transaction->saldo_masuk = round($request->saldo_masuk * $proportion, 2);
                
                // Set adjustment from form or 0 by default
                $transaction->adjustment = $request->adjustment ?? 0;
                
                // Calculate nominal_fix
                $transaction->calculateNominalFix();
                
                // Calculate outstanding
                $transaction->calculateOutstanding();
                
                // Calculate percentages
                $transaction->calculatePercentages();
                
                $transaction->save();
            }
            
            DB::commit();
            
            return redirect()->route('finance.tiktok.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menambahkan transaksi TikTok manual: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Adjust transaction value
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adjust(Request $request, $id)
    {
        $request->validate([
            'adjustment' => 'required|numeric',
            'adjustment_description' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            $transaction = TiktokFinancialTransaction::findOrFail($id);
            
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
                'platform' => 'tiktok',
                'old_value' => $oldAdjustment,
                'new_value' => $transaction->adjustment,
                'description' => $request->adjustment_description,
                'user_id' => auth()->id(),
            ]);
            
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.tiktok.index')
                ->with('success', 'Adjustment berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat adjust transaksi TikTok: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a transaction
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        try {
            $transaction = TiktokFinancialTransaction::findOrFail($id);
            $transaction->delete();
            
            return redirect()->route('finance.tiktok.index')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error("Error saat menghapus transaksi TikTok: " . $e->getMessage());
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
            $transaction = TiktokFinancialTransaction::with([
                    'order.orderItems.platformProduct',
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
            
            return view('financial.tiktok.print-invoice', compact('transaction', 'logoFile', 'isPKP'));
        } catch (\Exception $e) {
            Log::error("Error saat print invoice TikTok: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencetak invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate an invoice number for a manual order
     * 
     * @param Order $order
     * @return string
     */
    protected function generateInvoiceForOrder($order)
    {
        // For manual orders, default to tax_id 3 (SKINCARE - PKP ONLINE)
        return TiktokFinancialTransaction::generateInvoiceNumber($order, 3);
    }

    /**
     * Create a sample transaction for testing (temporary helper)
     */
    public function createSample()
    {
        try {
            DB::beginTransaction();
            
            // Find a TikTok order
            $order = Order::where('platform', 'tiktok')->first();
            
            if (!$order) {
                return redirect()->route('finance.tiktok.index')
                    ->with('error', 'No TikTok orders found in the database');
            }
            
            // Ensure order date is always valid
            $orderDate = $order->tanggal 
                ?? Order::where('id', $order->id)->value('tanggal')
                ?? throw new \Exception("Tanggal order tidak ditemukan untuk Order ID {$order->id}");
            
            // Check if transaction already exists for this order
            $exists = TiktokFinancialTransaction::where('order_id', $order->id)->exists();
            if ($exists) {
                return redirect()->route('finance.tiktok.index')
                    ->with('error', 'Transaction already exists for this order');
            }
            
            // Create a sample transaction
            $transaction = new TiktokFinancialTransaction();
            $transaction->order_id = $order->id;
            $transaction->tanggal_order = $orderDate;
            $transaction->hari_order = $order->hari;
            $transaction->no_order = $order->order_number;
            
            // Generate invoice number
            $transaction->no_invoice = TiktokFinancialTransaction::generateInvoiceNumber($order, 3);
            
            // Calculate total price from order items considering quantity
            $totalPrice = 0;
            foreach ($order->orderItems as $item) {
                $totalPrice += $item->price_after_discount * $item->quantity;
            }
            $transaction->nominal_harga = $totalPrice;
            
            // Sample discount values
            $transaction->nominal_diskon1 = -5000;  // BIAYA ADMIN
            $transaction->nominal_diskon2 = -2000;  // AFFILIATE COMMISSION
            $transaction->nominal_diskon3 = -7000;  // SELLER SHIPPING FEE
            $transaction->nominal_diskon4 = -3000;  // VOUCHER XTRA SERVICE FEE
            $transaction->nominal_diskon5 = -1000;  // CASHBACK FEE
            $transaction->nominal_diskon6 = 0;      // BIAYA 6
            $transaction->nominal_diskon7 = 0;      // BIAYA 7
            $transaction->nominal_diskon8 = 0;      // BIAYA 8
            $transaction->nominal_diskon9 = 0;      // BIAYA 9
            $transaction->nominal_diskon10 = 0;     // BIAYA 10
            $transaction->nominal_diskon11 = 0;     // BIAYA 11
            $transaction->nominal_diskon12 = 0;     // BIAYA 12
            $transaction->adjustment = 0;
            
            // Sample payment info
            $transaction->saldo_masuk = $totalPrice - 18000; // Total minus discounts
            $transaction->tanggal_masuk_pembayaran = Carbon::now();
            $transaction->hari_masuk_pembayaran = Carbon::now()->format('l');
            
            // Calculate nominal_fix and outstanding
            $transaction->calculateNominalFix();
            $transaction->calculateOutstanding();
            
            // Calculate percentages
            if ($transaction->nominal_harga > 0) {
                $transaction->persentase_diskon1 = ($transaction->nominal_diskon1 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon2 = ($transaction->nominal_diskon2 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon3 = ($transaction->nominal_diskon3 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon4 = ($transaction->nominal_diskon4 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon5 = ($transaction->nominal_diskon5 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon6 = ($transaction->nominal_diskon6 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon7 = ($transaction->nominal_diskon7 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon8 = ($transaction->nominal_diskon8 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon9 = ($transaction->nominal_diskon9 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon10 = ($transaction->nominal_diskon10 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon11 = ($transaction->nominal_diskon11 / $transaction->nominal_harga) * 100;
                $transaction->persentase_diskon12 = ($transaction->nominal_diskon12 / $transaction->nominal_harga) * 100;
                
                $transaction->total_persentase = 
                    $transaction->persentase_diskon1 + 
                    $transaction->persentase_diskon2 + 
                    $transaction->persentase_diskon3 + 
                    $transaction->persentase_diskon4 + 
                    $transaction->persentase_diskon5 + 
                    $transaction->persentase_diskon6 +
                    $transaction->persentase_diskon7 + 
                    $transaction->persentase_diskon8 + 
                    $transaction->persentase_diskon9 + 
                    $transaction->persentase_diskon10 + 
                    $transaction->persentase_diskon11 + 
                    $transaction->persentase_diskon12;
            }
            
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.tiktok.index')
                ->with('success', 'Sample transaction created successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error creating sample transaction: " . $e->getMessage());
            return redirect()->route('finance.tiktok.index')
                ->with('error', 'Error creating sample transaction: ' . $e->getMessage());
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
            $transaction = TiktokFinancialTransaction::findOrFail($id);
            $transaction->lock(auth()->id());
            
            return redirect()->back()->with('success', 'Transaksi berhasil dikunci.');
        } catch (\Exception $e) {
            Log::error("Error saat mengunci transaksi TikTok: " . $e->getMessage());
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
            $transaction = TiktokFinancialTransaction::findOrFail($id);
            
            // Only admin or the person who locked it can unlock
            if (auth()->user()->role != 'admin' && $transaction->locked_by != auth()->id()) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk membuka kunci transaksi ini.');
            }
            
            $transaction->unlock();
            
            return redirect()->back()->with('success', 'Kunci transaksi berhasil dibuka.');
        } catch (\Exception $e) {
            Log::error("Error saat membuka kunci transaksi TikTok: " . $e->getMessage());
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
            $transaction = TiktokFinancialTransaction::with('lockedByUser')->findOrFail($id);
            $adjustmentHistories = \App\Models\AdjustmentHistory::where('transaction_id', $transaction->id)
                ->where('platform', 'tiktok')
                ->orderBy('created_at', 'desc')
                ->with('user')
                ->get();
            return view('financial.tiktok.history', compact('transaction', 'adjustmentHistories'));
        } catch (\Exception $e) {
            Log::error("Error saat melihat history transaksi TikTok: " . $e->getMessage());
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
        $filename = 'tiktok_finance_analytics_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new TiktokFinanceAnalyticsExport($request->all()), $filename);
    }

    /**
     * Export cash flow data to Excel (compatible with arus kas import)
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCashFlow(Request $request)
    {
        $filename = 'tiktok_cash_flow_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new TiktokCashFlowExport($request->all()), $filename);
    }

    /**
     * Synchronize order dates for all TikTok financial transactions
     * This method ensures that tanggal_order matches the order's tanggal
     */
    public function syncOrderDates(Request $request)
    {
        try {
            $updated = TiktokFinancialTransaction::syncAllOrderDates();
            
            return redirect()->route('finance.tiktok.index')
                ->with('success', "Berhasil menyinkronkan {$updated} transaksi dengan tanggal order yang benar.");
                
        } catch (\Exception $e) {
            Log::error("Error synchronizing TikTok order dates: " . $e->getMessage());
            
            return redirect()->route('finance.tiktok.index')
                ->with('error', 'Error menyinkronkan tanggal order: ' . $e->getMessage());
        }
    }

    /**
     * Export data to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $query = TiktokFinancialTransaction::with(['order.orderItems.warehouseStock.tax', 'order.mainCategory']);
        
        // Apply the same filters as in index method
        // Filter by payment date range
        if ($request->filled('from_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', '>=', $request->from_date);
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
        
        // Exclude transactions with fully returned orders at query level
        $query->whereHas('order', function($q) {
            // Filter out orders that are fully returned
            $q->where(function($subQ) {
                $subQ->whereDoesntHave('returPenjualan', function($rq) {
                    $rq->whereIn('status', ['draft', 'selesai']);
                });
            });
        });
        
        $transactions = $query->orderBy('tanggal_order', 'desc')->get();
        
        $pdf = Pdf::loadView('exports.financial.tiktok', compact('transactions'))
                  ->setPaper('a4', 'landscape');
        
        return $pdf->download('tiktok_finance_analytics_' . date('Y-m-d_H-i-s') . '.pdf');
    }
} 