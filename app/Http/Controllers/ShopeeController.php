<?php

namespace App\Http\Controllers;

use App\Imports\ShopeeImport;
use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ShopeeController extends Controller
{
    
    /**
     * Tampilkan halaman import Excel Shopee
     */
    public function importExcel()
    {
        return view('sales.shopee.import-excel');
    }

    /**
     * Preview data Excel sebelum import
     */
    public function previewImport(Request $request)
    {
        \Log::info('---------- MULAI PROSES IMPORT SHOPEE ----------');
        
        // Validasi file yang diupload
        try {
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            ], [
                'excel_file.required' => 'File Excel wajib diupload.',
                'excel_file.file' => 'Upload harus berupa file.',
                'excel_file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV.',
            ]);
            
            if (!$request->hasFile('excel_file')) {
                \Log::error('Excel file not present in request');
                return redirect()->route('sales.shopee.import-excel')
                    ->with('error', 'File Excel tidak ditemukan. Pastikan Anda memilih file sebelum mengklik Preview Data.');
            }
            
            // Log file info for debugging
            $file = $request->file('excel_file');
            \Log::info('File info: ', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize() . ' bytes (' . round($file->getSize() / 1024 / 1024, 2) . ' MB)',
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
                'error' => $file->getError(),
                'valid' => $file->isValid()
            ]);
            
            if (!$file->isValid()) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File melebihi batas upload_max_filesize di php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'File melebihi batas MAX_FILE_SIZE yang ditentukan dalam form HTML',
                    UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                    UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
                    UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                    UPLOAD_ERR_EXTENSION => 'Sebuah ekstensi PHP menghentikan upload file'
                ];
                
                $errorCode = $file->getError();
                $errorMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Error upload dengan kode: ' . $errorCode;
                
                \Log::error('Invalid file upload: ' . $errorMessage);
                return redirect()->route('sales.shopee.import-excel')
                    ->with('error', 'Error file upload: ' . $errorMessage);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation exception: ' . json_encode($e->errors()));
            return redirect()->route('sales.shopee.import-excel')
                ->with('error', $e->errors()['excel_file'][0] ?? 'Error validasi file Excel.');
        } catch (\Exception $e) {
            \Log::error('Validation error in Shopee import: ' . $e->getMessage());
            return redirect()->route('sales.shopee.import-excel')
                ->with('error', 'Error validasi file: ' . $e->getMessage());
        }

        try {
            // Proses file Excel
            $import = new ShopeeImport;
            \Log::info('Before Excel import for Shopee');
            
            try {
                Excel::import($import, $request->file('excel_file'));
                \Log::info('After Excel import for Shopee - import successful');
            } catch (\Exception $e) {
                \Log::error('Excel import exception: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                
                // Provide more specific error messages based on common issues
                $errorMsg = 'Gagal mengimport file Excel: ' . $e->getMessage();
                
                if (strpos($e->getMessage(), 'identification as Excel') !== false) {
                    $errorMsg = 'File tidak dapat diidentifikasi sebagai file Excel yang valid. Pastikan format file benar.';
                } else if (strpos($e->getMessage(), 'Format header') !== false) {
                    $errorMsg = 'Format header tidak sesuai. Pastikan header sesuai dengan format Shopee.';
                }
                
                return redirect()->route('sales.shopee.import-excel')
                    ->with('error', $errorMsg);
            }

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

            // Get orders that contain unmapped products
            $ordersWithUnmappedProducts = $import->getOrdersWithUnmappedProducts();
            \Log::info('Orders with unmapped products: ' . count($ordersWithUnmappedProducts));

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
                    $existingOrder = Order::where('order_number', $orderNumber)
                        ->exists();

                    if ($existingOrder) {
                        $duplicateOrders[] = $orderNumber;
                    }
                }
                \Log::info('Duplicate orders found: ' . count($duplicateOrders));
            }
            
            // Cek ketersediaan stok
            $insufficientStockProducts = [];
            if (!empty($data)) {
                // Kelompokkan data berdasarkan product_id
                $productQuantities = [];
                foreach ($data as $row) {
                    if (isset($row['product_id'])) {
                        if (!isset($productQuantities[$row['product_id']])) {
                            $productQuantities[$row['product_id']] = 0;
                        }
                        $productQuantities[$row['product_id']] += $row['qty'];
                    }
                }
                
                // Cek stok untuk setiap produk
                foreach ($productQuantities as $productId => $requiredQty) {
                    $availableStock = \App\Models\WarehouseStock::where('product_id', $productId)
                        ->sum('qty');
                    
                    if ($availableStock < $requiredQty) {
                        // Ambil informasi produk
                        $product = \App\Models\Product::find($productId);
                        $insufficientStockProducts[] = [
                            'product_name' => $product ? $product->name : "Product ID: {$productId}",
                            'required_qty' => $requiredQty,
                            'available_qty' => $availableStock
                        ];
                    }
                }
                \Log::info('Insufficient stock products: ' . count($insufficientStockProducts));
            }

            // Jika ada masalah dengan header, tampilkan pesan error
            if (!empty($headerIssues)) {
                \Log::warning('Header issues detected: ' . implode(', ', $headerIssues));
                return redirect()->route('sales.shopee.import-excel')
                    ->with('error', 'Format file Excel tidak sesuai: ' . implode(', ', $headerIssues));
            }

            // Jika tidak ada data yang ditemukan, tampilkan error
            if (empty($data)) {
                $errorMessage = 'Tidak ada data yang dapat diproses dari file Excel.';
                if (!empty($skippedRows)) {
                    $errorMessage .= ' Beberapa data dilewati: ' . implode(', ', array_map(function ($value, $key) { 
                        return "$key: $value"; 
                    }, $skippedRows, array_keys($skippedRows)));
                }
                if (!empty($totalRows) && $totalRows > 0) {
                    $errorMessage .= " Total {$totalRows} baris ditemukan dalam file, tetapi tidak ada yang dapat diproses.";
                }
                $errorMessage .= ' Pastikan data memiliki format yang benar dan semua kolom wajib telah diisi.';
                \Log::warning('No data found in import: ' . $errorMessage);
                return redirect()->route('sales.shopee.import-excel')
                    ->with('error', $errorMessage);
            }

            // Simpan data ke session untuk digunakan di proses import
            session(['preview_data' => $data]);
            session(['unmapped_products' => $unmappedProducts]);
            session(['insufficient_stock_products' => $insufficientStockProducts]);
            session(['total_rows' => $totalRows]);
            session(['skipped_rows' => $skippedRows]);
            session(['duplicate_orders' => $duplicateOrders]);
            session(['invalid_data' => $invalidData]);
            session(['orders_with_unmapped_products' => $ordersWithUnmappedProducts]);
            \Log::info('Data saved to session, redirecting to preview');

            // Tampilkan preview dengan semua data yang diperlukan
            return view('sales.shopee.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'invalidData' => $invalidData,
                'canProceed' => empty($invalidData) && empty($insufficientStockProducts) && empty($unmappedProducts),
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
                'insufficientStockProducts' => $insufficientStockProducts,
                'ordersWithUnmappedProducts' => $ordersWithUnmappedProducts
            ])->with('warning', !empty($unmappedProducts) ? 'Beberapa produk perlu dimapping terlebih dahulu. Pesanan dengan produk tersebut akan dilewati.' : 
                    (!empty($insufficientStockProducts) ? 'Beberapa produk memiliki stok tidak mencukupi.' : null));
        } catch (\Exception $e) {
            \Log::error('Exception in previewImport: ' . $e->getMessage());
            \Log::error('Exception type: ' . get_class($e));
            \Log::error('Line: ' . $e->getLine() . ' in ' . $e->getFile());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('sales.shopee.import-excel')
                ->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Proses final import data
     */
    public function processImport(Request $request)
    {

        // Ambil data preview dari session
        $data = session('preview_data');
        $unmappedProducts = session('unmapped_products');
        $insufficientStockProducts = session('insufficient_stock_products', []);

        // Jika tidak ada data, kembalikan ke halaman import
        if (! $data) {
            return redirect()->route('sales.shopee.import-excel')
                ->with('error', 'Data preview tidak ditemukan. Silakan upload ulang file Excel.');
        }

        // Jika masih ada produk dengan stok tidak mencukupi, tampilkan error
        if (!empty($insufficientStockProducts)) {
            // Dapatkan info baris yang di-skip dan duplikat dari import
            $import = new ShopeeImport;
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

            // Simpan pesan warning dalam flash session
            session()->flash('warning', 'Beberapa produk memiliki stok tidak mencukupi. Harap isi stok terlebih dahulu.');

            return view('sales.shopee.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'canProceed' => false,
                'invalidData' => [],
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
                'insufficientStockProducts' => $insufficientStockProducts
            ]);
        }

        try {
            // Register our modified sales import handler
            \App\Models\WarehouseStock::$consolidateOrderItemsByProduct = true;
            \Log::info('Enabling order item consolidation for tax_id differences');
            
            // Proses import data
            $import = new ShopeeImport;

            // Set data yang akan diproses
            $import->setData($data);
            
            // Set unmapped products dari session
            $unmappedProducts = session('unmapped_products', []);
            $import->setUnmappedProducts($unmappedProducts);

            // Jalankan proses import
            $result = $import->processImport();

            // Reset the flag after import
            \App\Models\WarehouseStock::$consolidateOrderItemsByProduct = false;

            // Jika sukses, hapus session dan tampilkan pesan sukses
            if ($result['success'] > 0) {
                session()->forget(['preview_data', 'unmapped_products']);

                // Tambahkan informasi tambahan tentang nomor pesanan duplikat
                $successMessage = "Berhasil mengimport {$result['success']} data penjualan Shopee.";

                if (isset($result['duplicates']) && $result['duplicates'] > 0) {
                    $successMessage .= " {$result['duplicates']} nomor pesanan dilewati karena sudah ada di database.";
                }

                if (isset($result['skipped']) && $result['skipped'] > 0) {
                    $successMessage .= " {$result['skipped']} pesanan dilewati karena tidak memiliki data lengkap.";
                }
                
                if (isset($result['unmapped_skipped']) && $result['unmapped_skipped'] > 0) {
                    $successMessage .= " {$result['unmapped_skipped']} pesanan dilewati karena memiliki produk yang belum dimapping.";
                }

                return redirect()->route('sales.list')
                    ->with('success', $successMessage);
            } else {
                // Jika ada error, simpan pesan error dalam flash session
                $errorMessage = 'Gagal mengimport data: '.implode(', ', $result['errors']);
                session()->flash('error', $errorMessage);

                // Debug: tambahkan log untuk melihat pesan error
                \Log::info('Error message set in session: '.$errorMessage);

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

                return view('sales.shopee.preview-import', [
                    'data' => $data,
                    'unmappedProducts' => $unmappedProducts,
                    'canProceed' => empty($unmappedProducts),
                    'invalidData' => [],
                    'totalRows' => $totalRows,
                    'skippedRows' => $skippedRows,
                    'duplicateOrders' => $duplicateOrders,
                ]);
            }
        } catch (\Exception $e) {
            // Reset the flag in case of error
            \App\Models\WarehouseStock::$consolidateOrderItemsByProduct = false;
            
            \Log::error('Exception in processImport: '.$e->getMessage());
            \Log::error($e->getTraceAsString());

            // Simpan pesan error dalam flash session
            $errorMessage = 'Terjadi kesalahan saat menyimpan data: '.$e->getMessage();
            session()->flash('error', $errorMessage);

            // Debug: tambahkan log untuk melihat pesan error
            \Log::info('Exception message set in session: '.$errorMessage);

            // Dapatkan info baris yang di-skip dan duplikat
            $import = new ShopeeImport;
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

            return view('sales.shopee.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'canProceed' => empty($unmappedProducts),
                'invalidData' => [],
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
            ]);
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
        $duplicateOrders = session('duplicate_orders', []);
        $insufficientStockProducts = session('insufficient_stock_products', []);
        $ordersWithUnmappedProducts = session('orders_with_unmapped_products', []);

        // If no data in session, redirect to import page
        if (!$data) {
            return redirect()->route('sales.shopee.import-excel')
                ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
        }

        return view('sales.shopee.preview-import', [
            'data' => $data,
            'unmappedProducts' => $unmappedProducts,
            'invalidData' => $invalidData,
            'canProceed' => empty($invalidData) && empty($insufficientStockProducts) && empty($unmappedProducts),
            'totalRows' => $totalRows,
            'skippedRows' => $skippedRows,
            'duplicateOrders' => $duplicateOrders,
            'insufficientStockProducts' => $insufficientStockProducts,
            'ordersWithUnmappedProducts' => $ordersWithUnmappedProducts
        ]);
    }
}