<?php

namespace App\Http\Controllers;

use App\Models\MappingBarang;
use App\Models\Platform;
use App\Models\PlatformProduct;
use App\Models\Product;
use App\Models\MappingBarangHistory;
use Illuminate\Http\Request;

class MappingBarangController extends Controller
{
    /**
     * Tampilkan daftar mapping produk
     */
    public function index(Request $request)
    {
        // Ambil platform yang dipilih (opsional)
        $platform = $request->input('platform');
        $search = $request->input('search');
        $variant = $request->input('variant');
    
        // Query platform products dengan relasi - bypass semua scope
        $query = PlatformProduct::withoutGlobalScopes()->with(['platform', 'mappingBarang.product']);
    
        // Filter berdasarkan platform jika dipilih
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
    
        // Ambil semua platform untuk dropdown filter - bypass scope
        $platforms = Platform::withoutGlobalScopes()->get();
    
        // Debug: Cek data di database - bypass scope
        $totalPlatformProducts = PlatformProduct::withoutGlobalScopes()->count();
        $totalPlatforms = Platform::withoutGlobalScopes()->count();
        
        \Log::info('Database Debug', [
            'total_platform_products' => $totalPlatformProducts,
            'total_platforms' => $totalPlatforms,
            'platforms_list' => Platform::withoutGlobalScopes()->pluck('name')->toArray()
        ]);

        // Ambil data dengan pagination
        $platformProducts = $query->paginate(50);

        // Debug: Log data untuk troubleshooting
        \Log::info('MappingBarangController Debug', [
            'platform' => $platform,
            'search' => $search,
            'variant' => $variant,
            'platformProducts_count' => $platformProducts->count(),
            'platformProducts_type' => gettype($platformProducts),
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ]);

        // Debug: Pastikan data yang dikirim ke view benar
        \Log::info('View Data Debug', [
            'platformProducts_type' => gettype($platformProducts),
            'platformProducts_count' => is_countable($platformProducts) ? count($platformProducts) : 'Not countable',
            'platformProducts_class' => get_class($platformProducts),
            'platforms_type' => gettype($platforms),
            'platforms_count' => is_countable($platforms) ? count($platforms) : 'Not countable'
        ]);

        return view('master.mapping.index', [
            'platformProducts' => $platformProducts,
            'platforms' => $platforms,
            'selectedPlatform' => $platform,
            'search' => $search,
            'variant' => $variant,
        ]);
    }

