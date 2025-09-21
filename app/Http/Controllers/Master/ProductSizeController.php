<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductSize;
use App\Models\ProductType;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productSizes = ProductSize::with('productType')->paginate(10);
        return view('master.product-sizes.index', compact('productSizes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $productTypes = ProductType::where('is_active', true)->get();
        return view('master.product-sizes.create', compact('productTypes'));
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
                'product_type_id' => 'required|exists:product_types,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            $data = [
                'name' => $request->name,
                'product_type_id' => $request->product_type_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Creating ProductSize with data:', $data);
            
            $productSize = ProductSize::create($data);
            
            \Log::info('ProductSize created with ID: ' . $productSize->id);

            return redirect()->route('product-sizes.index')
                ->with('success', 'Ukuran produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Failed to create ProductSize: ' . $e->getMessage());
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
        $productSize = ProductSize::with(['productType', 'productVariants'])->findOrFail($id);
        return view('master.product-sizes.show', compact('productSize'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $productSize = ProductSize::findOrFail($id);
        $productTypes = ProductType::where('is_active', true)->get();
        return view('master.product-sizes.edit', compact('productSize', 'productTypes'));
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
            $productSize = ProductSize::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'product_type_id' => 'required|exists:product_types,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            $data = [
                'name' => $request->name,
                'product_type_id' => $request->product_type_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Updating ProductSize #' . $id . ' with data:', $data);
            
            $productSize->update($data);
            
            \Log::info('ProductSize #' . $id . ' updated successfully');

            return redirect()->route('product-sizes.index')
                ->with('success', 'Ukuran produk berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Failed to update ProductSize #' . $id . ': ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
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
        $productSize = ProductSize::findOrFail($id);
        $productSize->delete();

        return redirect()->route('product-sizes.index')
            ->with('success', 'Ukuran produk berhasil dihapus.');
    }
}
