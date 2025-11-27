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

        // Tax filter - only apply if explicitly provided
        if ($request->filled('tax_id')) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        // If no tax_id filter, include ALL tax_id values (both PKP and non-PKP)

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

        // Tax filter - only apply if explicitly provided
        if ($request->filled('tax_id')) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        // If no tax_id filter, include ALL tax_id values (both PKP and non-PKP)

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
        // Query data dengan filter yang sama seperti method analytics
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
        
        // Filter based on damaged status - default to false (non-damaged) like analytics method
        if ($request->has('is_damaged')) {
            $query->where('is_damaged', $request->is_damaged ? true : false);
        } else {
            // Default: show only non-damaged items (same as analytics method)
            $query->where('is_damaged', false);
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

        // Tax filter - only apply if explicitly provided
        if ($request->filled('tax_id')) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        // If no tax_id filter, include ALL tax_id values (both PKP and non-PKP)
        
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
        
        // Kelompokkan berdasarkan product_id + tax_id untuk menampilkan breakdown per tax_id
        // Ini memastikan LM dan HGN muncul sebagai baris terpisah seperti di stock-list
        $groupedStocks = $allStocks->groupBy(function($stock) {
            // Group by product_id + tax_id (null dianggap sebagai string 'null' untuk grouping)
            return $stock->product_id . '_' . ($stock->tax_id ?? 'null');
        })->map(function ($stocks) {
            $firstStock = $stocks->first();
            
            // Akumulasi qty berdasarkan kombinasi product_id + tax_id yang sama
            // Include all quantities including 0
            $totalQty = $stocks->sum(function($stock) {
                return $stock->qty;
            });
            
            // Buat salinan dari stock pertama untuk menyimpan data akumulasi
            $aggregatedStock = clone $firstStock;
            $aggregatedStock->qty = $totalQty;
            
            return $aggregatedStock;
        })->filter(function($stock) {
            // Filter out null values only (keep stocks with qty = 0)
            return $stock !== null;
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
        
        // Calculate total inventory value (harga beli x qty)
        $totalInventoryValue = 0;
        foreach ($allStocks as $stock) {
            if ($stock->penerimaanDetail) {
                $penerimaanDetail = $stock->penerimaanDetail;
                
                // Calculate HPP after discounts (same logic as in analytics)
                $hppAsli = $penerimaanDetail->harga_hpp ?? 0;
                $hppSetelahDiskon = $hppAsli;
                
                // Apply percentage discounts in sequence
                if ($penerimaanDetail->diskon_persen_1 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_1 / 100);
                }
                if ($penerimaanDetail->diskon_persen_2 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_2 / 100);
                }
                if ($penerimaanDetail->diskon_persen_3 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_3 / 100);
                }
                if ($penerimaanDetail->diskon_persen_4 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_4 / 100);
                }
                if ($penerimaanDetail->diskon_persen_5 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_5 / 100);
                }
                
                // Apply nominal discounts
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_1 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_2 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_3 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_4 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_5 ?? 0);
                
                // Ensure price doesn't go negative
                $finalHpp = max(0, $hppSetelahDiskon);
                
                // Add to total: harga beli x qty
                $totalInventoryValue += $finalHpp * $stock->qty;
            }
        }
        
        $filename = $request->has('is_damaged') && $request->is_damaged ? 
            'stock-rusak-'.date('Y-m-d').'.xlsx' : 
            'stock-gudang-'.date('Y-m-d').'.xlsx';
        
        return Excel::download(new StockExport($groupedStocks, $totalInventoryValue), $filename);
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

        // Tax filter - only apply if explicitly provided
        if ($request->filled('tax_id')) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        // If no tax_id filter, include ALL tax_id values (both PKP and non-PKP)

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
            
            // Calculate total quantity based on current warehouse stock
            // Include all quantities including 0
            $totalQty = $stocks->sum('qty');
            
            return [
                'product' => $firstStock->product,
                'total_qty' => $totalQty,
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
        
        // Calculate total inventory value (harga beli x qty)
        $totalInventoryValue = 0;
        foreach ($allStocks as $stock) {
            if ($stock->penerimaanDetail) {
                $penerimaanDetail = $stock->penerimaanDetail;
                
                // Calculate HPP after discounts (same logic as in view)
                $hppAsli = $penerimaanDetail->harga_hpp ?? 0;
                $hppSetelahDiskon = $hppAsli;
                
                // Apply percentage discounts in sequence
                if ($penerimaanDetail->diskon_persen_1 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_1 / 100);
                }
                if ($penerimaanDetail->diskon_persen_2 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_2 / 100);
                }
                if ($penerimaanDetail->diskon_persen_3 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_3 / 100);
                }
                if ($penerimaanDetail->diskon_persen_4 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_4 / 100);
                }
                if ($penerimaanDetail->diskon_persen_5 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_5 / 100);
                }
                
                // Apply nominal discounts
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_1 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_2 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_3 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_4 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_5 ?? 0);
                
                // Ensure price doesn't go negative
                $finalHpp = max(0, $hppSetelahDiskon);
                
                // Add to total: harga beli x qty
                $totalInventoryValue += $finalHpp * $stock->qty;
            }
        }
        
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
                    'returPenjualan.details',
                    'returOfflineSale.offlineSale',
                    'returOfflineSale.details'
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
            
            // Enhanced debug logging (before grouping)
            $product = \App\Models\Product::find($productId);
            
            \Log::info('Stock Analytics AJAX Request', [
                'product_id' => $productId,
                'product_found' => $product ? true : false,
                'product_name' => $product ? $product->name : 'N/A',
                'product_sku' => $product ? $product->sku : 'N/A',
                'stock_in_count_before_grouping' => $stockItems->count(),
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
            
            // Visual mutations removed - only show real database data
            
            // Group stock_in items by penerimaan_detail_id (simple approach)
            // Use penerimaan_detail.qty which is fixed and doesn't change
            // Don't use warehouse_stock.qty because it's dynamic
            $groupedStockIn = $stockItems->groupBy(function($item) {
                // For normal penerimaan (not returns), group by penerimaan_detail_id only
                // This ensures one entry per penerimaan, even if there are multiple EDs
                if (!$item->source_type || $item->source_type === 'penerimaan') {
                    return $item->penerimaan_detail_id ? 'penerimaan_' . $item->penerimaan_detail_id : 'single_' . $item->id;
                }
                // For returns, keep each item separate (they have different source_ids)
                return 'return_' . $item->id;
            });
            
            // Create a list - one entry per penerimaan_detail_id
            // Use penerimaan_detail.qty which is the original fixed quantity
            $consolidatedStockIn = $groupedStockIn->map(function($groupItems, $groupKey) {
                $firstItem = $groupItems->first();
                
                // For normal penerimaan, use penerimaan_detail.qty (fixed, doesn't change)
                // This is the original quantity received, regardless of current warehouse_stock.qty
                if ($firstItem->penerimaanDetail && $firstItem->penerimaanDetail->qty) {
                    $firstItem->qty = (float)$firstItem->penerimaanDetail->qty;
                }
                // For returns, keep warehouse_stock.qty as is
                
                // Check if there are multiple EDs for this penerimaan_detail_id
                if (strpos($groupKey, 'penerimaan_') === 0) {
                    // Get unique expired dates from all items in this group
                    $uniqueEDs = $groupItems->pluck('expired_date')
                        ->filter()
                        ->map(function($ed) {
                            return $ed ? \Carbon\Carbon::parse($ed) : null;
                        })
                        ->filter()
                        ->unique(function($ed) {
                            return $ed->format('Y-m-d');
                        })
                        ->sort()
                        ->values();
                    
                    // If multiple EDs exist, set expired_date to null and add flag with ED list
                    if ($uniqueEDs->count() > 1) {
                        $firstItem->expired_date = null;
                        $firstItem->has_multiple_ed = true;
                        $firstItem->ed_count = $uniqueEDs->count();
                        // Format ED list as "ED 3/2028, ED 8/2028" or similar
                        $firstItem->ed_list = $uniqueEDs->map(function($ed) {
                            return 'ED ' . $ed->format('m/Y');
                        })->toArray();
                    } else {
                        // Single ED, keep the expired_date
                        $firstItem->has_multiple_ed = false;
                        $firstItem->ed_count = 1;
                        $firstItem->ed_list = [];
                    }
                }
                
                return $firstItem;
            })->values();
            
            // Update log with consolidated count
            \Log::info('Stock Analytics - Consolidated', [
                'product_id' => $productId,
                'stock_in_count_after_grouping' => $consolidatedStockIn->count(),
                'grouped_keys' => $groupedStockIn->keys()->toArray(),
            ]);
            
            return response()->json([
                'success' => true,
                'stock_in' => $consolidatedStockIn,
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
            'totalInventoryValue' => $totalInventoryValue,
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

        // Tax filter - only apply if explicitly provided
        if ($request->filled('tax_id')) {
            if ($request->tax_id === 'N/A') {
                $query->whereNull('tax_id');
            } else {
                $query->where('tax_id', $request->tax_id);
            }
        }
        // If no tax_id filter, include ALL tax_id values (both PKP and non-PKP)
        
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
                'total_qty' => $stocks->sum(function($stock) {
                    return $stock->qty;
                }),
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
        
        // Calculate total inventory value (harga beli x qty)
        $totalInventoryValue = 0;
        foreach ($allStocks as $stock) {
            if ($stock->penerimaanDetail) {
                $penerimaanDetail = $stock->penerimaanDetail;
                
                // Calculate HPP after discounts (same logic as in analytics)
                $hppAsli = $penerimaanDetail->harga_hpp ?? 0;
                $hppSetelahDiskon = $hppAsli;
                
                // Apply percentage discounts in sequence
                if ($penerimaanDetail->diskon_persen_1 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_1 / 100);
                }
                if ($penerimaanDetail->diskon_persen_2 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_2 / 100);
                }
                if ($penerimaanDetail->diskon_persen_3 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_3 / 100);
                }
                if ($penerimaanDetail->diskon_persen_4 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_4 / 100);
                }
                if ($penerimaanDetail->diskon_persen_5 > 0) {
                    $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_5 / 100);
                }
                
                // Apply nominal discounts
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_1 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_2 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_3 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_4 ?? 0);
                $hppSetelahDiskon -= ($penerimaanDetail->diskon_nominal_5 ?? 0);
                
                // Ensure price doesn't go negative
                $finalHpp = max(0, $hppSetelahDiskon);
                
                // Add to total: harga beli x qty
                $totalInventoryValue += $finalHpp * $stock->qty;
            }
        }
        
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
                $request->has('include_empty'),
                $totalInventoryValue
            ), 
            $filename
        );
    }

    /**
     * Calculate running balance for a product based on all stock movements
     * This mimics the mutation table calculation by considering both stock in and stock out
     * Includes visual mutation adjustment for BB0088 (Product ID 87)
     */
    private function calculateRunningBalance($productId)
    {
        // Get all stock IN movements (all warehouse_stock records, including those with 0 qty)
        // This includes original penerimaan records that may now have 0 qty due to sales
        $stockInMovements = WarehouseStock::where('product_id', $productId)
            ->where('is_damaged', false)
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
                END ASC
            ')
            ->get();

        // Get all stock OUT movements (barang_keluar)
        $stockOutMovements = BarangKeluar::whereHas('warehouseStock', function($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->with('warehouseStock')
            ->orderBy('tanggal_keluar', 'asc')
            ->get();

        $runningBalance = 0;
        
        // Process stock IN movements - use original quantity from penerimaan_detail if available
        foreach ($stockInMovements as $movement) {
            if ($movement->penerimaanDetail && $movement->source_type != 'retur_penjualan') {
                // For penerimaan, use the original received quantity
                $runningBalance += $movement->penerimaanDetail->qty;
            } else {
                // For returns and other movements, use warehouse_stock qty
                $runningBalance += $movement->qty;
            }
        }
        
        // Process stock OUT movements
        foreach ($stockOutMovements as $movement) {
            $runningBalance -= $movement->qty;
        }
        
        // Add visual mutation adjustment for specific products
        // This simulates visual adjustments without modifying the actual database
        if ($productId == 87) {
            $runningBalance += 1; // BB0088 - Add visual +1 adjustment
        } elseif ($productId == 81) {
            $runningBalance += 2; // BB0082 - Add visual +2 adjustment
        }
        
        // Return max(0, balance) to avoid negative stock display
        return max(0, $runningBalance);
    }
} 