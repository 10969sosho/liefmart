<?php

namespace App\Http\Controllers;

use App\Imports\TokopediaImport;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TokopediaController extends Controller
{
    protected $platform;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $routeParam = 'tokopedia';
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
     * Tampilkan halaman import Excel Tokopedia
     */
    public function importExcel()
    {
        return view('sales.tokopedia.import-excel');
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
            // Proses file Excel
            $import = new TokopediaImport($this->platform->id);
            Excel::import($import, $request->file('excel_file'));

            // Dapatkan data untuk preview
            $data = $import->getData();
            $unmappedProducts = $import->getUnmappedProducts();
            $invalidData = $import->getInvalidData();
            $headerIssues = $import->getHeaderIssues();
            $totalRows = $import->getTotalRows();
            
            // Ensure unmapped products are stored in a decoded format
            $unmappedProducts = array_map('urldecode', $unmappedProducts);

            // Informasi tentang baris yang dilewati
            $skippedRows = [
                'Tanggal kosong' => $import->getSkippedForMissingDate(),
                'Hari kosong' => $import->getSkippedForMissingDay(),
                'No Resi kosong' => $import->getSkippedForMissingResi(),
            ];

            // Filter skippedRows yang nilainya 0
            $skippedRows = array_filter($skippedRows);

            // Cek order yang sudah ada di database
            $duplicateOrders = [];
            $orderNumbers = array_unique(array_column($data, 'no_order'));

            foreach ($orderNumbers as $orderNumber) {
                $existingOrder = Order::where('order_number', $orderNumber)
                    ->exists();

                if ($existingOrder) {
                    $duplicateOrders[] = $orderNumber;
                }
            }

            // Jika ada masalah dengan header, tampilkan pesan error tanpa redirect
            if (! empty($headerIssues)) {
                return redirect()->route('sales.tokopedia.import-excel')->with('error', 'Format file Excel tidak sesuai: '.implode(', ', $headerIssues));
            }

            // Jika tidak ada data yang ditemukan, tampilkan error tanpa redirect
            if (empty($data)) {
                return redirect()->route('sales.tokopedia.import-excel')->with('error', 'Tidak ada data yang dapat diproses dari file Excel. Pastikan semua pesanan memiliki tanggal yang valid.');
            }

            // Simpan data ke session untuk digunakan di proses import
            session(['preview_data' => $data]);
            session(['unmapped_products' => $unmappedProducts]);
            session(['invalid_data' => $invalidData]);
            session(['total_rows' => $totalRows]);
            session(['skipped_rows' => $skippedRows]);

            // Jika ada produk yang belum dimapping, tetap tampilkan preview
            return view('sales.tokopedia.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'invalidData' => $invalidData,
                'canProceed' => empty($unmappedProducts) && empty($invalidData),
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
            ])->with('warning', ! empty($unmappedProducts) ? 'Beberapa produk perlu dimapping terlebih dahulu.' : null);

        } catch (\Exception $e) {
            return redirect()->route('sales.tokopedia.import-excel')->with('error', 'Terjadi kesalahan saat memproses file: '.$e->getMessage());
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

        // Jika tidak ada data, kembalikan ke halaman import
        if (! $data) {
            return redirect()->route('sales.tokopedia.import-excel')
                ->with('error', 'Data preview tidak ditemukan. Silakan upload ulang file Excel.');
        }

        // Jika masih ada produk yang belum di-mapping, tampilkan error
        if (! empty($unmappedProducts)) {
            // Dapatkan info baris yang di-skip dan duplikat dari import
            $import = new TokopediaImport($this->platform->id);
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

            return view('sales.tokopedia.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'canProceed' => false,
                'invalidData' => [],
                'totalRows' => $totalRows,
                'skippedRows' => $skippedRows,
                'duplicateOrders' => $duplicateOrders,
            ]);
        }

        try {
            // Proses import data
            $import = new TokopediaImport($this->platform->id);

            // Set data yang akan diproses
            $import->setData($data);
            
            // Set unmapped products dari session
            $unmappedProducts = session('unmapped_products', []);
            $import->setUnmappedProducts($unmappedProducts);

            // Jalankan proses import
            $result = $import->processImport();

            // Jika sukses, hapus session dan tampilkan pesan sukses
            if ($result['success'] > 0) {
                session()->forget(['preview_data', 'unmapped_products']);

                // Tambahkan informasi tambahan tentang nomor pesanan duplikat
                $successMessage = "Berhasil mengimport {$result['success']} data penjualan Tokopedia.";

                if (isset($result['duplicates']) && $result['duplicates'] > 0) {
                    $successMessage .= " {$result['duplicates']} nomor pesanan dilewati karena sudah ada di database.";
                }

                if (isset($result['skipped']) && $result['skipped'] > 0) {
                    $successMessage .= " {$result['skipped']} pesanan dilewati karena tidak memiliki data lengkap.";
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

                return view('sales.tokopedia.preview-import', [
                    'data' => $data,
                    'unmappedProducts' => $unmappedProducts,
                    'canProceed' => true,
                    'invalidData' => [],
                    'totalRows' => $totalRows,
                    'skippedRows' => $skippedRows,
                    'duplicateOrders' => $duplicateOrders,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Exception in processImport: '.$e->getMessage());
            \Log::error($e->getTraceAsString());

            // Simpan pesan error dalam flash session
            $errorMessage = 'Terjadi kesalahan saat menyimpan data: '.$e->getMessage();
            session()->flash('error', $errorMessage);

            // Debug: tambahkan log untuk melihat pesan error
            \Log::info('Exception message set in session: '.$errorMessage);

            // Dapatkan info baris yang di-skip dan duplikat
            $import = new TokopediaImport($this->platform->id);
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

            return view('sales.tokopedia.preview-import', [
                'data' => $data,
                'unmappedProducts' => $unmappedProducts,
                'canProceed' => true,
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

        // If no data in session, redirect to import page
        if (!$data) {
            return redirect()->route('sales.tokopedia.import-excel')
                ->with('error', 'Tidak ada data preview. Silakan upload file terlebih dahulu.');
        }

        return view('sales.tokopedia.preview-import', [
            'data' => $data,
            'unmappedProducts' => $unmappedProducts,
            'invalidData' => $invalidData,
            'canProceed' => empty($unmappedProducts) && empty($invalidData),
            'totalRows' => $totalRows,
            'skippedRows' => $skippedRows,
            'duplicateOrders' => $duplicateOrders,
        ]);
    }

    public function import(Request $request)
    {
        try {
            $file = $request->file('file');
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ]);
            }

            $import = new TokopediaImport($this->platform->id);
            Excel::import($import, $file);

            // Check for header issues first
            $headerIssues = $import->getHeaderIssues();
            if (!empty($headerIssues)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kolom TANGGAL tidak ditemukan dalam file Excel'
                ]);
            }

            // Process the import
            $results = $import->processImport();

            if (!empty($results['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor data: ' . implode(', ', $results['errors'])
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diimpor',
                'data' => [
                    'total' => $import->getTotalRows(),
                    'success' => $results['success'],
                    'skipped' => $results['skipped'],
                    'duplicates' => $results['duplicates']
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in Tokopedia import: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}