    /**
     * Get mapping details for a platform product (AJAX)
     */
    public function getMappingDetails($platformProductId)
    {
        try {
            $platformProduct = PlatformProduct::withoutGlobalScopes()
                ->with(['platform', 'mappingBarang.product.productVariant'])
                ->findOrFail($platformProductId);

            $mappings = $platformProduct->mappingBarang()
                ->with(['product.productVariant'])
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'platformProduct' => $platformProduct,
                'mappings' => $mappings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat detail mapping: ' . $e->getMessage()
            ], 500);
        }
    }

        public function destroyAll($platformProductId)
    {
        // Check if user has permission to edit/delete mapping
        if (!auth()->user()->canEdit()) {
            return redirect()->route('master.mapping.index')
                ->with('error', 'Anda tidak memiliki izin untuk menghapus data.');
        }

        try {
            // Start a database transaction
            \DB::beginTransaction();
            
            // Verifikasi platform product exists
            $platformProduct = PlatformProduct::findOrFail($platformProductId);
            
            // Store the name for success message
            $productName = $platformProduct->platform_product_name;
            
            // Log the deletion attempt
            \Log::info('Attempting to delete mapping and platform product', [
                'platform_product_id' => $platformProductId,
                'product_name' => $productName,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name
            ]);
            
            // Hapus semua mapping yang terkait
            $mappingsDeleted = MappingBarang::where('platform_product_id', $platformProductId)->delete();
            \Log::info('Mappings deleted', ['count' => $mappingsDeleted]);
            
            // Hapus juga platform product itu sendiri
            $platformProduct->delete();
            \Log::info('Platform product deleted', ['id' => $platformProductId]);
            
            // Commit the transaction
            \DB::commit();
            
            return redirect()->route('master.mapping.index')
                ->with('success', 'Semua mapping untuk produk ' . $productName . ' berhasil dihapus.');
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            \DB::rollBack();
            
            // Log the error
            \Log::error('Error deleting mapping', [
                'platform_product_id' => $platformProductId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('master.mapping.index')
                ->with('error', 'Terjadi kesalahan saat menghapus mapping: ' . $e->getMessage());
        }
    }

   /**
 * Tampilkan form untuk membuat mapping baru
 */
public function create(Request $request)
{
    // Ambil semua platform
    $platforms = Platform::all();
    
    // Ambil semua produk yang aktif untuk dropdown
    $products = Product::where('is_active', true)->get();
    
    // Ambil parameter dari request jika ada
    $platformPreselected = $request->get('platform');
    $productNamePreselected = $request->get('product_name');
    $variantPreselected = $request->get('variant');
    
    return view('master.mapping.create', [
        'platforms' => $platforms,
        'products' => $products,
        'platformPreselected' => $platformPreselected,
        'productNamePreselected' => $productNamePreselected,
        'variantPreselected' => $variantPreselected,
        'fromAutoCreate' => false // Ini bukan dari auto-create
    ]);
}

/**
 * Simpan mapping baru ke database
 */
public function store(Request $request)
{
    // Log incoming request data for debugging
    \Log::info('Mapping store request data', [
        'platform_id' => $request->platform_id,
        'platform_product_name' => $request->platform_product_name,
        'variant' => $request->variant,
        'product_id' => $request->product_id,
        'quantity' => $request->quantity,
        'all_data' => $request->all(),
        'method' => $request->method(),
        'url' => $request->url(),
        'headers' => $request->headers->all()
    ]);
    
    try {
        $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'platform_product_name' => 'required|string',
            'variant' => 'nullable|string',
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|numeric|min:0.01',
        ]);
        \Log::info('Validation passed successfully');
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation failed', [
            'errors' => $e->errors(),
            'request_data' => $request->all()
        ]);
        throw $e;
    }

    try {
        // Mulai transaksi database
        \DB::beginTransaction();
        
        // Cek apakah platform product sudah ada
        // Handle variant null/empty string properly
        $variantValue = $request->variant ?: null;
        $platformProduct = PlatformProduct::where('platform_id', $request->platform_id)
            ->where('platform_product_name', $request->platform_product_name)
            ->where(function($query) use ($variantValue) {
                if ($variantValue === null) {
                    $query->whereNull('variant');
                } else {
                    $query->where('variant', $variantValue);
                }
            })
            ->first();
        
        // Debug log
        \Log::info('Store mapping debug', [
            'platform_id' => $request->platform_id,
            'platform_product_name' => $request->platform_product_name,
            'variant' => $request->variant,
            'platform_product_found' => $platformProduct ? $platformProduct->id : 'not found',
            'has_active_mapping' => $platformProduct ? MappingBarang::hasActiveMappingForPlatformVariant($platformProduct->id) : false
        ]);
        
        // Jika platform product sudah ada, cek apakah sudah ada mapping aktif
        if ($platformProduct && MappingBarang::hasActiveMappingForPlatformVariant($platformProduct->id)) {
            $existingMapping = MappingBarang::getActiveMappingForPlatformVariant($platformProduct->id);
            \DB::rollBack();
            return redirect()->back()
                ->with('error', 'Platform+variant ini sudah memiliki mapping aktif (v' . $existingMapping->version . '). Silakan edit mapping yang sudah ada atau buat versi baru.');
        }
        
        // Jika platform product sudah ada tapi belum ada mapping aktif, redirect ke edit
        if ($platformProduct && !MappingBarang::hasActiveMappingForPlatformVariant($platformProduct->id)) {
            \DB::rollBack();
            return redirect()->back()
                ->with('error', 'Platform+variant ini sudah ada di database tapi belum termapping. Silakan gunakan fitur Edit untuk menambahkan mapping.');
        }
        
        // Jika belum ada, buat baru
        if (!$platformProduct) {
            $platformProduct = new PlatformProduct([
                'platform_id' => $request->platform_id,
                'platform_product_name' => $request->platform_product_name,
                'variant' => $variantValue, // Use processed variant value
            ]);
            $platformProduct->save();
        }
        
        // Loop melalui semua product_id dan quantity
        foreach ($request->product_id as $index => $productId) {
            // Cek apakah mapping sudah ada
            $mapping = MappingBarang::where('platform_product_id', $platformProduct->id)
                ->where('product_id', $productId)
                ->first();
            
            // Jika belum ada, buat baru; jika sudah ada, update
            if (!$mapping) {
                $mapping = new MappingBarang([
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $productId,
                    'quantity' => $request->quantity[$index],
                ]);
                $mapping->save();
                
                \Log::info('Mapping created', [
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $productId,
                    'quantity' => $request->quantity[$index],
                    'mapping_id' => $mapping->id
                ]);
            } else {
                $mapping->quantity = $request->quantity[$index];
                $mapping->save();
                
                \Log::info('Mapping updated', [
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $productId,
                    'quantity' => $request->quantity[$index],
                    'mapping_id' => $mapping->id
                ]);
            }
        }
        
        // Commit transaksi
        \DB::commit();
        
        \Log::info('Mapping store completed successfully', [
            'platform_product_id' => $platformProduct->id,
            'platform_product_name' => $platformProduct->platform_product_name,
            'variant' => $platformProduct->variant,
            'mappings_created' => count($request->product_id)
        ]);
        
        // Check if this is from sales import process (has preview_data in session)
        $hasPreviewData = session('preview_data') !== null;
        
        if ($hasPreviewData) {
            // Skenario 1: Dari sales import - redirect back to preview
            $platform = Platform::findOrFail($request->platform_id);
            $platformName = strtolower($platform->name);
            
            // Update unmapped products in session
            $unmappedProducts = session('unmapped_products', []);
            $productName = $request->platform_product_name;
            $variant = $request->variant;
            
            // Create full product name with variant if exists
            $fullProductName = $variant ? $productName . ' - ' . $variant : $productName;
            
            \Log::info('Removing from unmapped products', [
                'product_name' => $productName,
                'variant' => $variant,
                'full_product_name' => $fullProductName,
                'unmapped_products_before' => $unmappedProducts
            ]);
            
            $key = array_search($fullProductName, $unmappedProducts);
            
            // If not found directly, try looking for URL-encoded versions
            if ($key === false) {
                foreach ($unmappedProducts as $index => $unmappedProduct) {
                    if (urldecode($unmappedProduct) === $fullProductName) {
                        $key = $index;
                        break;
                    }
                }
            }
            
            // If still not found, try without variant
            if ($key === false) {
                $key = array_search($productName, $unmappedProducts);
            }
            
            if ($key !== false) {
                unset($unmappedProducts[$key]);
                session(['unmapped_products' => array_values($unmappedProducts)]);
                
                \Log::info('Product removed from unmapped list', [
                    'removed_product' => $fullProductName,
                    'unmapped_products_after' => array_values($unmappedProducts)
                ]);
            } else {
                \Log::warning('Product not found in unmapped list', [
                    'searched_product' => $fullProductName,
                    'unmapped_products' => $unmappedProducts
                ]);
            }
            
            // Handle different route names for each platform
            switch ($platformName) {
                case 'blibli':
                    return redirect()->route('sales.blibli.show-preview-import')
                        ->with('success', 'Mapping produk berhasil disimpan. Silakan lanjutkan proses import.');
                case 'shopee':
                case 'tokopedia':
                case 'tiktok':
                    return redirect()->route('sales.' . $platformName . '.show-preview')
                        ->with('success', 'Mapping produk berhasil disimpan. Silakan lanjutkan proses import.');
                default:
                    return redirect()->route('sales.' . $platformName . '.show-preview')
                        ->with('success', 'Mapping produk berhasil disimpan. Silakan lanjutkan proses import.');
            }
        }
        
        return redirect()->route('master.mapping.index')
            ->with('success', 'Mapping produk berhasil disimpan.');
    } catch (\Exception $e) {
        // Rollback transaksi jika terjadi error
        \DB::rollBack();
        
        // Log the error
        \Log::error('Error storing mapping', [
            'platform_id' => $request->platform_id,
            'platform_product_name' => $request->platform_product_name,
            'variant' => $request->variant,
            'product_ids' => $request->product_id ?? [],
            'quantities' => $request->quantity ?? [],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()
            ->with('error', 'Terjadi kesalahan saat menyimpan mapping: '.$e->getMessage())
            ->withInput();
    }
}

/**
 * Hapus mapping (untuk route destroy)
 */
public function destroy($id)
{
    // Check if user has permission to edit/delete mapping
    if (!auth()->user()->canEdit()) {
        return redirect()->route('master.mapping.index')
            ->with('error', 'Anda tidak memiliki izin untuk menghapus data.');
    }

    try {
        \DB::beginTransaction();
        
        $mapping = MappingBarang::findOrFail($id);
        $platformProductId = $mapping->platform_product_id;
        $productId = $mapping->product_id;
        $quantity = $mapping->quantity;
        $version = $mapping->version;
        
        // Cek apakah mapping sudah pernah digunakan dalam penjualan
        $hasBeenUsed = $mapping->hasBeenUsedInSales();
        
        if ($hasBeenUsed) {
            // Kondisi 2: Sudah digunakan dalam penjualan, deactivate saja
            $mapping->update([
                'is_active' => false,
                'valid_until' => now()
            ]);
            
            // Log to history
            MappingBarangHistory::create([
                'platform_product_id' => $platformProductId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'action' => 'deactivate',
                'user_id' => auth()->id(),
                'keterangan' => 'Deactivate mapping v' . $version . ' (sudah digunakan dalam penjualan)',
            ]);
            
            \DB::commit();
            return redirect()->back()
                ->with('success', 'Mapping v' . $version . ' berhasil dinonaktifkan (menjadi history).');
        } else {
            // Kondisi 1: Belum digunakan dalam penjualan, hapus langsung
            $mapping->delete();

            // Log to history
            MappingBarangHistory::create([
                'platform_product_id' => $platformProductId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'action' => 'delete',
                'user_id' => auth()->id(),
                'keterangan' => 'Hapus mapping v' . $version,
            ]);
            
            \DB::commit();
            return redirect()->back()
                ->with('success', 'Mapping v' . $version . ' berhasil dihapus.');
        }
    } catch (\Exception $e) {
        \DB::rollBack();
        return redirect()->back()
            ->with('error', 'Terjadi kesalahan saat menghapus mapping: '.$e->getMessage());
    }
}

    public function edit($id)
    {
        // Ambil mapping berdasarkan ID dengan relasi
        $mapping = MappingBarang::with(['platformProduct.platform', 'product'])->findOrFail($id);

        // Cek apakah mapping sudah pernah digunakan dalam penjualan
        $hasBeenUsed = $mapping->hasBeenUsedInSales();

        // Ambil semua produk untuk dropdown
        $products = Product::where('is_active', true)->get();

        // Ambil riwayat versi mapping
        $mappingHistory = MappingBarang::where('platform_product_id', $mapping->platform_product_id)
            ->orderBy('version', 'desc')
            ->get();

        return view('master.mapping.edit', [
            'mapping' => $mapping,
            'products' => $products,
            'hasBeenUsed' => $hasBeenUsed,
            'mappingHistory' => $mappingHistory,
        ]);
    }

    /**
     * Edit platform product yang belum termapping
     */
    public function editProduct($platformProductId)
    {
        // Ambil platform product berdasarkan ID
        $platformProduct = PlatformProduct::with(['platform'])->findOrFail($platformProductId);

        // Ambil semua produk untuk dropdown
        $products = Product::where('is_active', true)->get();

        // Ambil mapping yang sudah ada (jika ada)
        $existingMappings = MappingBarang::where('platform_product_id', $platformProductId)
            ->where('is_active', true)
            ->with(['product'])
            ->get();

        return view('master.mapping.edit-product', [
            'platformProduct' => $platformProduct,
            'products' => $products,
            'existingMappings' => $existingMappings,
        ]);
    }

    /**
 * Update mapping yang sudah ada
 */
public function update(Request $request, $id)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|numeric|min:0.01',
        'change_reason' => 'nullable|string|max:500',
    ]);

    try {
        \DB::beginTransaction();
        
        $mapping = MappingBarang::findOrFail($id);
        $oldProductId = $mapping->product_id;
        $oldQuantity = $mapping->quantity;
        
        // Cek apakah mapping sudah pernah digunakan dalam penjualan
        $hasBeenUsed = $mapping->hasBeenUsedInSales();
        
        // Cek apakah ada perubahan data
        $hasDataChange = ($mapping->product_id != $request->product_id) || 
                        ($mapping->quantity != $request->quantity);
        
        if ($hasBeenUsed && $hasDataChange) {
            // Kondisi 2: Sudah digunakan dalam penjualan, buat versi baru
            $latestVersion = MappingBarang::where('platform_product_id', $mapping->platform_product_id)
                ->max('version');
            
            // Deactivate semua mapping aktif untuk platform product ini
            MappingBarang::where('platform_product_id', $mapping->platform_product_id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'valid_until' => now()
                ]);
            
            // Buat mapping baru dengan versi berikutnya
            $newMapping = new MappingBarang([
                'platform_product_id' => $mapping->platform_product_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'version' => $latestVersion + 1,
                'is_active' => true,
                'valid_from' => now(),
                'change_reason' => $request->change_reason,
            ]);
            
            $newMapping->save();
            
            // Log to history
            MappingBarangHistory::create([
                'platform_product_id' => $mapping->platform_product_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'action' => 'version_create',
                'user_id' => auth()->id(),
                'keterangan' => 'Buat versi baru mapping (dari v' . $mapping->version . ' ke v' . $newMapping->version . ') - ' . ($request->change_reason ?? 'Tidak ada alasan'),
            ]);
            
            \DB::commit();
            $message = 'Mapping berhasil diperbarui dengan versi baru (v' . $newMapping->version . '). Mapping lama menjadi history.';
            $redirectId = $newMapping->id;
        } else {
            // Kondisi 1: Belum digunakan dalam penjualan, edit langsung
            $mapping->update([
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);

            // Log to history
            MappingBarangHistory::create([
                'platform_product_id' => $mapping->platform_product_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'action' => 'update',
                'user_id' => auth()->id(),
                'keterangan' => 'Edit mapping (dari product_id: ' . $oldProductId . ', qty: ' . $oldQuantity . ')',
            ]);
            
            \DB::commit();
            $message = 'Mapping berhasil diperbarui.';
            $redirectId = $mapping->id;
        }

        // Redirect ke halaman edit dengan anchor untuk highlight
        return redirect()->route('master.mapping.edit', $redirectId)
            ->with('success', $message);
    } catch (\Exception $e) {
        \DB::rollBack();
        return redirect()->back()
            ->with('error', 'Terjadi kesalahan saat memperbarui mapping: '.$e->getMessage());
    }
}

