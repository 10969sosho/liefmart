<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MappingBarang;
use App\Models\Product;
use Illuminate\Http\Request;

class MappingApiController extends Controller
{
    public function index(Request $request)
    {
        $query = MappingBarang::with(['product']);

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('platform_product_name', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $mappings = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $mappings,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:shopee,shopee2,tiktok,tiktok2',
            'platform_product_name' => 'required|string',
            'product_id' => 'required|exists:products,id',
        ]);

        $mapping = MappingBarang::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $mapping->load('product'),
            'message' => 'Mapping berhasil ditambahkan',
        ], 201);
    }

    public function autoCreate($platform, $productName)
    {
        // Try to find matching product
        $product = Product::where('name', 'like', "%{$productName}%")->first();

        if ($product) {
            $mapping = MappingBarang::create([
                'platform' => $platform,
                'platform_product_name' => $productName,
                'product_id' => $product->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => $mapping->load('product'),
                'message' => 'Auto-mapping berhasil',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Produk tidak ditemukan untuk auto-mapping',
        ], 404);
    }
}
