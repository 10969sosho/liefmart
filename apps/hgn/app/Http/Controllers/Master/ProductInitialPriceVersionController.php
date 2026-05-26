<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductInitialPriceVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductInitialPriceVersionController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('latestInitialPriceVersion');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('updated_at', 'desc')->paginate(25)->withQueryString();

        return view('master.products.initial_price.index', compact('products'));
    }

    public function show(Product $product)
    {
        $versions = ProductInitialPriceVersion::where('product_id', $product->id)
            ->orderBy('version', 'desc')
            ->get();

        $activeVersion = $versions->firstWhere('is_active', true);

        return view('master.products.initial_price.show', compact('product', 'versions', 'activeVersion'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'initial_price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'effective_at' => 'required|date',
            'change_reason' => 'nullable|string|max:255',
        ]);

        $effectiveAt = $validated['effective_at'];

        $currentActive = ProductInitialPriceVersion::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();

        if ($currentActive) {
            $currentFrom = $currentActive->valid_from ?? $currentActive->created_at;
            if ($currentFrom && \Carbon\Carbon::parse($effectiveAt)->lte($currentFrom)) {
                return back()
                    ->withInput()
                    ->with('error', 'Tanggal berlaku harus lebih besar dari versi aktif saat ini.');
            }
        }

        DB::transaction(function () use ($product, $validated, $effectiveAt) {
            ProductInitialPriceVersion::createNewVersionForProduct(
                $product,
                [
                    'initial_price' => $validated['initial_price'],
                    'discount_percentage' => $validated['discount_percentage'] ?? 0,
                ],
                $validated['change_reason'] ?? 'update',
                auth()->id(),
                $effectiveAt
            );

            Product::withoutEvents(function () use ($product, $validated) {
                $product->update([
                    'initial_price' => $validated['initial_price'],
                    'discount_percentage' => $validated['discount_percentage'] ?? 0,
                ]);
            });
        });

        return redirect()
            ->route('products.initial-price.show', $product->id)
            ->with('success', 'Versi harga awal berhasil dibuat.');
    }
}

