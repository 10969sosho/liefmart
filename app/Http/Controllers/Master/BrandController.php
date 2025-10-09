<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\MainCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     */
    public function index()
    {
        $brands = Brand::with('mainCategory')->latest()->get();
        
        // Debug brands that have missing main categories
        foreach ($brands as $brand) {
            if (!$brand->mainCategory) {
                \Log::warning("Brand #{$brand->id} ({$brand->name}) has no associated MainCategory. main_category_id: {$brand->main_category_id}");
                
                // Try to fix the brand by finding the correct main category
                if ($brand->main_category_id) {
                    $mainCategory = MainCategory::find($brand->main_category_id);
                    if ($mainCategory) {
                        \Log::info("Found MainCategory #{$mainCategory->id} for Brand #{$brand->id}");
                    } else {
                        \Log::warning("MainCategory #{$brand->main_category_id} not found in database for Brand #{$brand->id}");
                    }
                }
            }
        }
        
        // Paginate after the debug to ensure we've logged all issues
        $brands = Brand::with('mainCategory')->latest()->paginate(10);
        
        return view('master.brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new brand.
     */
    public function create()
    {
        $mainCategories = MainCategory::where('is_active', true)->get();
        
        // If no main categories exist, create a default one
        if ($mainCategories->isEmpty()) {
            MainCategory::create([
                'name' => 'Default Category',
                'description' => 'Automatically created default category',
                'is_active' => true
            ]);
            
            $mainCategories = MainCategory::where('is_active', true)->get();
        }
        
        return view('master.brands.create', compact('mainCategories'));
    }

    /**
     * Store a newly created brand in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'main_category_id' => 'required|exists:main_categories,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                \Log::warning('Brand validation failed:', $validator->errors()->toArray());
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = [
                'name' => $request->name,
                'main_category_id' => $request->main_category_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Creating brand with data:', $data);
            
            $brand = Brand::create($data);
            
            \Log::info('Brand created with ID: ' . $brand->id);

            return redirect()->route('brands.index')
                ->with('success', 'Brand berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Failed to create brand: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store a newly created brand via API.
     */
    public function storeApi(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'main_category_id' => 'required|exists:main_categories,id',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = [
                'name' => $request->name,
                'main_category_id' => $request->main_category_id,
                'description' => $request->description,
                'is_active' => true,
            ];

            $brand = Brand::create($data);

            return response()->json([
                'success' => true,
                'id' => $brand->id,
                'name' => $brand->name,
                'message' => 'Brand berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create Brand via API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified brand.
     */
    public function show(Brand $brand)
    {
        $brand->load('mainCategory', 'subBrands');
        return view('master.brands.show', compact('brand'));
    }

    /**
     * Show the form for editing the specified brand.
     */
    public function edit(Brand $brand)
    {
        $mainCategories = MainCategory::where('is_active', true)->get();
        return view('master.brands.edit', compact('brand', 'mainCategories'));
    }

    /**
     * Update the specified brand in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'main_category_id' => 'required|exists:main_categories,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = [
                'name' => $request->name,
                'main_category_id' => $request->main_category_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            $brand->update($data);

            return redirect()->route('brands.index')
                ->with('success', 'Brand berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Failed to update brand: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified brand from storage.
     */
    public function destroy(Brand $brand)
    {
        // Check if brand has associated sub brands
        if ($brand->subBrands()->count() > 0) {
            return redirect()->route('brands.index')
                ->with('error', 'Brand tidak dapat dihapus karena masih memiliki Sub Brand.');
        }
        
        $brand->delete();

        return redirect()->route('brands.index')
            ->with('success', 'Brand berhasil dihapus.');
    }
    
    /**
     * Test brand creation directly for debugging
     */
    public function test()
    {
        try {
            // Get a main category
            $mainCategory = \App\Models\MainCategory::first();
            
            if (!$mainCategory) {
                // Create a main category if none exists
                $mainCategory = \App\Models\MainCategory::create([
                    'name' => 'Test Category',
                    'description' => 'Test description',
                    'is_active' => true
                ]);
            }
            
            // Try to create a brand
            $brand = Brand::create([
                'name' => 'Test Brand',
                'main_category_id' => $mainCategory->id,
                'description' => 'Test brand description',
                'is_active' => true
            ]);
            
            return response()->json([
                'success' => true,
                'brand' => $brand,
                'mainCategory' => $mainCategory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Fix brands with missing main categories
     */
    public function fixMissingCategories()
    {
        $brands = Brand::whereNotNull('main_category_id')->get();
        $fixedCount = 0;
        $notFixedCount = 0;
        
        foreach ($brands as $brand) {
            if (!$brand->mainCategory) {
                $mainCategory = MainCategory::find($brand->main_category_id);
                
                if (!$mainCategory) {
                    // Main category doesn't exist - create a default one if needed
                    $defaultCategory = MainCategory::firstOrCreate(
                        ['name' => 'Default Category'],
                        [
                            'description' => 'Auto-created default category', 
                            'is_active' => true
                        ]
                    );
                    
                    // Update the brand to use this category
                    $brand->main_category_id = $defaultCategory->id;
                    $brand->save();
                    $fixedCount++;
                    
                    \Log::info("Fixed Brand #{$brand->id} ({$brand->name}) by assigning to default category #{$defaultCategory->id}");
                } else {
                    // This shouldn't happen unless there's a relationship issue
                    $notFixedCount++;
                    \Log::warning("Brand #{$brand->id} has main_category_id {$brand->main_category_id} which exists but isn't loading properly");
                }
            }
        }
        
        return response()->json([
            'message' => "Fixed $fixedCount brands. $notFixedCount brands had issues that couldn't be automatically fixed.",
            'fixed_count' => $fixedCount,
            'not_fixed_count' => $notFixedCount
        ]);
    }
}
