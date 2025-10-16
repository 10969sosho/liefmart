<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\MappingBarang;
use App\Models\Product;
use App\Imports\LazadaImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LazadaController extends Controller
{
    /**
     * Tampilkan halaman platform Lazada
     */
    public function platform()
    {
        return view('sales.lazada.platform');
    }

    /**
     * Tampilkan halaman import Excel
     */
    public function import()
    {
        return view('sales.lazada.import');
    }

    /**
     * Preview import Excel
     */
    public function previewImport(Request $request)
    {
        \Log::info('---------- MULAI PROSES IMPORT LAZADA ----------');
        
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
                return redirect()->route('sales.lazada.import')
                    ->with('error', 'File Excel tidak ditemukan. Pastikan Anda memilih file sebelum mengklik Preview Data.');
            }
            
            $file = $request->file('excel_file');
            \Log::info('File info: ', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize() . ' bytes (' . round($file->getSize() / 1024 / 1024, 2) . ' MB)',
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ]);
            
            if (!$file->isValid()) {
                return redirect()->route('sales.lazada.import')
                    ->with('error', 'File tidak valid. Pastikan file Excel tidak rusak.');
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation exception: ' . json_encode($e->errors()));
            return redirect()->route('sales.lazada.import')
                ->with('error', $e->errors()['excel_file'][0] ?? 'Error validasi file Excel.');
        } catch (\Exception $e) {
            \Log::error('Validation error in Lazada import: ' . $e->getMessage());
            return redirect()->route('sales.lazada.import')
                ->with('error', 'Error validasi file: ' . $e->getMessage());
        }

        try {
            // Proses file Excel
            $import = new LazadaImport;
            \Log::info('Before Excel import for Lazada');
            
            try {
                Excel::import($import, $request->file('excel_file'));
                \Log::info('After Excel import for Lazada - import successful');
            } catch (\Exception $e) {
                \Log::error('Excel import exception: ' . $e->getMessage());
                
                $errorMsg = 'Gagal mengimport file Excel: ' . $e->getMessage();
                
                if (strpos($e->getMessage(), 'identification as Excel') !== false) {
                    $errorMsg = 'File tidak dapat diidentifikasi sebagai file Excel yang valid. Pastikan format file benar.';
                } else if (strpos($e->getMessage(), 'Format header') !== false) {
                    $errorMsg = 'Format header tidak sesuai. Pastikan header sesuai dengan format Lazada.';
                }
                
                return redirect()->route('sales.lazada.import')
                    ->with('error', $errorMsg);
            }

            // Dapatkan data untuk preview
            $data = $import->getData();
            $unmappedProducts = $import->getUnmappedProducts();
            $invalidData = $import->getInvalidData();
            $headerIssues = $import->getHeaderIssues();
            $stats = $import->getStats();
            
            \Log::info('Lazada import stats:', $stats);
            
            // Simpan data ke session untuk proses import
            session([
                'lazada_import_data' => $data,
                'lazada_import_stats' => $stats,
                'lazada_unmapped_products' => $unmappedProducts,
                'lazada_invalid_data' => $invalidData,
                'lazada_header_issues' => $headerIssues,
            ]);
            
            return view('sales.lazada.preview-import', compact('data', 'stats', 'unmappedProducts', 'invalidData', 'headerIssues'));
            
        } catch (\Exception $e) {
            \Log::error('Error in Lazada preview import: ' . $e->getMessage());
            return redirect()->route('sales.lazada.import')
                ->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman input manual
     */
    public function manual()
    {
        return view('sales.lazada.manual');
    }

    /**
     * Tampilkan daftar data penjualan Lazada
     */
    public function index()
    {
        $platform = Platform::where('name', 'lazada')->first();
        
        if (!$platform) {
            return redirect()->back()->with('error', 'Platform Lazada tidak ditemukan.');
        }

        // Ambil data penjualan Lazada
        $orders = DB::table('orders')
            ->where('platform_id', $platform->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('sales.lazada.index', compact('orders'));
    }

    /**
     * Tampilkan halaman input platform product
     */
    public function platformProduct()
    {
        $platform = Platform::where('name', 'lazada')->first();
        
        if (!$platform) {
            return redirect()->back()->with('error', 'Platform Lazada tidak ditemukan.');
        }

        $platformProducts = PlatformProduct::where('platform_id', $platform->id)
            ->orderBy('platform_product_name')
            ->paginate(20);

        return view('sales.lazada.platform-product', compact('platformProducts', 'platform'));
    }

    /**
     * Simpan platform product baru
     */
    public function storePlatformProduct(Request $request)
    {
        $request->validate([
            'platform_product_name' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
        ]);

        $platform = Platform::where('name', 'lazada')->first();
        
        if (!$platform) {
            return redirect()->back()->with('error', 'Platform Lazada tidak ditemukan.');
        }

        PlatformProduct::create([
            'platform_id' => $platform->id,
            'platform_product_name' => $request->platform_product_name,
            'variant' => $request->variant,
        ]);

        return redirect()->back()->with('success', 'Platform product berhasil ditambahkan.');
    }

    /**
     * Tampilkan halaman mapping barang
     */
    public function mapping()
    {
        $platform = Platform::where('name', 'lazada')->first();
        
        if (!$platform) {
            return redirect()->back()->with('error', 'Platform Lazada tidak ditemukan.');
        }

        $platformProducts = PlatformProduct::where('platform_id', $platform->id)
            ->whereDoesntHave('mappingBarang')
            ->orderBy('platform_product_name')
            ->get();

        $products = Product::orderBy('nama_produk')->get();

        return view('sales.lazada.mapping', compact('platformProducts', 'products', 'platform'));
    }

    /**
     * Simpan mapping barang
     */
    public function storeMapping(Request $request)
    {
        $request->validate([
            'platform_product_id' => 'required|exists:platform_products,id',
            'product_id' => 'required|exists:products,id',
        ]);

        // Cek apakah mapping sudah ada
        $existingMapping = MappingBarang::where('platform_product_id', $request->platform_product_id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingMapping) {
            return redirect()->back()->with('error', 'Mapping sudah ada.');
        }

        MappingBarang::create([
            'platform_product_id' => $request->platform_product_id,
            'product_id' => $request->product_id,
        ]);

        return redirect()->back()->with('success', 'Mapping berhasil disimpan.');
    }

    /**
     * Hapus mapping barang
     */
    public function deleteMapping($id)
    {
        $mapping = MappingBarang::findOrFail($id);
        $mapping->delete();

        return redirect()->back()->with('success', 'Mapping berhasil dihapus.');
    }

    /**
     * Proses import Excel
     */
    public function processImport(Request $request)
    {
        try {
            // Ambil data dari session
            $data = session('lazada_import_data', []);
            $stats = session('lazada_import_stats', []);
            
            if (empty($data)) {
                return redirect()->route('sales.lazada.import')
                    ->with('error', 'Tidak ada data untuk diimport. Silakan upload file Excel terlebih dahulu.');
            }

            // Simpan data ke database
            $import = new LazadaImport();
            $import->data = $data;
            $result = $import->saveToDatabase();
            
            // Clear session data
            session()->forget([
                'lazada_import_data',
                'lazada_import_stats',
                'lazada_unmapped_products',
                'lazada_invalid_data',
                'lazada_header_issues'
            ]);
            
            if ($result['success']) {
                $message = $result['message'];
                $unmappedProducts = session('lazada_unmapped_products', []);
                if (!empty($unmappedProducts)) {
                    $message .= ' Beberapa produk belum di-mapping dan akan ditampilkan di halaman mapping.';
                }
                return redirect()->route('sales.lazada.index')->with('success', $message);
            } else {
                return redirect()->route('sales.lazada.import')->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            \Log::error('Lazada import error: ' . $e->getMessage());
            return redirect()->route('sales.lazada.import')
                ->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Simpan input manual
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'hari' => 'required|string',
            'status_hari' => 'required|string',
            'no_order' => 'required|string',
            'qty' => 'required|integer|min:1',
            'harga_setelah_diskon' => 'required|numeric|min:0',
            'produk' => 'required|string',
            'varian' => 'nullable|string',
        ]);

        try {
            // TODO: Implementasi penyimpanan data manual untuk Lazada
            // Untuk sementara, redirect dengan pesan sukses
            return redirect()->route('sales.lazada.index')->with('success', 'Data berhasil disimpan. Fitur input manual akan segera tersedia.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
        }
    }
}
