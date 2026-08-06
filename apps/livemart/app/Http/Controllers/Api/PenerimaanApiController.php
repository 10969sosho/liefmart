<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penerimaan;
use App\Models\PenerimaanDetail;
use App\Models\Product;
use App\Models\Satuan;
use App\Models\TaxCategory;
use App\Models\MainCategory;
use Shared\Helpers\NumberFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenerimaanApiController extends Controller
{
    /**
     * Get all penerimaan with filters and pagination
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search', '');
            
            $query = Penerimaan::with(['mainCategory', 'taxCategory', 'lokasi'])
                ->when($search, function ($q) use ($search) {
                    return $q->where('kode_penerimaan', 'like', '%' . $search . '%')
                             ->orWhere('nomor_po', 'like', '%' . $search . '%');
                })
                ->orderBy('tanggal_penerimaan', 'desc');
            
            $penerimaan = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $penerimaan->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'kode_penerimaan' => $item->kode_penerimaan,
                        'nomor_po' => $item->nomor_po,
                        'tanggal_penerimaan' => $item->tanggal_penerimaan,
                        'main_category' => $item->mainCategory->name ?? null,
                        'tax_category' => $item->taxCategory->name ?? null,
                        'metode_pembayaran' => $item->metode_pembayaran,
                        'tanggal_jatuh_tempo' => $item->tanggal_jatuh_tempo,
                        'total_harga' => $item->total_harga,
                        'total_items' => $item->details()->count(),
                        'status' => $item->status,
                        'lokasi' => $item->lokasi->name ?? null,
                        'catatan' => $item->catatan,
                    ];
                }),
                'pagination' => [
                    'total' => $penerimaan->total(),
                    'per_page' => $penerimaan->perPage(),
                    'current_page' => $penerimaan->currentPage(),
                    'last_page' => $penerimaan->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in PenerimaanApiController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single penerimaan by ID
     */
    public function show($id)
    {
        try {
            $penerimaan = Penerimaan::with([
                'mainCategory',
                'taxCategory',
                'lokasi',
                'details.product',
                'details.satuan'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $penerimaan->id,
                    'kode_penerimaan' => $penerimaan->kode_penerimaan,
                    'main_category_id' => $penerimaan->main_category_id,
                    'tax_category_id' => $penerimaan->tax_category_id,
                    'nomor_po' => $penerimaan->nomor_po,
                    'tanggal_penerimaan' => $penerimaan->tanggal_penerimaan,
                    'metode_pembayaran' => $penerimaan->metode_pembayaran,
                    'tanggal_jatuh_tempo' => $penerimaan->tanggal_jatuh_tempo,
                    'total_harga' => $penerimaan->total_harga,
                    'status' => $penerimaan->status,
                    'catatan' => $penerimaan->catatan,
                    'lokasi_id' => $penerimaan->lokasi_id,
                    'main_category' => $penerimaan->mainCategory,
                    'tax_category' => $penerimaan->taxCategory,
                    'lokasi' => $penerimaan->lokasi,
                    'details' => $penerimaan->details->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'product_id' => $detail->product_id,
                            'product_name' => $detail->product->name ?? null,
                            'qty' => $detail->qty,
                            'satuan_id' => $detail->satuan_id,
                            'satuan_name' => $detail->satuan->name ?? null,
                            'harga_hpp' => $detail->harga_hpp,
                            'diskon_persen_1' => $detail->diskon_persen_1,
                            'diskon_nominal_1' => $detail->diskon_nominal_1,
                            'diskon_persen_2' => $detail->diskon_persen_2,
                            'diskon_nominal_2' => $detail->diskon_nominal_2,
                            'diskon_persen_3' => $detail->diskon_persen_3,
                            'diskon_nominal_3' => $detail->diskon_nominal_3,
                            'diskon_persen_4' => $detail->diskon_persen_4,
                            'diskon_nominal_4' => $detail->diskon_nominal_4,
                            'diskon_persen_5' => $detail->diskon_persen_5,
                            'diskon_nominal_5' => $detail->diskon_nominal_5,
                            'is_free' => $detail->is_free,
                            'subtotal' => $detail->subtotal,
                            'catatan' => $detail->catatan,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in PenerimaanApiController@show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new penerimaan header
     */
    public function createHeader(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'main_category_id' => 'required|exists:main_categories,id',
            'tax_category_id' => 'required|exists:tax_categories,id',
            'nomor_po' => 'required|string',
            'tanggal_penerimaan' => 'required|date',
            'metode_pembayaran' => 'required|in:Cash,Jatuh Tempo',
            'tanggal_jatuh_tempo' => 'required_if:metode_pembayaran,Jatuh Tempo|nullable|date',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $kode = $this->generateUniqueKodePenerimaan();

            $penerimaan = Penerimaan::create([
                'kode_penerimaan' => $kode,
                'main_category_id' => $request->main_category_id,
                'tax_category_id' => $request->tax_category_id,
                'nomor_po' => $request->nomor_po,
                'tanggal_penerimaan' => $request->tanggal_penerimaan,
                'metode_pembayaran' => $request->metode_pembayaran,
                'tanggal_jatuh_tempo' => $request->metode_pembayaran == 'Jatuh Tempo' ? $request->tanggal_jatuh_tempo : null,
                'total_harga' => 0,
                'status' => 'Unlocated',
                'catatan' => $request->catatan,
                'lokasi_id' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Header penerimaan berhasil dibuat',
                'data' => [
                    'id' => $penerimaan->id,
                    'kode_penerimaan' => $penerimaan->kode_penerimaan,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in PenerimaanApiController@createHeader: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat header penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store batch details
     */
    public function storeBatchDetails(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.harga_hpp' => 'required|numeric|min:0',
            'items.*.diskon_persen_1' => 'nullable|numeric|min:0|max:100',
            'items.*.diskon_persen_2' => 'nullable|numeric|min:0|max:100',
            'items.*.diskon_persen_3' => 'nullable|numeric|min:0|max:100',
            'items.*.diskon_persen_4' => 'nullable|numeric|min:0|max:100',
            'items.*.diskon_persen_5' => 'nullable|numeric|min:0|max:100',
            'items.*.diskon_nominal_1' => 'nullable|numeric|min:0',
            'items.*.diskon_nominal_2' => 'nullable|numeric|min:0',
            'items.*.diskon_nominal_3' => 'nullable|numeric|min:0',
            'items.*.diskon_nominal_4' => 'nullable|numeric|min:0',
            'items.*.diskon_nominal_5' => 'nullable|numeric|min:0',
            'items.*.is_free' => 'nullable|boolean',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $penerimaan = Penerimaan::findOrFail($id);

            // Hapus detail lama
            PenerimaanDetail::where('penerimaan_id', $penerimaan->id)->delete();

            $savedCount = 0;
            foreach ($request->items as $detail) {
                $isFree = isset($detail['is_free']) && $detail['is_free'] == 1;
                $harga = $isFree ? 0 : NumberFormatter::formatDecimal($detail['harga_hpp']);

                $subtotal = 0;
                if (!$isFree) {
                    $qty = NumberFormatter::formatDecimal($detail['qty']);
                    $subtotal = NumberFormatter::multiplyDecimal($qty, $harga);

                    // Hitung diskon bertingkat
                    for ($i = 1; $i <= 5; $i++) {
                        $diskonPersen = isset($detail["diskon_persen_$i"]) ? NumberFormatter::formatDecimal($detail["diskon_persen_$i"]) : 0;
                        $diskonNominal = isset($detail["diskon_nominal_$i"]) ? NumberFormatter::formatDecimal($detail["diskon_nominal_$i"]) : 0;

                        if ($diskonPersen > 0) {
                            $potongan = NumberFormatter::percentageOf($subtotal, $diskonPersen);
                            $subtotal = NumberFormatter::subtractDecimal($subtotal, $potongan);
                        } elseif ($diskonNominal > 0) {
                            $subtotal = NumberFormatter::subtractDecimal($subtotal, $diskonNominal);
                        }
                    }
                }

                PenerimaanDetail::create([
                    'penerimaan_id' => $penerimaan->id,
                    'product_id' => $detail['product_id'],
                    'qty' => NumberFormatter::formatDecimal($detail['qty']),
                    'satuan_id' => $detail['satuan_id'],
                    'harga_hpp' => $harga,
                    'diskon_persen_1' => NumberFormatter::formatDecimal($detail['diskon_persen_1'] ?? 0),
                    'diskon_nominal_1' => NumberFormatter::formatDecimal($detail['diskon_nominal_1'] ?? 0),
                    'diskon_persen_2' => NumberFormatter::formatDecimal($detail['diskon_persen_2'] ?? 0),
                    'diskon_nominal_2' => NumberFormatter::formatDecimal($detail['diskon_nominal_2'] ?? 0),
                    'diskon_persen_3' => NumberFormatter::formatDecimal($detail['diskon_persen_3'] ?? 0),
                    'diskon_nominal_3' => NumberFormatter::formatDecimal($detail['diskon_nominal_3'] ?? 0),
                    'diskon_persen_4' => NumberFormatter::formatDecimal($detail['diskon_persen_4'] ?? 0),
                    'diskon_nominal_4' => NumberFormatter::formatDecimal($detail['diskon_nominal_4'] ?? 0),
                    'diskon_persen_5' => NumberFormatter::formatDecimal($detail['diskon_persen_5'] ?? 0),
                    'diskon_nominal_5' => NumberFormatter::formatDecimal($detail['diskon_nominal_5'] ?? 0),
                    'is_free' => $isFree,
                    'subtotal' => $subtotal,
                    'catatan' => $detail['catatan'] ?? null,
                ]);
                $savedCount++;
            }

            // Recalculate total
            $penerimaan->recalculateTotalHarga();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menyimpan {$savedCount} item detail",
                'data' => [
                    'saved_count' => $savedCount,
                    'total_harga' => $penerimaan->fresh()->total_harga,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in PenerimaanApiController@storeBatchDetails: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan detail penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete penerimaan
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $penerimaan = Penerimaan::findOrFail($id);

            // Hanya boleh hapus jika statusnya masih Unlocated
            if ($penerimaan->status !== 'Unlocated') {
                return response()->json([
                    'success' => false,
                    'message' => 'Penerimaan yang sudah diproses (Located) tidak dapat dihapus.',
                ], 400);
            }

            // Hapus detail
            PenerimaanDetail::where('penerimaan_id', $id)->delete();

            // Hapus penerimaan
            $penerimaan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penerimaan berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in PenerimaanApiController@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus penerimaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tax categories by main category
     */
    public function getTaxCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'main_category_id' => 'required|exists:main_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $taxCategories = TaxCategory::where('main_category_id', $request->main_category_id)
                ->where('is_active', true)
                ->select('id', 'name', 'description', 'tax_percentage')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $taxCategories,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getTaxCategories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch tax categories',
            ], 500);
        }
    }

    /**
     * Get products
     */
    public function getProducts(Request $request)
    {
        try {
            $query = Product::where('is_active', true);
            
            if ($request->has('main_category_id')) {
                $query->where('main_category_id', $request->main_category_id);
            }
            
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $products = $query->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'default_satuan_id' => $product->default_satuan_id,
                    'tax_status' => $product->tax_status ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getProducts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch products'
            ], 500);
        }
    }

    /**
     * Get satuans
     */
    public function getSatuans()
    {
        try {
            $satuans = Satuan::where('is_active', true)->get(['id', 'name', 'code']);

            return response()->json([
                'success' => true,
                'data' => $satuans,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getSatuans: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch satuans'
            ], 500);
        }
    }

    /**
     * Get main categories
     */
    public function getMainCategories()
    {
        try {
            $categories = MainCategory::where('is_active', true)->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getMainCategories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch main categories'
            ], 500);
        }
    }

    /**
     * Generate unique kode penerimaan
     */
    private function generateUniqueKodePenerimaan(): string
    {
        $prefix = 'PNR-';
        $lastPenerimaan = Penerimaan::withoutGlobalScopes()
            ->where('kode_penerimaan', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastPenerimaan && preg_match('/^PNR-(\d+)$/', (string) $lastPenerimaan->kode_penerimaan, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        $candidate = $lastNumber + 1;
        while (true) {
            $kode = $prefix . str_pad((string) $candidate, 6, '0', STR_PAD_LEFT);
            if (!Penerimaan::withoutGlobalScopes()->where('kode_penerimaan', $kode)->exists()) {
                return $kode;
            }
            $candidate++;
        }
    }
}
