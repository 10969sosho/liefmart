<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BlibliFinancialTransaction;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BlibliFinanceAnalyticsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AdjustmentHistory;

class PembayaranBlibliController extends Controller
{
    public function index(Request $request)
    {
        $platform = 'blibli'; // Tetapkan platform
        
        // Base query
        $query = BlibliFinancialTransaction::with([
            'order.orderItems.platformProduct.mappingBarang', 
            'order.orderItems.warehouseStock.tax', 
            'order.mainCategory'
        ]);
        
        // Apply filters for payment dates
        if ($request->has('from_date') && $request->filled('from_date')) {
            $query->where('tanggal_masuk_pembayaran', '>=', $request->input('from_date'));
        }
        
        if ($request->has('to_date') && $request->filled('to_date')) {
            $query->where('tanggal_masuk_pembayaran', '<=', $request->input('to_date'));
        }
        
        // Apply filters for order dates
        if ($request->has('from_order_date') && $request->filled('from_order_date')) {
            $query->where('tanggal_order', '>=', $request->input('from_order_date'));
        }
        
        if ($request->has('to_order_date') && $request->filled('to_order_date')) {
            $query->where('tanggal_order', '<=', $request->input('to_order_date'));
        }
        
        if ($request->has('order_number') && $request->filled('order_number')) {
            $query->where('no_order', 'like', '%' . $request->input('order_number') . '%');
        }
        
        if ($request->has('invoice_number') && $request->filled('invoice_number')) {
            $query->where('no_invoice', 'like', '%' . $request->input('invoice_number') . '%');
        }
        
        if ($request->has('payment_date') && $request->filled('payment_date')) {
            $query->whereDate('tanggal_masuk_pembayaran', $request->input('payment_date'));
        }
        
        if ($request->has('tax_id') && is_array($request->input('tax_id'))) {
            $query->where(function($q) use ($request) {
                foreach ($request->input('tax_id') as $taxId) {
                    $q->orWhere('no_invoice', 'like', '%' . $taxId . '%');
                }
            });
        }
        
        if ($request->has('min_nominal') && $request->filled('min_nominal')) {
            $query->where('nominal_fix', '>=', $request->input('min_nominal'));
        }
        
        if ($request->has('max_nominal') && $request->filled('max_nominal')) {
            $query->where('nominal_fix', '<=', $request->input('max_nominal'));
        }
        
        // Filter by outstanding status
        if ($request->filled('outstanding_status')) {
            if ($request->outstanding_status === '0') {
                $query->where('outstanding', 0);
            } elseif ($request->outstanding_status === '1') {
                $query->where('outstanding', '>', 0);
            }
        }
        
        // Calculate totals for cards from ALL data (not filtered)
        $totalCount = \App\Models\BlibliFinancialTransaction::count();
        $totalNominalFix = \App\Models\BlibliFinancialTransaction::sum('nominal_fix');
        $totalSaldoMasuk = \App\Models\BlibliFinancialTransaction::sum('saldo_masuk');
        $totalOutstanding = \App\Models\BlibliFinancialTransaction::sum('outstanding');
        
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
        
        // Group transactions by order number for display
        $groupedTransactions = $transactions->groupBy('no_order');
        
        // Get all orders that don't have financial transactions
        $blibliPlatformId = Platform::where('name', 'blibli')->value('id');
        $missingOrders = Order::withoutGlobalScope('mainCategory')
            ->with('orderItems')
            ->whereDoesntHave('blibliFinancialTransactions')
            ->where('platform_id', $blibliPlatformId)
            ->orderBy('tanggal', 'desc')
            ->get();
            
        return view('financial.blibli.index', compact(
            'transactions', 
            'groupedTransactions',
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
        return view('financial.blibli.import');
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
        return view('financial.blibli.import');
    }

    public function preview(Request $request)
    {
        // If this is a GET request, check if we have data in the session
        if ($request->isMethod('get')) {
            if (!session()->has('blibli_import_data')) {
                return redirect()->route('finance.blibli.import')
                    ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
            }
            
            // Get the data from the session
            $data = session('blibli_import_data');
            $previewData = session('blibli_preview_data');
            $previewHeaders = session('blibli_preview_headers');
            $headerLabels = session('blibli_header_labels');
            $issues = session('blibli_issues');
            $totalRows = session('blibli_total_rows');
            $validRows = session('blibli_valid_rows');
            $invalidRows = session('blibli_invalid_rows');
            $transactionSummary = session('blibli_transaction_summary');
            
            // If any of the required data is missing, redirect to import
            if (!$previewData || !$previewHeaders || !$headerLabels) {
                return redirect()->route('finance.blibli.import')
                    ->with('error', 'Data preview tidak lengkap. Silakan upload file kembali.');
            }
            
            // Initialize transaction summary if not present in the session
            if (!$transactionSummary) {
                $transactionSummary = [
                    'total_rows_scanned' => $totalRows,
                    'valid_rows' => $validRows,
                    'invalid_rows' => $invalidRows,
                    'ignored_rows' => 0
                ];
            }
            
            return view('financial.blibli.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows', 'transactionSummary'));
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
            
            // Validasi header sesuai dengan yang kita butuhkan untuk Blibli
            $requiredHeaders = [
                'NOMOR PESANAN',
                'JUMLAH MASUK PEMBAYARAN'
            ];
            
            // Cek apakah header untuk tanggal dan hari pembayaran tersedia
            $hasPaymentDate = in_array('TANGGAL MASUK PEMBAYARAN', $headers);
            $hasPaymentDay = in_array('HARI MASUK PEMBAYARAN', $headers);
            $hasBiayaAdmin = in_array('BIAYA ADMIN', $headers);
            $hasBiayaLayanan = in_array('BIAYA LAYANAN', $headers);
            
            $missingHeaders = [];
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    $missingHeaders[] = $requiredHeader;
                }
            }
            
            if (!empty($missingHeaders)) {
                return redirect()->back()->with('error', 'Header yang diperlukan tidak ditemukan: ' . implode(', ', $missingHeaders));
            }
            
            // Proses data per baris
            $rowNumber = 1;
            $rows = $worksheet->rangeToArray('A2:' . $highestColumn . $worksheet->getHighestRow(), null, true, false);
            
            $totalRowsScanned = 0;
            $validRows = 0;
            $invalidRows = 0;
            $ignoredRows = 0;
            
            foreach ($rows as $row) {
                $rowNumber++;
                $totalRowsScanned++;
                
                // Skip baris kosong
                if (empty(array_filter($row))) {
                    $ignoredRows++;
                    continue;
                }
                
                // Pastikan jumlah kolom sesuai
                if (count($row) >= count($headers)) {
                    // Buat array data berdasarkan header
                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        if (isset($row[$index])) {
                            $rowData[$header] = $row[$index];
                        }
                    }
                    
                    // Set data yang akan diperiksa
                    $orderNumber = $rowData['NOMOR PESANAN'] ?? null;
                    $paymentAmount = $rowData['JUMLAH MASUK PEMBAYARAN'] ?? null;
                    $paymentDate = null;
                    if (!empty($rowData['TANGGAL MASUK PEMBAYARAN'])) {
                        try {
                            // Log the original payment date for debugging
                            \Log::info("Processing payment date for order {$orderNumber}: " . $rowData['TANGGAL MASUK PEMBAYARAN']);
                            
                            $dateValue = $rowData['TANGGAL MASUK PEMBAYARAN'];
                            
                            // Check if it's a numeric value (Excel serial date)
                            if (is_numeric($dateValue)) {
                                try {
                                    $excelDate = Date::excelToDateTimeObject($dateValue);
                                    $paymentDate = $excelDate->format('Y-m-d');
                                    \Log::info("Successfully parsed Excel serial date for order {$orderNumber}: {$paymentDate} (from serial: {$dateValue})");
                                } catch (\Exception $e) {
                                    \Log::warning("Failed to convert Excel serial date for order {$orderNumber}: " . $e->getMessage());
                                }
                            }
                            
                            // If still null, try to parse the date in various string formats
                            if ($paymentDate === null) {
                                $dateFormats = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'd/m/y', 'm/d/y', 'Y.m.d', 'd.m.Y', 'm.d.Y'];
                                foreach ($dateFormats as $format) {
                                    $parsedDate = \DateTime::createFromFormat($format, $dateValue);
                                    if ($parsedDate !== false) {
                                        $paymentDate = $parsedDate->format('Y-m-d');
                                        \Log::info("Successfully parsed payment date for order {$orderNumber}: {$paymentDate} using format {$format}");
                                        break;
                                    }
                                }
                            }
                            
                            // If still null, try Carbon's parse as last resort
                            if ($paymentDate === null) {
                                $paymentDate = Carbon::parse($dateValue)->format('Y-m-d');
                                \Log::info("Successfully parsed payment date for order {$orderNumber}: {$paymentDate} using Carbon parse");
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Could not parse payment date for order {$orderNumber}: " . $rowData['TANGGAL MASUK PEMBAYARAN'] . " - Error: " . $e->getMessage());
                        }
                    } else {
                        \Log::warning("No payment date provided for order {$orderNumber}, will use default current date");
                    }
                    $paymentDay = '';
                    if ($paymentDate) {
                        $paymentDay = Carbon::parse($paymentDate)->locale('id')->isoFormat('dddd');
                    }
                    $biayaAdmin = $hasBiayaAdmin ? ($rowData['BIAYA ADMIN'] ?? null) : null;
                    $biayaLayanan = $hasBiayaLayanan ? ($rowData['BIAYA LAYANAN'] ?? null) : null;
                    
                    // Translate English day names to Indonesian
                    $dayTranslations = [
                        'Monday' => 'Senin',
                        'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis',
                        'Friday' => 'Jumat',
                        'Saturday' => 'Sabtu',
                        'Sunday' => 'Minggu',
                    ];
                    
                    // Apply translation if paymentDay is in English
                    if (in_array($paymentDay, array_keys($dayTranslations))) {
                        $paymentDay = $dayTranslations[$paymentDay];
                    }
                    
                    // Skip row if BIAYA ADMIN or BIAYA LAYANAN is empty as requested
                    if (($hasBiayaAdmin && empty($biayaAdmin)) || ($hasBiayaLayanan && empty($biayaLayanan))) {
                        $ignoredRows++;
                        continue;
                    }
                    
                    // Validasi data yang dibutuhkan
                    $rowIssues = [];
                    if (empty($orderNumber)) {
                        $rowIssues[] = 'Nomor pesanan tidak boleh kosong';
                    } 
                    if (!isset($paymentAmount)) {
                        $rowIssues[] = 'Jumlah masuk pembayaran tidak ditemukan';
                    } elseif ($paymentAmount === '') {
                        $rowIssues[] = 'Jumlah masuk pembayaran tidak boleh kosong';
                    }
                    
                    // Validasi order di database - SEDERHANA DAN NATURAL
                    $order = null;
                    if (!empty($orderNumber)) {
                        // Get Blibli platform ID
                        $platform = Platform::where('name', 'blibli')->first();
                        if (!$platform) {
                            $rowIssues[] = 'Platform Blibli tidak ditemukan di database.';
                        } else {
                            // Check with Eloquent - BYPASS GLOBAL SCOPE
                            $order = Order::withoutGlobalScope('mainCategory')
                                ->where('order_number', $orderNumber)
                                ->where('platform_id', $platform->id)
                                ->first();
                            
                            if (!$order) {
                                $rowIssues[] = 'Nomor order ' . $orderNumber . ' tidak ditemukan di database untuk platform Blibli. Pastikan order sudah diimport terlebih dahulu dari menu Sales > Blibli.';
                            } else {
                                // Cek jika transaksi sudah ada
                                $transactionExists = BlibliFinancialTransaction::where('no_order', $orderNumber)->exists();
                                if ($transactionExists) {
                                    // Change from blocker to warning - just add a warning flag
                                    $rowData['is_duplicate'] = true;
                                    $rowData['warning'] = 'Transaksi untuk order ini sudah ada (akan dilewati saat import)';
                                }
                            }
                        }
                    }
                    
                    // Validate payment date
                    if (empty($paymentDate)) {
                        $rowIssues[] = 'Format tanggal pembayaran tidak valid atau tanggal kosong. Pastikan format tanggal benar.';
                    }
                    
                    // Process the valid row
                    if (empty($rowIssues) && $order) {
                        $validRows++;
                        
                        $orderData = [
                            'tanggal_order' => $order->tanggal ? $order->tanggal->format('Y-m-d') : null,
                            'hari_order' => $order->hari,
                            'no_order' => $orderNumber,
                            'saldo_masuk' => (float) $paymentAmount,
                            'tanggal_masuk_pembayaran' => $paymentDate, // ✅ No fallback to current date
                            'hari_masuk_pembayaran' => $paymentDay ?: Carbon::parse($paymentDate)->locale('id')->isoFormat('dddd'),
                            'invoices' => [],
                        ];
                        
                        // Log the payment date being used for this order
                        \Log::info("Order {$orderNumber} payment date set to: " . ($paymentDate ?: date('Y-m-d')));
                        
                        // Get barang keluar records for tax ID grouping
                        $barangKeluarItems = \App\Models\BarangKeluar::whereHas('orderItem', function($query) use ($order) {
                            $query->where('order_id', $order->id);
                        })->with('warehouseStock')->get();
                        
                        // Group items by tax_id
                        $taxGroups = [];
                        $totalQty = 0;
                        
                        if ($barangKeluarItems->count() > 0) {
                            foreach ($barangKeluarItems as $item) {
                                if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                                    $taxId = $item->warehouseStock->tax_id;
                                    if (!isset($taxGroups[$taxId])) {
                                        $taxGroups[$taxId] = [];
                                    }
                                    $taxGroups[$taxId][] = $item;
                                    $totalQty += $item->qty;
                                }
                            }
                        } else {
                            // If no barang keluar items, use default tax_id (3 for PKP Skincare)
                            $taxGroups[3] = [$order->orderItems];
                            foreach ($order->orderItems as $item) {
                                $totalQty += $item->quantity;
                            }
                        }
                        
                        // If still no tax groups (shouldn't happen), create default
                        if (empty($taxGroups)) {
                            $taxGroups[3] = [$order->orderItems];
                            foreach ($order->orderItems as $item) {
                                $totalQty += $item->quantity;
                            }
                        }
                        
                        // Calculate total order price for proportioning
                        $totalOrderPrice = $order->orderItems->sum(function($item) {
                            return $item->price_after_discount * $item->quantity;
                        });
                        
                        // Calculate discount values
                        $nominal_diskon1 = $hasBiayaAdmin ? -abs((float) $biayaAdmin) : 0; // Biaya Admin
                        $nominal_diskon2 = $hasBiayaLayanan ? -abs((float) $biayaLayanan) : 0; // Biaya Layanan
                        $nominal_diskon3 = 0;
                        $nominal_diskon4 = 0;
                        $nominal_diskon5 = !empty($rowData['BIAYA5']) ? -abs((float) $rowData['BIAYA5']) : 0;
                        $nominal_diskon6 = !empty($rowData['BIAYA6']) ? -abs((float) $rowData['BIAYA6']) : 0;
                        
                        // Create invoice entries for each tax group
                        foreach ($taxGroups as $taxId => $items) {
                            // Calculate invoice quantity and value
                            $invoiceQty = 0;
                            $invoiceValue = 0;
                            
                            foreach ($items as $itemOrCollection) {
                                if (is_array($itemOrCollection)) {
                                    foreach ($itemOrCollection as $subItem) {
                                        $itemQty = $subItem->quantity ?? 1;
                                        $invoiceQty += $itemQty;
                                        
                                        // Calculate value for this item
                                        if ($subItem instanceof \App\Models\BarangKeluar && $subItem->orderItem) {
                                            $unitPrice = $subItem->orderItem->price_after_discount;
                                            $invoiceValue += $unitPrice * $itemQty;
                                        } else {
                                            $unitPrice = $subItem->price_after_discount ?? 0;
                                            $invoiceValue += $unitPrice * $itemQty;
                                        }
                                    }
                                } elseif ($itemOrCollection instanceof \App\Models\BarangKeluar) {
                                    $itemQty = $itemOrCollection->qty;
                                    $invoiceQty += $itemQty;
                                    
                                    // Calculate value for BarangKeluar item
                                    if ($itemOrCollection->orderItem) {
                                        $unitPrice = $itemOrCollection->orderItem->price_after_discount;
                                        $invoiceValue += $unitPrice * $itemQty;
                                    }
                                } elseif ($itemOrCollection instanceof \Illuminate\Database\Eloquent\Collection) {
                                    foreach ($itemOrCollection as $subItem) {
                                        $itemQty = $subItem->quantity ?? 1;
                                        $invoiceQty += $itemQty;
                                        
                                        // Calculate value for this item
                                        if ($subItem instanceof \App\Models\BarangKeluar && $subItem->orderItem) {
                                            $unitPrice = $subItem->orderItem->price_after_discount;
                                            $invoiceValue += $unitPrice * $itemQty;
                                        } else {
                                            $unitPrice = $subItem->price_after_discount ?? 0;
                                            $invoiceValue += $unitPrice * $itemQty;
                                        }
                                    }
                                } else {
                                    $itemQty = $itemOrCollection->quantity ?? 1;
                                    $invoiceQty += $itemQty;
                                    
                                    // Calculate value for this item
                                    $unitPrice = $itemOrCollection->price_after_discount ?? 0;
                                    $invoiceValue += $unitPrice * $itemQty;
                                }
                            }
                            
                            // Calculate proportion based on VALUE, not quantity
                            $proportion = $totalOrderPrice > 0 ? $invoiceValue / $totalOrderPrice : 1;
                            
                            // Calculate proportional values for this invoice
                            $invoice_nominal_harga = round($invoiceValue, 2); // Use actual invoice value
                            $invoice_nominal_diskon1 = $nominal_diskon1 * $proportion;
                            $invoice_nominal_diskon2 = $nominal_diskon2 * $proportion;
                            $invoice_nominal_diskon3 = $nominal_diskon3 * $proportion;
                            $invoice_nominal_diskon4 = $nominal_diskon4 * $proportion;
                            $invoice_nominal_diskon5 = $nominal_diskon5 * $proportion;
                            $invoice_nominal_diskon6 = $nominal_diskon6 * $proportion;
                            
                            // Calculate adjustment, nominal_fix, saldo_masuk, outstanding
                            $adjustment = 0;
                            $invoice_nominal_fix = $invoice_nominal_harga + 
                                $invoice_nominal_diskon1 + $invoice_nominal_diskon2 + 
                                $invoice_nominal_diskon3 + $invoice_nominal_diskon4 + 
                                $invoice_nominal_diskon5 + $invoice_nominal_diskon6 + 
                                $adjustment;
                                
                            $invoice_saldo_masuk = $orderData['saldo_masuk'] * $proportion;
                            $outstanding = $invoice_nominal_fix - $invoice_saldo_masuk;
                            
                            // Calculate percentage values
                            $persentase_diskon1 = $invoice_nominal_harga > 0 ? ($invoice_nominal_diskon1 / $invoice_nominal_harga) * 100 : 0;
                            $persentase_diskon2 = $invoice_nominal_harga > 0 ? ($invoice_nominal_diskon2 / $invoice_nominal_harga) * 100 : 0;
                            $persentase_diskon3 = $invoice_nominal_harga > 0 ? ($invoice_nominal_diskon3 / $invoice_nominal_harga) * 100 : 0;
                            $persentase_diskon4 = $invoice_nominal_harga > 0 ? ($invoice_nominal_diskon4 / $invoice_nominal_harga) * 100 : 0;
                            $persentase_diskon5 = $invoice_nominal_harga > 0 ? ($invoice_nominal_diskon5 / $invoice_nominal_harga) * 100 : 0;
                            $persentase_diskon6 = $invoice_nominal_harga > 0 ? ($invoice_nominal_diskon6 / $invoice_nominal_harga) * 100 : 0;
                            
                            $total_persentase = $persentase_diskon1 + $persentase_diskon2 + $persentase_diskon3 + 
                                             $persentase_diskon4 + $persentase_diskon5 + $persentase_diskon6;
                                             
                            // Generate invoice number for this tax group
                            $no_invoice = BlibliFinancialTransaction::generateInvoiceNumber($order, $taxId);
                            
                            // Add this invoice to the order's invoices array
                            $orderData['invoices'][] = [
                                'no_invoice' => $no_invoice,
                                'tax_id' => $taxId,
                                'qty' => $invoiceQty,
                                'nominal_harga' => $invoice_nominal_harga,
                                'nominal_diskon1' => $invoice_nominal_diskon1,
                                'nominal_diskon2' => $invoice_nominal_diskon2,
                                'nominal_diskon3' => $invoice_nominal_diskon3,
                                'nominal_diskon4' => $invoice_nominal_diskon4,
                                'nominal_diskon5' => $invoice_nominal_diskon5,
                                'nominal_diskon6' => $invoice_nominal_diskon6,
                                'adjustment' => $adjustment,
                                'nominal_fix' => $invoice_nominal_fix,
                                'saldo_masuk' => $invoice_saldo_masuk,
                                'outstanding' => $outstanding,
                                'persentase_diskon1' => round($persentase_diskon1, 2),
                                'persentase_diskon2' => round($persentase_diskon2, 2),
                                'persentase_diskon3' => round($persentase_diskon3, 2),
                                'persentase_diskon4' => round($persentase_diskon4, 2),
                                'persentase_diskon5' => round($persentase_diskon5, 2),
                                'persentase_diskon6' => round($persentase_diskon6, 2),
                                'total_persentase' => round($total_persentase, 2),
                                'is_pkp' => in_array($taxId, [1, 3, 5, 7]), // Tax IDs 1, 3, 5, 7 are PKP
                            ];
                        }
                        
                        // Add duplicate warning if needed
                        if (isset($rowData['is_duplicate']) && $rowData['is_duplicate']) {
                            $orderData['is_duplicate'] = true;
                            $orderData['warning'] = $rowData['warning'];
                        }
                        
                        // Add row to preview data
                        $previewData[] = $orderData;
                        
                        // Add validation status to data
                        $rowData['_valid'] = true;
                        $rowData['_issues'] = [];
                        $rowData['_row'] = $rowNumber;
                        
                        $data[] = $rowData;
                    } else {
                        $invalidRows++;
                    }
                    
                    // Add issues if any
                    if (!empty($rowIssues)) {
                        $issues[$rowNumber] = $rowIssues;
                    }
                    
                    $rowData['_valid'] = empty($rowIssues);
                    $rowData['_issues'] = $rowIssues;
                    $rowData['_row'] = $rowNumber;
                    
                    $data[] = $rowData;
                } else {
                    $invalidRows++;
                    $issues[$rowNumber] = ['Jumlah kolom tidak sesuai dengan header'];
                }
            }
            
            // Create transaction summary
            $transactionSummary = [
                'total_rows_scanned' => $totalRowsScanned,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'ignored_rows' => $ignoredRows
            ];
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
        
        // Define display columns for preview
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
        
        // User-friendly header labels
        $headerLabels = [
            'tanggal_order' => 'Tanggal Order',
            'hari_order' => 'Hari Order',
            'no_order' => 'No. Order',
            'no_invoice' => 'No. Invoice',
            'nominal_harga' => 'Nominal Harga',
            'nominal_diskon1' => 'Biaya Admin',
            'nominal_diskon2' => 'Biaya Layanan',
            'nominal_diskon3' => 'Biaya 3',
            'nominal_diskon4' => 'Biaya 4',
            'nominal_diskon5' => 'Biaya 5',
            'nominal_diskon6' => 'Biaya 6',
            'nominal_diskon7' => 'Biaya 7',
            'nominal_diskon8' => 'Biaya 8',
            'nominal_diskon9' => 'Biaya 9',
            'nominal_diskon10' => 'Biaya 10',
            'nominal_diskon11' => 'Biaya 11',
            'nominal_diskon12' => 'Biaya 12',
            'adjustment' => 'Adjustment',
            'nominal_fix' => 'Nominal Fix',
            'qty' => 'Qty',
            'saldo_masuk' => 'Saldo Masuk',
            'tanggal_masuk_pembayaran' => 'Tanggal Masuk Pembayaran',
            'hari_masuk_pembayaran' => 'Hari Masuk Pembayaran',
            'outstanding' => 'Outstanding'
        ];
        
        // Save data to session
        session(['blibli_import_data' => $data]);
        session(['blibli_preview_data' => $previewData]);
        session(['blibli_preview_headers' => $previewHeaders]);
        session(['blibli_header_labels' => $headerLabels]);
        session(['blibli_issues' => $issues]);
        session(['blibli_total_rows' => count($data)]);
        session(['blibli_valid_rows' => $validRows]);
        session(['blibli_invalid_rows' => $invalidRows]);
        session(['blibli_transaction_summary' => $transactionSummary]);
        
        // Define total rows variable for compact
        $totalRows = count($data);
        
        return view('financial.blibli.preview', compact('data', 'previewData', 'previewHeaders', 'headerLabels', 'issues', 'totalRows', 'validRows', 'invalidRows', 'transactionSummary'));
    }

    public function process(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $data = $request->input('data', []);
            $importCount = 0;
            $skippedCount = 0;
            $groupedData = [];
            
            // First, group data by order number
            foreach ($data as $key => $row) {
                if ($row['_valid'] === 'true') {
                    $orderNumber = $row['no_order'];
                    
                    // Skip orders that already exist in the database
                    $transactionExists = BlibliFinancialTransaction::where('no_order', $orderNumber)->exists();
                    if ($transactionExists) {
                        $skippedCount++;
                        continue;
                    }
                    
                    if (!isset($groupedData[$orderNumber])) {
                        $groupedData[$orderNumber] = [];
                    }
                    $groupedData[$orderNumber][] = $row;
                }
            }
            
            // Process each order and its invoices
            foreach ($groupedData as $orderNumber => $rows) {
                // Find the order - PERBAIKAN: Gunakan platform_id langsung dan bypass global scope
                $blibliPlatformId = Platform::where('name', 'blibli')->value('id');
                $order = Order::withoutGlobalScope('mainCategory')
                    ->where('order_number', $orderNumber)
                    ->where('platform_id', $blibliPlatformId)
                    ->first();
                
                if (!$order) {
                    \Log::warning("Blibli order not found: " . $orderNumber);
                    continue;
                }
                
                // Get all order items for calculating proportions
                $orderItems = $order->orderItems;
                $totalQuantity = $orderItems->sum('quantity');
                $totalPrice = 0;
                foreach ($orderItems as $item) {
                    $totalPrice += ($item->price_after_discount * $item->quantity);
                }
                
                // Process each row (invoice) for this order
                foreach ($rows as $row) {
                    // Extract the tax_id from the input data
                    $taxId = isset($row['tax_id']) ? (int)$row['tax_id'] : 3; // Default to PKP Skincare (3) if not specified
                    
                    // Check if transaction already exists with this tax_id for this order
                    $exists = BlibliFinancialTransaction::where('order_id', $order->id)
                                ->where('no_invoice', 'like', '%/' . BlibliFinancialTransaction::getSuffixForTaxId($taxId))
                                ->exists();
                    
                    if (!$exists) {
                        // Get the proportion for this invoice (based on the data sent from preview page)
                        $proportion = 1;
                        if (count($rows) > 1) {
                            // Calculate proportion from input data
                            if (isset($row['proportion'])) {
                                $proportion = (float)$row['proportion'];
                            } else {
                                // Default to equal distribution if proportion not specified
                                $proportion = 1 / count($rows);
                            }
                        }
                        
                        $transaction = new BlibliFinancialTransaction();
                        
                        // Set order relationship and basic info
                        $transaction->order_id = $order->id;
                        $transaction->tanggal_order = $order->tanggal;
                        $transaction->hari_order = $order->hari;
                        $transaction->no_order = $order->order_number;
                        
                        // Generate invoice number based on tax_id
                        $transaction->no_invoice = BlibliFinancialTransaction::generateInvoiceNumber($order, $taxId);
                        
                        // Calculate price and discounts based on proportion
                        $transaction->nominal_harga = $totalPrice * $proportion;
                        
                        // Set discount values from imported data with proportion applied
                        $transaction->nominal_diskon1 = isset($row['BIAYA ADMIN']) ? -abs((float) $row['BIAYA ADMIN']) * $proportion : 0;
                        $transaction->nominal_diskon2 = isset($row['BIAYA LAYANAN']) ? -abs((float) $row['BIAYA LAYANAN']) * $proportion : 0;
                        $transaction->nominal_diskon3 = isset($row['BIAYA3']) ? -abs((float) $row['BIAYA3']) * $proportion : 0;
                        $transaction->nominal_diskon4 = isset($row['BIAYA4']) ? -abs((float) $row['BIAYA4']) * $proportion : 0;
                        $transaction->nominal_diskon5 = isset($row['BIAYA5']) ? -abs((float) $row['BIAYA5']) * $proportion : 0;
                        $transaction->nominal_diskon6 = isset($row['BIAYA6']) ? -abs((float) $row['BIAYA6']) * $proportion : 0;
                        $transaction->nominal_diskon7 = isset($row['BIAYA7']) ? -abs((float) $row['BIAYA7']) * $proportion : 0;
                        $transaction->nominal_diskon8 = isset($row['BIAYA8']) ? -abs((float) $row['BIAYA8']) * $proportion : 0;
                        $transaction->nominal_diskon9 = isset($row['BIAYA9']) ? -abs((float) $row['BIAYA9']) * $proportion : 0;
                        $transaction->nominal_diskon10 = isset($row['BIAYA10']) ? -abs((float) $row['BIAYA10']) * $proportion : 0;
                        $transaction->nominal_diskon11 = isset($row['BIAYA11']) ? -abs((float) $row['BIAYA11']) * $proportion : 0;
                        $transaction->nominal_diskon12 = isset($row['BIAYA12']) ? -abs((float) $row['BIAYA12']) * $proportion : 0;
                        
                        // Set payment info
                        $transaction->saldo_masuk = isset($row['JUMLAH MASUK PEMBAYARAN']) ? (float) $row['JUMLAH MASUK PEMBAYARAN'] * $proportion : 0;
                        
                        // Use the parsed payment date from preview, or parse raw Excel value if needed
                        if (isset($row['tanggal_masuk_pembayaran'])) {
                            $transaction->tanggal_masuk_pembayaran = $row['tanggal_masuk_pembayaran'];
                        } elseif (isset($row['TANGGAL MASUK PEMBAYARAN'])) {
                            // Apply the same date parsing logic as in preview
                            $dateValue = $row['TANGGAL MASUK PEMBAYARAN'];
                            $parsedDate = null;
                            
                            // Check if it's a numeric value (Excel serial date)
                            if (is_numeric($dateValue)) {
                                try {
                                    $excelDate = Date::excelToDateTimeObject($dateValue);
                                    $parsedDate = $excelDate->format('Y-m-d');
                                } catch (\Exception $e) {
                                    \Log::warning("Failed to convert Excel serial date in process: " . $e->getMessage());
                                }
                            }
                            
                            // If still null, try string formats
                            if ($parsedDate === null) {
                                try {
                                    $parsedDate = Carbon::parse($dateValue)->format('Y-m-d');
                                    \Log::info("Successfully parsed date using Carbon fallback for order {$orderNumber}: {$parsedDate}");
                                } catch (\Exception $e) {
                                    \Log::error("CRITICAL: Failed to parse payment date for order {$orderNumber}. Raw value: {$dateValue}. Error: " . $e->getMessage());
                                    // Instead of using current date, throw an error or skip the row
                                    throw new \Exception("Cannot parse payment date '{$dateValue}' for order {$orderNumber}. Please check Excel format.");
                                }
                            }

                            $transaction->tanggal_masuk_pembayaran = $parsedDate;
                        } else {
                            // If no payment date is provided, this should be an error condition
                            \Log::error("CRITICAL: No payment date provided for order {$orderNumber}");
                            throw new \Exception("Payment date is required for order {$orderNumber}. Column 'TANGGAL MASUK PEMBAYARAN' is missing or empty.");
                        }
                        
                        $transaction->hari_masuk_pembayaran = isset($row['hari_masuk_pembayaran']) ? $row['hari_masuk_pembayaran'] : (isset($row['HARI MASUK PEMBAYARAN']) ? $row['HARI MASUK PEMBAYARAN'] : date('l'));
                        
                        // Log the final payment date assignment
                        \Log::info("Final payment date assigned for order {$orderNumber}: " . $transaction->tanggal_masuk_pembayaran);
                        
                        // Translate English day names to Indonesian if needed
                        $dayTranslations = [
                            'Monday' => 'Senin',
                            'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis',
                            'Friday' => 'Jumat',
                            'Saturday' => 'Sabtu',
                            'Sunday' => 'Minggu',
                        ];
                        
                        if (in_array($transaction->hari_masuk_pembayaran, array_keys($dayTranslations))) {
                            $transaction->hari_masuk_pembayaran = $dayTranslations[$transaction->hari_masuk_pembayaran];
                        }
                        
                        // Set adjustment and calculate other values
                        $transaction->adjustment = 0; // Default adjustment to 0
                        $transaction->calculateNominalFix(); // This will set the nominal_fix value
                        $transaction->calculateOutstanding(); // This will set the outstanding value
                        $transaction->calculatePercentages(); // This will calculate and set the percentage values
                        
                        // Add validation for potential overpayment scenarios
                        if ($transaction->saldo_masuk > 0 && $transaction->nominal_fix > 0) {
                            $ratio = $transaction->saldo_masuk / $transaction->nominal_fix;
                            if ($ratio > 3.0) { // If payment is more than 3x the expected amount
                                \Log::warning("Potential overpayment detected for order {$orderNumber}: saldo_masuk={$transaction->saldo_masuk}, nominal_fix={$transaction->nominal_fix}");
                            }
                        }
                        
                        $transaction->save();
                        $importCount++;
                    } else {
                        \Log::warning("Transaction already exists for order {$orderNumber} with tax_id $taxId");
                    }
                }
            }
            
            DB::commit();
            
            $message = "Successfully imported $importCount financial transactions.";
            if ($skippedCount > 0) {
                $message .= " $skippedCount entries were skipped because they already exist in the database.";
            }
            
            // Clear session data after successful import
            session()->forget(['blibli_import_data', 'blibli_preview_data', 'blibli_preview_headers', 
                              'blibli_header_labels', 'blibli_issues', 'blibli_total_rows', 
                              'blibli_valid_rows', 'blibli_invalid_rows', 'blibli_transaction_summary']);
            
            return redirect()->route('finance.blibli.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error during Blibli financial import: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error processing data: ' . $e->getMessage());
        }
    }

    public function manual()
    {
        // Implementasi halaman manual input jika diperlukan
        return view('financial.blibli.manual');
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
            
            $order = Order::withoutGlobalScope('mainCategory')->findOrFail($request->order_id);
            
            // Cek jika sudah ada transaksi untuk order ini
            $exists = BlibliFinancialTransaction::where('order_id', $order->id)->exists();
            if ($exists) {
                return redirect()->back()->with('error', 'Transaksi untuk order ini sudah ada.');
            }
            
            // Get BarangKeluar items for this order for proportional calculations
            $barangKeluarItems = \App\Models\BarangKeluar::whereHas('orderItem', function($query) use ($order) {
                $query->where('order_id', $order->id);
            })->with('warehouseStock', 'orderItem')->get();
            
            // Group BarangKeluar by tax_id
            $itemsByTaxId = [];
            $totalQty = 0;
            
            if ($barangKeluarItems->count() > 0) {
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
            } else {
                // If no barang keluar items, use default tax_id (3 for PKP Skincare)
                $itemsByTaxId[3] = [$order->orderItems];
                foreach ($order->orderItems as $item) {
                    $totalQty += $item->quantity;
                }
            }
            
            // If still no tax groups (shouldn't happen), create default
            if (empty($itemsByTaxId)) {
                $itemsByTaxId[3] = [$order->orderItems];
                foreach ($order->orderItems as $item) {
                    $totalQty += $item->quantity;
                }
            }
            
            // Calculate total order price for proportioning
            $totalOrderPrice = $order->orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            
            // Store discount values from form input
            $nominal_diskon1 = $request->nominal_diskon1 ? -abs((float) $request->nominal_diskon1) : 0;
            $nominal_diskon2 = $request->nominal_diskon2 ? -abs((float) $request->nominal_diskon2) : 0;
            $nominal_diskon3 = $request->nominal_diskon3 ? -abs((float) $request->nominal_diskon3) : 0;
            $nominal_diskon4 = $request->nominal_diskon4 ? -abs((float) $request->nominal_diskon4) : 0;
            $nominal_diskon5 = $request->nominal_diskon5 ? -abs((float) $request->nominal_diskon5) : 0;
            $nominal_diskon6 = $request->nominal_diskon6 ? -abs((float) $request->nominal_diskon6) : 0;
            
            // Create a transaction for each tax group with value-based proportion
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
                
                // Create transaction for this tax group
                $transaction = new BlibliFinancialTransaction();
                $transaction->tanggal_order = $order->tanggal;
                $transaction->hari_order = $order->hari;
                $transaction->no_order = $order->order_number;
                $transaction->order_id = $order->id;
                
                // Set values based on value-based proportion
                $transaction->nominal_harga = round($groupValue, 2); // Use actual group value
                $transaction->qty = $groupQty; // Set the group quantity
                $transaction->nominal_diskon1 = round($nominal_diskon1 * $proportion, 2);
                $transaction->nominal_diskon2 = round($nominal_diskon2 * $proportion, 2);
                $transaction->nominal_diskon3 = round($nominal_diskon3 * $proportion, 2);
                $transaction->nominal_diskon4 = round($nominal_diskon4 * $proportion, 2);
                $transaction->nominal_diskon5 = round($nominal_diskon5 * $proportion, 2);
                $transaction->nominal_diskon6 = round($nominal_diskon6 * $proportion, 2);
                $transaction->adjustment = round(($request->adjustment ?? 0) * $proportion, 2);
                
                // Set payment info with proportion
                $transaction->saldo_masuk = round($request->saldo_masuk * $proportion, 2);
                $transaction->tanggal_masuk_pembayaran = $request->tanggal_masuk_pembayaran;
                $transaction->hari_masuk_pembayaran = $request->hari_masuk_pembayaran;
                
                // Translate English day names to Indonesian
                $dayTranslations = [
                    'Monday' => 'Senin',
                    'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu',
                    'Thursday' => 'Kamis',
                    'Friday' => 'Jumat',
                    'Saturday' => 'Sabtu',
                    'Sunday' => 'Minggu',
                ];
                
                // Apply translation if day is in English
                if (in_array($transaction->hari_masuk_pembayaran, array_keys($dayTranslations))) {
                    $transaction->hari_masuk_pembayaran = $dayTranslations[$transaction->hari_masuk_pembayaran];
                }
                
                // Generate invoice number using the new format
                $transaction->no_invoice = BlibliFinancialTransaction::generateInvoiceNumber($order, $taxId);
                
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
            }
            
            DB::commit();
            
            return redirect()->route('finance.blibli.index')->with('success', 'Transaksi keuangan berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menambahkan transaksi Blibli manual: " . $e->getMessage());
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
            
            $transaction = BlibliFinancialTransaction::findOrFail($id);
            
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
            
            // Recalculate values
            $transaction->calculateNominalFix();
            $transaction->outstanding = $transaction->nominal_fix - $transaction->saldo_masuk;
            $transaction->calculatePercentages();
            
            // Simpan ke adjustment_histories
            AdjustmentHistory::create([
                'transaction_id' => $transaction->id,
                'platform' => 'blibli',
                'old_value' => $oldAdjustment,
                'new_value' => $transaction->adjustment,
                'description' => $request->adjustment_description,
                'user_id' => auth()->id(),
            ]);
            
            $transaction->save();
            
            DB::commit();
            
            return redirect()->route('finance.blibli.index')
                ->with('success', 'Adjustment berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat adjust transaksi Blibli: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a transaction
     */
    public function delete($id)
    {
        try {
            $transaction = BlibliFinancialTransaction::findOrFail($id);
            $transaction->delete();
            
            return redirect()->route('finance.blibli.index')
                ->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error("Error saat menghapus transaksi Blibli: " . $e->getMessage());
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
            $transaction = BlibliFinancialTransaction::with([
                    'order.orderItems.platformProduct',
                    'order.orderItems.warehouseStock.tax'
                ])
                ->findOrFail($id);
                
            // Determine the tax_id from the invoice number and set logo accordingly
            $taxId = null;
            $logoFile = 'HGN.jpeg'; // Default logo file
            $isPKP = true; // Default to PKP
            
            if (strpos($transaction->no_invoice, 'BLBNSD-OLC/01') !== false) {
                $taxId = 1; // PKP - Cosmetic
                $logoFile = 'HGN.jpeg';
                $isPKP = true;
            } elseif (strpos($transaction->no_invoice, 'BLBNSD-OLC/02') !== false) {
                $taxId = 2; // Non PKP - Cosmetic
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
            
            return view('financial.blibli.print-invoice', compact('transaction', 'logoFile', 'isPKP'));
        } catch (\Exception $e) {
            Log::error("Error saat print invoice Blibli: " . $e->getMessage());
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
            $transaction = BlibliFinancialTransaction::findOrFail($id);
            $transaction->lock(auth()->id());
            
            return redirect()->back()->with('success', 'Transaksi berhasil dikunci.');
        } catch (\Exception $e) {
            Log::error("Error saat mengunci transaksi Blibli: " . $e->getMessage());
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
            $transaction = BlibliFinancialTransaction::findOrFail($id);
            
            // Only admin or the person who locked it can unlock
            if (auth()->user()->role != 'admin' && $transaction->locked_by != auth()->id()) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk membuka kunci transaksi ini.');
            }
            
            $transaction->unlock();
            
            return redirect()->back()->with('success', 'Kunci transaksi berhasil dibuka.');
        } catch (\Exception $e) {
            Log::error("Error saat membuka kunci transaksi Blibli: " . $e->getMessage());
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
            $transaction = BlibliFinancialTransaction::with('lockedByUser')->findOrFail($id);
            $adjustmentHistories = \App\Models\AdjustmentHistory::where('transaction_id', $transaction->id)
                ->where('platform', 'blibli')
                ->orderBy('created_at', 'desc')
                ->with('user')
                ->get();
            return view('financial.blibli.history', compact('transaction', 'adjustmentHistories'));
        } catch (\Exception $e) {
            Log::error("Error saat melihat history transaksi Blibli: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Export financial transactions to Excel
     */
    public function exportExcel(Request $request)
    {
        $filename = 'blibli_financial_transactions_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new BlibliFinanceAnalyticsExport($request->all()), $filename);
    }

    /**
     * Export financial transactions to PDF
     */
    public function exportPdf(Request $request)
    {
        // Build the same query as in the index method
        $query = BlibliFinancialTransaction::query();
        
        // Apply the same filters as in index method
        if ($request->has('from_date') && $request->filled('from_date')) {
            $query->where('tanggal_masuk_pembayaran', '>=', $request->from_date);
        }
        
        if ($request->has('to_date') && $request->filled('to_date')) {
            $query->where('tanggal_masuk_pembayaran', '<=', $request->to_date);
        }
        
        if ($request->has('from_order_date') && $request->filled('from_order_date')) {
            $query->where('tanggal_order', '>=', $request->from_order_date);
        }
        
        if ($request->has('to_order_date') && $request->filled('to_order_date')) {
            $query->where('tanggal_order', '<=', $request->to_order_date);
        }
        
        if ($request->has('order_number')) {
            $query->where('no_order', 'like', '%' . $request->order_number . '%');
        }
        
        if ($request->has('invoice_number')) {
            $query->where('no_invoice', 'like', '%' . $request->invoice_number . '%');
        }
        
        if ($request->has('min_nominal')) {
            $query->where('nominal_fix', '>=', $request->min_nominal);
        }
        
        if ($request->has('max_nominal')) {
            $query->where('nominal_fix', '<=', $request->max_nominal);
        }
        
        if ($request->has('outstanding_status')) {
            if ($request->outstanding_status === '0') {
                $query->where('outstanding', 0);
            } elseif ($request->outstanding_status === '1') {
                $query->where('outstanding', '>', 0);
            }
        }
        
        $transactions = $query->orderBy('tanggal_order', 'desc')->get();
        
        $pdf = Pdf::loadView('exports.financial.blibli', compact('transactions'))
            ->setPaper('a4', 'landscape');
            
        return $pdf->download('blibli_financial_transactions_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
}
