<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BlibliImport;

class BlibliController extends Controller
{
    protected $platform;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Dapatkan platform Blibli dari database
        $this->platform = Platform::where('name', 'blibli')->first();
        
        // Jika platform tidak ditemukan, buat baru
        if (!$this->platform) {
            $this->platform = Platform::create(['name' => 'blibli']);
        }
    }

    /**
     * Tampilkan halaman import Excel Blibli
     */
    public function importExcel()
    {
        return view('sales.blibli.import-excel');
    }

    /**
     * Preview data Excel sebelum import
     */
    public function previewImport(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240',
            ]);

            \Log::info("Blibli previewImport: Starting import with file: " . $request->file('excel_file')->getClientOriginalName());

            // Import data menggunakan BlibliImport
            $import = new BlibliImport();
            
            $file = $request->file('excel_file');
            $extension = strtolower($file->getClientOriginalExtension());
            
            \Log::info("Blibli previewImport: File extension: " . $extension);
            
            if ($extension == 'csv') {
                Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::CSV);
            } else if ($extension == 'xls') {
                Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::XLS);
            } else {
                Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::XLSX);
            }

            // Ambil data yang sudah diproses
            $data = $import->getData();
            $unmappedProducts = $import->getUnmappedProducts();
            $invalidData = $import->getInvalidData();
            $headerIssues = $import->getHeaderIssues();
            $totalRows = $import->getTotalRows();
            $skippedRows = [];

            \Log::info("Blibli previewImport: Data count: " . count($data));
            \Log::info("Blibli previewImport: Unmapped products: " . count($unmappedProducts));

            // Jika tidak ada data valid, tampilkan error
            if (empty($data)) {
                return redirect()->route('sales.blibli.import-excel')
                    ->with('error', 'Tidak ada data yang dapat diproses dari file Excel. Pastikan format file sudah benar.');
            }

            // Cek order duplikat dalam file
            $orderNumbers = [];
            $duplicateOrdersInFile = [];
            $ordersInDB = [];

            // Cek duplikat dalam file dan di database - SEDERHANA DAN NATURAL
            foreach ($data as $row) {
                $orderId = $row['no_order'];
                if (in_array($orderId, $orderNumbers)) {
                    $duplicateOrdersInFile[] = $orderId;
                } else {
                    $orderNumbers[] = $orderId;
                }
                
                // Cek duplikat di database - SIMPLE QUERY (Skip Global Scope untuk deteksi duplikat yang akurat)
                // Global scope 'mainCategory' dapat menghalangi deteksi duplikat jika order sudah ada dengan main_category_id berbeda
                $exists = \App\Models\Order::withoutGlobalScope('mainCategory')
                    ->where('order_number', $orderId)
                    ->where('platform_id', $this->platform->id)
                    ->exists();
                    
                \Log::info("Blibli previewImport: Checking order $orderId - exists: " . ($exists ? 'YES' : 'NO') . " (platform_id: {$this->platform->id})");
                    
                if ($exists) {
                    if (!in_array($orderId, $ordersInDB)) {
                        $ordersInDB[] = $orderId;
                    }
                }
            }
            
            // Log untuk debugging
            \Log::info("Blibli previewImport: Total orders: " . count(array_unique($orderNumbers)));
            \Log::info("Blibli previewImport: Orders in DB: " . count($ordersInDB));
            \Log::info("Blibli previewImport: Orders in DB list: " . implode(", ", $ordersInDB));
            \Log::info("Blibli previewImport: Platform ID: " . $this->platform->id);
            
            // Tandai data yang sudah ada di database
            $previewData = [];
            foreach ($data as $row) {
                $row['already_in_db'] = in_array($row['no_order'], $ordersInDB);
                $previewData[] = $row;
            }
            
            // Hitung apakah semua order sudah ada di database
            $uniqueOrders = array_unique($orderNumbers);
            $allAlreadyInDb = count($uniqueOrders) > 0 && count($uniqueOrders) === count($ordersInDB);
            \Log::info("Blibli previewImport: All already in DB: " . ($allAlreadyInDb ? 'YES' : 'NO'));
            
            session([
                'preview_data' => $previewData,
                'unmapped_products' => $unmappedProducts,
                'invalid_data' => $invalidData,
                'total_rows' => $totalRows,
                'valid_rows' => count($data),
                'importable_rows' => count($data) - count($duplicateOrdersInFile),
                'skipped_rows' => $skippedRows,
                'duplicate_orders' => $ordersInDB,
                'duplicate_orders_in_file' => array_unique($duplicateOrdersInFile),
                'all_already_in_db' => $allAlreadyInDb,
                'can_proceed' => (
                    count($unmappedProducts) === 0 && 
                    (empty($headerIssues) || (count($headerIssues) === 1 && $headerIssues[0] === 'Kolom variasi tidak ditemukan'))
                )
            ]);
            
            // Log session values after saving
            \Log::info("Blibli previewImport: Session duplicate_orders: " . json_encode(session('duplicate_orders')));
            \Log::info("Blibli previewImport: Session all_already_in_db: " . (session('all_already_in_db') ? 'YES' : 'NO'));
            
            return redirect()->route('sales.blibli.show-preview-import');
        } catch (\Exception $e) {
            \Log::error('Error in import process: '.$e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->route('sales.blibli.import-excel')->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman preview import
     */
    public function showPreviewImport()
    {
        if (!session('preview_data')) {
            return view('sales.blibli.import-excel')->with('error', 'Tidak ada data yang dapat diproses dari file Excel. Pastikan semua pesanan memiliki tanggal yang valid.');
        }
        
        \Log::info("Blibli showPreviewImport: Starting with session data");
        
        // Get data from session
        $data = session('preview_data');
        
        // ALWAYS check duplicates directly from database
        \Log::info("Blibli showPreviewImport: Checking database directly");
        $orderNumbers = array_unique(array_column($data, 'no_order'));
        $platformId = $this->platform->id;
        
        \Log::info("Blibli showPreviewImport: Checking " . count($orderNumbers) . " orders for platform_id: " . $platformId);
        \Log::info("Blibli showPreviewImport: Order numbers: " . json_encode($orderNumbers));
        
        $duplicateOrders = [];
        foreach ($orderNumbers as $orderNumber) {
            $exists = \App\Models\Order::withoutGlobalScope('mainCategory')
                ->where('order_number', $orderNumber)
                ->where('platform_id', $platformId)
                ->exists();
            
            // Simple check - no complex variations needed
            
            \Log::info("Blibli showPreviewImport: Order $orderNumber exists: " . ($exists ? 'YES' : 'NO'));
            
            if ($exists) {
                $duplicateOrders[] = $orderNumber;
            }
        }
        
        \Log::info("Blibli showPreviewImport: Found " . count($duplicateOrders) . " duplicates directly from database");
        \Log::info("Blibli showPreviewImport: Duplicate orders: " . json_encode($duplicateOrders));
        
        // Update session
        session(['duplicate_orders' => $duplicateOrders]);
        
        // Recalculate all_already_in_db
        $allAlreadyInDb = count($orderNumbers) > 0 && count($orderNumbers) === count($duplicateOrders);
        session(['all_already_in_db' => $allAlreadyInDb]);
        
        \Log::info("Blibli showPreviewImport: Updated all_already_in_db: " . ($allAlreadyInDb ? 'YES' : 'NO'));
        
        // Pass all session data to the view
        return view('sales.blibli.preview-import', [
            'data' => $data,
            'unmappedProducts' => session('unmapped_products', []),
            'invalidData' => session('invalid_data', []),
            'totalRows' => session('total_rows', 0),
            'validRows' => session('valid_rows', 0),
            'importableRows' => session('importable_rows', 0),
            'skippedRows' => session('skipped_rows', []),
            'duplicateOrders' => $duplicateOrders,
            'duplicateOrdersInFile' => session('duplicate_orders_in_file', []),
            'canProceed' => session('can_proceed', false),
            'allAlreadyInDb' => $allAlreadyInDb
        ]);
    }

    /**
     * Proses import data dari preview
     */
    public function processImport(Request $request)
    {

        // Tambahkan log untuk debugging
        \Log::info("Blibli processImport: Starting import process");

        // Ambil data dari session
        $data = session('preview_data');
        
        \Log::info("Blibli processImport: Session data count: " . ($data ? count($data) : 0));
        
        if (!$data) {
            return redirect()->route('blibli.import-excel')
                ->with('error', 'Tidak ada data untuk diimport. Silakan upload file Excel terlebih dahulu.');
        }
        
        // ALWAYS check duplicates directly from database
        \Log::info("Blibli processImport: Checking database directly");
        $orderNumbers = array_unique(array_column($data, 'no_order'));
        $platformId = $this->platform->id;
        
        \Log::info("Blibli processImport: Checking " . count($orderNumbers) . " orders for platform_id: " . $platformId);
        
        $duplicateOrders = [];
        foreach ($orderNumbers as $orderNumber) {
            $exists = \App\Models\Order::withoutGlobalScope('mainCategory')
                ->where('order_number', $orderNumber)
                ->where('platform_id', $platformId)
                ->exists();
            
            // Simple check - no complex variations needed
            
            \Log::info("Blibli processImport: Order $orderNumber exists: " . ($exists ? 'YES' : 'NO'));
            
            if ($exists) {
                $duplicateOrders[] = $orderNumber;
            }
        }
        
        \Log::info("Blibli processImport: Found " . count($duplicateOrders) . " duplicates directly from database");
        
        // Recalculate all_already_in_db
        $allAlreadyInDb = count($orderNumbers) > 0 && count($orderNumbers) === count($duplicateOrders);
        
        \Log::info("Blibli processImport: All already in DB: " . ($allAlreadyInDb ? 'YES' : 'NO'));
        
        // Jika semua order sudah ada di database, tampilkan warning dan jangan lakukan import
        if ($allAlreadyInDb) {
            \Log::info("Blibli processImport: All orders already exist in database, skipping import");
            return redirect()->route('sales.blibli.show-preview-import')
                ->with('error', 'Semua order pada file ini sudah ada di database. Tidak ada data yang diimport.');
        }
        
        // Cek unmapped products
        $unmappedProducts = session('unmapped_products', []);
        if (count($unmappedProducts) > 0) {
            return redirect()->route('sales.blibli.show-preview-import')
                ->with('error', 'Terdapat produk yang belum di-mapping. Tidak dapat melanjutkan import.');
        }
        
        // Track duplicates for reporting
        $duplicateOrdersInFile = session('duplicate_orders_in_file', []);
        
        \Log::info("Blibli processImport: Duplicate orders in DB: " . count($duplicateOrders));

        try {
            // Kelompokkan data berdasarkan no_order
            $groupedData = [];
            foreach ($data as $item) {
                $groupedData[$item['no_order']][] = $item;
            }
            
            \Log::info("Blibli processImport: Grouped into " . count($groupedData) . " unique orders");
            
            // Cek order yang sudah ada di database (double check)
            $ordersToImport = [];
            foreach ($groupedData as $orderNumber => $items) {
                $exists = \App\Models\Order::withoutGlobalScope('mainCategory')
                    ->where('order_number', $orderNumber)
                    ->where('platform_id', $this->platform->id)
                    ->exists();
                
                // Simple check - no complex variations needed
                
                \Log::info("Blibli processImport: Order $orderNumber exists: " . ($exists ? 'YES' : 'NO'));
                
                if (!$exists) {
                    $ordersToImport[$orderNumber] = $items;
                }
            }
            
            \Log::info("Blibli processImport: Orders to import: " . count($ordersToImport) . " of " . count($groupedData));
            
            // Jika semua order sudah ada di database, tampilkan warning dan jangan lakukan import
            if (empty($ordersToImport)) {
                \Log::info("Blibli processImport: All orders already exist in database, skipping import");
                return redirect()->route('sales.blibli.show-preview-import')
                    ->with('error', 'Semua order pada file ini sudah ada di database. Tidak ada data yang diimport.');
            }

            // Proses import data
            $import = new BlibliImport;

            // Format data untuk import - AMBIL SEMUA DATA DARI PREVIEW TANPA DIKURANGI
            $importData = [];
            foreach ($ordersToImport as $orderNumber => $items) {
                foreach ($items as $item) {
                    $importData[] = [
                        'no_order' => $orderNumber,
                        'tanggal' => $item['tanggal'],
                        'hari' => $item['hari'],
                        'nama_produk' => $item['nama_produk'],
                        'variasi' => $item['variasi'],
                        'qty' => $item['qty'],
                        'harga_setelah_diskon' => $item['harga_setelah_diskon'],
                        'no_resi' => $item['no_resi']
                    ];
                }
            }

            \Log::info("Blibli processImport: Prepared " . count($importData) . " items for import");
            
            // Set data yang akan diproses
            $import->setData($importData);
            
            // Set unmapped products dari session
            $unmappedProducts = session('unmapped_products', []);
            $import->setUnmappedProducts($unmappedProducts);

            // Jalankan proses import
            $result = $import->processImport();
            
            \Log::info("Blibli processImport: Import completed with result: " . json_encode($result));

            // Jika sukses, hapus session dan tampilkan pesan sukses
            if ($result['success'] > 0) {
                session()->forget(['preview_data', 'unmapped_products', 'invalid_data', 'total_rows', 
                                  'valid_rows', 'importable_rows', 'skipped_rows', 'duplicate_orders', 
                                  'duplicate_orders_in_file', 'all_already_in_db', 'can_proceed']);

                // Create success message with detailed information
                $successMessage = "Berhasil mengimport {$result['success']} data penjualan Blibli.";
                
                // Include duplicates skipped during import process
                if (isset($result['duplicates']) && $result['duplicates'] > 0) {
                    $successMessage .= " {$result['duplicates']} nomor pesanan dilewati karena sudah ada di database.";
                }
                
                // Include other skipped items
                if (isset($result['skipped']) && $result['skipped'] > 0) {
                    $successMessage .= " {$result['skipped']} pesanan dilewati karena tidak memiliki data lengkap.";
                }
                
                return redirect()->route('sales.list')
                    ->with('success', $successMessage);
            } else {
                // Jika ada error, simpan pesan error dalam flash session
                $errorMessage = 'Gagal mengimport data: '.implode(', ', $result['errors']);
                
                return redirect()->route('sales.blibli.show-preview-import')
                    ->with('error', $errorMessage);
            }
        } catch (\Exception $e) {
            \Log::error('Error in process import: '.$e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->route('sales.blibli.show-preview-import')
                ->with('error', 'Terjadi kesalahan saat memproses import: '.$e->getMessage());
        }
    }

    /**
     * Show the preview page for GET requests
     */
    public function showPreview()
    {
        // Get data from session
        $data = session('preview_data');
        $unmappedProducts = session('unmapped_products');
        $invalidData = session('invalid_data', []);
        $totalRows = session('total_rows', 0);
        $skippedRows = session('skipped_rows', []);

        \Log::info("Blibli showPreview: Starting with session data");

        // If no data in session, redirect to import page
        if (!$data) {
            return redirect()->route('sales.blibli.import-excel')
                ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
        }

        // ALWAYS check duplicates directly from database
        \Log::info("Blibli showPreview: Checking database directly");
        $orderNumbers = array_unique(array_column($data, 'no_order'));
        $platformId = $this->platform->id;
        
        \Log::info("Blibli showPreview: Checking " . count($orderNumbers) . " orders for platform_id: " . $platformId);
        \Log::info("Blibli showPreview: Order numbers: " . json_encode($orderNumbers));
        
        $duplicateOrders = [];
        foreach ($orderNumbers as $orderNumber) {
            $exists = \App\Models\Order::where('order_number', $orderNumber)
                ->where('platform_id', $platformId)
                ->exists();
            
            // If not found with exact match, try variations
            if (!$exists) {
                $possibleVariations = [
                    '1' . $orderNumber,   // Add 1 digit
                    '12' . $orderNumber,  // Add 2 digits  
                    '121' . $orderNumber, // Add 3 digits
                    substr($orderNumber, 1), // Remove first digit
                    substr($orderNumber, 2), // Remove first 2 digits
                ];
                
                foreach ($possibleVariations as $variation) {
                    $exists = \App\Models\Order::where('order_number', $variation)
                        ->where('platform_id', $platformId)
                        ->exists();
                    if ($exists) {
                        \Log::info("Found duplicate with variation in showPreview: $orderNumber -> $variation");
                        break;
                    }
                }
            }
            
            \Log::info("Blibli showPreview: Order $orderNumber exists: " . ($exists ? 'YES' : 'NO'));
            
            if ($exists) {
                $duplicateOrders[] = $orderNumber;
            }
        }
        
        \Log::info("Blibli showPreview: Found " . count($duplicateOrders) . " duplicates directly from database");
        \Log::info("Blibli showPreview: Duplicate orders: " . json_encode($duplicateOrders));
        
        // Update session
        session(['duplicate_orders' => $duplicateOrders]);
        
        // Recalculate all_already_in_db
        $allAlreadyInDb = count($orderNumbers) > 0 && count($orderNumbers) === count($duplicateOrders);
        session(['all_already_in_db' => $allAlreadyInDb]);
        
        \Log::info("Blibli showPreview: Updated all_already_in_db: " . ($allAlreadyInDb ? 'YES' : 'NO'));

        return view('sales.blibli.preview-import', [
            'data' => $data,
            'unmappedProducts' => $unmappedProducts,
            'invalidData' => $invalidData,
            'canProceed' => empty($unmappedProducts),
            'totalRows' => $totalRows,
            'skippedRows' => $skippedRows,
            'duplicateOrders' => $duplicateOrders,
            'allAlreadyInDb' => $allAlreadyInDb
        ]);
    }
} 