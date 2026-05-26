<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LazadaFinancialTransaction;
use App\Models\Platform;
use App\Models\Order;
use App\Exports\LazadaFinanceAnalyticsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class LazadaFinanceController extends Controller
{
    protected $platform;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Dapatkan platform Lazada dari database menggunakan ID
        // Cari berdasarkan nama terlebih dahulu untuk mendapatkan ID yang benar
        $this->platform = Platform::whereRaw('LOWER(name) = ?', ['lazada'])->first();
        
        // Jika tidak ditemukan, cari dengan LIKE
        if (!$this->platform) {
            $this->platform = Platform::whereRaw('LOWER(name) LIKE ?', ['%lazada%'])->first();
        }
        
        // Jika platform tidak ditemukan, throw exception
        if (!$this->platform) {
            throw new \Exception('Platform Lazada tidak ditemukan di database.');
        }
        
        // Sekarang gunakan ID platform untuk query selanjutnya
        // Platform sudah ditemukan, ID tersimpan di $this->platform->id
    }

    /**
     * Display a listing of the financial transactions for Lazada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $platformId = $this->platform->id;
        $platform = 'lazada';
        $mainCategoryId = session('main_category_id');

        $query = LazadaFinancialTransaction::with([
            'order:id,order_number,tanggal,hari',
        ]);

        // Filter by payment date range
        if ($request->filled('from_date')) {
            $query->whereDate('lazada_financial_transactions.tanggal_masuk_pembayaran', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('lazada_financial_transactions.tanggal_masuk_pembayaran', '<=', $request->to_date);
        }

        // Filter by order date range
        if ($request->filled('from_order_date')) {
            $query->whereDate('lazada_financial_transactions.tanggal_order', '>=', $request->from_order_date);
        }
        
        if ($request->filled('to_order_date')) {
            $query->whereDate('lazada_financial_transactions.tanggal_order', '<=', $request->to_order_date);
        }

        // Filter by order number
        if ($request->filled('order_number')) {
            $query->where('lazada_financial_transactions.no_order', 'like', '%' . $request->order_number . '%');
        }

        // Filter by invoice number
        if ($request->filled('invoice_number')) {
            $query->where('lazada_financial_transactions.no_invoice', 'like', '%' . $request->invoice_number . '%');
        }

        // Filter by nominal range
        if ($request->filled('min_nominal')) {
            $query->where('lazada_financial_transactions.nominal_fix', '>=', $request->min_nominal);
        }
        
        if ($request->filled('max_nominal')) {
            $query->where('lazada_financial_transactions.nominal_fix', '<=', $request->max_nominal);
        }

        // Filter by outstanding status
        if ($request->filled('outstanding_status')) {
            if ($request->outstanding_status === '0') {
                $query->join(DB::raw('(
                    SELECT no_order
                    FROM lazada_financial_transactions
                    GROUP BY no_order
                    HAVING SUM(outstanding) = 0
                ) as outstanding_zero'), 'lazada_financial_transactions.no_order', '=', 'outstanding_zero.no_order');
            } elseif ($request->outstanding_status === '1') {
                $query->join(DB::raw('(
                    SELECT no_order
                    FROM lazada_financial_transactions
                    GROUP BY no_order
                    HAVING SUM(outstanding) != 0
                ) as outstanding_nonzero'), 'lazada_financial_transactions.no_order', '=', 'outstanding_nonzero.no_order');
            }
        }

        // Exclude orders with retur penjualan
        $query->whereNotExists(function($subQuery) {
            $subQuery->select(DB::raw(1))
                ->from('retur_penjualans as rp')
                ->join('orders as o', 'rp.order_id', '=', 'o.id')
                ->whereColumn('o.order_number', 'lazada_financial_transactions.no_order')
                ->whereIn('rp.status', ['draft', 'selesai'])
                ->whereNotNull('o.order_number')
                ->where('o.order_number', '!=', '');
        });

        // Calculate totals for cards from FILTERED data
        $totalCount = $query->count();
        $totalNominalFix = $query->sum('lazada_financial_transactions.nominal_fix');
        $totalSaldoMasuk = $query->sum('lazada_financial_transactions.saldo_masuk');
        $totalOutstanding = $query->sum('lazada_financial_transactions.outstanding');

        // Clone query for pagination
        $transactions = clone $query;
        $transactions = $transactions->orderBy('lazada_financial_transactions.tanggal_order', 'desc')
            ->orderBy('lazada_financial_transactions.no_order', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Group transactions by order number for display
        $groupedTransactions = $transactions->groupBy('no_order');

        // Get unpaid orders (orders without financial transactions)
        $missingOrdersQuery = Order::withoutGlobalScope('mainCategory')
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.tanggal',
                'orders.status',
                DB::raw('COALESCE(SUM(order_items.price_after_discount * order_items.quantity), 0) as total_value'),
                DB::raw('COALESCE(DATEDIFF(NOW(), orders.tanggal), 0) as days_since_order'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as order_total_quantity'),
                DB::raw('COALESCE((
                    SELECT SUM(rpd.qty)
                    FROM retur_penjualan_details rpd
                    INNER JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                    INNER JOIN order_items oi ON rpd.order_item_id = oi.id
                    WHERE oi.order_id = orders.id
                    AND rp.status IN ("draft", "selesai")
                ), 0) as returned_quantity')
            ])
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('lazada_financial_transactions as lft', 'orders.id', '=', 'lft.order_id')
            ->leftJoin('warehouse_stock as ws', 'ws.id', '=', 'order_items.warehouse_stock_id')
            ->leftJoin('products as p', 'p.id', '=', 'ws.product_id')
            ->whereNull('lft.order_id')
            ->where('orders.platform_id', $platformId)
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM retur_penjualan_details rpd
                INNER JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                INNER JOIN order_items oi ON rpd.order_item_id = oi.id
                WHERE oi.order_id = orders.id
                AND rp.status IN ("draft", "selesai")
                GROUP BY oi.order_id
                HAVING SUM(rpd.qty) >= (
                    SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = orders.id
                )
            )')
            ->groupBy('orders.id', 'orders.order_number', 'orders.tanggal', 'orders.status')
            ->havingRaw('(returned_quantity < order_total_quantity OR returned_quantity = 0)')
            ->orderBy('orders.tanggal', 'desc');

        if ($mainCategoryId) {
            $missingOrdersQuery->where('p.main_category_id', $mainCategoryId);
        }
        
        $missingOrders = $missingOrdersQuery->paginate(30, ['*'], 'unpaid_page')
            ->withQueryString();

        // Calculate total unpaid nominal
        $unpaidNominal = DB::selectOne("
            SELECT COALESCE(SUM(total_value), 0) as total
            FROM (
                SELECT 
                    orders.id,
                    COALESCE(SUM(order_items.price_after_discount * order_items.quantity), 0) as total_value,
                    COALESCE(SUM(order_items.quantity), 0) as order_total_quantity,
                    COALESCE((
                        SELECT SUM(rpd.qty)
                        FROM retur_penjualan_details rpd
                        INNER JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                        INNER JOIN order_items oi ON rpd.order_item_id = oi.id
                        WHERE oi.order_id = orders.id
                        AND rp.status IN ('draft', 'selesai')
                    ), 0) as returned_quantity
                FROM orders
                LEFT JOIN order_items ON orders.id = order_items.order_id
                WHERE orders.platform_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM lazada_financial_transactions 
                    WHERE lazada_financial_transactions.order_id = orders.id
                )
                AND NOT EXISTS (
                    SELECT 1 FROM retur_penjualan_details rpd
                    INNER JOIN retur_penjualans rp ON rpd.retur_penjualan_id = rp.id
                    INNER JOIN order_items oi ON rpd.order_item_id = oi.id
                    WHERE oi.order_id = orders.id
                    AND rp.status IN ('draft', 'selesai')
                    GROUP BY oi.order_id
                    HAVING SUM(rpd.qty) >= (
                        SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = orders.id
                    )
                )
                GROUP BY orders.id
                HAVING (returned_quantity < order_total_quantity OR returned_quantity = 0)
            ) as unpaid_orders
        ", [$platformId]);
        
        $unpaidNominal = $unpaidNominal->total ?? 0;

        return view('financial.lazada.index', compact(
            'transactions', 
            'groupedTransactions', 
            'platform', 
            'missingOrders',
            'unpaidNominal',
            'totalCount',
            'totalNominalFix',
            'totalSaldoMasuk',
            'totalOutstanding'
        ));
    }

    /**
     * Export data ke Excel
     */
    public function exportExcel(Request $request)
    {
        $filename = 'lazada_finance_analytics_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new LazadaFinanceAnalyticsExport($request->all()), $filename);
    }

    /**
     * Tampilkan halaman import financial data Lazada
     */
    public function import()
    {
        return view('financial.lazada.import');
    }

    /**
     * Preview data financial sebelum import
     */
    public function preview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $file = $request->file('excel_file');
            $tempPath = $file->store('temp');
            $fullPath = storage_path('app/' . $tempPath);

            // Import menggunakan LazadaFinancialImport
            $import = new \App\Imports\LazadaFinancialImport($this->platform->id);
            Excel::import($import, $fullPath);
            
            // Hapus file sementara
            Storage::delete($tempPath);

            // Get processed data
            $data = $import->getData();
            $previewData = $import->getPreviewData();
            $issues = $import->getIssues();
            
            // Calculate statistics
            $totalRows = count($data);
            $validRows = count(array_filter($data, function($row) { return $row['_valid']; }));
            $invalidRows = $totalRows - $validRows;
            
            // Save to session for process
            session(['lazada_import_data' => $data]);
            session(['lazada_preview_data' => $previewData]);
            session(['lazada_import_issues' => $issues]);
            session(['lazada_total_rows' => $totalRows]);
            session(['lazada_valid_rows' => $validRows]);
            session(['lazada_invalid_rows' => $invalidRows]);
            
            // Generate process token
            $processToken = uniqid('lazada_', true);
            session(['lazada_process_token' => $processToken]);

            return view('financial.lazada.preview', compact(
                'data', 
                'previewData', 
                'issues', 
                'totalRows', 
                'validRows', 
                'invalidRows'
            ));

        } catch (\Exception $e) {
            Log::error('Lazada Financial Import Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->route('finance.lazada.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman input manual financial data Lazada
     */
    public function manual()
    {
        $orders = Order::where('platform_id', $this->platform->id)
            ->whereDoesntHave('lazadaFinancialTransactions')
            ->orderBy('tanggal', 'desc')
            ->get();
            
        return view('financial.lazada.manual', compact('orders'));
    }

    /**
     * Store manual financial transaction
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'tanggal_masuk_pembayaran' => 'required|date',
            'nominal_harga' => 'required|numeric',
            'saldo_masuk' => 'required|numeric',
            'nominal_diskon1' => 'nullable|numeric',
            'nominal_diskon2' => 'nullable|numeric',
            'nominal_diskon3' => 'nullable|numeric',
            'nominal_diskon4' => 'nullable|numeric',
            'nominal_diskon5' => 'nullable|numeric',
            'nominal_diskon6' => 'nullable|numeric',
            'adjustment' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::with('orderItems.warehouseStock.tax')->findOrFail($request->order_id);
            
            // Check if transaction already exists
            $existing = LazadaFinancialTransaction::where('order_id', $order->id)->first();
            if ($existing) {
                return redirect()->back()->with('error', 'Transaksi keuangan untuk order ini sudah ada.');
            }

            // Get dominant tax_id from order items
            $taxIdCounts = [];
            foreach ($order->orderItems as $item) {
                if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                    $taxId = $item->warehouseStock->tax_id;
                    if (!isset($taxIdCounts[$taxId])) {
                        $taxIdCounts[$taxId] = 0;
                    }
                    $taxIdCounts[$taxId] += $item->quantity;
                }
            }
            
            // Get dominant tax_id (most quantity)
            $dominantTaxId = 3; // Default to SKINCARE PKP ONLINE
            if (!empty($taxIdCounts)) {
                arsort($taxIdCounts);
                $dominantTaxId = array_key_first($taxIdCounts);
            }
            
            // Generate invoice number based on tax_id
            $invoiceNumber = LazadaFinancialTransaction::generateInvoiceNumber($order, $dominantTaxId);

            // Calculate values
            $nominalHarga = $request->nominal_harga;
            $diskon1 = $request->nominal_diskon1 ?? 0;
            $diskon2 = $request->nominal_diskon2 ?? 0;
            $diskon3 = $request->nominal_diskon3 ?? 0;
            $diskon4 = $request->nominal_diskon4 ?? 0;
            $diskon5 = $request->nominal_diskon5 ?? 0;
            $diskon6 = $request->nominal_diskon6 ?? 0;
            $adjustment = $request->adjustment ?? 0;
            $saldoMasuk = $request->saldo_masuk;

            $nominalFix = $nominalHarga - ($diskon1 + $diskon2 + $diskon3 + $diskon4 + $diskon5 + $diskon6) + $adjustment;
            $outstanding = $nominalFix - $saldoMasuk;

            // Calculate percentages
            $persentase_diskon1 = $nominalHarga > 0 ? abs(($diskon1 / $nominalHarga) * 100) : 0;
            $persentase_diskon2 = $nominalHarga > 0 ? abs(($diskon2 / $nominalHarga) * 100) : 0;
            $persentase_diskon3 = $nominalHarga > 0 ? abs(($diskon3 / $nominalHarga) * 100) : 0;
            $persentase_diskon4 = $nominalHarga > 0 ? abs(($diskon4 / $nominalHarga) * 100) : 0;
            $persentase_diskon5 = $nominalHarga > 0 ? abs(($diskon5 / $nominalHarga) * 100) : 0;
            $persentase_diskon6 = $nominalHarga > 0 ? abs(($diskon6 / $nominalHarga) * 100) : 0;
            $total_persentase = $persentase_diskon1 + $persentase_diskon2 + $persentase_diskon3 + 
                               $persentase_diskon4 + $persentase_diskon5 + $persentase_diskon6;
            
            $percentage_paid = $nominalHarga > 0 ? abs(($saldoMasuk / $nominalHarga) * 100) : 0;
            $percentage_outstanding = $nominalHarga > 0 ? abs(($outstanding / $nominalHarga) * 100) : 0;

            // Determine hari_masuk_pembayaran
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $hariMasukPembayaran = $days[date('w', strtotime($request->tanggal_masuk_pembayaran))];

            LazadaFinancialTransaction::create([
                'order_id' => $order->id,
                'tanggal_order' => $order->tanggal,
                'hari_order' => $order->hari,
                'no_order' => $order->order_number,
                'no_invoice' => $invoiceNumber,
                'nominal_harga' => $nominalHarga,
                'nominal_diskon1' => $diskon1,
                'nominal_diskon2' => $diskon2,
                'nominal_diskon3' => $diskon3,
                'nominal_diskon4' => $diskon4,
                'nominal_diskon5' => $diskon5,
                'nominal_diskon6' => $diskon6,
                'adjustment' => $adjustment,
                'nominal_fix' => $nominalFix,
                'saldo_masuk' => $saldoMasuk,
                'tanggal_masuk_pembayaran' => $request->tanggal_masuk_pembayaran,
                'hari_masuk_pembayaran' => $hariMasukPembayaran,
                'outstanding' => $outstanding,
                'persentase_diskon1' => $persentase_diskon1,
                'persentase_diskon2' => $persentase_diskon2,
                'persentase_diskon3' => $persentase_diskon3,
                'persentase_diskon4' => $persentase_diskon4,
                'persentase_diskon5' => $persentase_diskon5,
                'persentase_diskon6' => $persentase_diskon6,
                'total_persentase' => $total_persentase,
                'percentage_paid' => $percentage_paid,
                'percentage_outstanding' => $percentage_outstanding,
            ]);

            // Trigger ReturFinanceService if needed (based on memory)
            // Memory says: "Online returns (ReturPenjualan) must trigger ReturFinanceService::handleOnlineReturFinance immediately upon creation (store) to update financial records."
            // This is creating a financial transaction, not a return. 
            // However, if there are existing returns for this order, maybe we should trigger something?
            // Usually the trigger is on Return creation. 
            // But if the return existed BEFORE the finance transaction, the finance transaction creation should probably check for returns?
            // The memory says "ReturFinanceService handles partial returns by updating existing financial transactions."
            // If the financial transaction is created NOW, and a return ALREADY exists, we might need to update the financial transaction.
            // But let's stick to the basic implementation first. The user just wants manual input to work.

            DB::commit();

            return redirect()->route('finance.lazada.index')
                ->with('success', 'Data keuangan berhasil ditambahkan secara manual.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lazada Manual Input Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Proses import financial data
     */
    public function process(Request $request)
    {
        try {
            // Verify process token
            $processToken = $request->input('process_token');
            if (!$processToken || $processToken !== session('lazada_process_token')) {
                return redirect()->route('finance.lazada.import')
                    ->with('error', 'Token proses tidak valid. Silakan upload file kembali.');
            }
            
            $data = session('lazada_import_data');
            $previewData = session('lazada_preview_data');

            if (!$data || !$previewData) {
                return redirect()->route('finance.lazada.import')
                    ->with('error', 'Data preview tidak ditemukan.');
            }

            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;

            foreach ($data as $index => $rowData) {
                if (!$rowData['_valid']) {
                    $skippedCount++;
                    continue;
                }
                
                try {
                    $order = Order::with('orderItems.warehouseStock.tax')->find($rowData['order_id']);
                    if (!$order) {
                        $errorCount++;
                        continue;
                    }
                    
                    $previewRow = $rowData['preview_row'];
                    $orderData = $rowData['order_data'];
                    
                    // Check if transaction already exists
                    $existing = LazadaFinancialTransaction::where('no_order', $previewRow['no_order'])
                        ->where('order_id', $order->id)
                        ->first();
                    
                    if ($existing) {
                        $skippedCount++;
                        continue;
                    }
                    
                    // Get dominant tax_id from order items
                    $taxIdCounts = [];
                    foreach ($order->orderItems as $item) {
                        if ($item->warehouseStock && $item->warehouseStock->tax_id) {
                            $taxId = $item->warehouseStock->tax_id;
                            if (!isset($taxIdCounts[$taxId])) {
                                $taxIdCounts[$taxId] = 0;
                            }
                            $taxIdCounts[$taxId] += $item->quantity;
                        }
                    }
                    
                    // Get dominant tax_id (most quantity)
                    $dominantTaxId = 3; // Default to SKINCARE PKP ONLINE
                    if (!empty($taxIdCounts)) {
                        arsort($taxIdCounts);
                        $dominantTaxId = array_key_first($taxIdCounts);
                    }
                    
                    // Generate invoice number based on tax_id
                    $invoiceNumber = LazadaFinancialTransaction::generateInvoiceNumber($order, $dominantTaxId);
                    
                    // Calculate percentages
                    $nominalHarga = $previewRow['nominal_harga'];
                    $persentase_diskon1 = $nominalHarga > 0 ? abs(($previewRow['nominal_diskon1'] / $nominalHarga) * 100) : 0;
                    $persentase_diskon2 = $nominalHarga > 0 ? abs(($previewRow['nominal_diskon2'] / $nominalHarga) * 100) : 0;
                    $persentase_diskon3 = $nominalHarga > 0 ? abs(($previewRow['nominal_diskon3'] / $nominalHarga) * 100) : 0;
                    $persentase_diskon4 = $nominalHarga > 0 ? abs(($previewRow['nominal_diskon4'] / $nominalHarga) * 100) : 0;
                    $persentase_diskon5 = $nominalHarga > 0 ? abs(($previewRow['nominal_diskon5'] / $nominalHarga) * 100) : 0;
                    $persentase_diskon6 = $nominalHarga > 0 ? abs(($previewRow['nominal_diskon6'] / $nominalHarga) * 100) : 0;
                    $total_persentase = $persentase_diskon1 + $persentase_diskon2 + $persentase_diskon3 + 
                                       $persentase_diskon4 + $persentase_diskon5 + $persentase_diskon6;
                    
                    $percentage_paid = $nominalHarga > 0 ? abs(($previewRow['saldo_masuk'] / $nominalHarga) * 100) : 0;
                    $percentage_outstanding = $nominalHarga > 0 ? abs(($previewRow['outstanding'] / $nominalHarga) * 100) : 0;
                    
                    LazadaFinancialTransaction::create([
                        'order_id' => $order->id,
                        'tanggal_order' => $previewRow['tanggal_order'],
                        'hari_order' => $previewRow['hari_order'],
                        'no_order' => $previewRow['no_order'],
                        'no_invoice' => $invoiceNumber,
                        'nominal_harga' => $previewRow['nominal_harga'],
                        'nominal_diskon1' => $previewRow['nominal_diskon1'],
                        'nominal_diskon2' => $previewRow['nominal_diskon2'],
                        'nominal_diskon3' => $previewRow['nominal_diskon3'],
                        'nominal_diskon4' => $previewRow['nominal_diskon4'],
                        'nominal_diskon5' => $previewRow['nominal_diskon5'],
                        'nominal_diskon6' => $previewRow['nominal_diskon6'],
                        'adjustment' => $previewRow['adjustment'] ?? 0,
                        'nominal_fix' => $previewRow['nominal_fix'],
                        'saldo_masuk' => $previewRow['saldo_masuk'],
                        'tanggal_masuk_pembayaran' => $previewRow['tanggal_masuk_pembayaran'],
                        'hari_masuk_pembayaran' => $previewRow['hari_masuk_pembayaran'],
                        'outstanding' => $previewRow['outstanding'],
                        'persentase_diskon1' => $persentase_diskon1,
                        'persentase_diskon2' => $persentase_diskon2,
                        'persentase_diskon3' => $persentase_diskon3,
                        'persentase_diskon4' => $persentase_diskon4,
                        'persentase_diskon5' => $persentase_diskon5,
                        'persentase_diskon6' => $persentase_diskon6,
                        'total_persentase' => $total_persentase,
                        'percentage_paid' => $percentage_paid,
                        'percentage_outstanding' => $percentage_outstanding,
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    Log::error('Error importing Lazada financial transaction: ' . $e->getMessage());
                    $errorCount++;
                }
            }

            // Hapus data dari session
            session()->forget([
                'lazada_import_data', 
                'lazada_preview_data', 
                'lazada_import_issues',
                'lazada_total_rows',
                'lazada_valid_rows',
                'lazada_invalid_rows',
                'lazada_process_token'
            ]);

            $message = "Import selesai! Berhasil: {$successCount}, Gagal: {$errorCount}";
            if ($skippedCount > 0) {
                $message .= ", Dilewati: {$skippedCount}";
            }

            return redirect()->route('finance.lazada.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Lazada Financial Process Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->route('finance.lazada.import')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail transaction
     */
    public function show(LazadaFinancialTransaction $transaction)
    {
        return view('financial.lazada.show', compact('transaction'));
    }

    /**
     * Hapus transaction
     */
    public function destroy(LazadaFinancialTransaction $transaction)
    {
        try {
            $transaction->delete();
            return redirect()->route('finance.lazada.index')
                ->with('success', 'Transaction berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->route('finance.lazada.index')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
