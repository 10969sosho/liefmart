<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productTypes = ProductType::with('productCategory')->paginate(10);
        return view('master.product-types.index', compact('productTypes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $productCategories = ProductCategory::where('is_active', true)->get();
        return view('master.product-types.create', compact('productCategories'));
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
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'product_category_id' => 'required|exists:product_categories,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            $data = [
                'name' => $request->name,
                'product_category_id' => $request->product_category_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Creating ProductType with data:', $data);
            
            $productType = ProductType::create($data);
            
            \Log::info('ProductType created with ID: ' . $productType->id);

            return redirect()->route('product-types.index')
                ->with('success', 'Tipe produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Failed to create ProductType: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
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
        $productType = ProductType::with(['productCategory', 'productVariants' => function($query) {
            $query->with('productSize');
        }])->findOrFail($id);
        
        return view('master.product-types.show', compact('productType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $productType = ProductType::findOrFail($id);
        $productCategories = ProductCategory::where('is_active', true)->get();
        return view('master.product-types.edit', compact('productType', 'productCategories'));
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
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'product_category_id' => 'required|exists:product_categories,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            $productType = ProductType::findOrFail($id);
            
            $data = [
                'name' => $request->name,
                'product_category_id' => $request->product_category_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Updating ProductType #' . $id . ' with data:', $data);
            
            $productType->update($data);
            
            \Log::info('ProductType #' . $id . ' updated successfully');

            return redirect()->route('product-types.index')
                ->with('success', 'Tipe produk berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Failed to update ProductType #' . $id . ': ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Store a newly created product type via API.
     */
    public function storeApi(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'product_category_id' => 'required|exists:product_categories,id',
                'description' => 'nullable|string',
            ]);

            $data = [
                'name' => $request->name,
                'product_category_id' => $request->product_category_id,
                'description' => $request->description,
                'is_active' => true,
            ];

            $productType = ProductType::create($data);

            return response()->json([
                'success' => true,
                'id' => $productType->id,
                'name' => $productType->name,
                'message' => 'Tipe Produk berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create ProductType via API: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
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
        $productType = ProductType::findOrFail($id);
        $productType->delete();

        return redirect()->route('product-types.index')
            ->with('success', 'Tipe produk berhasil dihapus.');
    }
}
