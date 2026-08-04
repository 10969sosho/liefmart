<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'product_category']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $perPage = $request->input('per_page', 20);
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products',
            'brand_id' => 'nullable|exists:brands,id',
            'initial_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $product = Product::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Produk berhasil ditambahkan',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan produk: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::with(['brand', 'product_category', 'sub_brand', 'product_type', 'product_size', 'product_variant'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $id,
            'brand_id' => 'nullable|exists:brands,id',
            'initial_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $product->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Produk berhasil diupdate',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate produk: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus',
        ]);
    }

    public function export($format)
    {
        // Mock export - in real implementation, use Laravel Excel
        $products = Product::with(['brand', 'product_category'])->get();
        
        if ($format === 'excel') {
            // Would use Maatwebsite\Excel
            return response()->json([
                'success' => true,
                'message' => 'Export functionality ready',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Format tidak didukung',
        ], 400);
    }
}
