<?php

namespace App\Http\Controllers;

use App\Imports\TiktokImport;
use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TiktokController extends Controller
{
    /**
     * Tampilkan halaman import Excel Tiktok
     */
/**
 * Tampilkan halaman import Excel Tiktok
 */
public function importExcel()
{
    // Tambahkan informasi untuk ditampilkan di halaman import
    $importGuidelines = [
        'Pastikan kolom berikut ada di file Excel Anda:' => [
            'NOMOR PESANAN (wajib)',
            'NAMA PRODUK (wajib)',
            'JUMLAH/QTY (wajib)',
            'HARGA SETELAH DISKON (wajib)',
            'TANGGAL PESANAN',
            'NOMOR RESI',
            'HARI',
        ],
        'Catatan Penting:' => [
            'Format header bisa fleksibel (tidak harus sama persis)',
            'Header bisa berada di baris mana saja dalam file Excel',
            'Kolom HARI boleh kosong dan tetap bisa diimpor',
            'Semua produk harus sudah terdaftar dan dimapping di sistem'
        ]
    ];

    return view('sales.tiktok.import-excel', [
        'importGuidelines' => $importGuidelines
    ]);
}

    /**
     * Preview data Excel sebelum import
     */
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
            $import = new TiktokImport();
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
            \Log::info('TikTok PreviewImport - Main Category set to: ' . ($mainCategoryId ?: 'NULL'));
            
            // Cek ketersediaan stok dengan detail yang lebih lengkap
            $insufficientStockProducts = [];
            $ordersWithStockIssues = [];
            $stockIssuesSummary = [];
            
            if (!empty($data)) {
                // Kelompokkan data berdasarkan order dan produk
                $orderProductQuantities = [];
                foreach ($data as $row) {
                    $orderNumber = $row['no_order'];
                    if (!isset($orderProductQuantities[$orderNumber])) {
                        $orderProductQuantities[$orderNumber] = [];
                    }
                    
                    // Cari platform product dan mapping
                    $platform = \App\Models\Platform::where('name', 'tiktok')->first();
                    $platformProduct = \App\Models\PlatformProduct::where('platform_id', $platform->id)
                        ->where('platform_product_name', $row['nama_barang'])
                        ->where('variant', $row['variasi'] ?? null)
                        ->first();
                    
                    if ($platformProduct) {
                        $mappings = \App\Models\MappingBarang::where('platform_product_id', $platformProduct->id)
                            ->where('is_active', true)
                            ->get();
                        foreach ($mappings as $mapping) {
                            $requiredQty = $row['qty'] * $mapping->quantity;
                            if (!isset($orderProductQuantities[$orderNumber][$mapping->product_id])) {
                                $orderProductQuantities[$orderNumber][$mapping->product_id] = 0;
                            }
                            $orderProductQuantities[$orderNumber][$mapping->product_id] += $requiredQty;
                        }
                    }
                }
                
                // Cek stok untuk setiap order dan produk
                foreach ($orderProductQuantities as $orderNumber => $products) {
                    $orderStockIssues = [];
                    foreach ($products as $productId => $requiredQty) {
                        $availableStock = \App\Models\WarehouseStock::where('product_id', $productId)
                            ->where('qty', '>', 0)
                            ->sum('qty');
                        
                        if ($availableStock < $requiredQty) {
                            $product = \App\Models\Product::find($productId);
                            $productName = $product ? $product->name : "Product ID: {$productId}";
                            
                            $orderStockIssues[] = [
                                'product_name' => $productName,
                                'required_qty' => $requiredQty,
                                'available_qty' => $availableStock,
                                'shortage' => $requiredQty - $availableStock
                            ];
                            
                            // Tambahkan ke summary
                            if (!isset($stockIssuesSummary[$productId])) {
                                $stockIssuesSummary[$productId] = [
                                    'product_name' => $productName,
                                    'total_required' => 0,
                                    'available_qty' => $availableStock,
                                    'affected_orders' => []
                                ];
                            }
                            $stockIssuesSummary[$productId]['total_required'] += $requiredQty;
                            $stockIssuesSummary[$productId]['affected_orders'][] = $orderNumber;
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

            // Jika ada masalah dengan header, tampilkan pesan error tanpa redirect
            if (!empty($headerIssues)) {
                \Log::warning('Header issues detected: ' . implode(', ', $headerIssues));
                return redirect()->route('sales.tiktok.import-excel')->with('error', 'Format file Excel tidak sesuai: ' . implode(', ', $headerIssues));
            }

            // Jika tidak ada data yang ditemukan, tampilkan error tanpa redirect
            if (empty($data)) {
                $errorMessage = 'Tidak ada data yang dapat diproses dari file Excel.';
                if (!empty($skippedRows)) {
                    $errorMessage .= ' Beberapa data dilewati: ' . implode(', ', array_map(function ($value, $key) { return "$key: $value"; }, $skippedRows, array_keys($skippedRows)));
                }
                if (!empty($totalRows) && $totalRows > 0) {
                    $errorMessage .= " Total {$totalRows} baris ditemukan dalam file, tetapi tidak ada yang dapat diproses.";
                }
                $errorMessage .= ' Pastikan data memiliki format yang benar dan semua kolom wajib telah diisi.';
                \Log::warning('No data found in import: ' . $errorMessage);
                return redirect()->route('sales.tiktok.import-excel')->with('error', $errorMessage);
            }

            // Simpan data ke session untuk digunakan di proses import
            session(['preview_data' => $data]);
            session(['unmapped_products' => $unmappedProducts]);
            session(['insufficient_stock_products' => $insufficientStockProducts]);
            session(['orders_with_stock_issues' => $ordersWithStockIssues]);
            \Log::info('Data saved to session, redirecting to preview');

            // Tentukan apakah bisa melanjutkan import
            // Tetap bisa preview meskipun ada masalah stok, tapi tidak bisa import
            $canProceed = empty($invalidData);
            
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

            // Jika ada produk yang belum dimapping, tetap tampilkan preview
            return view('sales.tiktok.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'invalidData' => $invalidData,
                'canProceed' => $canProceed,
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
                'insufficientStockProducts' => $insufficientStockProducts,
                'ordersWithStockIssues' => $ordersWithStockIssues,
                'stockIssuesSummary' => $stockIssuesSummary
            ])->with('warning', $warningMessage);
        } catch (\Exception $e) {
            \Log::error('Exception in previewImport: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->route('sales.tiktok.import-excel')->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
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
        $insufficientStockProducts = session('insufficient_stock_products');

        // Jika tidak ada data, kembalikan ke halaman import
        if (!$data) {
            return redirect()->route('sales.tiktok.import-excel')->with('error', 'Data preview tidak ditemukan. Silakan upload ulang file Excel.');
        }

        // Jika masih ada produk yang belum di-mapping, tampilkan error
        if (!empty($unmappedProducts)) {
            // Dapatkan info baris yang di-skip dan duplikat dari import
            $import = new TiktokImport();
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
            session()->flash('warning', 'Masih ada produk yang belum di-mapping. Harap selesaikan mapping produk terlebih dahulu.');

            return view('sales.tiktok.preview-import', [
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
        
        // Jika masih ada produk dengan stok tidak mencukupi, tampilkan warning tapi tetap bisa preview
        if (!empty($insufficientStockProducts)) {
            // Dapatkan info baris yang di-skip dan duplikat dari import
            $import = new TiktokImport();
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

            // Ambil data stock issues dari session
            $ordersWithStockIssues = session('orders_with_stock_issues', []);
            $stockIssuesSummary = session('stock_issues_summary', []);

            // Buat pesan warning yang detail
            $warningDetails = [];
            foreach ($insufficientStockProducts as $product) {
                $warningDetails[] = "• {$product['product_name']}: Butuh {$product['total_required']} unit, tersedia {$product['available_qty']} unit (kurang " . ($product['total_required'] - $product['available_qty']) . " unit)";
            }
            
            $warningMessage = '⚠️ PERHATIAN: Beberapa produk memiliki stok tidak mencukupi. Pesanan dengan produk tersebut akan dilewati saat import.' . "\n\n" . implode("\n", $warningDetails);

            // Simpan pesan warning dalam flash session
            session()->flash('warning', $warningMessage);

            return view('sales.tiktok.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'canProceed' => false, // Tidak bisa import jika ada masalah stok
                'invalidData' => [],
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
                'insufficientStockProducts' => $insufficientStockProducts,
                'ordersWithStockIssues' => $ordersWithStockIssues,
                'stockIssuesSummary' => $stockIssuesSummary
            ]);
        }

        try {
            // Proses import data
            $import = new TiktokImport();

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
                $successMessage = "Berhasil mengimport {$result['success']} data penjualan Tiktok.";

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

                return view('sales.tiktok.preview-import', [
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
        } catch (\Exception $e) {
            \Log::error('Exception in processImport: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            // Simpan pesan error dalam flash session
            $errorMessage = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
            session()->flash('error', $errorMessage);

            // Debug: tambahkan log untuk melihat pesan error
            \Log::info('Exception message set in session: ' . $errorMessage);

            // Dapatkan info baris yang di-skip dan duplikat
            $import = new TiktokImport();
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

            return view('sales.tiktok.preview-import', [
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

        // If no data in session, redirect to import page
        if (!$data) {
            return redirect()->route('sales.tiktok.import-excel')
                ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
        }

        return view('sales.tiktok.preview-import', [
            'data' => $data,
            'unmappedProducts' => $unmappedProducts,
            'invalidData' => $invalidData,
            'canProceed' => empty($invalidData) && empty($insufficientStockProducts),
            'totalRows' => $totalRows,
            'skippedRows' => $skippedRows,
            'duplicateOrders' => $duplicateOrders,
            'insufficientStockProducts' => $insufficientStockProducts
        ]);
    }
}
