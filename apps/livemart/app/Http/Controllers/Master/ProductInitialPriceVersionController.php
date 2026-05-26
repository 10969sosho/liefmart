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
        $search = $request->input('search');

        $products = Product::query()
            ->when($search, function ($q) use ($search) {
                $search = trim($search);
                if ($search !== '') {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%')
                            ->orWhere('sku', 'like', '%' . $search . '%')
                            ->orWhere('barcode', 'like', '%' . $search . '%');
                    });
                }
            })
            ->with(['latestInitialPriceVersion'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('initial_price.index', compact('products', 'search'));
    }

    public function show(Product $product)
    {
        $versions = $product->initialPriceVersions()
            ->orderByDesc('version')
            ->get();

        $activeVersion = $product->initialPriceVersions()
            ->active()
            ->orderByDesc('version')
            ->first();

        return view('initial_price.show', compact('product', 'versions', 'activeVersion'));
    }

    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'initial_price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'effective_at' => 'required|date',
            'change_reason' => 'nullable|string|max:255',
        ]);

        $newPrice = $validated['initial_price'];
        $newDiscount = $validated['discount_percentage'] ?? $product->discount_percentage ?? 0;
        $validFrom = $validated['effective_at'];
        $reason = $validated['change_reason'] ?? null;

        DB::transaction(function () use ($product, $newPrice, $newDiscount, $validFrom, $reason) {
            $previousActive = ProductInitialPriceVersion::where('product_id', $product->id)
                ->active()
                ->orderByDesc('version')
                ->first();

            ProductInitialPriceVersion::where('product_id', $product->id)
                ->active()
                ->update([
                    'is_active' => false,
                    'valid_until' => $validFrom,
                ]);

            $latestVersion = (int) ProductInitialPriceVersion::where('product_id', $product->id)->max('version');

            ProductInitialPriceVersion::create([
                'product_id' => $product->id,
                'version' => $latestVersion + 1,
                'initial_price' => $newPrice,
                'discount_percentage' => $newDiscount,
                'is_active' => true,
                'valid_from' => $validFrom,
                'valid_until' => null,
                'parent_version_id' => $previousActive?->id,
                'change_reason' => $reason,
            ]);

            Product::withoutEvents(function () use ($product, $newPrice, $newDiscount) {
                $product->update([
                    'initial_price' => $newPrice,
                    'discount_percentage' => $newDiscount,
                ]);
            });
        });

        return redirect()
            ->route('products.initial-price.show', $product)
            ->with('success', 'Versi initial price baru berhasil dibuat.');
    }
}