/**
 * Tambahkan produk master baru ke mapping yang sudah ada
 */
    public function addProduct(Request $request)
    {
        $request->validate([
            'platform_product_id' => 'required|exists:platform_products,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            \DB::beginTransaction();
            
            // Cek apakah produk master yang sama sudah ada di mapping manapun (aktif atau tidak)
            $existingMapping = MappingBarang::where('platform_product_id', $request->platform_product_id)
                ->where('product_id', $request->product_id)
                ->first();
                
            if ($existingMapping) {
                \DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Produk master ini sudah ada dalam mapping. Silakan edit mapping yang sudah ada.');
            }
            
            // Cek apakah ada mapping aktif untuk platform product ini
            $activeMappings = MappingBarang::where('platform_product_id', $request->platform_product_id)
                ->where('is_active', true)
                ->get();
            
            // Jika belum ada mapping aktif, buat mapping baru dengan versi 1
            if ($activeMappings->isEmpty()) {
                $mapping = new MappingBarang([
                    'platform_product_id' => $request->platform_product_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'version' => 1,
                    'is_active' => true,
                    'valid_from' => now(),
                ]);
                
                $mapping->save();
                
                // Log to history
                MappingBarangHistory::create([
                    'platform_product_id' => $request->platform_product_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'action' => 'add',
                    'user_id' => auth()->id(),
                    'keterangan' => 'Tambah mapping baru (v1)',
                ]);
                
                \DB::commit();
                return redirect()->back()
                    ->with('success', 'Produk master berhasil ditambahkan ke mapping.');
            }
            
            // Jika sudah ada mapping aktif, cek apakah ada yang sudah digunakan dalam penjualan
            $hasUsedMappings = false;
            foreach ($activeMappings as $activeMapping) {
                if ($activeMapping->hasBeenUsedInSales()) {
                    $hasUsedMappings = true;
                    break;
                }
            }
            
            if ($hasUsedMappings) {
                // Kondisi 2: Ada penjualan, buat versi baru (V2)
                $latestVersion = MappingBarang::where('platform_product_id', $request->platform_product_id)
                    ->max('version');
                
                // Deactivate semua mapping aktif
                MappingBarang::where('platform_product_id', $request->platform_product_id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'valid_until' => now()
                    ]);
                
                // Buat mapping baru dengan versi berikutnya
                $mapping = new MappingBarang([
                    'platform_product_id' => $request->platform_product_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'version' => $latestVersion + 1,
                    'is_active' => true,
                    'valid_from' => now(),
                ]);
                
                $mapping->save();
                
                // Log to history
                MappingBarangHistory::create([
                    'platform_product_id' => $request->platform_product_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'action' => 'add',
                    'user_id' => auth()->id(),
                    'keterangan' => 'Tambah mapping baru (v' . ($latestVersion + 1) . ') - mapping lama menjadi history',
                ]);
                
                \DB::commit();
                return redirect()->back()
                    ->with('success', 'Produk master berhasil ditambahkan ke mapping versi ' . ($latestVersion + 1) . '. Mapping lama menjadi history.');
            } else {
                // Kondisi 1: Belum ada penjualan, tambah ke mapping aktif yang sama
                $mapping = new MappingBarang([
                    'platform_product_id' => $request->platform_product_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'version' => $activeMappings->first()->version, // Gunakan versi yang sama
                    'is_active' => true,
                    'valid_from' => now(),
                ]);
                
                $mapping->save();
                
                // Log to history
                MappingBarangHistory::create([
                    'platform_product_id' => $request->platform_product_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'action' => 'add',
                    'user_id' => auth()->id(),
                    'keterangan' => 'Tambah mapping ke versi ' . $activeMappings->first()->version,
                ]);
                
                \DB::commit();
                return redirect()->back()
                    ->with('success', 'Produk master berhasil ditambahkan ke mapping.');
            }

            // Ambil mapping lain untuk mendapatkan ID yang valid untuk redirect
            $anyMapping = MappingBarang::where('platform_product_id', $request->platform_product_id)
            ->first();
        
        return redirect()->route('master.mapping.edit', $anyMapping->id)
            ->with('success', 'Produk master berhasil ditambahkan ke mapping.');
    } catch (\Exception $e) {
        return redirect()->back()
            ->with('error', 'Terjadi kesalahan saat menambahkan produk: '.$e->getMessage());
    }
}

    public function show($id)
    {
        $mapping = MappingBarang::with(['platformProduct.platform', 'product'])->findOrFail($id);

        $products = Product::where('is_active', true)->get();

        return view('master.mapping.show', [
            'mapping' => $mapping,
            'products' => $products,
        ]);
    }

    /**
     * Cek produk yang belum dimapping
     */
    public function checkUnmappedProducts(Request $request, $platform)
    {
        // Ambil data produk yang belum dimapping dari session
        $unmappedProducts = session('unmapped_products', []);

        // Jika tidak ada data dari session, redirect ke halaman daftar mapping
        if (empty($unmappedProducts)) {
            return redirect()
                ->route('master.mapping.index', ['platform' => $platform])
                ->with('info', 'Tidak ada produk yang perlu dimapping.');
        }

        return view('master.mapping.check', [
            'platform' => $platform,
            'unmappedProducts' => $unmappedProducts,
        ]);
    }

    /**
     * Auto create mapping by redirecting to create form with prefilled data
     */
    public function autoCreateMapping($platform, $productName)
    {
        \Log::info('[AUTO-MAPPING] Mulai autoCreateMapping', [
            'platform' => $platform,
            'productName' => $productName,
            'timestamp' => now()->toDateTimeString()
        ]);
        // Decode the URL-encoded product name (convert + to spaces)
        $decodedProductName = urldecode($productName);
        
        // Parse product name and variant from the full product name
        // Improved parsing to handle products that already contain " - " in their names
        $productParts = explode(' - ', $decodedProductName, 2);
        $productNameOnly = $productParts[0];
        $variant = isset($productParts[1]) ? $productParts[1] : '';
        
        // Try to find the correct parsing by checking against existing platform products
        // This helps when the product name itself contains " - "
        $platform_entity = Platform::where('name', $platform)->first();
        if ($platform_entity) {
            // Check if there's a platform product that matches exactly with different parsing
            $alternativeParsing = null;
            
            // If we have multiple " - " in the string, try different splitting points
            $allParts = explode(' - ', $decodedProductName);
            if (count($allParts) > 2) {
                // Try different combinations
                for ($i = 1; $i < count($allParts); $i++) {
                    $altProductName = implode(' - ', array_slice($allParts, 0, $i));
                    $altVariant = implode(' - ', array_slice($allParts, $i));
                    
                    $platformProduct = \App\Models\PlatformProduct::where('platform_id', $platform_entity->id)
                        ->where('platform_product_name', $altProductName)
                        ->where('variant', $altVariant)
                        ->first();
                        
                    if ($platformProduct) {
                        $productNameOnly = $altProductName;
                        $variant = $altVariant;
                        break;
                    }
                }
            }
        }
        
        \Log::info('[AUTO-MAPPING] Parsed product data', [
            'fullProductName' => $decodedProductName,
            'productNameOnly' => $productNameOnly,
            'variant' => $variant
        ]);
        
        // Ambil platform dari database
        $platformEntity = Platform::where('name', $platform)->first();
        if (!$platformEntity) {
            \Log::warning('[AUTO-MAPPING] Platform tidak ditemukan', ['platform' => $platform]);
            return redirect()->route('master.mapping.index')->with('error', 'Platform tidak ditemukan di database.');
        }

        // Ambil semua produk yang bisa di-mapping
        $products = Product::where('is_active', true)->get();

        \Log::info('[AUTO-MAPPING] Sukses tampilkan form auto-mapping', [
            'platform' => $platform,
            'productName' => $productNameOnly,
            'variant' => $variant,
            'timestamp' => now()->toDateTimeString()
        ]);
        // Redirect ke halaman create dengan parameter yang sudah diisi
        return view('master.mapping.create', [
            'platforms' => Platform::all(),
            'products' => $products,
            'platformPreselected' => $platformEntity->id,
            'productNamePreselected' => $productNameOnly,
            'variantPreselected' => $variant,
            'fromAutoCreate' => true
        ]);
    }

    /**
     * Tampilkan halaman mapping produk
     */
    public function showMapping($platform, $productName)
    {
        // Ambil platform dari database
        $platformEntity = Platform::where('name', $platform)->first();
        if (!$platformEntity) {
            return redirect()->route('master.mapping.index')->with('error', 'Platform tidak ditemukan di database.');
        }

        // Decode the URL-encoded product name
        $decodedProductName = urldecode($productName);

        // Cek apakah produk sudah ada di database
        $platformProduct = PlatformProduct::where('platform_id', $platformEntity->id)
            ->where('platform_product_name', $decodedProductName)
            ->first();

        // Jika platform product tidak ada, redirect ke create mapping
        if (!$platformProduct) {
            return redirect()->route('master.mapping.auto-create', [
                'platform' => $platform,
                'productName' => $productName
            ]);
        }

        // Ambil mapping aktif untuk platform product ini
        $mapping = MappingBarang::where('platform_product_id', $platformProduct->id)
            ->where('is_active', true)
            ->with(['platformProduct.platform', 'product'])
            ->first();

        // Jika tidak ada mapping aktif, redirect ke create mapping (bukan auto-create)
        if (!$mapping) {
            return redirect()->route('master.mapping.create', [
                'platform' => $platformEntity->id,
                'product_name' => $decodedProductName
            ]);
        }

        // Ambil semua produk yang bisa di-mapping
        $products = Product::where('is_active', true)->get();

        return view('master.mapping.show', [
            'mapping' => $mapping,
            'products' => $products,
        ]);
    }

    /**
     * Simpan mapping produk
     */
    public function saveMapping(Request $request)
    {
        $request->validate([
            'platform' => 'required|string',
            'product_name' => 'required|string',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            // Ambil platform dari database
            $platform = Platform::where('name', $request->platform)->first();
            if (!$platform) {
                throw new \Exception('Platform tidak ditemukan di database.');
            }

            // Cek apakah platform product sudah ada
            $platformProduct = PlatformProduct::where('platform_id', $platform->id)->where('platform_product_name', $request->product_name)->first();

            // Jika belum ada, buat baru
            if (!$platformProduct) {
                $platformProduct = new PlatformProduct([
                    'platform_id' => $platform->id,
                    'platform_product_name' => $request->product_name,
                ]);
                $platformProduct->save();
            }

            // Cek apakah mapping sudah ada
            $mapping = MappingBarang::where('platform_product_id', $platformProduct->id)->where('product_id', $request->product_id)->first();

            // Jika belum ada, buat baru; jika sudah ada, update
            if (!$mapping) {
                $mapping = new MappingBarang([
                    'platform_product_id' => $platformProduct->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                ]);
            } else {
                $mapping->quantity = $request->quantity;
            }

            $mapping->save();

            // Jika dari halaman check, update session unmapped_products
            if ($request->has('from_check') && $request->from_check) {
                $unmappedProducts = session('unmapped_products', []);
                $key = array_search($request->product_name, $unmappedProducts);
                if ($key !== false) {
                    unset($unmappedProducts[$key]);
                    session(['unmapped_products' => array_values($unmappedProducts)]);
                }

                // Jika semua produk sudah dimapping, redirect ke preview import
                if (empty($unmappedProducts)) {
                    return redirect()
                        ->route('sales.' . $request->platform . '.show-preview')
                        ->with('success', 'Semua produk berhasil dimapping. Silakan lanjutkan proses import.');
                }
            }

            return redirect()->back()->with('success', 'Mapping produk berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat menyimpan mapping: ' . $e->getMessage());
        }
    }

    /**
     * Hapus mapping produk
     */
    public function deleteMapping($id)
    {
        try {
            $mapping = MappingBarang::findOrFail($id);
            $mapping->delete();

            return redirect()->back()->with('success', 'Mapping produk berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat menghapus mapping: ' . $e->getMessage());
        }
    }
}
