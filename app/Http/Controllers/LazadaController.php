<?php

namespace App\Http\Controllers;

use App\Imports\LazadaImport;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LazadaController extends Controller
{
    protected $platform;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $routeParam = 'lazada';
        $this->platform = $this->getPlatformByRouteParam($routeParam);
    }
    
    /**
     * Helper method untuk mendapatkan platform berdasarkan route parameter
     */
    protected function getPlatformByRouteParam($routeParam)
    {
        // 1. Cari berdasarkan ID jika ada di request/session
        $platformId = request()->route('platform_id') ?? session('platform_id');
        if ($platformId) {
            $platform = Platform::find($platformId);
            if ($platform) {
                return $platform;
            }
        }
        
        // 2. Cari berdasarkan nama dengan case-insensitive
        $platform = Platform::whereRaw('LOWER(name) = ?', [strtolower($routeParam)])->first();
        
        // 3. Jika tidak ditemukan, cari dengan LIKE (untuk menangani variasi nama)
        if (!$platform) {
            $platform = Platform::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($routeParam) . '%'])->first();
        }
        
        // 4. Jika masih tidak ditemukan, ambil platform pertama (fallback)
        if (!$platform) {
            $platform = Platform::first();
        }
        
        // 5. Jika benar-benar tidak ada platform di database
        if (!$platform) {
            throw new \Exception('Platform tidak ditemukan di database. Pastikan ada platform yang terdaftar.');
        }
        
        return $platform;
    }
    /**
     * Tampilkan halaman import Excel Lazada
     */
    public function import()
    {
        // Tambahkan informasi untuk ditampilkan di halaman import
        $importGuidelines = [
            'Pastikan kolom berikut ada di file Excel Anda:' => [
                'TANGGAL (wajib)',
                'HARI (opsional)',
                'STATUS HARI (opsional)',
                'NOMOR PESANAN (wajib)',
                'QTY (wajib)',
                'HARGA SETELAH DISKON (wajib)',
                'PRODUK (wajib)',
                'VARIAN (opsional)',
                'NOMOR RESI (opsional)',
            ],
            'Catatan Penting:' => [
                'Format header bisa fleksibel (tidak harus sama persis)',
                'Header bisa berada di baris mana saja dalam file Excel',
                'Kolom HARI, STATUS HARI, VARIAN, dan NOMOR RESI boleh kosong',
                'Semua produk harus sudah terdaftar dan dimapping di sistem'
            ]
        ];

        return view('sales.lazada.import', [
            'importGuidelines' => $importGuidelines
        ]);
    }

    /**
     * Preview data Excel sebelum import
     */
    public function previewImport(Request $request)
    {
        // Validasi file yang diupload
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            // Log awal proses import
            \Log::info('Starting Excel import preview process');

            // Proses file Excel
            $import = new LazadaImport($this->platform->id);
            \Log::info('Before Excel import');
            Excel::import($import, $request->file('excel_file'));
            \Log::info('After Excel import');

            // Dapatkan data untuk preview
            $data = $import->getData();
            \Log::info('Retrieved data from import, count: ' . count($data));

            $unmappedProducts = $import->getUnmappedProducts();
            \Log::info('Unmapped products count: ' . count($unmappedProducts));

            $invalidData = $import->getInvalidData();
            \Log::info('Invalid data count: ' . count($invalidData));

            $headerIssues = $import->getHeaderIssues();
            \Log::info('Header issues: ' . json_encode($headerIssues));

            $totalRows = $import->getTotalRows();
            \Log::info('Total rows from Excel: ' . $totalRows);

            // Informasi tentang baris yang dilewati
            $skippedRows = [
                'Tanggal kosong' => $import->getSkippedForMissingDate(),
                'Hari kosong' => $import->getSkippedForMissingDay(),
                'No Resi kosong' => $import->getSkippedForMissingResi(),
            ];

            // Filter skippedRows yang nilainya 0
            $skippedRows = array_filter($skippedRows);
            \Log::info('Skipped rows: ' . json_encode($skippedRows));

            // Cek order yang sudah ada di database
            $duplicateOrders = [];
            if (!empty($data)) {
                $orderNumbers = array_unique(array_column($data, 'no_order'));
                \Log::info('Unique order numbers: ' . count($orderNumbers));

                foreach ($orderNumbers as $orderNumber) {
                    $existingOrder = Order::where('order_number', $orderNumber)->exists();

                    if ($existingOrder) {
                        $duplicateOrders[] = $orderNumber;
                    }
                }
                \Log::info('Duplicate orders found: ' . count($duplicateOrders));
            }
            
            // Set main category ke Kosmetik sebelum validasi stok
            session(['main_category_id' => 2]);
            session(['main_category_name' => 'Kosmetik']);
            
            // Debug logging
            $mainCategoryId = \App\Helpers\MainCategoryHelper::getSelectedMainCategoryId();
            \Log::info('Lazada PreviewImport - Main Category set to: ' . ($mainCategoryId ?: 'NULL'));
            
            // Cek ketersediaan stok dengan detail yang lebih lengkap
            $insufficientStockProducts = [];
            $ordersWithStockIssues = [];
            $stockIssuesSummary = [];
            
            if (!empty($data)) {
                // Hitung total kebutuhan dari seluruh file Excel (bukan per order) - sama seperti TikTok
                $totalProductQuantities = [];
                $orderProductQuantities = [];
                
                foreach ($data as $row) {
                    $orderNumber = $row['no_order'] ?? 'N/A';
                    if (!isset($orderProductQuantities[$orderNumber])) {
                        $orderProductQuantities[$orderNumber] = [];
                    }
                    
                    // Cari platform product jika belum ada di data
                    $platformProductId = $row['platform_product_id'] ?? null;
                    
                    if (!$platformProductId) {
                        // Cari platform product berdasarkan nama dan variasi
                        $platformProduct = \App\Models\PlatformProduct::where('platform_id', $this->platform->id)
                            ->where('platform_product_name', $row['nama_barang'] ?? '')
                            ->where('variant', $row['variasi'] ?? '')
                            ->first();
                        
                        if ($platformProduct) {
                            $platformProductId = $platformProduct->id;
                        }
                    }
                    
                    if (!$platformProductId) {
                        // Skip jika platform product tidak ditemukan (akan ditangani oleh unmapped products)
                        continue;
                    }
                    
                    // Ambil semua mapping barang AKTIF untuk platform product ini
                    $mappings = \App\Models\MappingBarang::where('platform_product_id', $platformProductId)
                        ->where('is_active', true)
                        ->get();
                    
                    if ($mappings->isEmpty()) {
                        // Skip jika tidak ada mapping
                        continue;
                    }
                    
                    $itemQty = $row['qty'] ?? 0;
                    
                    foreach ($mappings as $mapping) {
                        $requiredQty = $itemQty * $mapping->quantity;
                        
                        // Hitung per order (untuk detail)
                        if (!isset($orderProductQuantities[$orderNumber][$mapping->product_id])) {
                            $orderProductQuantities[$orderNumber][$mapping->product_id] = 0;
                        }
                        $orderProductQuantities[$orderNumber][$mapping->product_id] += $requiredQty;
                        
                        // Hitung total dari seluruh file Excel
                        if (!isset($totalProductQuantities[$mapping->product_id])) {
                            $totalProductQuantities[$mapping->product_id] = 0;
                        }
                        $totalProductQuantities[$mapping->product_id] += $requiredQty;
                    }
                }
                
                // Cek stok berdasarkan TOTAL kebutuhan dari seluruh file Excel
                foreach ($totalProductQuantities as $productId => $totalRequiredQty) {
                    $availableStock = \App\Models\WarehouseStock::where('product_id', $productId)
                        ->where('qty', '>', 0)
                        ->sum('qty');
                    
                    // Debug logging untuk stok
                    \Log::info("Preview Stock Check - Product ID: {$productId}, Total Required: {$totalRequiredQty}, Available: {$availableStock}, Main Category: {$mainCategoryId}");
                    
                    if ($availableStock < $totalRequiredQty) {
                        $product = \App\Models\Product::find($productId);
                        $productName = $product ? $product->name : "Product ID: {$productId}";
                        
                        // Cari order mana yang terpengaruh
                        $affectedOrders = [];
                        foreach ($orderProductQuantities as $orderNumber => $products) {
                            if (isset($products[$productId]) && $products[$productId] > 0) {
                                $affectedOrders[] = $orderNumber;
                            }
                        }
                        
                        $stockIssuesSummary[$productId] = [
                            'product_name' => $productName,
                            'total_required' => $totalRequiredQty,
                            'available_qty' => $availableStock,
                            'shortage' => $totalRequiredQty - $availableStock,
                            'affected_orders' => $affectedOrders
                        ];
                        
                        \Log::warning("Preview Stock Check - Insufficient stock detected: {$productName}, Total Required: {$totalRequiredQty}, Available: {$availableStock}, Affected Orders: " . implode(', ', $affectedOrders));
                    }
                }
                
                // Buat detail per order untuk display
                foreach ($orderProductQuantities as $orderNumber => $products) {
                    $orderStockIssues = [];
                    foreach ($products as $productId => $requiredQty) {
                        if (isset($stockIssuesSummary[$productId])) {
                            $product = \App\Models\Product::find($productId);
                            $productName = $product ? $product->name : "Product ID: {$productId}";
                            
                            $orderStockIssues[] = [
                                'product_name' => $productName,
                                'required_qty' => $requiredQty,
                                'available_qty' => $stockIssuesSummary[$productId]['available_qty'],
                                'shortage' => $requiredQty - $stockIssuesSummary[$productId]['available_qty']
                            ];
                        }
                    }
                    
                    if (!empty($orderStockIssues)) {
                        $ordersWithStockIssues[$orderNumber] = $orderStockIssues;
                    }
                }
                
                // Konversi summary ke array untuk display
                $insufficientStockProducts = array_values($stockIssuesSummary);
                
                \Log::info('Insufficient stock products: ' . count($insufficientStockProducts));
                \Log::info('Orders with stock issues: ' . count($ordersWithStockIssues));
            }

            // Simpan data ke session untuk proses import
            session(['preview_data' => $data]);
            session(['unmapped_products' => $unmappedProducts]);
            session(['insufficient_stock_products' => $insufficientStockProducts]);
            session(['orders_with_stock_issues' => $ordersWithStockIssues]);
            session(['stock_issues_summary' => $stockIssuesSummary]);
            
            // Debug logging untuk memastikan data tersimpan
            \Log::info('PreviewImport - Data saved to session:', [
                'preview_data_count' => count($data),
                'unmapped_products_count' => count($unmappedProducts),
                'insufficient_stock_count' => count($insufficientStockProducts),
                'orders_with_stock_issues_count' => count($ordersWithStockIssues),
                'session_id' => session()->getId(),
                'session_saved' => session()->has('preview_data')
            ]);

            // Tentukan apakah bisa melanjutkan import
            // Tidak bisa import jika ada masalah stok, mapping, atau data tidak valid
            $canProceed = empty($invalidData) && empty($unmappedProducts) && empty($insufficientStockProducts) && empty($headerIssues);
            
            // Buat pesan warning yang lebih detail
            $warningMessages = [];
            if (!empty($unmappedProducts)) {
                $warningMessages[] = 'Beberapa produk perlu dimapping terlebih dahulu.';
            }
            if (!empty($insufficientStockProducts)) {
                $warningMessages[] = 'Beberapa produk memiliki stok tidak mencukupi.';
            }
            if (!empty($skippedRows)) {
                $skippedCount = array_sum($skippedRows);
                $warningMessages[] = "{$skippedCount} pesanan akan dilewati karena data tidak lengkap.";
            }
            
            $warningMessage = !empty($warningMessages) ? implode(' ', $warningMessages) : null;

            return view('sales.lazada.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'canProceed' => $canProceed,
                'invalidData' => $invalidData,
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
                'insufficientStockProducts' => $insufficientStockProducts,
                'ordersWithStockIssues' => $ordersWithStockIssues,
                'stockIssuesSummary' => $stockIssuesSummary,
                'platformId' => $this->platform->id ?? null
            ])->with('warning', $warningMessage);
            
        } catch (\Exception $e) {
            \Log::error('Error during preview: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('sales.lazada.import')
                ->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Proses import data dari preview
     */
    public function processImport(Request $request)
    {
        \Log::info('---------- MULAI PROSES IMPORT LAZADA ----------');
        
        try {
            $data = session('preview_data');
            
            if (!$data) {
                return redirect()->route('sales.lazada.import')
                    ->with('error', 'Data preview tidak ditemukan. Silakan upload ulang file.');
            }

            try {
                // Proses import data
                $import = new LazadaImport($this->platform->id);

                // Set data yang akan diproses
                $import->setData($data);
                
                // Set unmapped products dari session
                $unmappedProducts = session('unmapped_products', []);
                $import->setUnmappedProducts($unmappedProducts);

                // Jalankan proses import
                $result = $import->processImport();

                // Jika sukses, hapus session dan tampilkan pesan sukses
                if ($result['success'] > 0) {
                    session()->forget(['preview_data', 'unmapped_products', 'insufficient_stock_products']);

                    // Tambahkan informasi tambahan tentang nomor pesanan duplikat
                    $successMessage = "Berhasil mengimport {$result['success']} data penjualan Lazada.";

                    if (isset($result['duplicates']) && $result['duplicates'] > 0) {
                        $successMessage .= " {$result['duplicates']} nomor pesanan dilewati karena sudah ada di database.";
                    }

                    if (isset($result['skipped']) && $result['skipped'] > 0) {
                        $successMessage .= " {$result['skipped']} pesanan dilewati karena tidak memiliki data lengkap.";
                    }

                    // Tambahkan informasi tentang order yang gagal jika ada
                    if (isset($result['failed_orders']) && !empty($result['failed_orders'])) {
                        $failedCount = count($result['failed_orders']);
                        $successMessage .= " {$failedCount} pesanan gagal diproses.";
                        
                        // Simpan detail order yang gagal untuk ditampilkan sebagai warning
                        $failedOrderDetails = [];
                        foreach ($result['failed_orders'] as $failedOrder) {
                            $failedOrderDetails[] = "Order {$failedOrder['order_number']}: {$failedOrder['error']}";
                        }
                        session()->flash('warning', 'Beberapa pesanan gagal diproses: ' . implode('; ', $failedOrderDetails));
                    }

                    return redirect()->route('sales.list')->with('success', $successMessage);
                } else {
                    // Jika ada error, simpan pesan error dalam flash session
                    $errorMessage = 'Gagal mengimport data: ' . implode(', ', $result['errors']);
                    session()->flash('error', $errorMessage);

                    // Debug: tambahkan log untuk melihat pesan error
                    \Log::info('Error message set in session: ' . $errorMessage);

                    // Dapatkan info baris yang di-skip dan duplikat
                    $totalRows = $import->getTotalRows();
                    $skippedRows = [
                        'Tanggal kosong' => $import->getSkippedForMissingDate(),
                        'Hari kosong' => $import->getSkippedForMissingDay(),
                        'No Resi kosong' => $import->getSkippedForMissingResi(),
                    ];
                    $skippedRows = array_filter($skippedRows);

                    // Cek order yang sudah ada di database
                    $duplicateOrders = [];
                    $orderNumbers = array_unique(array_column($data, 'no_order'));
                    foreach ($orderNumbers as $orderNumber) {
                        $existingOrder = Order::where('order_number', $orderNumber)->exists();
                        if ($existingOrder) {
                            $duplicateOrders[] = $orderNumber;
                        }
                    }

                    return view('sales.lazada.preview-import', [
                        'data' => $data,
                        'unmappedProducts' => $unmappedProducts,
                        'canProceed' => false,
                        'invalidData' => [],
                        'totalRows' => $totalRows,
                        'skippedRows' => $skippedRows,
                        'duplicateOrders' => $duplicateOrders,
                        'insufficientStockProducts' => []
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Exception in processImport: ' . $e->getMessage());
                \Log::error($e->getTraceAsString());

                // Simpan pesan error dalam flash session dengan detail yang lebih jelas
                $errorMessage = 'Import gagal: ' . $e->getMessage();
                
                // Jika error terkait stok, berikan pesan yang lebih spesifik
                if (strpos($e->getMessage(), 'Stok tidak cukup') !== false) {
                    $errorMessage = 'Import gagal karena stok tidak mencukupi. Silakan periksa ketersediaan stok produk yang akan diimport.';
                }

                return redirect()->route('sales.lazada.import')
                    ->with('error', $errorMessage);
            }
                
        } catch (\Exception $e) {
            \Log::error('Error during import: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('sales.lazada.import')
                ->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman platform Lazada
     */
    public function platform()
    {
        $platform = \App\Models\Platform::where('name', 'lazada')->first();
        return view('sales.lazada.platform', compact('platform'));
    }

    /**
     * Tampilkan halaman manual input
     */
    public function manual()
    {
        $platform = \App\Models\Platform::where('name', 'lazada')->first();
        return view('sales.lazada.manual', compact('platform'));
    }

    /**
     * Simpan data manual
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'order_date' => 'required|date',
        ]);

        try {
            $order = Order::create([
                'order_id' => $request->order_id,
                'platform_id' => 6, // Lazada platform ID
                'product_name' => $request->product_name,
                'quantity' => $request->quantity,
                'price' => $request->price,
                'total_amount' => $request->quantity * $request->price,
                'order_date' => $request->order_date,
                'status' => $request->status ?? 'pending',
                'customer_name' => $request->customer_name ?? '',
                'customer_phone' => $request->customer_phone ?? '',
                'customer_address' => $request->customer_address ?? '',
            ]);

            return redirect()->route('sales.lazada.index')
                ->with('success', 'Order berhasil ditambahkan!');

        } catch (\Exception $e) {
            return redirect()->route('sales.lazada.manual')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan daftar orders Lazada
     */
    public function index()
    {
        $orders = Order::where('platform_id', 6) // Lazada platform ID
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('sales.lazada.index', compact('orders'));
    }

    /**
     * Tampilkan halaman platform products
     */
    public function platformProduct()
    {
        $platform = \App\Models\Platform::where('name', 'lazada')->first();
        $platformProducts = \App\Models\PlatformProduct::where('platform_id', $platform->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('sales.lazada.platform-product', compact('platform', 'platformProducts'));
    }

    /**
     * Simpan platform product
     */
    public function storePlatformProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'variant' => 'required|string|max:255',
        ]);

        try {
            $platform = \App\Models\Platform::where('name', 'lazada')->first();
            
            $platformProduct = \App\Models\PlatformProduct::create([
                'platform_id' => $platform->id,
                'name' => $request->name,
                'variant' => $request->variant,
            ]);

            return redirect()->route('sales.lazada.platform-product')
                ->with('success', 'Platform product berhasil ditambahkan!');

        } catch (\Exception $e) {
            return redirect()->route('sales.lazada.platform-product')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman mapping
     */
    public function mapping()
    {
        $platform = \App\Models\Platform::where('name', 'lazada')->first();
        $mappings = \App\Models\MappingBarang::whereHas('platformProduct', function($query) use ($platform) {
            $query->where('platform_id', $platform->id);
        })->with(['platformProduct', 'product'])
          ->orderBy('created_at', 'desc')
          ->paginate(20);
          
        return view('sales.lazada.mapping', compact('platform', 'mappings'));
    }

    /**
     * Simpan mapping
     */
    public function storeMapping(Request $request)
    {
        $request->validate([
            'platform_product_id' => 'required|exists:platform_products,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $mapping = \App\Models\MappingBarang::create([
                'platform_product_id' => $request->platform_product_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'is_active' => true,
                'version' => 1,
            ]);

            return redirect()->route('sales.lazada.mapping')
                ->with('success', 'Mapping berhasil ditambahkan!');

        } catch (\Exception $e) {
            return redirect()->route('sales.lazada.mapping')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus mapping
     */
    public function deleteMapping($id)
    {
        try {
            $mapping = \App\Models\MappingBarang::findOrFail($id);
            $mapping->delete();

            return redirect()->route('sales.lazada.mapping')
                ->with('success', 'Mapping berhasil dihapus!');

        } catch (\Exception $e) {
            return redirect()->route('sales.lazada.mapping')
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}