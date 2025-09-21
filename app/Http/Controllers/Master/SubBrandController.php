<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\SubBrand;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubBrandController extends Controller
{
    /**
     * Display a listing of sub brands.
     */
    public function index()
    {
        $subBrands = SubBrand::with('brand')->latest()->get();
        
        // Debug subbrands that have missing brands
        foreach ($subBrands as $subbrand) {
            if (!$subbrand->brand) {
                \Log::warning("SubBrand #{$subbrand->id} ({$subbrand->name}) has no associated Brand. brand_id: {$subbrand->brand_id}");
                
                // Try to fix the subbrand by finding the correct brand
                if ($subbrand->brand_id) {
                    $brand = Brand::find($subbrand->brand_id);
                    if ($brand) {
                        \Log::info("Found Brand #{$brand->id} for SubBrand #{$subbrand->id}");
                    } else {
                        \Log::warning("Brand #{$subbrand->brand_id} not found in database for SubBrand #{$subbrand->id}");
                    }
                }
            }
        }
        
        // Paginate after the debug to ensure we've logged all issues
        $subBrands = SubBrand::with('brand')->latest()->paginate(10);
        
        return view('master.subbrands.index', compact('subBrands'));
    }

    /**
     * Show the form for creating a new sub brand.
     */
    public function create()
    {
        $brands = Brand::where('is_active', true)->get();
        
        // If no active brands exist, check if there are any brands regardless of status
        if ($brands->isEmpty()) {
            $brands = Brand::all();
            
            // If still no brands, create a default one
            if ($brands->isEmpty()) {
                // Check if there's a main category first
                $mainCategory = \App\Models\MainCategory::first();
                
                if (!$mainCategory) {
                    // Create a default main category if none exists
                    $mainCategory = \App\Models\MainCategory::create([
                        'name' => 'Default Category',
                        'description' => 'Automatically created default category',
                        'is_active' => true
                    ]);
                }
                
                // Create a default brand
                Brand::create([
                    'name' => 'Default Brand',
                    'main_category_id' => $mainCategory->id,
                    'description' => 'Automatically created default brand',
                    'is_active' => true
                ]);
                
                // Refresh the brands list
                $brands = Brand::where('is_active', true)->get();
            }
        }
        
        return view('master.subbrands.create', compact('brands'));
    }

    /**
     * Store a newly created sub brand in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'brand_id' => 'required|exists:brands,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                \Log::warning('SubBrand validation failed:', $validator->errors()->toArray());
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = [
                'name' => $request->name,
                'brand_id' => $request->brand_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Creating SubBrand with data:', $data);
            
            $subBrand = SubBrand::create($data);
            
            \Log::info('SubBrand created with ID: ' . $subBrand->id);

            return redirect()->route('subbrands.index')
                ->with('success', 'Sub Brand berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Failed to create SubBrand: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified sub brand.
     */
    public function show(SubBrand $subbrand)
    {
        $subbrand->load('brand', 'productCategories');
        return view('master.subbrands.show', compact('subbrand'));
    }

    /**
     * Show the form for editing the specified sub brand.
     */
    public function edit(SubBrand $subbrand)
    {
        $brands = Brand::where('is_active', true)->get();
        return view('master.subbrands.edit', compact('subbrand', 'brands'));
    }

    /**
     * Update the specified sub brand in storage.
     */
    public function update(Request $request, SubBrand $subbrand)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'brand_id' => 'required|exists:brands,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            if ($validator->fails()) {
                \Log::warning('SubBrand update validation failed:', $validator->errors()->toArray());
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $data = [
                'name' => $request->name,
                'brand_id' => $request->brand_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Updating SubBrand #' . $subbrand->id . ' with data:', $data);
            
            $subbrand->update($data);
            
            \Log::info('SubBrand #' . $subbrand->id . ' updated successfully');

            return redirect()->route('subbrands.index')
                ->with('success', 'Sub Brand berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Failed to update SubBrand #' . $subbrand->id . ': ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified sub brand from storage.
     */
    public function destroy(SubBrand $subbrand)
    {
        // Check if sub brand has associated product categories
        if ($subbrand->productCategories()->count() > 0) {
            return redirect()->route('subbrands.index')
                ->with('error', 'Sub Brand tidak dapat dihapus karena masih memiliki Kategori Produk.');
        }
        
        $subbrand->delete();

        return redirect()->route('subbrands.index')
            ->with('success', 'Sub Brand berhasil dihapus.');
    }

    /**
     * Fix subbrands with missing brands
     */
    public function fixMissingBrands()
    {
        $subBrands = SubBrand::whereNotNull('brand_id')->get();
        $fixedCount = 0;
        $notFixedCount = 0;
        
        foreach ($subBrands as $subbrand) {
            if (!$subbrand->brand) {
                $brand = Brand::find($subbrand->brand_id);
                
                if (!$brand) {
                    // Brand doesn't exist - create a default one if needed
                    $mainCategory = \App\Models\MainCategory::first();
                    
                    if (!$mainCategory) {
                        $mainCategory = \App\Models\MainCategory::create([
                            'name' => 'Default Category',
                            'description' => 'Auto-created default category', 
                            'is_active' => true
                        ]);
                    }
                    
                    $defaultBrand = Brand::firstOrCreate(
                        ['name' => 'Default Brand'],
                        [
                            'main_category_id' => $mainCategory->id,
                            'description' => 'Auto-created default brand', 
                            'is_active' => true
                        ]
                    );
                    
                    // Update the subbrand to use this brand
                    $subbrand->brand_id = $defaultBrand->id;
                    $subbrand->save();
                    $fixedCount++;
                    
                    \Log::info("Fixed SubBrand #{$subbrand->id} ({$subbrand->name}) by assigning to default brand #{$defaultBrand->id}");
                } else {
                    // This shouldn't happen unless there's a relationship issue
                    $notFixedCount++;
                    \Log::warning("SubBrand #{$subbrand->id} has brand_id {$subbrand->brand_id} which exists but isn't loading properly");
                }
            }
        }
        
        return response()->json([
            'message' => "Fixed $fixedCount subbrands. $notFixedCount subbrands had issues that couldn't be automatically fixed.",
            'fixed_count' => $fixedCount,
            'not_fixed_count' => $notFixedCount
        ]);
    }
}
