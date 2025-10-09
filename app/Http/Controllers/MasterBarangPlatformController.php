<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\PlatformProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterBarangPlatformController extends Controller
{
    /**
     * Tampilkan daftar master barang platform
     */
    public function index(Request $request)
    {
        $platform = $request->input('platform');
        $search = $request->input('search');
        $variant = $request->input('variant');

        // Query platform products with proper relations
        $query = PlatformProduct::with([
            'platform:id,name',
            'mappingBarang:id,platform_product_id,product_id,quantity',
            'mappingBarang.product:id,name'
        ]);

        // Filter berdasarkan platform
        if ($platform) {
            $query->whereHas('platform', function ($q) use ($platform) {
                $q->where('name', $platform);
            });
        }

        // Filter berdasarkan search
        if ($search) {
            $query->where('platform_product_name', 'like', '%' . $search . '%');
        }

        // Filter berdasarkan variant
        if ($variant) {
            $query->where('variant', 'like', '%' . $variant . '%');
        }

        $perPage = $request->input('per_page', 20);
        $platformProducts = $query->paginate($perPage);
        $platforms = Platform::all();

        return view('master.barang-platform.index', compact('platformProducts', 'platforms'));
    }

    /**
     * Tampilkan form create
     */
    public function create()
    {
        $platforms = Platform::all();
        return view('master.barang-platform.create', compact('platforms'));
    }

    /**
     * Simpan master barang platform baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'platform_product_name' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
        ]);

        // Cek apakah sudah ada kombinasi platform + nama + variant
        $existing = PlatformProduct::where('platform_id', $request->platform_id)
            ->where('platform_product_name', $request->platform_product_name)
            ->where('variant', $request->variant ?? '')
            ->first();

        if ($existing) {
            return back()->with('error', 'Barang platform dengan nama dan variant ini sudah ada.')
                ->withInput();
        }

        PlatformProduct::create([
            'platform_id' => $request->platform_id,
            'platform_product_name' => $request->platform_product_name,
            'variant' => $request->variant,
        ]);

        return redirect()->route('barang-platform.index')
            ->with('success', 'Master barang platform berhasil dibuat.');
    }

    /**
     * Tampilkan form edit
     */
    public function edit($id)
    {
        $platformProduct = PlatformProduct::with('platform')->findOrFail($id);
        $platforms = Platform::all();
        
        return view('master.barang-platform.edit', compact('platformProduct', 'platforms'));
    }

    /**
     * Update master barang platform
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'platform_product_name' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
        ]);

        $platformProduct = PlatformProduct::findOrFail($id);

        // Cek apakah kombinasi platform + nama + variant sudah ada (kecuali untuk record yang sama)
        $existing = PlatformProduct::where('platform_id', $platformProduct->platform_id)
            ->where('platform_product_name', $request->platform_product_name)
            ->where('variant', $request->variant ?? '')
            ->where('id', '!=', $id)
            ->first();

        if ($existing) {
            return back()->with('error', 'Barang platform dengan nama dan variant ini sudah ada.')
                ->withInput();
        }

        // Cek apakah ada perubahan nama
        if ($platformProduct->platform_product_name !== $request->platform_product_name) {
            // Update nama di PlatformProduct
            $platformProduct->update([
                'platform_product_name' => $request->platform_product_name,
                'variant' => $request->variant,
            ]);

            return redirect()->route('barang-platform.index')
                ->with('success', 'Nama barang platform berhasil diupdate. Semua analytics dan laporan akan mengikuti nama baru.');
        }

        // Jika hanya variant yang berubah
        $platformProduct->update([
            'variant' => $request->variant,
        ]);

        return redirect()->route('barang-platform.index')
            ->with('success', 'Variant barang platform berhasil diupdate.');
    }

    /**
     * Hapus master barang platform
     */
    public function destroy($id)
    {
        $platformProduct = PlatformProduct::findOrFail($id);

        // Cek apakah sudah digunakan di mapping
        if ($platformProduct->mappingBarang()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus barang platform yang sudah digunakan di mapping.');
        }

        // Cek apakah sudah digunakan di order items
        if (DB::table('order_items')->where('platform_product_id', $id)->exists()) {
            return back()->with('error', 'Tidak dapat menghapus barang platform yang sudah digunakan di transaksi penjualan.');
        }

        $platformProduct->delete();

        return redirect()->route('barang-platform.index')
            ->with('success', 'Master barang platform berhasil dihapus.');
    }

    /**
     * API untuk mendapatkan daftar barang platform berdasarkan platform
     */
    public function getByPlatform($platformId)
    {
        $platformProducts = PlatformProduct::where('platform_id', $platformId)
            ->select('id', 'platform_product_name', 'variant')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->platform_product_name . ($item->variant ? ' - ' . $item->variant : ''),
                    'platform_product_name' => $item->platform_product_name,
                    'variant' => $item->variant,
                ];
            });

        return response()->json($platformProducts);
    }

    /**
     * API untuk create barang platform baru dari mapping
     */
    public function createFromMapping(Request $request)
    {
        $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'platform_product_name' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
        ]);

        // Cek apakah sudah ada
        $existing = PlatformProduct::where('platform_id', $request->platform_id)
            ->where('platform_product_name', $request->platform_product_name)
            ->where('variant', $request->variant ?? '')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'platform_product' => $existing,
                'message' => 'Barang platform sudah ada'
            ]);
        }

        $platformProduct = PlatformProduct::create([
            'platform_id' => $request->platform_id,
            'platform_product_name' => $request->platform_product_name,
            'variant' => $request->variant,
        ]);

        return response()->json([
            'success' => true,
            'platform_product' => $platformProduct,
            'message' => 'Barang platform berhasil dibuat'
        ]);
    }
}
