<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::withCount('subBrands');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $brands = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $brands,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands',
        ]);

        $brand = Brand::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Brand berhasil ditambahkan',
        ], 201);
    }

    public function show($id)
    {
        $brand = Brand::with('subBrands')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $brand,
        ]);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $id,
        ]);

        $brand->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Brand berhasil diupdate',
        ]);
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand berhasil dihapus',
        ]);
    }
}
