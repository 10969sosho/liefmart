<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\SubBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of product categories.
     */
    public function index()
    {
        $productCategories = ProductCategory::with('subBrand')->latest()->paginate(10);
        return view('master.product-categories.index', compact('productCategories'));
    }

    /**
     * Show the form for creating a new product category.
     */
    public function create()
    {
        $subBrands = SubBrand::where('is_active', true)->get();
        return view('master.product-categories.create', compact('subBrands'));
    }

    /**
     * Store a newly created product category in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sub_brand_id' => 'required|exists:sub_brands,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                \Log::warning('ProductCategory validation failed:', $validator->errors()->toArray());
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = [
                'name' => $request->name,
                'sub_brand_id' => $request->sub_brand_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Creating ProductCategory with data:', $data);
            
            $productCategory = ProductCategory::create($data);
            
            \Log::info('ProductCategory created with ID: ' . $productCategory->id);

            return redirect()->route('product-categories.index')
                ->with('success', 'Kategori Produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Failed to create ProductCategory: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified product category.
     */
    public function show(ProductCategory $productCategory)
    {
        $productCategory->load('subBrand', 'productTypes');
        return view('master.product-categories.show', compact('productCategory'));
    }

    /**
     * Show the form for editing the specified product category.
     */
    public function edit(ProductCategory $productCategory)
    {
        $subBrands = SubBrand::where('is_active', true)->get();
        return view('master.product-categories.edit', compact('productCategory', 'subBrands'));
    }

    /**
     * Update the specified product category in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'sub_brand_id' => 'required|exists:sub_brands,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                \Log::warning('ProductCategory update validation failed:', $validator->errors()->toArray());
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = [
                'name' => $request->name,
                'sub_brand_id' => $request->sub_brand_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Updating ProductCategory #' . $productCategory->id . ' with data:', $data);
            
            $productCategory->update($data);
            
            \Log::info('ProductCategory #' . $productCategory->id . ' updated successfully');

            return redirect()->route('product-categories.index')
                ->with('success', 'Kategori Produk berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Failed to update ProductCategory #' . $productCategory->id . ': ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified product category from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        // Check if product category has associated product types
        if ($productCategory->productTypes()->count() > 0) {
            return redirect()->route('product-categories.index')
                ->with('error', 'Kategori Produk tidak dapat dihapus karena masih memiliki Tipe Produk.');
        }
        
        $productCategory->delete();

        return redirect()->route('product-categories.index')
            ->with('success', 'Kategori Produk berhasil dihapus.');
    }
}
