<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\MainCategory;
use App\Models\ProductCategory;
use App\Models\ProductSize;
use App\Models\ProductType;
use App\Models\ProductVariant;
use App\Models\SubBrand;
use App\Models\TaxCategory;
use App\Models\WarehouseStock;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockExport;
use App\Exports\StockMutationExport;

class WarehouseStockController extends Controller
{
    public function list(Request $request)
    {
        $query = WarehouseStock::with([
            'product.mainCategory',
            'product.brand',
            'product.subBrand',
            'product.productCategory',
            'product.productType',
            'product.productSize',
            'product.productVariant',
            'penerimaanDetail.satuan',
            'lokasi',
            'tax'
        ]);

        // Show only non-damaged items by default
        $query->where('is_damaged', false);

        // Search filter
        if ($request->search) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // SKU filter
        if ($request->sku) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sku', 'like', '%' . $request->sku . '%');
            });
        }

        // Status ED filter
        if ($request->status_ed) {
            switch ($request->status_ed) {
                case 'kadaluarsa':
                    $query->where('expired_date', '<', now());
                    break;
                case 'kurang_dari_3_bulan':
                    $query->whereBetween('expired_date', [now(), now()->addMonths(3)]);
                    break;
                case 'kurang_dari_6_bulan':
                    $query->whereBetween('expired_date', [now()->addMonths(3), now()->addMonths(6)]);
                    break;
                case 'kurang_dari_1_tahun':
                    $query->whereBetween('expired_date', [now()->addMonths(6), now()->addYear()]);
                    break;
                case 'lebih_dari_1_tahun':
                    $query->where('expired_date', '>', now()->addYear());
                    break;
                case 'tidak_ada_ed':
                    $query->whereNull('expired_date');
                    break;
            }
        }

        // Tax filter
        if ($request->tax_id) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }

        // Free item filter
        if ($request->is_free !== null && $request->is_free !== '') {
            $query->whereHas('penerimaanDetail', function ($q) use ($request) {
                $q->where('is_free', (bool) $request->is_free);
            });
        }

        // Advanced filters
        if ($request->brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->sub_brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sub_brand_id', $request->sub_brand_id);
            });
        }

        if ($request->product_category_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        if ($request->product_type_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_type_id', $request->product_type_id);
            });
        }

        if ($request->product_size_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_size_id', $request->product_size_id);
            });
        }

        if ($request->product_variant_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_variant_id', $request->product_variant_id);
            });
        }

        // Clone query for summary cards (without pagination)
        $summaryQuery = clone $query;
        $filteredStocks = $summaryQuery->get();

        // Process ED status for summary cards
        $today = now();
        foreach ($filteredStocks as $stock) {
            if ($stock->expired_date) {
                $diffInDays = $today->diffInDays($stock->expired_date, false);

                if ($diffInDays < 0) {
                    $stock->ed_status = 'kadaluarsa';
                } elseif ($diffInDays <= 90) {
                    $stock->ed_status = 'kurang_dari_3_bulan';
                } elseif ($diffInDays <= 180) {
                    $stock->ed_status = 'kurang_dari_6_bulan';
                } elseif ($diffInDays <= 365) {
                    $stock->ed_status = 'kurang_dari_1_tahun';
                } else {
                    $stock->ed_status = 'lebih_dari_1_tahun';
                }
            } else {
                $stock->ed_status = 'tidak_ada_ed';
            }
            $stock->is_free = $stock->penerimaanDetail && $stock->penerimaanDetail->is_free;
        }

        // Paginate for table display
        $stocks = $query->paginate(25)->withQueryString();

        // Process ED status for paginated results
        foreach ($stocks as $stock) {
            if ($stock->expired_date) {
                $diffInDays = $today->diffInDays($stock->expired_date, false);

                if ($diffInDays < 0) {
                    $stock->ed_status = 'kadaluarsa';
                } elseif ($diffInDays <= 90) {
                    $stock->ed_status = 'kurang_dari_3_bulan';
                } elseif ($diffInDays <= 180) {
                    $stock->ed_status = 'kurang_dari_6_bulan';
                } elseif ($diffInDays <= 365) {
                    $stock->ed_status = 'kurang_dari_1_tahun';
                } else {
                    $stock->ed_status = 'lebih_dari_1_tahun';
                }
            } else {
                $stock->ed_status = 'tidak_ada_ed';
            }
            $stock->is_free = $stock->penerimaanDetail && $stock->penerimaanDetail->is_free;
        }

        return view('warehouse.stock-list', [
            'stocks' => $stocks,
            'filteredStocks' => $filteredStocks,
            'brands' => Brand::where('is_active', true)->get(),
            'subBrands' => SubBrand::where('is_active', true)->get(),
            'productCategories' => ProductCategory::where('is_active', true)->get(),
            'productTypes' => ProductType::where('is_active', true)->get(),
            'productSizes' => ProductSize::where('is_active', true)->get(),
            'productVariants' => ProductVariant::where('is_active', true)->get(),
            'taxCategories' => TaxCategory::where('is_active', true)->get(),
            'isDamaged' => false,
        ]);
    }

    /**
     * Display the damaged items in the warehouse
     */
    public function damagedList(Request $request)
    {
        $query = WarehouseStock::with([
            'product.mainCategory',
            'product.brand',
            'product.subBrand',
            'product.productCategory',
            'product.productType',
            'product.productSize',
            'product.productVariant',
            'penerimaanDetail.satuan',
            'lokasi',
            'tax'
        ]);

        // Show only damaged items
        $query->where('is_damaged', true);

        // Search filter
        if ($request->search) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // SKU filter
        if ($request->sku) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sku', 'like', '%' . $request->sku . '%');
            });
        }
        
        // Status ED filter
        if ($request->status_ed) {
            switch ($request->status_ed) {
                case 'kadaluarsa':
                    $query->where('expired_date', '<', now());
                    break;
                case 'kurang_dari_3_bulan':
                    $query->whereBetween('expired_date', [now(), now()->addMonths(3)]);
                    break;
                case 'kurang_dari_6_bulan':
                    $query->whereBetween('expired_date', [now()->addMonths(3), now()->addMonths(6)]);
                    break;
                case 'kurang_dari_1_tahun':
                    $query->whereBetween('expired_date', [now()->addMonths(6), now()->addYear()]);
                    break;
                case 'lebih_dari_1_tahun':
                    $query->where('expired_date', '>', now()->addYear());
                    break;
                case 'tidak_ada_ed':
                    $query->whereNull('expired_date');
                    break;
            }
        }

        // Tax filter
        if ($request->tax_id) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }

        // Free item filter
        if ($request->is_free !== null && $request->is_free !== '') {
            $query->whereHas('penerimaanDetail', function ($q) use ($request) {
                $q->where('is_free', (bool) $request->is_free);
            });
        }

        // Advanced filters
        if ($request->brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->sub_brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sub_brand_id', $request->sub_brand_id);
            });
        }

        if ($request->product_category_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        if ($request->product_type_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_type_id', $request->product_type_id);
            });
        }

        if ($request->product_size_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_size_id', $request->product_size_id);
            });
        }

        if ($request->product_variant_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_variant_id', $request->product_variant_id);
            });
        }
        
        // Clone query for summary cards (without pagination)
        $summaryQuery = clone $query;
        $filteredStocks = $summaryQuery->get();

        // Process ED status for summary cards
        $today = now();
        foreach ($filteredStocks as $stock) {
            if ($stock->expired_date) {
                $diffInDays = $today->diffInDays($stock->expired_date, false);

                if ($diffInDays < 0) {
                    $stock->ed_status = 'kadaluarsa';
                } elseif ($diffInDays <= 90) {
                    $stock->ed_status = 'kurang_dari_3_bulan';
                } elseif ($diffInDays <= 180) {
                    $stock->ed_status = 'kurang_dari_6_bulan';
                } elseif ($diffInDays <= 365) {
                    $stock->ed_status = 'kurang_dari_1_tahun';
                } else {
                    $stock->ed_status = 'lebih_dari_1_tahun';
                }
            } else {
                $stock->ed_status = 'tidak_ada_ed';
            }
            $stock->is_free = $stock->penerimaanDetail && $stock->penerimaanDetail->is_free;
        }
        
        // Get all damaged stock with pagination
        $stocks = $query->paginate(25)->withQueryString();

        // Process ED status for paginated results
        foreach ($stocks as $stock) {
            if ($stock->expired_date) {
                $diffInDays = $today->diffInDays($stock->expired_date, false);

                if ($diffInDays < 0) {
                    $stock->ed_status = 'kadaluarsa';
                } elseif ($diffInDays <= 90) {
                    $stock->ed_status = 'kurang_dari_3_bulan';
                } elseif ($diffInDays <= 180) {
                    $stock->ed_status = 'kurang_dari_6_bulan';
                } elseif ($diffInDays <= 365) {
                    $stock->ed_status = 'kurang_dari_1_tahun';
                } else {
                    $stock->ed_status = 'lebih_dari_1_tahun';
                }
            } else {
                $stock->ed_status = 'tidak_ada_ed';
            }
            $stock->is_free = $stock->penerimaanDetail && $stock->penerimaanDetail->is_free;
        }
        
        // Get retur information
        $returPenjualanDetails = \App\Models\ReturPenjualanDetail::with(['returPenjualan', 'product'])
            ->where('kondisi', 'RUSAK')
            ->whereHas('returPenjualan', function($q) {
                $q->where('status', 'selesai');
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        // Get offline retur information
        $returOfflineDetails = \App\Models\ReturOfflineSaleDetail::with(['returOfflineSale', 'product'])
            ->where('kondisi', 'RUSAK')
            ->whereHas('returOfflineSale', function($q) {
                $q->where('status', 'selesai');
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('warehouse.stock-list', [
            'stocks' => $stocks,
            'filteredStocks' => $filteredStocks,
            'brands' => Brand::where('is_active', true)->get(),
            'subBrands' => SubBrand::where('is_active', true)->get(),
            'productCategories' => ProductCategory::where('is_active', true)->get(),
            'productTypes' => ProductType::where('is_active', true)->get(),
            'productSizes' => ProductSize::where('is_active', true)->get(),
            'productVariants' => ProductVariant::where('is_active', true)->get(),
            'taxCategories' => TaxCategory::where('is_active', true)->get(),
            'isDamaged' => true,
            'returPenjualanDetails' => $returPenjualanDetails,
            'returOfflineDetails' => $returOfflineDetails,
        ]);
    }

    public function export(Request $request)
    {
        // Query data dengan filter yang sama seperti method list
        $query = WarehouseStock::with([
            'product.mainCategory',
            'product.brand',
            'product.subBrand',
            'product.productCategory',
            'product.productType',
            'product.productSize',
            'product.productVariant',
            'penerimaanDetail.satuan',
            'penerimaanDetail.penerimaan',
            'lokasi',
            'tax'
        ]);
        
        // Filter based on damaged status
        if ($request->has('is_damaged')) {
            $query->where('is_damaged', $request->is_damaged);
        }
        
        // Apply search filter
        if ($request->search) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Status ED filter
        if ($request->status_ed) {
            switch ($request->status_ed) {
                case 'kadaluarsa':
                    $query->where('expired_date', '<', now());
                    break;
                case 'kurang_dari_3_bulan':
                    $query->whereBetween('expired_date', [now(), now()->addMonths(3)]);
                    break;
                case 'kurang_dari_6_bulan':
                    $query->whereBetween('expired_date', [now()->addMonths(3), now()->addMonths(6)]);
                    break;
                case 'kurang_dari_1_tahun':
                    $query->whereBetween('expired_date', [now()->addMonths(6), now()->addYear()]);
                    break;
                case 'lebih_dari_1_tahun':
                    $query->where('expired_date', '>', now()->addYear());
                    break;
                case 'tidak_ada_ed':
                    $query->whereNull('expired_date');
                    break;
            }
        }

        // Tax filter
        if ($request->tax_id) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        
        // Advanced filters
        if ($request->main_category_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('main_category_id', $request->main_category_id);
            });
        }

        if ($request->brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->sub_brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sub_brand_id', $request->sub_brand_id);
            });
        }

        if ($request->product_category_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        if ($request->product_type_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_type_id', $request->product_type_id);
            });
        }

        if ($request->product_size_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_size_id', $request->product_size_id);
            });
        }

        if ($request->product_variant_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_variant_id', $request->product_variant_id);
            });
        }
        
        // Ambil semua data untuk export
        $allStocks = $query->get();
        
        // Kelompokkan berdasarkan product_id dan akumulasi qty
        $groupedStocks = $allStocks->groupBy('product_id')->map(function ($stocks) {
            $firstStock = $stocks->first();
            
            // Akumulasi qty berdasarkan produk yang sama
            $totalQty = $stocks->sum('qty');
            
            // Buat salinan dari stock pertama untuk menyimpan data akumulasi
            $aggregatedStock = clone $firstStock;
            $aggregatedStock->qty = $totalQty;
            
            return $aggregatedStock;
        })->values();
        
        // Tambahkan status ED
        $today = now();
        foreach ($groupedStocks as $stock) {
            if ($stock->expired_date) {
                $diffInDays = $today->diffInDays($stock->expired_date, false);

                if ($diffInDays < 0) {
                    $stock->ed_status = 'Kadaluarsa';
                } elseif ($diffInDays <= 90) {
                    $stock->ed_status = '< 3 Bulan';
                } elseif ($diffInDays <= 180) {
                    $stock->ed_status = '< 6 Bulan';
                } elseif ($diffInDays <= 365) {
                    $stock->ed_status = '< 1 Tahun';
                } else {
                    $stock->ed_status = '> 1 Tahun';
                }
            } else {
                $stock->ed_status = 'Tanpa ED';
            }
            
            // Set the is_free flag - dinonaktifkan karena tidak ada kolom is_free di warehouse_stock
            // $stock->is_free = $stock->penerimaanDetail && $stock->penerimaanDetail->is_free;
        }
        
        $filename = $request->has('is_damaged') && $request->is_damaged ? 
            'stock-rusak-'.date('Y-m-d').'.xlsx' : 
            'stock-gudang-'.date('Y-m-d').'.xlsx';
        
        return Excel::download(new StockExport($groupedStocks), $filename);
    }

    /**
     * Display analytics of stock with consolidated view
     */
    public function analytics(Request $request)
    {
        // Build base query with all needed relationships
        $query = WarehouseStock::with([
            'product.mainCategory',
            'product.brand',
            'product.subBrand',
            'product.productCategory',
            'product.productType',
            'product.productSize',
            'product.productVariant',
            'penerimaanDetail.satuan',
            'penerimaanDetail.penerimaan',
            'lokasi',
            'tax'
        ]);

        // Show only non-damaged items by default unless specifically requesting damaged items
        if ($request->has('is_damaged')) {
            $query->where('is_damaged', $request->is_damaged ? true : false);
        } else {
            $query->where('is_damaged', false);
        }

        // Apply all the same filters as the regular stock list
        // Search filter
        if ($request->search) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // SKU filter
        if ($request->sku) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sku', 'like', '%' . $request->sku . '%');
            });
        }

        // Status ED filter
        if ($request->status_ed) {
            switch ($request->status_ed) {
                case 'kadaluarsa':
                    $query->where('expired_date', '<', now());
                    break;
                case 'kurang_dari_3_bulan':
                    $query->whereBetween('expired_date', [now(), now()->addMonths(3)]);
                    break;
                case 'kurang_dari_6_bulan':
                    $query->whereBetween('expired_date', [now()->addMonths(3), now()->addMonths(6)]);
                    break;
                case 'kurang_dari_1_tahun':
                    $query->whereBetween('expired_date', [now()->addMonths(6), now()->addYear()]);
                    break;
                case 'lebih_dari_1_tahun':
                    $query->where('expired_date', '>', now()->addYear());
                    break;
                case 'tidak_ada_ed':
                    $query->whereNull('expired_date');
                    break;
            }
        }

        // Tax filter
        if ($request->tax_id) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }

        // Advanced filters
        if ($request->main_category_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('main_category_id', $request->main_category_id);
            });
        }

        if ($request->brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->sub_brand_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('sub_brand_id', $request->sub_brand_id);
            });
        }

        if ($request->product_category_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_category_id', $request->product_category_id);
            });
        }

        if ($request->product_type_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_type_id', $request->product_type_id);
            });
        }

        if ($request->product_size_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_size_id', $request->product_size_id);
            });
        }

        if ($request->product_variant_id) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('product_variant_id', $request->product_variant_id);
            });
        }

        // Get all stocks matching the criteria
        $allStocks = $query->get();

        // Group stocks by product ID and aggregate quantities
        $groupedStocks = $allStocks->groupBy('product_id')->map(function ($stocks) {
            $firstStock = $stocks->first();
            
            return [
                'product' => $firstStock->product,
                'total_qty' => $stocks->sum('qty'),
                'locations' => $stocks->groupBy('lokasi_id')->map(function ($locStocks, $locId) {
                    return [
                        'lokasi' => $locStocks->first()->lokasi,
                        'qty' => $locStocks->sum('qty')
                    ];
                })->values(),
                'stock_items' => $stocks,
                'expired_dates_count' => $stocks->whereNotNull('expired_date')->unique('expired_date')->count(),
                'has_expired' => $stocks->where('expired_date', '<', now())->count() > 0,
                'has_damaged' => $stocks->where('is_damaged', true)->count() > 0 || $stocks->sum('qty_damaged') > 0,
                'earliest_expiry' => $stocks->whereNotNull('expired_date')->min('expired_date'),
                'tax_categories' => $stocks->groupBy('tax_id')->map(function ($taxStocks, $taxId) {
                    return [
                        'tax' => $taxStocks->first()->tax,
                        'qty' => $taxStocks->sum('qty')
                    ];
                })->values(),
            ];
        })->values();
        
        // Calculate damaged goods count separately (for the card)
        $damagedQuery = WarehouseStock::with(['product'])
            ->where(function($q) {
                $q->where('is_damaged', true)->orWhere('qty_damaged', '>', 0);
            });
        
        // Apply same filters to damaged query as main query
        if ($request->search) {
            $damagedQuery->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->sku) {
            $damagedQuery->whereHas('product', function ($q) use ($request) {
                $q->where('sku', 'like', '%' . $request->sku . '%');
            });
        }
        
        // Apply other filters to damaged query
        if ($request->main_category_id) {
            $damagedQuery->whereHas('product', function ($q) use ($request) {
                $q->where('main_category_id', $request->main_category_id);
            });
        }
        
        if ($request->brand_id) {
            $damagedQuery->whereHas('product', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }
        
        $damagedProductsCount = $damagedQuery->distinct('product_id')->count();
        
        // Build product stock history data (for AJAX fetch later)
        if ($request->ajax() && $request->has('product_id')) {
            $productId = $request->product_id;
            
            // Stock items for this product - include all stock movements
            // Order by actual transaction date from newest to oldest
            $stockItems = WarehouseStock::where('product_id', $productId)
                ->with([
                    'lokasi', 
                    'tax',
                    'penerimaanDetail.penerimaan',
                    'penerimaanDetail.satuan',
                    'returPenjualan.order.platform',
                    'returOfflineSale.offlineSale'
                ])
                ->orderByRaw('
                    CASE 
                        WHEN warehouse_stock.source_date IS NOT NULL THEN warehouse_stock.source_date
                        WHEN warehouse_stock.penerimaan_detail_id IS NOT NULL THEN (
                            SELECT tanggal_penerimaan 
                            FROM penerimaan 
                            WHERE id = (
                                SELECT penerimaan_id 
                                FROM penerimaan_detail 
                                WHERE id = warehouse_stock.penerimaan_detail_id
                            )
                        )
                        ELSE warehouse_stock.created_at
                    END DESC
                ')
                ->get();
            
            // For "Barang Masuk" display, we should show the ORIGINAL quantity that was received
            // DO NOT modify warehouse_stock.qty as it represents current stock, not original receipt
            // The view will handle displaying the correct quantity for each purpose:
            // - For "Barang Masuk": use penerimaan_detail.qty (original received quantity)
            // - For "Returns": use warehouse_stock.qty (actual returned quantity)
            // - For "Current Stock": use warehouse_stock.qty (current available quantity)
            
            // Keep warehouse_stock.qty as-is for current stock reference
            // The view will access penerimaan_detail.qty directly when needed
            
            // Relasi retur data sudah dimuat otomatis melalui eager loading di atas
                
            // Stock outgoing history - include all barang keluar records from newest to oldest
            $stockOutItems = BarangKeluar::whereHas('warehouseStock', function($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->with([
                'warehouseStock.penerimaanDetail.penerimaan',
                'warehouseStock.penerimaanDetail.satuan',
                'warehouseStock.lokasi',
                'warehouseStock.tax',
                'orderItem.order',
                'offlineSaleItem.offlineSale',
                'offlineSaleItem.offlineSale.customerInfo'
            ])
            ->orderBy('barang_keluar.tanggal_keluar', 'desc')
            ->get();
            
            // Enhanced debug logging
            $product = \App\Models\Product::find($productId);
            
            \Log::info('Stock Analytics AJAX Request', [
                'product_id' => $productId,
                'product_found' => $product ? true : false,
                'product_name' => $product ? $product->name : 'N/A',
                'product_sku' => $product ? $product->sku : 'N/A',
                'stock_in_count' => $stockItems->count(),
                'stock_out_count' => $stockOutItems->count(),
                'stock_in_sample' => $stockItems->take(3)->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'qty' => $item->qty,
                        'source_type' => $item->source_type,
                        'source_date' => $item->source_date,
                        'created_at' => $item->created_at,
                        'lokasi_nama' => $item->lokasi ? $item->lokasi->nama : null,
                        'penerimaan_detail' => $item->penerimaanDetail ? [
                            'id' => $item->penerimaanDetail->id,
                            'penerimaan_id' => $item->penerimaanDetail->penerimaan_id
                        ] : null
                    ];
                })->toArray(),
                'stock_out_sample' => $stockOutItems->take(3)->map(function($item) {
                    return [
                        'id' => $item->id,
                        'qty' => $item->qty,
                        'tanggal_keluar' => $item->tanggal_keluar,
                        'kode_barang_keluar' => $item->kode_barang_keluar,
                        'warehouse_stock_id' => $item->warehouse_stock_id
                    ];
                })->toArray(),
                'total_warehouse_stocks' => \App\Models\WarehouseStock::where('product_id', $productId)->count(),
                'total_barang_keluar' => \App\Models\BarangKeluar::whereHas('warehouseStock', function($q) use ($productId) {
                    $q->where('product_id', $productId);
                })->count()
            ]);
            
            return response()->json([
                'success' => true,
                'stock_in' => $stockItems,
                'stock_out' => $stockOutItems,
            ]);
        }

        // Paginate the grouped results
        $perPage = 25;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = $groupedStocks->count();
        
        $paginatedStocks = $groupedStocks->slice($offset, $perPage)->values();
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedStocks,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('warehouse.stock-analytics', [
            'groupedStocks' => $paginator,
            'mainCategories' => MainCategory::where('is_active', true)->get(),
            'brands' => Brand::where('is_active', true)->get(),
            'subBrands' => SubBrand::where('is_active', true)->get(),
            'productCategories' => ProductCategory::where('is_active', true)->get(),
            'productTypes' => ProductType::where('is_active', true)->get(),
            'productSizes' => ProductSize::where('is_active', true)->get(),
            'productVariants' => ProductVariant::where('is_active', true)->get(),
            'taxCategories' => TaxCategory::where('is_active', true)->get(),
            'isDamaged' => $request->has('is_damaged') && $request->is_damaged,
            'totalItems' => $total,
            'totalQuantity' => $groupedStocks->sum('total_qty'),
            'damagedProductsCount' => $damagedProductsCount,
        ]);
    }

    /**
     * Export selected products with their stock mutations
     */
    public function exportSelected(Request $request)
    {
        // Validate the request
        $request->validate([
            'selected_products' => 'required|string',
        ]);
        
        // Decode the JSON string to get product IDs
        $productIds = json_decode($request->selected_products, true);
        
        if (empty($productIds) || !is_array($productIds)) {
            return redirect()->back()->with('error', 'Tidak ada produk yang dipilih untuk diekspor.');
        }
        
        // Get the selected products with their data
        $query = WarehouseStock::with([
            'product.mainCategory',
            'product.brand',
            'product.subBrand',
            'product.productCategory',
            'product.productType',
            'product.productSize',
            'product.productVariant',
            'penerimaanDetail.satuan',
            'penerimaanDetail.penerimaan',
            'lokasi',
            'tax'
        ])
        ->whereIn('product_id', $productIds);
        
        // Apply damaged filter if provided
        if ($request->has('is_damaged')) {
            $query->where('is_damaged', $request->is_damaged ? true : false);
        } else {
            $query->where('is_damaged', false);
        }
        
        // Apply additional filters if provided
        
        // Status ED filter
        if ($request->status_ed) {
            switch ($request->status_ed) {
                case 'kadaluarsa':
                    $query->where('expired_date', '<', now());
                    break;
                case 'kurang_dari_3_bulan':
                    $query->whereBetween('expired_date', [now(), now()->addMonths(3)]);
                    break;
                case 'kurang_dari_6_bulan':
                    $query->whereBetween('expired_date', [now()->addMonths(3), now()->addMonths(6)]);
                    break;
                case 'kurang_dari_1_tahun':
                    $query->whereBetween('expired_date', [now()->addMonths(6), now()->addYear()]);
                    break;
                case 'lebih_dari_1_tahun':
                    $query->where('expired_date', '>', now()->addYear());
                    break;
                case 'tidak_ada_ed':
                    $query->whereNull('expired_date');
                    break;
            }
        }

        // Tax filter
        if ($request->tax_id) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        
        // Location filter
        if ($request->lokasi_id) {
            $query->where('lokasi_id', $request->lokasi_id);
        }
        
        // Get all stocks matching the criteria
        $allStocks = $query->get();
        
        // If no stocks found, redirect back with error
        if ($allStocks->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data stok untuk produk yang dipilih.');
        }
        
        // Group stocks by product ID and aggregate quantities
        $groupedStocks = $allStocks->groupBy('product_id')->map(function ($stocks) {
            $firstStock = $stocks->first();
            
            $today = now();
            $hasExpired = false;
            $earliestExpiry = null;
            
            foreach ($stocks as $stock) {
                if ($stock->expired_date) {
                    $expDate = \Carbon\Carbon::parse($stock->expired_date);
                    
                    if ($expDate < $today) {
                        $hasExpired = true;
                    }
                    
                    if (!$earliestExpiry || $expDate < $earliestExpiry) {
                        $earliestExpiry = $expDate;
                    }
                }
            }
            
            return [
                'product' => $firstStock->product,
                'total_qty' => $stocks->sum('qty'),
                'locations' => $stocks->groupBy('lokasi_id')->map(function ($locStocks, $locId) {
                    return [
                        'lokasi' => $locStocks->first()->lokasi,
                        'qty' => $locStocks->sum('qty')
                    ];
                })->values(),
                'stock_items' => $stocks,
                'expired_dates_count' => $stocks->whereNotNull('expired_date')->unique('expired_date')->count(),
                'has_expired' => $hasExpired,
                'earliest_expiry' => $earliestExpiry
            ];
        })->values();
        
        // Format date for filename
        $dateFormatted = date('Y-m-d');
        
        // Add filters to filename if applied
        $filenamePrefix = 'mutasi-stok';
        if ($request->has('is_damaged') && $request->is_damaged) {
            $filenamePrefix = 'mutasi-stok-rusak';
        }
        
        // Generate the Excel file
        $filename = $filenamePrefix . '-' . $dateFormatted . '.xlsx';
        
        return Excel::download(
            new StockMutationExport(
                $groupedStocks, 
                $request->start_date, 
                $request->end_date, 
                $request->has('include_empty')
            ), 
            $filename
        );
    }
} 