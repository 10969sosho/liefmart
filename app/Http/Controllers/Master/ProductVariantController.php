<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productVariants = ProductVariant::with('productSize')->paginate(10);
        return view('master.product-variants.index', compact('productVariants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $productSizes = ProductSize::where('is_active', true)->get();
        return view('master.product-variants.create', compact('productSizes'));
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
                'product_size_id' => 'required|exists:product_sizes,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            $data = [
                'name' => $request->name,
                'product_size_id' => $request->product_size_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Creating ProductVariant with data:', $data);
            
            $productVariant = ProductVariant::create($data);
            
            \Log::info('ProductVariant created with ID: ' . $productVariant->id);

            return redirect()->route('product-variants.index')
                ->with('success', 'Varian produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            \Log::error('Failed to create ProductVariant: ' . $e->getMessage());
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
        $productVariant = ProductVariant::with('productSize')->findOrFail($id);
        return view('master.product-variants.show', compact('productVariant'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $productVariant = ProductVariant::findOrFail($id);
        $productSizes = ProductSize::where('is_active', true)->get();
        return view('master.product-variants.edit', compact('productVariant', 'productSizes'));
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
            $productVariant = ProductVariant::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'product_size_id' => 'required|exists:product_sizes,id',
                'description' => 'nullable|string',
                'is_active' => 'nullable',
            ]);

            $data = [
                'name' => $request->name,
                'product_size_id' => $request->product_size_id,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? true : false,
            ];

            \Log::info('Updating ProductVariant #' . $id . ' with data:', $data);
            
            $productVariant->update($data);
            
            \Log::info('ProductVariant #' . $id . ' updated successfully');

            return redirect()->route('product-variants.index')
                ->with('success', 'Varian produk berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Failed to update ProductVariant #' . $id . ': ' . $e->getMessage());
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
        $productVariant = ProductVariant::findOrFail($id);
        $productVariant->delete();

        return redirect()->route('product-variants.index')
            ->with('success', 'Varian produk berhasil dihapus.');
    }
}
