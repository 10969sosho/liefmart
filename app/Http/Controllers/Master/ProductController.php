<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\MainCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSize;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\SubBrand;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Start building query
        $query = Product::with([
            'mainCategory', 
            'brand', 
            'subBrand', 
            'productCategory', 
            'productType', 
            'productSize', 
            'productVariant'
        ]);
        
        // Apply filters if they exist
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('main_category_id')) {
            $query->where('main_category_id', $request->main_category_id);
        }
        
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        if ($request->filled('status')) {
            $isActive = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $isActive);
        }
        
        // Order by
        $orderBy = $request->order_by ?? 'created_at';
        $orderDirection = $request->order_direction ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);
        
        // Paginate with all current query parameters preserved
        $perPage = $request->per_page ?? 15;
        $products = $query->paginate($perPage)->withQueryString();
        
        // Get data for filter dropdowns
        $mainCategories = MainCategory::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        
        return view('master.products.index', compact('products', 'mainCategories', 'brands'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Get the main category ID from session that was selected at login
        $mainCategoryId = session('main_category_id');
        
        // If there's no main category ID in session, redirect back with error
        if (!$mainCategoryId) {
            return redirect()->back()->with('error', 'Main Category tidak ditemukan. Silakan login ulang.');
        }
        
        // Get only the main category that was selected at login
        $mainCategory = MainCategory::find($mainCategoryId);
        $mainCategories = collect([$mainCategory]);
        
        // Get brands for this main category
        $brands = Brand::where('is_active', true)
                       ->where('main_category_id', $mainCategoryId)
                       ->orderBy('name')
                       ->get();
        
        // Get all related data to handle form validation and re-population
        $subBrands = SubBrand::where('is_active', true)
            ->whereHas('brand', function($query) use ($mainCategoryId) {
                $query->where('main_category_id', $mainCategoryId);
            })
            ->when(old('brand_id'), function($query) {
                return $query->where('brand_id', old('brand_id'));
            })
            ->orderBy('name')
            ->get();
            
        $productCategories = ProductCategory::where('is_active', true)
            ->whereHas('subBrand.brand', function($query) use ($mainCategoryId) {
                $query->where('main_category_id', $mainCategoryId);
            })
            ->when(old('sub_brand_id'), function($query) {
                return $query->where('sub_brand_id', old('sub_brand_id'));
            })
            ->orderBy('name')
            ->get();
            
        $productTypes = ProductType::where('is_active', true)
            ->whereHas('productCategory.subBrand.brand', function($query) use ($mainCategoryId) {
                $query->where('main_category_id', $mainCategoryId);
            })
            ->when(old('product_category_id'), function($query) {
                return $query->where('product_category_id', old('product_category_id'));
            })
            ->orderBy('name')
            ->get();
            
        $productSizes = ProductSize::where('is_active', true)->orderBy('name')->get();
        $productVariants = ProductVariant::where('is_active', true)->orderBy('name')->get();
        
        return view('master.products.create', compact(
            'mainCategories',
            'brands',
            'subBrands',
            'productCategories',
            'productTypes',
            'productSizes',
            'productVariants',
            'mainCategoryId'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Log data yang diterima untuk debugging
            \Log::info('Product Store Request', $request->all());
            
            // Validasi data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'main_category_id' => 'required|exists:main_categories,id',
                'brand_id' => 'required|exists:brands,id',
                'sub_brand_id' => 'required|exists:sub_brands,id',
                'product_category_id' => 'required|exists:product_categories,id',
                'product_type_id' => 'required|exists:product_types,id',
                'product_size_id' => 'required|exists:product_sizes,id',
                'product_variant_id' => 'nullable|exists:product_variants,id',
                'sku' => 'nullable|string|max:50|unique:products,sku',
                'description' => 'nullable|string',
                'initial_price' => 'nullable|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
            ]);

            // Pastikan value is_active selalu ada
            // Jika hidden input ada dan checkbox tidak di-check, gunakan nilai 0
            // Jika checkbox di-check, gunakan nilai 1
            $validated['is_active'] = $request->has('is_active') ? 1 : 0;
            
            \Log::info('Is Active Value: ' . $validated['is_active']);
            
            // Pastikan product_variant_id null jika kosong
            if (empty($validated['product_variant_id'])) {
                $validated['product_variant_id'] = null;
            }
            
            // Log data yang akan disimpan
            \Log::info('Data Product yang akan disimpan:', $validated);
            
            // Coba buat produk
            $product = Product::create($validated);
            
            // Log produk yang berhasil dibuat
            \Log::info('Product berhasil dibuat dengan ID:', ['id' => $product->id]);
            
            // Redirect dengan pesan sukses
            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil ditambahkan.');
                
        } catch (\Exception $e) {
            // Log error
            \Log::error('Error saat membuat produk:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Redirect dengan pesan error
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with([
            'mainCategory', 
            'brand', 
            'subBrand', 
            'productCategory', 
            'productType', 
            'productSize', 
            'productVariant'
        ])->findOrFail($id);
        
        return view('master.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        
        $mainCategories = MainCategory::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();
        $subBrands = SubBrand::where('is_active', true)->get();
        $productCategories = ProductCategory::where('is_active', true)->get();
        $productTypes = ProductType::where('is_active', true)->get();
        $productSizes = ProductSize::where('is_active', true)->get();
        $productVariants = ProductVariant::where('is_active', true)->get();
        
        return view('master.products.edit', compact(
            'product',
            'mainCategories',
            'brands',
            'subBrands',
            'productCategories',
            'productTypes',
            'productSizes',
            'productVariants'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Log data yang diterima untuk debugging
            \Log::info('Product Update Request', $request->all());
            
            // Validasi data
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'main_category_id' => 'required|exists:main_categories,id',
                'brand_id' => 'required|exists:brands,id',
                'sub_brand_id' => 'required|exists:sub_brands,id',
                'product_category_id' => 'required|exists:product_categories,id',
                'product_type_id' => 'required|exists:product_types,id',
                'product_size_id' => 'required|exists:product_sizes,id',
                'product_variant_id' => 'nullable|exists:product_variants,id',
                'sku' => 'nullable|string|max:50|unique:products,sku,' . $id,
                'description' => 'nullable|string',
                'initial_price' => 'nullable|numeric|min:0',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
            ]);
            
            // Pastikan value is_active selalu ada
            $validated['is_active'] = $request->has('is_active') ? 1 : 0;
            
            \Log::info('Is Active Value (update): ' . $validated['is_active']);
            
            // Pastikan product_variant_id null jika kosong
            if (empty($validated['product_variant_id'])) {
                $validated['product_variant_id'] = null;
            }
            
            // Log data yang akan diupdate
            \Log::info('Data Product yang akan diupdate:', $validated);
            
            // Update produk
            $product->update($validated);
            
            // Log produk yang berhasil diupdate
            \Log::info('Product berhasil diupdate dengan ID:', ['id' => $product->id]);
            
            // Redirect dengan pesan sukses
            return redirect()->route('products.index')
                ->with('success', 'Produk berhasil diperbarui.');
                
        } catch (\Exception $e) {
            // Log error
            \Log::error('Error saat mengupdate produk:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Redirect dengan pesan error
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        
        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }
    
    /**
     * Get sub brands for a specific brand
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSubBrands(Request $request)
    {
        $brandId = $request->input('brand_id');
        $subBrands = SubBrand::where('brand_id', $brandId)
            ->where('is_active', true)
            ->get();
            
        return response()->json($subBrands);
    }
    
    /**
     * Get product categories for a specific sub brand
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductCategories(Request $request)
    {
        $subBrandId = $request->input('sub_brand_id');
        $productCategories = ProductCategory::where('sub_brand_id', $subBrandId)
            ->where('is_active', true)
            ->get();
            
        return response()->json($productCategories);
    }
    
    /**
     * Get product types for a specific product category
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductTypes(Request $request)
    {
        $productCategoryId = $request->input('product_category_id');
        $productTypes = ProductType::where('product_category_id', $productCategoryId)
            ->where('is_active', true)
            ->get();
            
        return response()->json($productTypes);
    }
} 