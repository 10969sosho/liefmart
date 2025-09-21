<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Platform;
use App\Models\Product;
use App\Models\Brand;
use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanDetail;
use App\Models\PenerimaanDetail;
use App\Models\Penerimaan;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MonthlySalesSummaryExport;
use App\Exports\OfflineMonthlySalesExport;
use App\Exports\OfflineSalesByCustomerExport;
use App\Exports\OfflineSalesByProductExport;
use App\Exports\SalesByDayOfWeekExport;
use App\Exports\SalesByDateNumberExport;
use App\Exports\SalesDetailReportExport;
use App\Exports\OfflineSalesDetailReportExport;
use App\Exports\SalesByMasterProductExport;
use App\Exports\SalesByPlatformProductExport;
use App\Exports\SalesByPlatformExport;
use App\Exports\SalesByStatusDayExport;
use App\Models\MainCategory;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Models\BarangKeluar;

class AnalyticController extends Controller
{
    /**
     * Sort profit data based on selected option
     *
     * @param Collection $profitData
     * @param string $sortBy
     * @return Collection
     */
    private function sortProfitData($profitData, $sortBy)
    {
        switch ($sortBy) {
            case 'profit_highest':
                return $profitData->sortByDesc('gross_profit');
            case 'profit_lowest':
                return $profitData->sortBy('gross_profit');
            case 'margin_highest':
                return $profitData->sortByDesc('margin_percent');
            case 'margin_lowest':
                return $profitData->sortBy('margin_percent');
            case 'sales_highest':
                return $profitData->sortByDesc('total_sales');
            case 'sales_lowest':
                return $profitData->sortBy('total_sales');
            default:
                return $profitData->sortByDesc('gross_profit');
        }
    }
    
    /**
     * Calculate summary statistics for the profit report
     *
     * @param Collection $profitData
     * @return array
     */
    private function calculateProfitSummary($profitData)
    {
        $totalSales = $profitData->sum('total_sales');
        $totalCogs = $profitData->sum('total_cogs');
        $totalProfit = $profitData->sum('gross_profit');
        $totalQuantity = $profitData->sum('qty_sold');
        
        // Count profitable products vs loss-making ones
        $profitableProducts = $profitData->filter(function ($item) {
            return $item['gross_profit'] > 0;
        })->count();
        
        $lossProducts = $profitData->filter(function ($item) {
            return $item['gross_profit'] <= 0;
        })->count();
        
        // Calculate average margin
        $avgMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
        
        return [
            'total_sales' => $totalSales,
            'total_cogs' => $totalCogs,
            'total_profit' => $totalProfit,
            'avg_margin' => $avgMargin,
            'total_quantity' => $totalQuantity,
            'profitable_products' => $profitableProducts,
            'loss_products' => $lossProducts,
            'total_products' => $profitData->count()
        ];
    }
    
    /**
     * Get the latest purchase cost for a product
     *
     * @param int $productId
     * @return float
     */
    /**
     * Calculate selling price for a product (initial_price with discount applied)
     * 
     * @param Product $product
     * @return float
     */
    private function calculateSellingPrice($product)
    {
        $initialPrice = $product->initial_price ?? 0;
        $discountPercentage = $product->discount_percentage ?? 0;
        
        if ($discountPercentage > 0) {
            return $initialPrice * (1 - ($discountPercentage / 100));
        }
        
        return $initialPrice;
    }

    private function getLatestPurchaseCost($productId)
    {
        // Get the latest purchase cost for a product from penerimaan details
        $latestPenerimaan = PenerimaanDetail::where('product_id', $productId)
            ->whereHas('penerimaan', function($query) {
                $query->whereIn('status', ['Located', 'approved']); // Both Located and approved
            })
            ->with(['penerimaan.taxCategory'])
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$latestPenerimaan) {
            return 0;
        }
        
        // Calculate final HPP after discounts
        $hppAsli = $latestPenerimaan->harga_hpp;
        $hppSetelahDiskon = $hppAsli;
        
        // Apply percentage discounts in sequence
        if ($latestPenerimaan->diskon_persen_1 > 0) {
            $hppSetelahDiskon -= ($hppSetelahDiskon * $latestPenerimaan->diskon_persen_1 / 100);
        }
        if ($latestPenerimaan->diskon_persen_2 > 0) {
            $hppSetelahDiskon -= ($hppSetelahDiskon * $latestPenerimaan->diskon_persen_2 / 100);
        }
        if ($latestPenerimaan->diskon_persen_3 > 0) {
            $hppSetelahDiskon -= ($hppSetelahDiskon * $latestPenerimaan->diskon_persen_3 / 100);
        }
        if ($latestPenerimaan->diskon_persen_4 > 0) {
            $hppSetelahDiskon -= ($hppSetelahDiskon * $latestPenerimaan->diskon_persen_4 / 100);
        }
        if ($latestPenerimaan->diskon_persen_5 > 0) {
            $hppSetelahDiskon -= ($hppSetelahDiskon * $latestPenerimaan->diskon_persen_5 / 100);
        }
        
        // Apply nominal discounts
        $hppSetelahDiskon -= $latestPenerimaan->diskon_nominal_1;
        $hppSetelahDiskon -= $latestPenerimaan->diskon_nominal_2;
        $hppSetelahDiskon -= $latestPenerimaan->diskon_nominal_3;
        $hppSetelahDiskon -= $latestPenerimaan->diskon_nominal_4;
        $hppSetelahDiskon -= $latestPenerimaan->diskon_nominal_5;
        
        // Ensure price doesn't go negative
        $finalHpp = max(0, $hppSetelahDiskon);
        
        // Skip tax calculation - use HPP directly without tax
        
        return $finalHpp;
    }
    
    /**
     * Get actual modal cost from barang keluar (warehouse stock outbound records)
     * This method gets the real cost based on which warehouse stock was actually used
     */
    private function getActualModalFromBarangKeluar($orderItemId, $productId)
    {
        // Get all barang_keluar records for this order item and product
        $barangKeluarItems = \App\Models\BarangKeluar::where('order_item_id', $orderItemId)
            ->whereHas('warehouseStock', function($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->with(['warehouseStock.penerimaanDetail.penerimaan.taxCategory'])
            ->get();
            
        if ($barangKeluarItems->isEmpty()) {
            // Fallback to latest purchase cost if no barang keluar found
            return $this->getLatestPurchaseCost($productId);
        }
        
        $totalCost = 0;
        $totalQty = 0;
        
        foreach ($barangKeluarItems as $barangKeluar) {
            if ($barangKeluar->warehouseStock && $barangKeluar->warehouseStock->penerimaanDetail) {
                $penerimaanDetail = $barangKeluar->warehouseStock->penerimaanDetail;
                $qty = $barangKeluar->qty;
                
                // Calculate HPP after discounts (same logic as above)
                $hppAsli = $penerimaanDetail->harga_hpp;
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
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_1;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_2;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_3;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_4;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_5;
                
                // Ensure price doesn't go negative
                $finalHpp = max(0, $hppSetelahDiskon);
                
                // Add tax for HGN (products purchased with tax) - same as platform product
                if ($penerimaanDetail->penerimaan && $penerimaanDetail->penerimaan->taxCategory && $penerimaanDetail->penerimaan->taxCategory->name === 'HGN') {
                    $taxPercentage = $penerimaanDetail->penerimaan->taxCategory->tax_percentage ?? 0;
                    $finalHpp = $finalHpp * (1 + ($taxPercentage / 100));
                }
                
                $totalCost += $finalHpp * $qty;
                $totalQty += $qty;
            }
        }
        
        // Return average cost per unit
        return $totalQty > 0 ? $totalCost / $totalQty : 0;
    }

    private function getTotalModalFromBarangKeluar($orderItemId, $productId)
    {
        // Get all barang_keluar records for this order item and product
        $barangKeluarItems = \App\Models\BarangKeluar::where('order_item_id', $orderItemId)
            ->whereHas('warehouseStock', function($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->with(['warehouseStock.penerimaanDetail.penerimaan.taxCategory'])
            ->get();
            
        if ($barangKeluarItems->isEmpty()) {
            return 0; // Return 0 if no barang keluar found, will use fallback
        }
        
        $totalCost = 0;
        
        foreach ($barangKeluarItems as $barangKeluar) {
            if ($barangKeluar->warehouseStock && $barangKeluar->warehouseStock->penerimaanDetail) {
                $penerimaanDetail = $barangKeluar->warehouseStock->penerimaanDetail;
                $qty = $barangKeluar->qty;
                
                // Calculate HPP after discounts (same logic as above)
                $hppAsli = $penerimaanDetail->harga_hpp;
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
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_1;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_2;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_3;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_4;
                $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_5;
                
                // Ensure price doesn't go negative
                $finalHpp = max(0, $hppSetelahDiskon);
                
                // Add tax for HGN (products purchased with tax) - same as platform product
                if ($penerimaanDetail->penerimaan && $penerimaanDetail->penerimaan->taxCategory && $penerimaanDetail->penerimaan->taxCategory->name === 'HGN') {
                    $taxPercentage = $penerimaanDetail->penerimaan->taxCategory->tax_percentage ?? 0;
                    $finalHpp = $finalHpp * (1 + ($taxPercentage / 100));
                }
                
                $totalCost += $finalHpp * $qty;
            }
        }
        
        // Return total cost directly (not average)
        return $totalCost;
    }

    /**
     * Display sales data by platform with both value and volume metrics
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function salesByPlatformReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Set default date range - Default ke null untuk menampilkan semua data
        $startDate = $request->filled('start_date') ? $request->input('start_date') : null;
        $endDate = $request->filled('end_date') ? $request->input('end_date') : null;
        
        // Build the query for orders - ensure we're only getting online orders (with platform_id)
        // PERBAIKAN: Tambahkan withoutGlobalScope untuk mengatasi masalah filtering
        $query = Order::withoutGlobalScope('mainCategory')->with([
            'platform',
            'orderItems',
            'orderItems.platformProduct.mappingBarang.product',
        ])->whereNotNull('platform_id'); // Ensure only online orders
        
        // Terapkan filter tanggal hanya jika ada input
        if ($startDate && $endDate) {
            // Pastikan format tanggal benar dengan Carbon
            try {
                $startDateCarbon = Carbon::parse($startDate)->startOfDay();
                $endDateCarbon = Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon]);
            } catch (\Exception $e) {
                // Jika format tanggal invalid, abaikan filter tanggal
            }
        }
        
        // Apply platform filter if set
        if ($request->filled('platform_id')) {
            $query->where('platform_id', $request->platform_id);
        }
        
        // Determine sort order - PERBAIKAN: Sesuaikan dengan kolom yang benar-benar ada di tabel
        $sortBy = $request->input('sort', 'date_newest');
        
        // Get the orders without sorting first
        $orders = $query->get();
        
        // Sort orders based on user selection
        switch ($sortBy) {
            case 'value_highest':
                // Sort by total price in order items
                $orders = $orders->sortByDesc(function($order) {
                    return $order->orderItems->sum(function($item) {
                        return $item->price_after_discount * $item->quantity;
                    });
                });
                break;
            case 'value_lowest':
                // Sort by total price in order items
                $orders = $orders->sortBy(function($order) {
                    return $order->orderItems->sum(function($item) {
                        return $item->price_after_discount * $item->quantity;
                    });
                });
                break;
            case 'volume_highest':
                // Sort by total quantity in order items
                $orders = $orders->sortByDesc(function($order) {
                    return $order->orderItems->sum('quantity');
                });
                break;
            case 'volume_lowest':
                // Sort by total quantity in order items
                $orders = $orders->sortBy(function($order) {
                    return $order->orderItems->sum('quantity');
                });
                break;
            case 'date_newest':
                $orders = $orders->sortByDesc('tanggal');
                break;
            case 'date_oldest':
                $orders = $orders->sortBy('tanggal');
                break;
            default:
                // Default sorting by date newest
                $orders = $orders->sortByDesc('tanggal');
                break;
        }
        
        // Calculate total value and volume for each order
        $orders = $orders->map(function($order) {
            $order->total_value = $order->orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            $order->total_volume = $order->orderItems->sum('quantity');
            return $order;
        });
        
        // Get order IDs for returns query
        $orderIds = $orders->pluck('id')->toArray();
        
        // Get total returns for the filtered orders
        $returPenjualanQuery = ReturPenjualan::whereIn('order_id', $orderIds);
        
        // Apply the same date filter to returns if provided
        if ($startDate && $endDate) {
            try {
                $startDateCarbon = Carbon::parse($startDate)->startOfDay();
                $endDateCarbon = Carbon::parse($endDate)->endOfDay();
                $returPenjualanQuery->whereBetween('tanggal_retur', [$startDateCarbon, $endDateCarbon]);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }
        
        $totalReturns = $returPenjualanQuery->count();
        
        // Calculate summary data
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_value'),
            'total_volume' => $orders->sum('total_volume'),
            'avg_order_value' => $orders->count() > 0 ? 
                $orders->sum('total_value') / $orders->count() : 0,
            'avg_order_volume' => $orders->count() > 0 ? 
                $orders->sum('total_volume') / $orders->count() : 0,
            'total_returns' => $totalReturns,
        ];
        
        // Get summary by platform
        $platformSummary = $orders->groupBy('platform_id')->map(function ($platformOrders, $platformId) {
            $platform = Platform::find($platformId);
            return [
                'platform' => $platform ? $platform->name : 'Unknown',
                'order_count' => $platformOrders->count(),
                'total_value' => $platformOrders->sum('total_value'),
                'total_volume' => $platformOrders->sum('total_volume'),
                'avg_order_value' => $platformOrders->count() > 0 ? 
                    $platformOrders->sum('total_value') / $platformOrders->count() : 0,
                'avg_order_volume' => $platformOrders->count() > 0 ? 
                    $platformOrders->sum('total_volume') / $platformOrders->count() : 0,
            ];
        });
        
        return view('analytics.sales_by_platform', [
            'orders' => $orders,
            'platforms' => $platforms,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $request->platform_id,
            'sortBy' => $sortBy,
            'summary' => $summary,
            'platformSummary' => $platformSummary,
        ]);
    }

    /**
     * Display a detailed sales report with various filters
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function salesDetailReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Get orders with filter
        // PERBAIKAN: Tambahkan withoutGlobalScope untuk mengatasi masalah filtering
        $query = Order::withoutGlobalScope('mainCategory')->with([
                'orderItems.platformProduct.mappingBarang', 
                'platform'
            ])
            ->orderBy('created_at', 'desc');
        
        // Apply date range filter if provided or default to today
        $startDate = $request->input('start_date') ?? now()->format('Y-m-d');
        $endDate = $request->input('end_date') ?? now()->format('Y-m-d');
        
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if provided
        if ($request->has('platform_id') && !empty($request->platform_id)) {
            $query->where('platform_id', $request->platform_id);
        }
        
        // Apply price range filters if provided
        if ($request->has('min_price') && !empty($request->min_price)) {
            $minPrice = $request->min_price;
            $query->whereHas('orderItems', function($q) use ($minPrice) {
                $q->where('price_after_discount', '>=', $minPrice);
            });
        }
        
        if ($request->has('max_price') && !empty($request->max_price)) {
            $maxPrice = $request->max_price;
            $query->whereHas('orderItems', function($q) use ($maxPrice) {
                $q->where('price_after_discount', '<=', $maxPrice);
            });
        }
        
        // Apply quantity range filters if provided
        if ($request->has('min_qty') && !empty($request->min_qty)) {
            $minQty = $request->min_qty;
            $query->where(function($q) use ($minQty) {
                $q->whereHas('orderItems', function($q) use ($minQty) {
                    $q->havingRaw('SUM(quantity) >= ?', [$minQty]);
                });
            });
        }
        
        if ($request->has('max_qty') && !empty($request->max_qty)) {
            $maxQty = $request->max_qty;
            $query->where(function($q) use ($maxQty) {
                $q->whereHas('orderItems', function($q) use ($maxQty) {
                    $q->havingRaw('SUM(quantity) <= ?', [$maxQty]);
                });
            });
        }
        
        // Apply sorting
        $sortBy = $request->input('sort', 'date_newest');
        
        switch ($sortBy) {
            case 'date_oldest':
                $query->orderBy('tanggal', 'asc');
                break;
            case 'value_highest':
                $query->orderBy('total', 'desc');
                break;
            case 'value_lowest':
                $query->orderBy('total', 'asc');
                break;
            case 'date_newest':
            default:
                $query->orderBy('tanggal', 'desc');
                break;
        }
        
        // Clone the query to get all orders for summary calculation (ignoring pagination)
        $summaryQuery = clone $query;
        $allFilteredOrders = $summaryQuery->get();
        
        // Process all filtered orders to calculate metrics for the summary
        $allFilteredOrders->each(function($order) {
            $orderItems = $order->orderItems;
            
            $order->total_value = $orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            
            $order->total_volume = $orderItems->sum('quantity');
            
            // Make sure day of week is set
            if ($order->tanggal) {
                $order->hari = Carbon::parse($order->tanggal)->locale('id')->isoFormat('dddd');
            }
            
            return $order;
        });
        
        // Get paginated results for display
        $orders = $query->paginate(25);
        
        // Process paginated orders to calculate additional metrics
        $orders->getCollection()->transform(function($order) {
            $orderItems = $order->orderItems;
            
            $order->total_value = $orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            
            $order->total_volume = $orderItems->sum('quantity');
            
            // Make sure day of week is set
            if ($order->tanggal) {
                $order->hari = Carbon::parse($order->tanggal)->locale('id')->isoFormat('dddd');
            }
            
            return $order;
        });
        
        // Calculate summary metrics using all filtered orders (not just paginated ones)
        $allOrdersCount = Order::count();
        $filteredOrdersCount = $allFilteredOrders->count();
        $percentageShown = $allOrdersCount > 0 
            ? round(($filteredOrdersCount / $allOrdersCount) * 100, 1) 
            : 0;
        
        // Calculate orders after returns - only count orders that have remaining quantity after returns
        $ordersAfterReturns = 0;
        $totalValueAfterReturns = 0;
        $totalVolumeAfterReturns = 0;
        
        foreach ($allFilteredOrders as $order) {
            $hasRemainingItems = false;
            $orderValueAfterReturns = 0;
            $orderVolumeAfterReturns = 0;
            
            foreach ($order->orderItems as $item) {
                // Calculate return quantity for this item
                $qtyReturIndividual = ReturPenjualanDetail::where('order_item_id', $item->id)
                    ->whereHas('returPenjualan', function($q) { 
                        $q->whereIn('status', ['draft', 'selesai']); 
                    })
                    ->sum('qty');
                $qtyReturIndividual = (float) $qtyReturIndividual;
                
                // Check if this is a package product and get total package quantity
                $packageQuantity = 1;
                if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                    $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
                }
                
                // Convert individual retur quantity back to package quantity
                $qtyRetur = $packageQuantity > 0 ? $qtyReturIndividual / $packageQuantity : $qtyReturIndividual;
                
                // Calculate original quantity (current + returned)
                $currentQty = (float) ($item->quantity ?? 0);
                $originalQty = $currentQty + $qtyRetur;
                
                // Calculate remaining quantity after return
                $remainingQty = max(0.0, $originalQty - $qtyRetur);
                
                if ($remainingQty > 0) {
                    $hasRemainingItems = true;
                    $orderVolumeAfterReturns += $remainingQty;
                    
                    // Calculate remaining value after return
                    $itemPrice = (float) ($item->price_after_discount ?? 0);
                    $remainingValue = round($itemPrice * $remainingQty, 2);
                    $orderValueAfterReturns += $remainingValue;
                }
            }
            
            if ($hasRemainingItems) {
                $ordersAfterReturns++;
                $totalValueAfterReturns += $orderValueAfterReturns;
                $totalVolumeAfterReturns += $orderVolumeAfterReturns;
            }
        }
        
        // Calculate totals for summary cards from all filtered orders
        $totalValue = $allFilteredOrders->sum('total_value');
        $totalVolume = $allFilteredOrders->sum('total_volume');
        
        $summary = [
            'total_orders' => $filteredOrdersCount,
            'total_orders_after_returns' => $ordersAfterReturns,
            'total_value' => $totalValueAfterReturns,
            'total_volume' => $totalVolumeAfterReturns,
            'avg_order_value' => $ordersAfterReturns > 0 ? 
                $totalValueAfterReturns / $ordersAfterReturns : 0,
            'percentage_shown' => $percentageShown,
            'total_orders_all' => $allOrdersCount
        ];
        
        return view('analytics.sales_detail_report', [
            'orders' => $orders,
            'platforms' => $platforms,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $request->platform_id,
            'sortBy' => $sortBy,
            'summary' => $summary
        ]);
    }
    
    // Additional methods for other analytics reports will go here
    // ... (other methods)

    public function salesByDayOfWeekReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Set default date range to today if not provided
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }
        
        // Build the query for orders with all related data
        $query = Order::with([
            'platform',
            'orderItems',
            'orderItems.platformProduct',
            'orderItems.warehouseStock',
            'orderItems.warehouseStock.product',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);
        
        // Apply date filter
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if set
        if ($request->filled('platform_id')) {
            $platformId = $request->input('platform_id');
            $query->where('platform_id', $platformId);
        }
        
        // Get all orders
        $allOrders = $query->get();
        
        // Filter orders to only include those with valid financial transactions (having saldo_masuk)
        $orders = $allOrders->filter(function($order) {
            $hasValidTransaction = false;
            
            // Check if order has any financial transactions with saldo_masuk
            if ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            }
            
            return $hasValidTransaction;
        });
        
        // Initialize day of week data structure
        // MySQL DAYOFWEEK: 1=Sunday, 2=Monday, 3=Tuesday, 4=Wednesday, 5=Thursday, 6=Friday, 7=Saturday
        $dayOfWeekData = [];
            $dayNames = [
            1 => 'Minggu',
            2 => 'Senin', 
            3 => 'Selasa',
            4 => 'Rabu',
            5 => 'Kamis',
            6 => 'Jumat',
            7 => 'Sabtu'
        ];
        
        // Initialize all days with zero values
        for ($i = 1; $i <= 7; $i++) {
            $dayOfWeekData[$i] = [
                'day_number' => $i,
                'day_name' => $dayNames[$i],
                'total_value' => 0,
                'total_volume' => 0,
                'order_count' => 0,
                'orders' => collect()
            ];
        }
        
        // Group orders by day of week (MySQL DAYOFWEEK format)
        foreach ($orders as $order) {
            if ($order->tanggal) {
                $dayNumber = Carbon::parse($order->tanggal)->dayOfWeek;
                // Carbon dayOfWeek: 0=Sunday, 1=Monday, etc. Convert to MySQL format
                $mysqlDayNumber = $dayNumber == 0 ? 1 : $dayNumber + 1;
                
                $dayOfWeekData[$mysqlDayNumber]['orders']->push($order);
                $dayOfWeekData[$mysqlDayNumber]['order_count']++;
            }
        }
        
        // Calculate financial data for each day
        foreach ($dayOfWeekData as $dayNumber => &$dayData) {
            $totalValue = 0;
            $totalVolume = 0;
            
            foreach ($dayData['orders'] as $order) {
                // Process Shopee transactions
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process TikTok transactions
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Tokopedia transactions
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Blibli transactions
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            $dayData['total_value'] = $totalValue;
            $dayData['total_volume'] = $totalVolume;
        }
        
        // Apply business logic: Saturday gets 1/6 of Monday's orders
        $mondayOrders = $dayOfWeekData[2]['orders']; // Monday = day 2
        $mondayOrderCount = $mondayOrders->count();
        $ordersToMove = (int)($mondayOrderCount / 6); // 1/6 of Monday orders
        
        if ($ordersToMove > 0) {
            // Take the last 1/6 orders from Monday and move them to Saturday
            $ordersToMoveToSaturday = $mondayOrders->take(-$ordersToMove);
            $ordersToKeepInMonday = $mondayOrders->take($mondayOrderCount - $ordersToMove);
            
            // Recalculate Monday data
            $mondayValue = 0;
            $mondayVolume = 0;
            foreach ($ordersToKeepInMonday as $order) {
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $mondayValue += $transaction->saldo_masuk;
                    $mondayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $mondayValue += $transaction->saldo_masuk;
                    $mondayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $mondayValue += $transaction->saldo_masuk;
                    $mondayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $mondayValue += $transaction->saldo_masuk;
                    $mondayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            // Recalculate Saturday data (add the moved orders)
            $saturdayValue = $dayOfWeekData[7]['total_value']; // Saturday = day 7
            $saturdayVolume = $dayOfWeekData[7]['total_volume'];
            foreach ($ordersToMoveToSaturday as $order) {
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $saturdayValue += $transaction->saldo_masuk;
                    $saturdayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $saturdayValue += $transaction->saldo_masuk;
                    $saturdayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $saturdayValue += $transaction->saldo_masuk;
                    $saturdayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $saturdayValue += $transaction->saldo_masuk;
                    $saturdayVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            // Update the data
            $dayOfWeekData[2]['total_value'] = $mondayValue;
            $dayOfWeekData[2]['total_volume'] = $mondayVolume;
            $dayOfWeekData[2]['order_count'] = $mondayOrderCount - $ordersToMove;
            
            $dayOfWeekData[7]['total_value'] = $saturdayValue;
            $dayOfWeekData[7]['total_volume'] = $saturdayVolume;
            $dayOfWeekData[7]['order_count'] += $ordersToMove;
        }
        
        // Convert to collection format for view
        $completeDayOfWeekData = collect();
        for ($i = 1; $i <= 7; $i++) {
                $completeDayOfWeekData->push([
                    'day_number' => $i,
                    'day_name' => $dayNames[$i],
                'total_value' => $dayOfWeekData[$i]['total_value'],
                'total_volume' => $dayOfWeekData[$i]['total_volume'],
                'order_count' => $dayOfWeekData[$i]['order_count']
            ]);
        }
        
        // Calculate total saldo masuk and volume from financial transactions
        $totalValue = 0;
        $totalVolume = 0;
        
        foreach ($orders as $order) {
            // Process Shopee transactions
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process TikTok transactions
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Tokopedia transactions
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Blibli transactions
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
        }
        
        $totalOrders = $orders->count();
        
        $summary = [
            'total_orders' => $totalOrders,
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
        ];
        
        $summary['avg_order_value'] = $totalOrders > 0 ? $totalValue / $totalOrders : 0;
        $summary['avg_order_volume'] = $totalOrders > 0 ? $totalVolume / $totalOrders : 0;
        
        // Find best performing days
        $bestDaySales = $completeDayOfWeekData->max('total_value');
        $bestDayVolume = $completeDayOfWeekData->max('total_volume');
        $bestDayOrders = $completeDayOfWeekData->max('order_count');
        
        $summary['best_day_sales'] = $bestDaySales;
        $summary['best_day_volume'] = $bestDayVolume;
        $summary['best_day_orders'] = $bestDayOrders;
        
        // Create day of week summary indexed by day number (0 for Sunday, 1-6 for Monday-Saturday)
        $dayOfWeekSummary = [];
        foreach ($completeDayOfWeekData as $day) {
            // Convert from MySQL day number (1-7) to JavaScript day number (0-6)
            $jsDay = ($day['day_number'] % 7); // Convert 1 (Sunday in MySQL) to 0 (Sunday in JS)
            $dayOfWeekSummary[$jsDay] = [
                'day_name' => $day['day_name'],
                'total_value' => $day['total_value'],
                'total_volume' => $day['total_volume'],
                'order_count' => $day['order_count']
            ];
        }
        
        // Define day names for JavaScript
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];
        
        // Prepare platform summary data if needed
        $platformSummary = [];
        if ($request->filled('platform_id')) {
            $selectedPlatform = $platforms->where('id', $request->platform_id)->first();
            if ($selectedPlatform) {
                $platformOrders = $orders->where('platform_id', $selectedPlatform->id);
                $platformValue = 0;
                $platformVolume = 0;
                
                foreach ($platformOrders as $order) {
                    if ($selectedPlatform->name == 'Shopee') {
                        foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $platformValue += $transaction->saldo_masuk;
                            $platformVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($selectedPlatform->name == 'TikTok') {
                        foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $platformValue += $transaction->saldo_masuk;
                            $platformVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($selectedPlatform->name == 'Tokopedia') {
                        foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $platformValue += $transaction->saldo_masuk;
                            $platformVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($selectedPlatform->name == 'Blibli') {
                        foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $platformValue += $transaction->saldo_masuk;
                            $platformVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    }
                }
                
                $platformSummary = collect([[
                    'platform' => $selectedPlatform ? $selectedPlatform->name : 'Unknown',
                    'order_count' => $platformOrders->count(),
                    'total_value' => $platformValue,
                    'total_volume' => $platformVolume
                ]]);
            }
        } else {
            // Group by platform for all platforms
            $platformSummary = $platforms->map(function($platform) use ($orders) {
                $platformOrders = $orders->where('platform_id', $platform->id);
                    $totalValue = 0;
                    $totalVolume = 0;
                    
                    foreach ($platformOrders as $order) {
                        if ($platform->name == 'Shopee') {
                            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                                $totalValue += $transaction->saldo_masuk;
                                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                            }
                        } elseif ($platform->name == 'TikTok') {
                            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                                $totalValue += $transaction->saldo_masuk;
                                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                            }
                        } elseif ($platform->name == 'Tokopedia') {
                            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                                $totalValue += $transaction->saldo_masuk;
                                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                            }
                        } elseif ($platform->name == 'Blibli') {
                            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                                $totalValue += $transaction->saldo_masuk;
                                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                            }
                        }
                    }
                    
                    return [
                        'platform' => $platform ? $platform->name : 'Unknown',
                        'order_count' => $platformOrders->count(),
                        'total_value' => $totalValue,
                        'total_volume' => $totalVolume
                    ];
                })->values();
        }
        
        // Add information about filtered vs total orders
        $summary['total_all_orders'] = $allOrders->count();
        $summary['total_filtered_orders'] = $orders->count();
        $summary['percent_filtered'] = $allOrders->count() > 0 
            ? round(($orders->count() / $allOrders->count()) * 100, 1) 
            : 0;
        
        return view('analytics.sales_by_day_of_week', [
            'dayOfWeekData' => $completeDayOfWeekData,
            'dayOfWeekSummary' => $dayOfWeekSummary,
            'dayNames' => $dayNames,
            'platforms' => $platforms,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $request->input('platform_id'),
            'platformSummary' => $platformSummary,
            'summary' => $summary
        ]);
    }

    // ... continue with other methods
    public function salesByDateNumberReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Set default date range (last 3 months if not provided)
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonths(3)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }
        
        // View mode (volume or value)
        $viewMode = $request->input('view_mode', 'volume');
        
        // Build the query for orders
        $query = Order::with([
            'platform', 
            'orderItems',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);
        
        // Apply date filter
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if set
        $selectedPlatform = null;
        if ($request->filled('platform_id')) {
            $selectedPlatform = $request->input('platform_id');
            $query->where('platform_id', $selectedPlatform);
        }
        
        // Get all orders
        $allOrders = $query->get();
        
        // Filter orders to only include those with valid financial transactions (having saldo_masuk)
        $orders = $allOrders->filter(function($order) {
            $hasValidTransaction = false;
            
            // Check if order has any financial transactions with saldo_masuk
            if ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            }
            
            return $hasValidTransaction;
        });
        
        // Group orders by date number (1-31) and calculate totals
        $dateNumberSummary = $orders->groupBy(function($order) {
            return Carbon::parse($order->tanggal)->format('d');
        })->map(function($dateOrders, $dateNumber) {
            // Calculate total value from financial transactions
            $totalValue = 0;
            $totalVolume = 0;
            
            foreach ($dateOrders as $order) {
                // Process Shopee transactions
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process TikTok transactions
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Tokopedia transactions
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Blibli transactions
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            return [
                'date_number' => $dateNumber,
                'order_count' => $dateOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        })->sortBy('date_number');
        
        // Create complete date number data (1-31, including dates with zero sales)
        $completeDateNumberSummary = [];
        
        for ($i = 1; $i <= 31; $i++) {
            $dateKey = sprintf('%02d', $i);
            
            if (isset($dateNumberSummary[$dateKey])) {
                $completeDateNumberSummary[$dateKey] = $dateNumberSummary[$dateKey];
            } else {
                $completeDateNumberSummary[$dateKey] = [
                    'date_number' => $dateKey,
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_volume' => 0,
                ];
            }
        }
        
        // Calculate total saldo masuk and volume from financial transactions
        $totalValue = 0;
        $totalVolume = 0;
        
        foreach ($orders as $order) {
            // Process Shopee transactions
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process TikTok transactions
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Tokopedia transactions
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Blibli transactions
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
        }
        
        // Calculate summary metrics
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
            'avg_order_value' => $orders->count() > 0 ? 
                $totalValue / $orders->count() : 0,
            'avg_order_volume' => $orders->count() > 0 ?
                $totalVolume / $orders->count() : 0,
            'total_all_orders' => $allOrders->count(),
            'total_filtered_orders' => $orders->count(),
            'percent_filtered' => $allOrders->count() > 0 
                ? round(($orders->count() / $allOrders->count()) * 100, 1) 
                : 0,
        ];
        
        // Get summary by platform
        $platformSummary = [];
        if ($orders->count() > 0) {
            $platformSummary = $orders->groupBy('platform_id')->map(function($platformOrders, $platformId) {
                $platform = Platform::find($platformId);
                
                // Calculate total saldo masuk and volume for this platform
                $totalValue = 0;
                $totalVolume = 0;
                
                foreach ($platformOrders as $order) {
                    // Add financial transactions based on platform
                    if ($platform->name == 'Shopee') {
                        foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'TikTok') {
                        foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'Tokopedia') {
                        foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'Blibli') {
                        foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    }
                }
                
                return [
                    'platform' => $platform ? $platform->name : 'Unknown',
                    'order_count' => $platformOrders->count(),
                    'total_value' => $totalValue,
                    'total_volume' => $totalVolume,
                ];
            });
        }
        
        return view('analytics.sales_by_date_number', [
            'dateNumberSummary' => $completeDateNumberSummary,
            'platforms' => $platforms,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $selectedPlatform,
            'viewMode' => $viewMode,
            'summary' => $summary,
            'platformSummary' => $platformSummary,
        ]);
    }

    public function salesByStatusAndDayReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Set default date range (last month if not provided)
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonth()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }
        
        // View mode (volume or value)
        $viewMode = $request->input('view_mode', 'volume');
        
        // Build the query for orders
        $query = Order::with([
            'platform', 
            'orderItems',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);
        
        // Apply date filter
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if set
        $selectedPlatform = null;
        if ($request->filled('platform_id')) {
            $selectedPlatform = $request->input('platform_id');
            $query->where('platform_id', $selectedPlatform);
        }
        
        // Apply status_hari filter if set
        $selectedStatus = null;
        if ($request->filled('status')) {
            $selectedStatus = $request->input('status');
            // Filter orders that contain the selected status (either as single value or part of comma-separated values)
            $query->where(function($q) use ($selectedStatus) {
                $q->where('status_hari', $selectedStatus)
                  ->orWhere('status_hari', 'LIKE', $selectedStatus . ',%')
                  ->orWhere('status_hari', 'LIKE', '%,' . $selectedStatus . ',%')
                  ->orWhere('status_hari', 'LIKE', '%,' . $selectedStatus);
            });
        }
        
        // Get all orders (don't filter by financial transactions for counting)
        $orders = $query->get();
        
        // Get all status_hari values and expand comma-separated values
        $rawStatuses = Order::distinct()->pluck('status_hari')->filter()->values()->toArray();
        $allStatuses = [];
        
        // Expand comma-separated status values
        foreach ($rawStatuses as $status) {
            if (strpos($status, ',') !== false) {
                // Split by comma and trim each status
                $statuses = array_map('trim', explode(',', $status));
                foreach ($statuses as $singleStatus) {
                    if (!empty($singleStatus) && !in_array($singleStatus, $allStatuses)) {
                        $allStatuses[] = $singleStatus;
                    }
                }
            } else {
                if (!in_array($status, $allStatuses)) {
                    $allStatuses[] = $status;
                }
            }
        }
        
        // Sort statuses for consistent display
        sort($allStatuses);
        
        // Day of week names
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
        
        // Group orders by status_hari and day of week
        $statusDayMatrix = [];
        
        // Initialize matrix with zeros
        foreach ($allStatuses as $status) {
            $statusDayMatrix[$status] = [];
            foreach (range(0, 6) as $dayNum) {
                $statusDayMatrix[$status][$dayNum] = [
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_volume' => 0,
                ];
            }
        }
        
        // Fill matrix with actual data
        foreach ($orders as $order) {
            $status = $order->status_hari;
            $dayOfWeek = Carbon::parse($order->tanggal)->dayOfWeek;
            
            // Calculate value and volume from financial transactions (only if they exist)
            $totalValue = 0;
            $totalVolume = 0;
            
            // Process Shopee transactions
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process TikTok transactions
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Tokopedia transactions
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Blibli transactions
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Handle multiple status values separated by comma
            if (!empty($status)) {
                if (strpos($status, ',') !== false) {
                    // Split by comma and process each status separately
                    $statuses = array_map('trim', explode(',', $status));
                    foreach ($statuses as $singleStatus) {
                        if (!empty($singleStatus) && isset($statusDayMatrix[$singleStatus][$dayOfWeek])) {
                            $statusDayMatrix[$singleStatus][$dayOfWeek]['order_count']++;
                            $statusDayMatrix[$singleStatus][$dayOfWeek]['total_value'] += $totalValue;
                            $statusDayMatrix[$singleStatus][$dayOfWeek]['total_volume'] += $totalVolume;
                        }
                    }
                } else {
                    // Single status
            if (isset($statusDayMatrix[$status][$dayOfWeek])) {
                $statusDayMatrix[$status][$dayOfWeek]['order_count']++;
                $statusDayMatrix[$status][$dayOfWeek]['total_value'] += $totalValue;
                $statusDayMatrix[$status][$dayOfWeek]['total_volume'] += $totalVolume;
                    }
                }
            }
        }
        
        // Calculate summary by status_hari
        $statusSummary = [];
        foreach ($allStatuses as $status) {
            // Find orders that contain this status (either as single value or part of comma-separated values)
            $statusOrders = $orders->filter(function($order) use ($status) {
                if (empty($order->status_hari)) {
                    return false;
                }
                
                if (strpos($order->status_hari, ',') !== false) {
                    // Check if status is part of comma-separated values
                    $statuses = array_map('trim', explode(',', $order->status_hari));
                    return in_array($status, $statuses);
                } else {
                    // Check if status matches exactly
                    return $order->status_hari === $status;
                }
            });
            
            // Calculate total value and volume from financial transactions
            $totalValue = 0;
            $totalVolume = 0;
            
            foreach ($statusOrders as $order) {
                // Process Shopee transactions
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process TikTok transactions
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Tokopedia transactions
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Blibli transactions
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            $statusSummary[$status] = [
                'total_orders' => $statusOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        }
        
        // Calculate summary by day of week
        $dayOfWeekSummary = [];
        foreach (range(0, 6) as $dayNum) {
            $dayOrders = $orders->filter(function($order) use ($dayNum) {
                return Carbon::parse($order->tanggal)->dayOfWeek == $dayNum;
            });
            
            // Calculate total value and volume from financial transactions
            $totalValue = 0;
            $totalVolume = 0;
            
            foreach ($dayOrders as $order) {
                // Process Shopee transactions
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process TikTok transactions
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Tokopedia transactions
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Blibli transactions
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            $dayOfWeekSummary[$dayNum] = [
                'day_name' => $dayNames[$dayNum],
                'order_count' => $dayOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        }
        
        // Calculate total saldo masuk and volume from financial transactions
        $totalValue = 0;
        $totalVolume = 0;
        
        foreach ($orders as $order) {
            // Process Shopee transactions
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process TikTok transactions
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Tokopedia transactions
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Blibli transactions
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
        }
        
        // Calculate overall summary
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
            'avg_order_value' => $orders->count() > 0 ? 
                $totalValue / $orders->count() : 0,
            'avg_order_volume' => $orders->count() > 0 ?
                $totalVolume / $orders->count() : 0,
            'total_all_orders' => $orders->count(),
            'total_filtered_orders' => $orders->filter(function($order) {
                $hasValidTransaction = false;
                
                // Check if order has any financial transactions with saldo_masuk
                if ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                    $hasValidTransaction = true;
                } elseif ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                    $hasValidTransaction = true;
                } elseif ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                    $hasValidTransaction = true;
                } elseif ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                    $hasValidTransaction = true;
                }
                
                return $hasValidTransaction;
            })->count(),
        ];
        
        $summary['percent_filtered'] = $summary['total_all_orders'] > 0 
            ? round(($summary['total_filtered_orders'] / $summary['total_all_orders']) * 100, 1) 
            : 0;
        
        // Get summary by platform
        $platformSummary = [];
        if ($orders->count() > 0) {
            $platformSummary = $orders->groupBy('platform_id')->map(function($platformOrders, $platformId) {
                $platform = Platform::find($platformId);
                
                // Calculate total saldo masuk and volume for this platform
                $totalValue = 0;
                $totalVolume = 0;
                
                foreach ($platformOrders as $order) {
                    // Add financial transactions based on platform
                    if ($platform->name == 'Shopee') {
                        foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'TikTok') {
                        foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'Tokopedia') {
                        foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'Blibli') {
                        foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    }
                }
                
                return [
                    'platform' => $platform ? $platform->name : 'Unknown',
                    'order_count' => $platformOrders->count(),
                    'total_value' => $totalValue,
                    'total_volume' => $totalVolume,
                ];
            });
        }
        
        return view('analytics.sales_by_status_day', [
            'platforms' => $platforms,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $selectedPlatform,
            'selectedStatus' => $selectedStatus,
            'viewMode' => $viewMode,
            'summary' => $summary,
            'platformSummary' => $platformSummary,
            'allStatuses' => $allStatuses,
            'dayNames' => $dayNames,
            'statusDayMatrix' => $statusDayMatrix,
            'statusSummary' => $statusSummary,
            'dayOfWeekSummary' => $dayOfWeekSummary,
        ]);
    }

    public function monthlySalesSummaryReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Set default date range (last 6 months if not provided)
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonths(6)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
                case '6months':
                    $startDate = now()->subMonths(6)->format('Y-m-d');
                    break;
                case '1year':
                    $startDate = now()->subYear()->format('Y-m-d');
                    break;
            }
        }
        
        // View mode (volume or value)
        $viewMode = $request->input('view_mode', 'value');
        
        // Build the query for orders
        $query = Order::with([
            'platform', 
            'orderItems',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);
        
        // Apply date filter
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if set
        $selectedPlatform = null;
        if ($request->filled('platform_id')) {
            $selectedPlatform = $request->input('platform_id');
            $query->where('platform_id', $selectedPlatform);
        }
        
        // Get all orders
        $allOrders = $query->get();
        
        // Filter orders to only include those with valid financial transactions (having saldo_masuk)
        $orders = $allOrders->filter(function($order) {
            $hasValidTransaction = false;
            
            // Check if order has any financial transactions with saldo_masuk
            if ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            } elseif ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0) {
                $hasValidTransaction = true;
            }
            
            return $hasValidTransaction;
        });
        
        // Group orders by year-month and calculate monthly totals
        $monthlySummary = $orders->groupBy(function($order) {
            return Carbon::parse($order->tanggal)->format('Y-m');
        })->map(function($monthOrders, $yearMonth) {
            // Calculate total value from financial transactions
            $totalValue = 0;
            $totalVolume = 0;
            
            foreach ($monthOrders as $order) {
                // Process Shopee transactions
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process TikTok transactions
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Tokopedia transactions
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
                
                // Process Blibli transactions
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                    $totalValue += $transaction->saldo_masuk;
                    $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                }
            }
            
            $monthYear = Carbon::createFromFormat('Y-m', $yearMonth);
            
            return [
                'year_month' => $yearMonth,
                'month_name' => $monthYear->format('M Y'),
                'order_count' => $monthOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
                'avg_value' => $monthOrders->count() > 0 ? $totalValue / $monthOrders->count() : 0,
                'avg_volume' => $monthOrders->count() > 0 ? $totalVolume / $monthOrders->count() : 0,
                'value_volume_ratio' => $totalVolume > 0 ? $totalValue / $totalVolume : 0,
            ];
        })->sortBy('year_month');
        
        // Calculate total saldo masuk and volume from financial transactions
        $totalValue = 0;
        $totalVolume = 0;
        
        foreach ($orders as $order) {
            // Process Shopee transactions
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process TikTok transactions
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Tokopedia transactions
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
            
            // Process Blibli transactions
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                $totalValue += $transaction->saldo_masuk;
                $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
            }
        }
        
        // Calculate overall summary
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
            'avg_order_value' => $orders->count() > 0 ? 
                $totalValue / $orders->count() : 0,
            'avg_order_volume' => $orders->count() > 0 ?
                $totalVolume / $orders->count() : 0,
            'months_count' => $monthlySummary->count(),
            'avg_value_volume_ratio' => $totalVolume > 0 ? 
                $totalValue / $totalVolume : 0,
            'total_all_orders' => $allOrders->count(),
            'total_filtered_orders' => $orders->count(),
            'percent_filtered' => $allOrders->count() > 0 
                ? round(($orders->count() / $allOrders->count()) * 100, 1) 
                : 0,
        ];
        
        // Get summary by platform
        $platformSummary = [];
        if ($orders->count() > 0) {
            $platformSummary = $orders->groupBy('platform_id')->map(function($platformOrders, $platformId) {
                $platform = Platform::find($platformId);
                
                // Calculate total saldo masuk and volume for this platform
                $totalValue = 0;
                $totalVolume = 0;
                
                foreach ($platformOrders as $order) {
                    // Add financial transactions based on platform
                    if ($platform->name == 'Shopee') {
                        foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'TikTok') {
                        foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'Tokopedia') {
                        foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    } elseif ($platform->name == 'Blibli') {
                        foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $transaction) {
                            $totalValue += $transaction->saldo_masuk;
                            $totalVolume += $transaction->qty > 0 ? $transaction->qty : 0;
                        }
                    }
                }
                
                return [
                    'platform' => $platform ? $platform->name : 'Unknown',
                    'order_count' => $platformOrders->count(),
                    'total_value' => $totalValue,
                    'total_volume' => $totalVolume,
                ];
            });
        }
        
        return view('analytics.monthly_sales_summary', [
            'monthlySummary' => $monthlySummary,
            'platforms' => $platforms,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $selectedPlatform,
            'viewMode' => $viewMode,
            'summary' => $summary,
            'platformSummary' => $platformSummary,
        ]);
    }

    public function salesByMasterProductReport(Request $request)
    {
        $platforms = Platform::all();
        $productCategories = \App\Models\ProductCategory::orderBy('name')->get();
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonth()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            switch ($range) {
                case '7days': $startDate = now()->subDays(7)->format('Y-m-d'); break;
                case '2weeks': $startDate = now()->subWeeks(2)->format('Y-m-d'); break;
                case '1month': $startDate = now()->subMonth()->format('Y-m-d'); break;
                case '3months': $startDate = now()->subMonths(3)->format('Y-m-d'); break;
            }
        }
        
        // --- FILTERS ---
        $selectedPlatform = $request->input('platform_id');
        $search = $request->input('search');
        $orderNumber = $request->input('order_number');
        
        // Get filter data from database (main categories removed per requirement)
        $brands = \App\Models\Brand::orderBy('name')->get();
        $subBrands = \App\Models\SubBrand::orderBy('name')->get();
        $productTypes = \App\Models\ProductType::orderBy('name')->get();
        $productSizes = \App\Models\ProductSize::orderBy('name')->get();
        $productVariants = \App\Models\ProductVariant::orderBy('name')->get();
        
        // Get selected values from request (main categories removed)
        $selectedBrands = (array) $request->input('brands', []);
        $selectedSubBrands = (array) $request->input('sub_brands', []);
        $selectedProductCategories = (array) $request->input('product_categories', []);
        $selectedProductTypes = (array) $request->input('product_types', []);
        $selectedProductSizes = (array) $request->input('product_sizes', []);
        $selectedProductVariants = (array) $request->input('product_variants', []);

        // Cascade: filter sub brands by selected brands
        if (!empty($selectedBrands)) {
            $subBrands = \App\Models\SubBrand::whereIn('brand_id', $selectedBrands)->orderBy('name')->get();
        }
        
        // --- QUERY ORDERS ---
        $query = Order::with([
            'platform',
            'orderItems.platformProduct.mappingBarang.product',
            'orderItems.platformProduct.mappingBarang.product.mainCategory',
            'orderItems.platformProduct.mappingBarang.product.brand',
            'orderItems.platformProduct.mappingBarang.product.subBrand',
            'orderItems.platformProduct.mappingBarang.product.productCategory',
            'orderItems.platformProduct.mappingBarang.product.productType',
            'orderItems.platformProduct.mappingBarang.product.productSize',
            'orderItems.platformProduct.mappingBarang.product.productVariant',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ])->whereBetween('tanggal', [$startDate, $endDate]);
        
        if ($selectedPlatform) $query->where('platform_id', $selectedPlatform);
        if ($orderNumber) $query->where('order_number', 'like', "%$orderNumber%");
        
        // Apply advanced filters
        // main categories filter removed
        
        if (!empty($selectedBrands)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.brand', function($q) use ($selectedBrands) {
                $q->whereIn('id', $selectedBrands);
            });
        }
        
        if (!empty($selectedSubBrands)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.subBrand', function($q) use ($selectedSubBrands) {
                $q->whereIn('id', $selectedSubBrands);
            });
        }
        
        if (!empty($selectedProductCategories)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productCategory', function($q) use ($selectedProductCategories) {
                $q->whereIn('id', $selectedProductCategories);
            });
        }
        
        if (!empty($selectedProductTypes)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productType', function($q) use ($selectedProductTypes) {
                $q->whereIn('id', $selectedProductTypes);
            });
        }
        
        if (!empty($selectedProductSizes)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productSize', function($q) use ($selectedProductSizes) {
                $q->whereIn('id', $selectedProductSizes);
            });
        }
        
        if (!empty($selectedProductVariants)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productVariant', function($q) use ($selectedProductVariants) {
                $q->whereIn('id', $selectedProductVariants);
            });
        }
        
        $allOrders = $query->get();
        
        // --- ONLY PAID ORDERS ---
        $validOrders = $allOrders->filter(function($order) {
            return (
                $order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0
            );
        });
        
        $productRows = collect();
        
        foreach ($validOrders as $order) {
            // Get total saldo masuk by platform and the invoice number
            $totalSaldoMasuk = 0;
            $invoiceNumber = null;
            $platformName = strtolower($order->platform->name);
            
            if ($platformName === 'shopee') {
                $totalSaldoMasuk = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
                $invoiceNumber = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            } elseif ($platformName === 'tiktok') {
                $totalSaldoMasuk = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
                $invoiceNumber = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            } elseif ($platformName === 'tokopedia') {
                $totalSaldoMasuk = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
                $invoiceNumber = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            } elseif ($platformName === 'blibli') {
                $totalSaldoMasuk = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
                $invoiceNumber = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            }
            
            // Calculate total order value from products table (not platform prices)
            $totalOrderValueFromProducts = 0;
            $orderMasterProducts = collect();
            
            // First pass: collect all master products and their selling prices
            foreach ($order->orderItems as $orderItem) {
                $platformProduct = $orderItem->platformProduct;
                if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                    continue;
                }
                
                foreach ($platformProduct->mappingBarang as $mapping) {
                    $product = $mapping->product;
                    if (!$product) continue;
                    
                    // Apply search filter
                    if ($search) {
                        $searchLower = strtolower($search);
                        $match = false;
                        if ($platformProduct && strpos(strtolower($platformProduct->platform_product_name), $searchLower) !== false) $match = true;
                        if ($product && strpos(strtolower($product->name), $searchLower) !== false) $match = true;
                        if ($product && $product->sku && strpos(strtolower($product->sku), $searchLower) !== false) $match = true;
                        if (!$match) continue;
                    }
                    
                    // Apply category filters (use selected arrays, not Eloquent collections)
                    if (!empty($selectedMainCategories) && !in_array($product->main_category_id, $selectedMainCategories)) continue;
                    if (!empty($selectedBrands) && !in_array($product->brand_id, $selectedBrands)) continue;
                    if (!empty($selectedSubBrands) && !in_array($product->sub_brand_id, $selectedSubBrands)) continue;
                    if (!empty($selectedProductCategories) && !in_array($product->product_category_id, $selectedProductCategories)) continue;
                    if (!empty($selectedProductTypes) && !in_array($product->product_type_id, $selectedProductTypes)) continue;
                    if (!empty($selectedProductSizes) && !in_array($product->product_size_id, $selectedProductSizes)) continue;
                    if (!empty($selectedProductVariants) && !in_array($product->product_variant_id, $selectedProductVariants)) continue;
                    
                    // Use selling price (initial_price with discount applied) from products table
                    $pricelistPrice = $this->calculateSellingPrice($product);
                    
                    // Calculate master product quantity (platform qty × mapping qty)
                    $masterProductQty = $orderItem->quantity * $mapping->quantity;
                    
                    // Calculate total value for this master product using pricelist
                    $masterProductValue = $pricelistPrice * $masterProductQty;
                    $totalOrderValueFromProducts += $masterProductValue;
                    
                    $orderMasterProducts->push([
                        'order_item' => $orderItem,
                        'platform_product' => $platformProduct,
                        'mapping' => $mapping,
                        'product' => $product,
                        'selling_price' => $pricelistPrice,
                        'master_qty' => $masterProductQty,
                        'master_value' => $masterProductValue,
                    ]);
                }
            }
            
            if ($orderMasterProducts->isEmpty()) continue;
            
            // Second pass: calculate revenue distribution and create rows
            foreach ($orderMasterProducts as $masterProductData) {
                $orderItem = $masterProductData['order_item'];
                $platformProduct = $masterProductData['platform_product'];
                $mapping = $masterProductData['mapping'];
                $product = $masterProductData['product'];
                $actualPrice = $masterProductData['selling_price'];
                $masterQty = $masterProductData['master_qty'];
                $masterValue = $masterProductData['master_value'];
                
                // Calculate revenue distribution percentage based on pricelist
                // Persentase = (Harga Pricelist 1 Barang / Total Harga Pricelist 1 Order) × 100%
                $revenueDistributionPercent = $totalOrderValueFromProducts > 0 ? 
                    ($masterValue / $totalOrderValueFromProducts) * 100 : 0;
                
                // Calculate proportional saldo masuk
                $proportionalSaldoMasuk = $totalSaldoMasuk * ($revenueDistributionPercent / 100);
                
                // Get actual modal cost from barang keluar records (more accurate)
                // Calculate total modal directly from barang keluar, not average
                $totalModal = $this->getTotalModalFromBarangKeluar($orderItem->id, $product->id);
                
                // If no barang keluar found, fallback to latest purchase cost
                if ($totalModal == 0) {
                    $modalPerUnit = $this->getLatestPurchaseCost($product->id);
                    $totalModal = $modalPerUnit * $masterQty;
                }
                
                $modalPerUnit = $masterQty > 0 ? $totalModal / $masterQty : 0;
                
                // Calculate gross profit using new formula
                // Gross profit per unit = (saldo masuk / qty) - modal per unit
                $grossProfitPerUnit = $masterQty > 0 ? ($proportionalSaldoMasuk / $masterQty) - $modalPerUnit : 0;
                
                // Gross profit total = total saldo masuk order - modal total order
                // For individual product row, we need to calculate proportional gross profit
                $grossProfitTotal = $proportionalSaldoMasuk - $totalModal;
                
                // Check if product is from package
                $isPackageItem = $platformProduct->mappingBarang->count() > 1;
                $packageInfo = null;
                if ($isPackageItem) {
                    $packageInfo = [
                        'is_package_item' => true,
                        'package_name' => $platformProduct->platform_product_name,
                    ];
                }
                
                $productRows->push([
                    'platform_product_id' => $platformProduct->id,
                    'platform_product_name' => $platformProduct->platform_product_name,
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id, // Added for barang keluar calculation
                    'product_id' => $product->id, // Added for barang keluar calculation
                    'order_number' => $order->order_number,
                    'order_date' => $order->tanggal,
                    'platform' => $order->platform->name,
                    'quantity' => $masterQty, // Master product quantity (platform qty × mapping qty)
                    'platform_quantity' => $orderItem->quantity, // QTY yang diorder di platform
                    'price' => $pricelistPrice, // Pricelist price from products table
                    'revenue' => $proportionalSaldoMasuk, // Proportional saldo masuk (per product)
                    'order_total_payment' => $totalSaldoMasuk, // Total saldo masuk untuk 1 order (sama untuk semua row dalam order)
                    'capital' => $totalModal, // Total modal (modal per unit × qty)
                    'gross_profit_per_unit' => $grossProfitPerUnit, // (saldo masuk / qty) - modal per unit
                    'gross_profit_total' => $grossProfitTotal, // saldo masuk - modal x qty
                    'margin_percent' => $proportionalSaldoMasuk > 0 ? ($grossProfitTotal / $proportionalSaldoMasuk) * 100 : 0,
                    'proportion_percent' => round($revenueDistributionPercent, 2),
                    'invoice_number' => $invoiceNumber,
                    'product_name' => $product->name,
                    'category' => $product->productCategory ? $product->productCategory->name : 'N/A',
                    'brand' => $product->brand ? $product->brand->name : 'N/A',
                    'sku' => $product->sku ?? 'N/A',
                    'main_category' => $product->mainCategory ? $product->mainCategory->name : 'N/A',
                    'sub_brand' => $product->subBrand ? $product->subBrand->name : 'N/A',
                    'product_type' => $product->productType ? $product->productType->name : 'N/A',
                    'product_size' => $product->productSize ? $product->productSize->name : 'N/A',
                    'product_variant' => $product->productVariant ? $product->productVariant->name : 'N/A',
                    'package_info' => $packageInfo,
                ]);
            }
        }
        
        // --- SORT ---
        $sortBy = $request->input('sort', 'revenue_highest');
        $productRows = $this->sortProductRows($productRows, $sortBy);
        
        // --- SUMMARY ---
        $totalRevenue = $productRows->sum('revenue');
        $totalCapital = $productRows->sum('capital');
        $totalGrossProfit = $totalRevenue - $totalCapital;
        
        // Calculate total barang keluar count for master products
        $totalBarangKeluar = 0;
        foreach ($productRows as $row) {
            // Count barang keluar records for this order item and product
            $barangKeluarCount = \App\Models\BarangKeluar::where('order_item_id', $row['order_item_id'] ?? null)
                ->whereHas('warehouseStock', function($query) use ($row) {
                    $query->where('product_id', $row['product_id'] ?? null);
                })
                ->count();
            $totalBarangKeluar += $barangKeluarCount;
        }
        
        $summary = [
            'total_products' => $totalBarangKeluar, // Changed to barang keluar count
            'total_rows' => $productRows->count(),
            'total_revenue' => $totalRevenue,
            'total_capital' => $totalCapital,
            'total_gross_profit' => $totalGrossProfit,
            'total_quantity' => $productRows->sum('quantity'),
            'profit_margin' => $totalRevenue > 0 ? ($totalGrossProfit / $totalRevenue) * 100 : 0,
        ];
        
        // --- PAGINATION ---
        $perPage = 20;
        $page = $request->input('page', 1);
        $paginatedRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $productRows->values()->forPage($page, $perPage),
            $productRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        // --- RETURN ---
        return view('analytics.sales_by_master_product_new', [
            'productRows' => $paginatedRows,
            'platforms' => $platforms,
            'productCategories' => $productCategories,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $selectedPlatform,
            'sortBy' => $sortBy,
            'summary' => $summary,
            // For filters
            'brands' => $brands,
            'subBrands' => $subBrands,
            'productTypes' => $productTypes,
            'productSizes' => $productSizes,
            'productVariants' => $productVariants,
            'selectedBrands' => $selectedBrands,
            'selectedSubBrands' => $selectedSubBrands,
            'selectedProductCategories' => $selectedProductCategories,
            'selectedProductTypes' => $selectedProductTypes,
            'selectedProductSizes' => $selectedProductSizes,
            'selectedProductVariants' => $selectedProductVariants,
        ]);
    }

    public function salesByPlatformProductReport(Request $request)
    {
        // Get platforms for filter
        $platforms = Platform::all();
        
        // Get product categories
        $productCategories = \App\Models\ProductCategory::orderBy('name')->get();
        
        // Set default date range (last month if not provided)
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonth()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        $selectedPlatform = $request->input('platform_id');
        $search = $request->input('search');
        $orderNumber = $request->input('order_number');
        $sortBy = $request->input('sort', 'revenue_highest');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }
        
        // Build the query for orders
        $query = Order::with([
            'platform',
            'orderItems',
            'orderItems.platformProduct.mappingBarang.product',
            'orderItems.warehouseStock',
            'orderItems.warehouseStock.product',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);
        
        // Apply date filter
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if set
        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }
        
        // Apply order number filter if set
        if ($orderNumber) {
            $query->where('order_number', 'like', "%$orderNumber%");
        }
        
        // Get all orders
        $allOrders = $query->get();
        
        // Filter orders to only include those with valid financial transactions (having saldo_masuk)
        $validOrders = $allOrders->filter(function($order) {
            return (
                $order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0
            );
        });
        
        // Process platform products
        $platformProductRows = collect();
        
        foreach ($validOrders as $order) {
            // Get paid amount by platform
            $totalSaldoMasuk = 0;
            $platformName = strtolower($order->platform->name);
            if ($platformName === 'shopee') {
                $totalSaldoMasuk = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'tiktok') {
                $totalSaldoMasuk = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'tokopedia') {
                $totalSaldoMasuk = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'blibli') {
                $totalSaldoMasuk = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            }
            
            $validOrderItems = [];
            $totalOrderValue = 0;
            
            foreach ($order->orderItems as $item) {
                $platformProduct = $item->platformProduct;
                
                // Apply search filter
                if ($search) {
                    $searchLower = strtolower($search);
                    $match = false;
                    if ($platformProduct && strpos(strtolower($platformProduct->platform_product_name), $searchLower) !== false) $match = true;
                    if (!$match) continue;
                }
                
                    $validOrderItems[] = $item;
                    $itemValue = $item->price_after_discount * $item->quantity;
                    $totalOrderValue += $itemValue;
            }
            
            if (count($validOrderItems) === 0) continue;
            
            foreach ($validOrderItems as $item) {
                $itemProportion = 1; // Inisialisasi default
                $platformProduct = $item->platformProduct;
                $itemValue = $item->price_after_discount * $item->quantity;
                $productPrice = $item->price_after_discount;
                
                if (count($validOrderItems) === 1) {
                    // Paid-only: gunakan saldo_masuk aktual seluruh order
                    $itemProportion = 1;
                    $itemRevenue = $totalSaldoMasuk; 
                } else {
                    // Distribusikan hanya dari saldo_masuk aktual, tanpa fallback harga
                    $itemProportion = $totalOrderValue > 0 ? $itemValue / $totalOrderValue : 0;
                    $itemRevenue = $totalSaldoMasuk * $itemProportion;
                }
                
                // Calculate modal (cost)
                $totalModalCost = 0;
                $mappedProducts = [];
                
                // Get all barang_keluar records for this order item
                $barangKeluarItems = \App\Models\BarangKeluar::where('order_item_id', $item->id)
                    ->with(['warehouseStock.penerimaanDetail.penerimaan.taxCategory', 'warehouseStock.product'])
                    ->get();
                
                if ($platformProduct->mappingBarang && $platformProduct->mappingBarang->count() > 0) {
                    // Group barang_keluar by product_id to match with mappings
                    $barangKeluarByProduct = $barangKeluarItems->groupBy(function($barangKeluar) {
                        return $barangKeluar->warehouseStock ? $barangKeluar->warehouseStock->product_id : null;
                    })->filter(function($group, $productId) {
                        return $productId !== null;
                    });
                    
                    foreach ($platformProduct->mappingBarang as $mapping) {
                        if ($mapping->product) {
                            $product = $mapping->product;
                            $productId = $product->id;
                            
                            // Get barang_keluar items for this specific product
                            $productBarangKeluar = $barangKeluarByProduct->get($productId, collect());
                            
                            $totalProductCost = 0;
                            $totalProductQty = 0;
                            
                            foreach ($productBarangKeluar as $barangKeluar) {
                                if ($barangKeluar->warehouseStock && $barangKeluar->warehouseStock->penerimaanDetail) {
                                    $penerimaanDetail = $barangKeluar->warehouseStock->penerimaanDetail;
                                    $hpp = $penerimaanDetail->harga_hpp;
                                    $qty = $barangKeluar->qty;
                                    
                                    // Apply discounts
                                    $hppSetelahDiskon = $hpp;
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
                                    $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_1;
                                    $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_2;
                                    $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_3;
                                    $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_4;
                                    $hppSetelahDiskon -= $penerimaanDetail->diskon_nominal_5;
                                    
                                    // Ensure price doesn't go negative
                                    $finalHpp = max(0, $hppSetelahDiskon);
                                    
                                    // Add tax for HGN (products purchased with tax)
                                    if ($penerimaanDetail->penerimaan && $penerimaanDetail->penerimaan->taxCategory && $penerimaanDetail->penerimaan->taxCategory->name === 'HGN') {
                                        $taxPercentage = $penerimaanDetail->penerimaan->taxCategory->tax_percentage ?? 0;
                                        $finalHpp = $finalHpp * (1 + ($taxPercentage / 100));
                                    }
                                    
                                    $itemCost = $finalHpp * $qty;
                                    $totalProductCost += $itemCost;
                                    $totalProductQty += $qty;
                                }
                            }
                            
                            $totalModalCost += $totalProductCost;
                            
                            $unitCost = $totalProductQty > 0 ? $totalProductCost / $totalProductQty : 0;
                            
                            $mappedProducts[] = [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'sku' => $product->sku,
                                'quantity' => $totalProductQty,
                                'cost' => $totalProductCost,
                                'unit_cost' => $unitCost
                            ];
                        }
                    }
                }
                
                // Calculate gross profit and margin
                $modalPerUnit = $item->quantity > 0 ? $totalModalCost / $item->quantity : 0;
                $grossProfit = $itemRevenue - $totalModalCost; // Gross profit = saldo masuk - modal x qty
                $marginPercent = $itemRevenue > 0 ? ($grossProfit / $itemRevenue) * 100 : 0; // Margin dari saldo masuk, bukan harga jual
                
                // Get invoice number from financial transactions
                $invoiceNumber = null;
                $platformName = strtolower($order->platform->name);
                if ($platformName === 'shopee') {
                    $invoiceNumber = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                } elseif ($platformName === 'tiktok') {
                    $invoiceNumber = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                } elseif ($platformName === 'tokopedia') {
                    $invoiceNumber = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                } elseif ($platformName === 'blibli') {
                    $invoiceNumber = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                }
                
                // Fallback to order invoice_number if not found in financial transactions
                if (!$invoiceNumber) {
                    $invoiceNumber = $order->invoice_number ?? '-';
                }

                $platformProductRows->push([
                    'platform_product_id' => $platformProduct->id,
                    'platform_product_name' => $platformProduct->platform_product_name,
                    'platform_sku' => $platformProduct->sku,
                    'product_variant' => $platformProduct->variant ?? '-',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_number' => $invoiceNumber,
                    'order_date' => $order->tanggal,
                    'platform' => $order->platform->name,
                    'quantity' => $item->quantity,
                    'price' => $productPrice,
                    'revenue' => $itemRevenue,
                    'capital' => $totalModalCost,
                    'gross_profit' => $grossProfit,
                    'margin_percent' => $marginPercent,
                    'mapped_products' => $mappedProducts,
                    'has_multiple_items' => count($validOrderItems) > 1,
                    'proportion_percent' => round($itemProportion * 100, 2),
                ]);
            }
        }
        
        // Sort the platform product rows based on user selection
        switch ($sortBy) {
            case 'profit_highest':
                $platformProductRows = $platformProductRows->sortByDesc('gross_profit');
                break;
            case 'profit_lowest':
                $platformProductRows = $platformProductRows->sortBy('gross_profit');
                break;
            case 'revenue_highest':
                $platformProductRows = $platformProductRows->sortByDesc('revenue');
                break;
            case 'revenue_lowest':
                $platformProductRows = $platformProductRows->sortBy('revenue');
                break;
            case 'quantity_highest':
                $platformProductRows = $platformProductRows->sortByDesc('quantity');
                break;
            case 'quantity_lowest':
                $platformProductRows = $platformProductRows->sortBy('quantity');
                break;
            default:
                $platformProductRows = $platformProductRows->sortByDesc('revenue');
                break;
        }
        
        // Calculate summary
        // total_products diselaraskan dengan laporan master: hitung produk master unik
        $uniqueMasterProductNames = $platformProductRows
            ->flatMap(function($row){ return collect($row['mapped_products'] ?? [])->pluck('name'); })
            ->filter()
            ->unique()
            ->count();

        // Calculate total unique orders for platform products
        $totalUniqueOrders = $platformProductRows->unique('order_number')->count();
        
        // Calculate orders after returns - only count orders that have remaining quantity after returns
        $ordersAfterReturns = 0;
        $uniqueOrderNumbers = $platformProductRows->unique('order_number')->pluck('order_number');
        
        foreach ($uniqueOrderNumbers as $orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
            if ($order) {
                $hasRemainingItems = false;
                
                foreach ($order->orderItems as $item) {
                    // Calculate return quantity for this item
                    $qtyReturIndividual = ReturPenjualanDetail::where('order_item_id', $item->id)
                        ->whereHas('returPenjualan', function($q) { 
                            $q->whereIn('status', ['draft', 'selesai']); 
                        })
                        ->sum('qty');
                    $qtyReturIndividual = (float) $qtyReturIndividual;
                    
                    // Check if this is a package product and get total package quantity
                    $packageQuantity = 1;
                    if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                        $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
                    }
                    
                    // Convert individual retur quantity back to package quantity
                    $qtyRetur = $packageQuantity > 0 ? $qtyReturIndividual / $packageQuantity : $qtyReturIndividual;
                    
                    // Calculate original quantity (current + returned)
                    $currentQty = (float) ($item->quantity ?? 0);
                    $originalQty = $currentQty + $qtyRetur;
                    
                    // Calculate remaining quantity after return
                    $remainingQty = max(0.0, $originalQty - $qtyRetur);
                    
                    if ($remainingQty > 0) {
                        $hasRemainingItems = true;
                        break; // At least one item has remaining quantity
                    }
                }
                
                if ($hasRemainingItems) {
                    $ordersAfterReturns++;
                }
            }
        }
        
        $summary = [
            'total_platform_products' => $totalUniqueOrders, // Changed to unique order count
            'total_platform_products_after_returns' => $ordersAfterReturns,
            'total_products' => $uniqueMasterProductNames,
            'total_rows' => $platformProductRows->count(),
            'total_revenue' => $platformProductRows->sum('revenue'),
            'total_capital' => $platformProductRows->sum('capital'),
            'total_gross_profit' => $platformProductRows->sum('gross_profit'),
            'total_quantity' => $platformProductRows->sum('quantity'),
            'profit_margin' => $platformProductRows->sum('revenue') > 0 ? ($platformProductRows->sum('gross_profit') / $platformProductRows->sum('revenue')) * 100 : 0,
        ];
        
        // Paginate the results
        $perPage = 20;
        $page = $request->input('page', 1);
        $paginatedRows = new \Illuminate\Pagination\LengthAwarePaginator(
            $platformProductRows->values()->forPage($page, $perPage),
            $platformProductRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.sales_by_platform_product', [
            'platformProductRows' => $paginatedRows,
            'platforms' => $platforms,
            'productCategories' => $productCategories,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedPlatform' => $selectedPlatform,
            'search' => $search,
            'orderNumber' => $orderNumber,
            'sortBy' => $sortBy,
            'summary' => $summary,
        ]);
    }

    /**
     * Return SubBrands filtered by selected Brand IDs (JSON)
     */
    public function getSubBrands(Request $request)
    {
        $brandIds = $request->input('brand_ids', $request->input('brand_ids', []));
        if (!is_array($brandIds)) {
            // Support single id or comma separated
            $brandIds = array_filter(explode(',', (string) $brandIds));
        }

        $query = \App\Models\SubBrand::query();
        if (!empty($brandIds)) {
            $query->whereIn('brand_id', $brandIds);
        } else {
            // No brand selected → return empty to enforce cascading behavior
            return response()->json([]);
        }

        $subBrands = $query->orderBy('name')->get(['id', 'name', 'brand_id']);
        return response()->json($subBrands);
    }

    /**
     * Return Product Types filtered by selected Category IDs (JSON)
     */
    public function getProductTypes(Request $request)
    {
        $categoryIds = $request->input('category_ids', []);
        if (!is_array($categoryIds)) {
            $categoryIds = array_filter(explode(',', (string) $categoryIds));
        }
        if (empty($categoryIds)) {
            return response()->json([]);
        }
        $types = \App\Models\ProductType::whereIn('product_category_id', $categoryIds)
            ->orderBy('name')
            ->get(['id', 'name', 'product_category_id']);
        return response()->json($types);
    }

    /**
     * Return Product Sizes filtered by selected Type IDs (JSON)
     */
    public function getProductSizes(Request $request)
    {
        $typeIds = $request->input('type_ids', []);
        if (!is_array($typeIds)) {
            $typeIds = array_filter(explode(',', (string) $typeIds));
        }
        if (empty($typeIds)) {
            return response()->json([]);
        }
        $sizes = \App\Models\ProductSize::whereIn('product_type_id', $typeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'product_type_id']);
        return response()->json($sizes);
    }

    /**
     * Return Product Variants filtered by selected Size IDs (JSON)
     */
    public function getProductVariants(Request $request)
    {
        $sizeIds = $request->input('size_ids', []);
        if (!is_array($sizeIds)) {
            $sizeIds = array_filter(explode(',', (string) $sizeIds));
        }
        if (empty($sizeIds)) {
            return response()->json([]);
        }
        $variants = \App\Models\ProductVariant::whereIn('product_size_id', $sizeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'product_size_id']);
        return response()->json($variants);
    }

    /**
     * Return Product Categories filtered by selected SubBrand IDs (JSON)
     */
    public function getProductCategories(Request $request)
    {
        $subBrandIds = $request->input('sub_brand_ids', []);
        if (!is_array($subBrandIds)) {
            $subBrandIds = array_filter(explode(',', (string) $subBrandIds));
        }
        if (empty($subBrandIds)) {
            return response()->json([]);
        }

        // Find distinct category IDs used by products under the selected sub-brands
        $categoryIds = \App\Models\Product::whereIn('sub_brand_id', $subBrandIds)
            ->whereNotNull('product_category_id')
            ->distinct()
            ->pluck('product_category_id')
            ->toArray();

        if (empty($categoryIds)) {
            return response()->json([]);
        }

        $categories = \App\Models\ProductCategory::whereIn('id', $categoryIds)
            ->orderBy('name')
            ->get(['id','name']);

        return response()->json($categories);
    }

    /**
     * Display sales data by month for offline sales
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function offlineMonthlySalesSummaryReport(Request $request)
    {
        // Get default date range - current year
        $currentYear = date('Y');
        $selectedYear = $request->input('year', $currentYear);
        $selectedCustomer = $request->input('customer_id');
        
        // Get all customers for the filter
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        // Build the query for offline sales
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'items.product', 'customerInfo'])
            ->whereYear('sale_date', $selectedYear);
            
        // Apply customer filter if selected
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        // Get the sales
        $sales = $query->get();
        
        // Initialize monthly summary data
        $monthlySummary = collect();
        
        // Process each month
        for ($month = 1; $month <= 12; $month++) {
            $monthName = date('F', mktime(0, 0, 0, $month, 1, $selectedYear));
            
            // Filter sales for this month
            $monthSales = $sales->filter(function ($sale) use ($month) {
                return $sale->sale_date->month == $month;
            });
            
            // Calculate metrics for this month
            $totalOrders = $monthSales->count();
            $totalValue = $monthSales->sum('total_amount');
            
            $totalVolume = 0;
            foreach ($monthSales as $sale) {
                foreach ($sale->items as $item) {
                    $totalVolume += $item->quantity;
                }
            }
            
            // Get average metrics
            $avgOrderValue = $totalOrders > 0 ? $totalValue / $totalOrders : 0;
            $avgOrderVolume = $totalOrders > 0 ? $totalVolume / $totalOrders : 0;
            
            // Add to monthly summary
            $monthlySummary->push([
                'month' => $month,
                'month_name' => $monthName,
                'total_orders' => $totalOrders,
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
                'avg_order_value' => $avgOrderValue,
                'avg_order_volume' => $avgOrderVolume,
            ]);
        }
        
        // Calculate year totals for summary
        $yearSummary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('total_amount'),
            'total_volume' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
        ];
        
        // Calculate averages
        $yearSummary['avg_order_value'] = $yearSummary['total_orders'] > 0 
            ? $yearSummary['total_value'] / $yearSummary['total_orders'] 
            : 0;
            
        $yearSummary['avg_order_volume'] = $yearSummary['total_orders'] > 0 
            ? $yearSummary['total_volume'] / $yearSummary['total_orders'] 
            : 0;
        
        // Available years for dropdown (last 5 years)
        $availableYears = [];
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $availableYears[$year] = $year;
        }
        
        return view('analytics.offline_monthly_sales_summary', [
            'monthlySummary' => $monthlySummary,
            'yearSummary' => $yearSummary,
            'selectedYear' => $selectedYear,
            'selectedCustomer' => $selectedCustomer,
            'availableYears' => $availableYears,
            'customers' => $customers,
        ]);
    }

    /**
     * Display sales data by customer for offline sales
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function offlineSalesByCustomerReport(Request $request)
    {
        // Set default date range
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        
        // Get selected customer if any
        $selectedCustomer = $request->input('customer_id');
        
        // Get all customers for the filter
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        // Build the query for offline sales
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'items.product', 'customerInfo']);
            
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }
        
        // Apply customer filter if selected
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        // Get the sales
        $sales = $query->get();
        
        // Group sales by customer
        $customerSummary = $sales->groupBy('customer_id')->map(function ($customerSales, $customerId) {
            $customer = $customerSales->first()->customerInfo;
            $customerName = $customer ? $customer->name : 'Unknown';
            
            $totalOrders = $customerSales->count();
            $totalValue = $customerSales->sum('total_amount');
            
            $totalVolume = 0;
            foreach ($customerSales as $sale) {
                foreach ($sale->items as $item) {
                    $totalVolume += $item->quantity;
                }
            }
            
            return [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'total_orders' => $totalOrders,
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
                'avg_order_value' => $totalOrders > 0 ? $totalValue / $totalOrders : 0,
                'avg_order_volume' => $totalOrders > 0 ? $totalVolume / $totalOrders : 0,
            ];
        });
        
        // Sort by the selected criteria
        $sortBy = $request->input('sort', 'value_highest');
        
        switch ($sortBy) {
            case 'value_highest':
                $customerSummary = $customerSummary->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $customerSummary = $customerSummary->sortBy('total_value');
                break;
            case 'volume_highest':
                $customerSummary = $customerSummary->sortByDesc('total_volume');
                break;
            case 'volume_lowest':
                $customerSummary = $customerSummary->sortBy('total_volume');
                break;
            case 'orders_highest':
                $customerSummary = $customerSummary->sortByDesc('total_orders');
                break;
            case 'orders_lowest':
                $customerSummary = $customerSummary->sortBy('total_orders');
                break;
            case 'name_asc':
                $customerSummary = $customerSummary->sortBy('customer_name');
                break;
            case 'name_desc':
                $customerSummary = $customerSummary->sortByDesc('customer_name');
                break;
            default:
                $customerSummary = $customerSummary->sortByDesc('total_value');
        }
        
        // Calculate overall summary
        $summary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('total_amount'),
            'total_volume' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 
            ? $summary['total_value'] / $summary['total_orders'] 
            : 0;
            
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 
            ? $summary['total_volume'] / $summary['total_orders'] 
            : 0;
        
        return view('analytics.offline_sales_by_customer', [
            'customerSummary' => $customerSummary,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCustomer' => $selectedCustomer,
            'sortBy' => $sortBy,
            'customers' => $customers,
        ]);
    }

    /**
     * Calculate total value after all cascading discounts for an offline sale item
     */
    private function calculateTotalAfterDiscounts($item)
    {
        $basePrice = (float)($item->unit_price ?? 0);
        $qty = (float)($item->quantity ?? 0);
        
        // Start with base total (price × quantity)
        $currentTotal = $basePrice * $qty;
        
        // Apply percentage discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $percentField = "discount_percent_" . $i;
            $discountPercent = (float)($item->$percentField ?? 0);
            if($discountPercent > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculatePercentageDiscount($currentTotal, $discountPercent);
            }
        }
        
        // Apply nominal discounts (1-5) in cascading order
        for($i = 1; $i <= 5; $i++) {
            $amountField = "discount_amount_" . $i;
            $discountAmount = (float)($item->$amountField ?? 0);
            if($discountAmount > 0) {
                $currentTotal = \App\Helpers\NumberFormatter::calculateNominalDiscount($currentTotal, $discountAmount * $qty);
            }
        }
        
        return \App\Helpers\NumberFormatter::formatForDatabase($currentTotal);
    }

    /**
     * Display detailed sales report for offline sales
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function offlineSalesDetailReport(Request $request)
    {
        // Set default date range
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        
        // Get selected customer if any
        $selectedCustomer = $request->input('customer_id');
        
        // Get all customers for the filter
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        // Build the query for offline sales
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'items.product', 'customerInfo']);
            
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }
        
        // Apply customer filter if selected
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        // Get the sales
        $sales = $query->get();
        
        // Sort by the selected criteria
        $sortBy = $request->input('sort', 'date_newest');
        
        switch ($sortBy) {
            case 'value_highest':
                $sales = $sales->sortByDesc('total_amount');
                break;
            case 'value_lowest':
                $sales = $sales->sortBy('total_amount');
                break;
            case 'date_newest':
                $sales = $sales->sortByDesc('sale_date');
                break;
            case 'date_oldest':
                $sales = $sales->sortBy('sale_date');
                break;
            default:
                $sales = $sales->sortByDesc('sale_date');
        }
        
        // Calculate total volume for each sale
        $sales = $sales->map(function ($sale) {
            $sale->total_volume = $sale->items->sum('quantity');
            
            // Calculate actual value and volume after returns
            $qtyRetur = 0;
            $valueAfterReturns = 0;
            
            foreach ($sale->items as $item) {
                // Get return quantity for this specific item
                $itemQtyRetur = \App\Models\ReturOfflineSaleDetail::where('offline_sale_item_id', $item->id)
                    ->whereHas('returOfflineSale', function($q) { $q->where('status', 'selesai'); })
                    ->sum('qty');
                
                $itemQtyRetur = (float) $itemQtyRetur;
                $qtyRetur += $itemQtyRetur;
                
                // Calculate quantity after return for this item
                $qtyAfterRetur = max(0, $item->quantity - $itemQtyRetur);
                
                // ✅ Calculate total value after ALL DISCOUNTS for this item
                $itemTotalAfterDiscounts = $this->calculateTotalAfterDiscounts($item);
                
                // ✅ Use full value regardless of returns - VALUE should show original total with discounts
                $valueAfterReturns += $itemTotalAfterDiscounts;
            }
            
            // Set calculated values
            $sale->total_retur_qty = $qtyRetur;
            $sale->total_volume_after_returns = $sale->total_volume; // ✅ Use original volume, not reduced by returns
            $sale->value_after_returns = $valueAfterReturns;
            
            return $sale;
        });
        
        // Calculate overall summary using corrected values
        $summary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('value_after_returns'), // Use value after returns
            'total_volume' => $sales->sum('total_volume_after_returns'), // Use original volume (not reduced by returns)
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 
            ? $summary['total_value'] / $summary['total_orders'] 
            : 0;
            
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 
            ? $summary['total_volume'] / $summary['total_orders'] 
            : 0;
        
        return view('analytics.offline_sales_detail_report', [
            'sales' => $sales,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCustomer' => $selectedCustomer,
            'sortBy' => $sortBy,
            'customers' => $customers,
        ]);
    }

    /**
     * Display sales data by product for offline sales
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function offlineSalesByProductReport(Request $request)
    {
        // Set default date range
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        
        // Get selected customer and product if any
        $selectedCustomer = $request->input('customer_id');
        $selectedProduct = $request->input('product_id');
        
        // Get all customers and products for the filter
        $customers = \App\Models\Customer::orderBy('name')->get();
        $products = \App\Models\Product::orderBy('name')->get();
        
        // Build the query for offline sales
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items.product', 'customerInfo']);
            
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }
        
        // Apply customer filter if selected
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        // Get the sales
        $sales = $query->get();
        
        // Filter sale items by product if selected
        $allSaleItems = collect();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if (!$selectedProduct || $item->product_id == $selectedProduct) {
                    // Add sale data to the item
                    $item->sale_date = $sale->sale_date;
                    $item->customer_name = $sale->customerInfo ? $sale->customerInfo->name : 'Unknown';
                    $item->surat_jalan_number = $sale->surat_jalan_number;
                    
                    $allSaleItems->push($item);
                }
            }
        }
        
        // Group items by product
        $productSummary = $allSaleItems->groupBy('product_id')->map(function ($items, $productId) {
            $product = $items->first()->product;
            $productName = $product ? $product->name : 'Unknown';
            
            $totalQuantity = $items->sum('quantity');
            $totalValue = $items->sum('subtotal');
            
            return [
                'product_id' => $productId,
                'product_name' => $productName,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'avg_price' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
            ];
        });
        
        // Sort by the selected criteria
        $sortBy = $request->input('sort', 'value_highest');
        
        switch ($sortBy) {
            case 'value_highest':
                $productSummary = $productSummary->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $productSummary = $productSummary->sortBy('total_value');
                break;
            case 'quantity_highest':
                $productSummary = $productSummary->sortByDesc('total_quantity');
                break;
            case 'quantity_lowest':
                $productSummary = $productSummary->sortBy('total_quantity');
                break;
            case 'name_asc':
                $productSummary = $productSummary->sortBy('product_name');
                break;
            case 'name_desc':
                $productSummary = $productSummary->sortByDesc('product_name');
                break;
            default:
                $productSummary = $productSummary->sortByDesc('total_value');
        }
        
        // Calculate overall summary
        $summary = [
            'total_products' => $productSummary->count(),
            'total_value' => $productSummary->sum('total_value'),
            'total_quantity' => $productSummary->sum('total_quantity'),
        ];
        
        return view('analytics.offline_sales_by_product', [
            'productSummary' => $productSummary,
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedCustomer' => $selectedCustomer,
            'selectedProduct' => $selectedProduct,
            'sortBy' => $sortBy,
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    /**
     * Export monthly sales summary to Excel
     */
    public function exportMonthlySalesSummary(Request $request)
    {
        // Get the same data as the view
        $platforms = Platform::all();
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonths(6)->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        $selectedPlatform = $request->input('platform_id');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
                case '6months':
                    $startDate = now()->subMonths(6)->format('Y-m-d');
                    break;
                case '1year':
                    $startDate = now()->subYear()->format('Y-m-d');
                    break;
            }
        }

        // Build the query for orders (same logic as in the view method)
        $query = Order::with(['platform', 'items', 'financialTransactions']);
        
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }
        
        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }
        
        $orders = $query->get();
        
        // Filter orders that have financial transactions
        $validOrders = $orders->filter(function ($order) {
            return $order->financialTransactions->isNotEmpty();
        });

        // Group by month and calculate summary
        $monthlySummary = $validOrders->groupBy(function ($order) {
            return $order->order_date->format('Y-m');
        })->map(function ($monthOrders, $yearMonth) {
            $totalValue = $monthOrders->sum(function ($order) {
                return $order->financialTransactions->sum('nominal_fix');
            });
            
            $totalVolume = $monthOrders->sum(function ($order) {
                return $order->items->sum('quantity');
            });
            
            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->format('M Y'),
                'order_count' => $monthOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        })->sortBy('year_month')->values();

        // Calculate summary
        $summary = [
            'total_orders' => $validOrders->count(),
            'total_value' => $validOrders->sum(function ($order) {
                return $order->financialTransactions->sum('nominal_fix');
            }),
            'total_volume' => $validOrders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $platformName = $selectedPlatform ? $platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown' : null;
        
        $filename = 'analisis-saldo-masuk-bulanan-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new MonthlySalesSummaryExport($monthlySummary, $summary, $startDate, $endDate, $platformName), $filename);
    }

    /**
     * Export offline monthly sales to Excel
     */
    public function exportOfflineMonthlySales(Request $request)
    {
        // Get the same data as the view
        $selectedYear = $request->input('year', date('Y'));
        $selectedCustomer = $request->input('customer_id');
        
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'customerInfo'])
            ->whereYear('sale_date', $selectedYear);
            
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        $sales = $query->get();
        
        // Group by month
        $monthlySummary = $sales->groupBy(function ($sale) {
            return $sale->sale_date->format('Y-m');
        })->map(function ($monthSales, $yearMonth) {
            $totalVolume = $monthSales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });
            
            return [
                'year_month' => $yearMonth,
                'month_name' => Carbon::createFromFormat('Y-m', $yearMonth)->format('M Y'),
                'total_orders' => $monthSales->count(),
                'total_value' => $monthSales->sum('total_amount'),
                'total_volume' => $totalVolume,
                'avg_order_value' => $monthSales->count() > 0 ? $monthSales->sum('total_amount') / $monthSales->count() : 0,
                'avg_order_volume' => $monthSales->count() > 0 ? $totalVolume / $monthSales->count() : 0,
            ];
        })->sortBy('year_month')->values();

        // Calculate year summary
        $yearSummary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('total_amount'),
            'total_volume' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
        ];
        
        $yearSummary['avg_order_value'] = $yearSummary['total_orders'] > 0 ? $yearSummary['total_value'] / $yearSummary['total_orders'] : 0;
        $yearSummary['avg_order_volume'] = $yearSummary['total_orders'] > 0 ? $yearSummary['total_volume'] / $yearSummary['total_orders'] : 0;

        $customerName = $selectedCustomer ? $customers->where('id', $selectedCustomer)->first()->name ?? 'Unknown' : null;
        
        $filename = 'penjualan-bulanan-offline-' . $selectedYear . '-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new OfflineMonthlySalesExport($monthlySummary, $yearSummary, $selectedYear, $customerName), $filename);
    }

    /**
     * Export offline sales by customer to Excel
     */
    public function exportOfflineSalesByCustomer(Request $request)
    {
        // Get the same data as the view
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        $selectedCustomer = $request->input('customer_id');
        $sortBy = $request->input('sort', 'value_highest');
        
        $customers = \App\Models\Customer::orderBy('name')->get();
        
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'customerInfo']);
            
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }
        
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        $sales = $query->get();
        
        // Group by customer
        $customerSummary = $sales->groupBy('customer_id')->map(function ($customerSales, $customerId) {
            $customer = $customerSales->first()->customerInfo;
            $customerName = $customer ? $customer->name : 'Unknown';
            
            $totalVolume = $customerSales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            });
            
            return [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'total_orders' => $customerSales->count(),
                'total_value' => $customerSales->sum('total_amount'),
                'total_volume' => $totalVolume,
                'avg_order_value' => $customerSales->count() > 0 ? $customerSales->sum('total_amount') / $customerSales->count() : 0,
                'avg_order_volume' => $customerSales->count() > 0 ? $totalVolume / $customerSales->count() : 0,
            ];
        });

        // Sort data
        switch ($sortBy) {
            case 'value_highest':
                $customerSummary = $customerSummary->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $customerSummary = $customerSummary->sortBy('total_value');
                break;
            case 'volume_highest':
                $customerSummary = $customerSummary->sortByDesc('total_volume');
                break;
            case 'volume_lowest':
                $customerSummary = $customerSummary->sortBy('total_volume');
                break;
            case 'orders_highest':
                $customerSummary = $customerSummary->sortByDesc('total_orders');
                break;
            case 'orders_lowest':
                $customerSummary = $customerSummary->sortBy('total_orders');
                break;
            case 'name_asc':
                $customerSummary = $customerSummary->sortBy('customer_name');
                break;
            case 'name_desc':
                $customerSummary = $customerSummary->sortByDesc('customer_name');
                break;
            default:
                $customerSummary = $customerSummary->sortByDesc('total_value');
        }

        // Calculate summary
        $summary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('total_amount'),
            'total_volume' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $customerName = $selectedCustomer ? $customers->where('id', $selectedCustomer)->first()->name ?? 'Unknown' : null;
        
        $filename = 'penjualan-offline-by-customer-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new OfflineSalesByCustomerExport($customerSummary, $summary, $startDate, $endDate, $customerName), $filename);
    }

    /**
     * Export offline sales by product to Excel
     */
    public function exportOfflineSalesByProduct(Request $request)
    {
        // Get the same data as the view
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        $selectedCustomer = $request->input('customer_id');
        $selectedProduct = $request->input('product_id');
        $sortBy = $request->input('sort', 'value_highest');
        
        $customers = \App\Models\Customer::orderBy('name')->get();
        $products = \App\Models\Product::orderBy('name')->get();
        
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items.product', 'customerInfo']);
            
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }
        
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        $sales = $query->get();
        
        // Filter sale items by product if selected
        $allSaleItems = collect();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if (!$selectedProduct || $item->product_id == $selectedProduct) {
                    $allSaleItems->push($item);
                }
            }
        }
        
        // Group items by product
        $productSummary = $allSaleItems->groupBy('product_id')->map(function ($items, $productId) {
            $product = $items->first()->product;
            $productName = $product ? $product->name : 'Unknown';
            
            $totalQuantity = $items->sum('quantity');
            $totalValue = $items->sum('subtotal');
            
            return [
                'product_id' => $productId,
                'product_name' => $productName,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'avg_price' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
            ];
        });
        
        // Sort data
        switch ($sortBy) {
            case 'value_highest':
                $productSummary = $productSummary->sortByDesc('total_value');
                break;
            case 'value_lowest':
                $productSummary = $productSummary->sortBy('total_value');
                break;
            case 'quantity_highest':
                $productSummary = $productSummary->sortByDesc('total_quantity');
                break;
            case 'quantity_lowest':
                $productSummary = $productSummary->sortBy('total_quantity');
                break;
            case 'name_asc':
                $productSummary = $productSummary->sortBy('product_name');
                break;
            case 'name_desc':
                $productSummary = $productSummary->sortByDesc('product_name');
                break;
            default:
                $productSummary = $productSummary->sortByDesc('total_value');
        }

        // Calculate summary
        $summary = [
            'total_products' => $productSummary->count(),
            'total_value' => $productSummary->sum('total_value'),
            'total_quantity' => $productSummary->sum('total_quantity'),
        ];

        $customerName = $selectedCustomer ? $customers->where('id', $selectedCustomer)->first()->name ?? 'Unknown' : null;
        $productName = $selectedProduct ? $products->where('id', $selectedProduct)->first()->name ?? 'Unknown' : null;
        
        $filename = 'penjualan-offline-by-product-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new OfflineSalesByProductExport($productSummary, $summary, $startDate, $endDate, $customerName, $productName), $filename);
    }

    /**
     * Export sales by day of week to Excel
     */
    public function exportSalesByDayOfWeek(Request $request)
    {
        // Get the same data as the view
        $platforms = Platform::all();
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subWeek()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        $selectedPlatform = $request->input('platform_id');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subWeek()->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }

        // Build the query for orders
        $query = Order::with(['platform', 'items', 'financialTransactions']);
        
        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        }
        
        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }
        
        $orders = $query->get();
        
        // Filter orders that have financial transactions
        $validOrders = $orders->filter(function ($order) {
            return $order->financialTransactions->isNotEmpty();
        });

        // Initialize day of week summary
        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin', 
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];
        
        $dayOfWeekSummary = [];
        foreach ($dayNames as $dayNum => $dayName) {
            $dayOfWeekSummary[$dayNum] = [
                'day_name' => $dayName,
                'order_count' => 0,
                'total_value' => 0,
                'total_volume' => 0,
            ];
        }

        // Group by day of week
        foreach ($validOrders as $order) {
            $dayOfWeek = $order->order_date->dayOfWeek;
            
            $dayOfWeekSummary[$dayOfWeek]['order_count']++;
            $dayOfWeekSummary[$dayOfWeek]['total_value'] += $order->financialTransactions->sum('nominal_fix');
            $dayOfWeekSummary[$dayOfWeek]['total_volume'] += $order->items->sum('quantity');
        }

        // Calculate summary
        $summary = [
            'total_orders' => $validOrders->count(),
            'total_value' => $validOrders->sum(function ($order) {
                return $order->financialTransactions->sum('nominal_fix');
            }),
            'total_volume' => $validOrders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;

        $platformName = $selectedPlatform ? $platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown' : null;
        
        $filename = 'analisis-saldo-masuk-per-hari-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new SalesByDayOfWeekExport($dayOfWeekSummary, $summary, $startDate, $endDate, $platformName), $filename);
    }

    /**
     * Export sales by date number to Excel
     */
    public function exportSalesByDateNumber(Request $request)
    {
        // Get the same data as the view
        $platforms = Platform::all();
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subWeek()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        $selectedPlatform = $request->input('platform_id');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subWeek()->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }

        // Build the query for orders (mirror view logic)
        $query = Order::with([
            'platform',
            'orderItems',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);

        // Date filter uses existing 'tanggal' column
        $query->whereBetween('tanggal', [$startDate, $endDate]);

        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }

        $allOrders = $query->get();

        // Keep only orders that have any financial transaction with saldo_masuk > 0
        $orders = $allOrders->filter(function($order) {
            return (
                $order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0
            );
        });

        // Group orders by date number (1-31) and compute totals from transactions
        $grouped = $orders->groupBy(function($order) {
            return \Carbon\Carbon::parse($order->tanggal)->format('d');
        })->map(function($dateOrders, $dateNumber) {
            $totalValue = 0;
            $totalVolume = 0;
            foreach ($dateOrders as $order) {
                foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                    $totalValue += $t->saldo_masuk;
                    $totalVolume += $t->qty > 0 ? $t->qty : 0;
                }
                foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                    $totalValue += $t->saldo_masuk;
                    $totalVolume += $t->qty > 0 ? $t->qty : 0;
                }
                foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                    $totalValue += $t->saldo_masuk;
                    $totalVolume += $t->qty > 0 ? $t->qty : 0;
                }
                foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                    $totalValue += $t->saldo_masuk;
                    $totalVolume += $t->qty > 0 ? $t->qty : 0;
                }
            }
            return [
                'date_number' => $dateNumber,
                'order_count' => $dateOrders->count(),
                'total_value' => $totalValue,
                'total_volume' => $totalVolume,
            ];
        });

        // Create complete 01-31 array to keep rows consistent
        $dateNumberSummary = [];
        for ($i = 1; $i <= 31; $i++) {
            $key = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            if (isset($grouped[$key])) {
                $dateNumberSummary[$i] = $grouped[$key];
            } else {
                $dateNumberSummary[$i] = [
                    'date_number' => $key,
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_volume' => 0,
                ];
            }
        }

        // Calculate overall summary identical to view
        $totalValue = 0;
        $totalVolume = 0;
        foreach ($orders as $order) {
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += $t->qty > 0 ? $t->qty : 0;
            }
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += $t->qty > 0 ? $t->qty : 0;
            }
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += $t->qty > 0 ? $t->qty : 0;
            }
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $t) {
                $totalValue += $t->saldo_masuk;
                $totalVolume += $t->qty > 0 ? $t->qty : 0;
            }
        }

        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
        ];
         
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;
 
        $platformName = $selectedPlatform ? $platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown' : null;
         
        $filename = 'analisis-saldo-masuk-per-tanggal-' . date('Y-m-d') . '.xlsx';
         
        return Excel::download(new SalesByDateNumberExport($dateNumberSummary, $summary, $startDate, $endDate, $platformName), $filename);
    }

    /**
     * Export sales detail report to Excel
     */
    public function exportSalesDetailReport(Request $request)
    {
        // Use the same logic as the view method to ensure consistency
        $platforms = Platform::all();
        
        // Apply date range filter if provided or default to today
        $startDate = $request->input('start_date') ?? now()->format('Y-m-d');
        $endDate = $request->input('end_date') ?? now()->format('Y-m-d');
        
        // Build the query for orders with the same filters as the view
        $query = Order::withoutGlobalScope('mainCategory')->with([
            'orderItems.platformProduct.mappingBarang', 
            'platform'
        ]);
        
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if provided
        if ($request->has('platform_id') && !empty($request->platform_id)) {
            $query->where('platform_id', $request->platform_id);
        }
        
        // Apply price range filters if provided
        if ($request->has('min_price') && !empty($request->min_price)) {
            $minPrice = $request->min_price;
            $query->whereHas('orderItems', function($q) use ($minPrice) {
                $q->where('price_after_discount', '>=', $minPrice);
            });
        }
        
        if ($request->has('max_price') && !empty($request->max_price)) {
            $maxPrice = $request->max_price;
            $query->whereHas('orderItems', function($q) use ($maxPrice) {
                $q->where('price_after_discount', '<=', $maxPrice);
            });
        }
        
        // Apply quantity range filters if provided
        if ($request->has('min_qty') && !empty($request->min_qty)) {
            $minQty = $request->min_qty;
            $query->where(function($q) use ($minQty) {
                $q->whereHas('orderItems', function($q) use ($minQty) {
                    $q->havingRaw('SUM(quantity) >= ?', [$minQty]);
                });
            });
        }
        
        if ($request->has('max_qty') && !empty($request->max_qty)) {
            $maxQty = $request->max_qty;
            $query->where(function($q) use ($maxQty) {
                $q->whereHas('orderItems', function($q) use ($maxQty) {
                    $q->havingRaw('SUM(quantity) <= ?', [$maxQty]);
                });
            });
        }
        
        // Apply sorting
        $sortBy = $request->input('sort', 'date_newest');
        
        switch ($sortBy) {
            case 'date_oldest':
                $query->orderBy('tanggal', 'asc');
                break;
            case 'value_highest':
                $query->orderBy('total', 'desc');
                break;
            case 'value_lowest':
                $query->orderBy('total', 'asc');
                break;
            case 'date_newest':
            default:
                $query->orderBy('tanggal', 'desc');
                break;
        }
        
        // Get all filtered orders
        $orders = $query->get();
        
        // Process orders to calculate additional metrics
        $orders->each(function($order) {
            $orderItems = $order->orderItems;
            
            $order->total_value = $orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            
            $order->total_volume = $orderItems->sum('quantity');
            
            // Make sure day of week is set
            if ($order->tanggal) {
                $order->hari = Carbon::parse($order->tanggal)->locale('id')->isoFormat('dddd');
            }
            
            return $order;
        });
        
        // Collect all order items for export (matching the view's table structure)
        $orderItems = collect();
        foreach ($orders as $order) {
            foreach ($order->orderItems as $item) {
                $item->order = $order; // Attach order data to item
                $orderItems->push($item);
            }
        }
        
        // Calculate summary
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_value'),
            'total_volume' => $orders->sum('total_volume'),
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;
        
        $filename = 'laporan-detail-penjualan-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new SalesDetailReportExport($orderItems, $summary, $startDate, $endDate, $request->platform_id), $filename);
    }

    /**
     * Export offline sales detail report to Excel
     */
    public function exportOfflineSalesDetailReport(Request $request)
    {
        // Set default date range
        $startDate = $request->filled('start_date') ? $request->input('start_date') : date('Y-m-01');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : date('Y-m-d');
        
        // Get selected customer if any
        $selectedCustomer = $request->input('customer_id');
        
        // Get selected product if any
        $selectedProduct = $request->input('product_id');
        
        // Build the query for offline sales
        $query = \App\Models\OfflineSale::withoutGlobalScope('mainCategory')
            ->with(['items', 'items.product', 'customerInfo']);
            
        // Apply date filter
        if ($startDate && $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        }
        
        // Apply customer filter if selected
        if ($selectedCustomer) {
            $query->where('customer_id', $selectedCustomer);
        }
        
        // Get the sales
        $sales = $query->get();
        
        // Sort by the selected criteria
        $sortBy = $request->input('sort', 'date_newest');
        
        switch ($sortBy) {
            case 'value_highest':
                $sales = $sales->sortByDesc('total_amount');
                break;
            case 'value_lowest':
                $sales = $sales->sortBy('total_amount');
                break;
            case 'date_newest':
                $sales = $sales->sortByDesc('sale_date');
                break;
            case 'date_oldest':
                $sales = $sales->sortBy('sale_date');
                break;
            default:
                $sales = $sales->sortByDesc('sale_date');
        }
        
        // Calculate total volume for each sale and value after returns
        $sales = $sales->map(function ($sale) {
            $sale->total_volume = $sale->items->sum('quantity');
            
            // Calculate actual value and volume after returns (same logic as display)
            $qtyRetur = 0;
            $valueAfterReturns = 0;
            
            foreach ($sale->items as $item) {
                // Get return quantity for this specific item
                $itemQtyRetur = \App\Models\ReturOfflineSaleDetail::where('offline_sale_item_id', $item->id)
                    ->whereHas('returOfflineSale', function($q) { $q->where('status', 'selesai'); })
                    ->sum('qty');
                
                $itemQtyRetur = (float) $itemQtyRetur;
                $qtyRetur += $itemQtyRetur;
                
                // Calculate quantity after return for this item
                $qtyAfterRetur = max(0, $item->quantity - $itemQtyRetur);
                
                // ✅ Calculate total value after ALL DISCOUNTS for this item (export method)
                $itemTotalAfterDiscounts = $this->calculateTotalAfterDiscounts($item);
                
                // ✅ Use full value regardless of returns - VALUE should show original total with discounts (export)
                $valueAfterReturns += $itemTotalAfterDiscounts;
            }
            
            // Set calculated values
            $sale->total_retur_qty = $qtyRetur;
            $sale->total_volume_after_returns = $sale->total_volume; // ✅ Use original volume, not reduced by returns (export)
            $sale->value_after_returns = $valueAfterReturns;
            
            return $sale;
        });
        
        // Calculate overall summary using corrected values
        $summary = [
            'total_orders' => $sales->count(),
            'total_value' => $sales->sum('value_after_returns'), // Use value after returns
            'total_volume' => $sales->sum('total_volume_after_returns'), // Use original volume (not reduced by returns) (export)
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 
            ? $summary['total_value'] / $summary['total_orders'] 
            : 0;
            
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 
            ? $summary['total_volume'] / $summary['total_orders'] 
            : 0;
        
        $filename = 'laporan-detail-penjualan-offline-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new OfflineSalesDetailReportExport($sales, $summary, $startDate, $endDate, $selectedCustomer, $selectedProduct), $filename);
    }

    /**
     * Export sales by master product to Excel
     */
    public function exportSalesByMasterProduct(Request $request)
    {
        // Use the EXACT same logic as salesByMasterProductReport method
        $platforms = Platform::all();
        
        // Set default date range
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonth()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        
        // Get filter parameters
        $selectedPlatform = $request->input('platform_id');
        $searchTerm = $request->input('search');
        $orderNumber = $request->input('order_number');
        $sortBy = $request->input('sort', 'revenue_highest');
        
        // Get selected filter arrays
        $selectedMainCategories = $request->input('main_categories', []);
        $selectedBrands = $request->input('brands', []);
        $selectedSubBrands = $request->input('sub_brands', []);
        $selectedProductCategories = $request->input('product_categories', []);
        $selectedProductTypes = $request->input('product_types', []);
        $selectedProductSizes = $request->input('product_sizes', []);
        $selectedProductVariants = $request->input('product_variants', []);
        
        // Get all master data for filters
        $mainCategories = MainCategory::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $subBrands = SubBrand::orderBy('name')->get();
        $productCategories = ProductCategory::orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        $productSizes = ProductSize::orderBy('name')->get();
        $productVariants = ProductVariant::orderBy('name')->get();
        
        // Build the query for orders with payment only - SAME AS VIEW METHOD
        $query = Order::with([
            'platform',
            'orderItems',
            'orderItems.platformProduct',
            'orderItems.platformProduct.mappingBarang',
            'orderItems.platformProduct.mappingBarang.product',
            'orderItems.platformProduct.mappingBarang.product.mainCategory',
            'orderItems.platformProduct.mappingBarang.product.brand',
            'orderItems.platformProduct.mappingBarang.product.subBrand',
            'orderItems.platformProduct.mappingBarang.product.productCategory',
            'orderItems.platformProduct.mappingBarang.product.productType',
            'orderItems.platformProduct.mappingBarang.product.productSize',
            'orderItems.platformProduct.mappingBarang.product.productVariant',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions'
        ])
        ->whereBetween('tanggal', [$startDate, $endDate])
        ->where(function($q) {
            // Only include orders that have payment (saldo_masuk > 0)
            $q->whereHas('shopeeFinancialTransactions', function($sq) {
                $sq->where('saldo_masuk', '>', 0);
            })
            ->orWhereHas('tiktokFinancialTransactions', function($sq) {
                $sq->where('saldo_masuk', '>', 0);
            })
            ->orWhereHas('tokopediaFinancialTransactions', function($sq) {
                $sq->where('saldo_masuk', '>', 0);
            })
            ->orWhereHas('blibliFinancialTransactions', function($sq) {
                $sq->where('saldo_masuk', '>', 0);
            });
        });
        
        // Apply platform filter
        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }
        
        // Apply order number filter
        if ($orderNumber) {
            $query->where('order_number', 'like', '%' . $orderNumber . '%');
        }
        
        // Apply product category filters
        if (!empty($selectedMainCategories)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.mainCategory', function($q) use ($selectedMainCategories) {
                $q->whereIn('id', $selectedMainCategories);
            });
        }
        
        if (!empty($selectedBrands)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.brand', function($q) use ($selectedBrands) {
                $q->whereIn('id', $selectedBrands);
            });
        }
        
        if (!empty($selectedSubBrands)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.subBrand', function($q) use ($selectedSubBrands) {
                $q->whereIn('id', $selectedSubBrands);
            });
        }
        
        if (!empty($selectedProductCategories)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productCategory', function($q) use ($selectedProductCategories) {
                $q->whereIn('id', $selectedProductCategories);
            });
        }
        
        if (!empty($selectedProductTypes)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productType', function($q) use ($selectedProductTypes) {
                $q->whereIn('id', $selectedProductTypes);
            });
        }
        
        if (!empty($selectedProductSizes)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productSize', function($q) use ($selectedProductSizes) {
                $q->whereIn('id', $selectedProductSizes);
            });
        }
        
        if (!empty($selectedProductVariants)) {
            $query->whereHas('orderItems.platformProduct.mappingBarang.product.productVariant', function($q) use ($selectedProductVariants) {
                $q->whereIn('id', $selectedProductVariants);
            });
        }
        
        $orders = $query->get();
        
        // Apply search filter after getting orders
        if ($searchTerm) {
            $orders = $orders->filter(function($order) use ($searchTerm) {
                return $order->orderItems->some(function($item) use ($searchTerm) {
                    if ($item->platformProduct) {
                        return stripos($item->platformProduct->platform_product_name, $searchTerm) !== false ||
                               stripos($item->platformProduct->sku ?? '', $searchTerm) !== false;
                    }
                    return false;
                });
            });
        }
        
        // Process orders into product rows - SAME LOGIC AS VIEW METHOD
        $productRows = collect();
        
        foreach ($orders as $order) {
            // Get total saldo masuk for this order - SAME LOGIC AS VIEW METHOD
            $totalSaldoMasuk = 0;
            $platformName = strtolower($order->platform->name);
            
            if ($platformName === 'shopee') {
                $totalSaldoMasuk = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'tiktok') {
                $totalSaldoMasuk = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'tokopedia') {
                $totalSaldoMasuk = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'blibli') {
                $totalSaldoMasuk = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            }
            
            // Get invoice number - SAME LOGIC AS VIEW METHOD
            $invoiceNumber = null;
            $platformName = strtolower($order->platform->name);
            
            if ($platformName === 'shopee') {
                $invoiceNumber = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            } elseif ($platformName === 'tiktok') {
                $invoiceNumber = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            } elseif ($platformName === 'tokopedia') {
                $invoiceNumber = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            } elseif ($platformName === 'blibli') {
                $invoiceNumber = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)
                    ->orderBy('tanggal_masuk_pembayaran')
                    ->value('no_invoice');
            }
            
            // Fallback to order invoice_number if not found in financial transactions
            if (!$invoiceNumber) {
                $invoiceNumber = $order->invoice_number ?? '-';
            }
            
            // First pass: collect all master products and calculate total order value
            $orderMasterProducts = collect();
            $totalOrderValueFromProducts = 0;
            
            foreach ($order->orderItems as $orderItem) {
                $platformProduct = $orderItem->platformProduct;
                if (!$platformProduct || !$platformProduct->mappingBarang || $platformProduct->mappingBarang->isEmpty()) {
                    continue;
                }
                
                foreach ($platformProduct->mappingBarang as $mapping) {
                    $product = $mapping->product;
                    if (!$product) continue;
                    
                    // Apply search filter
                    if ($searchTerm) {
                        $searchLower = strtolower($searchTerm);
                        $match = false;
                        if ($platformProduct && strpos(strtolower($platformProduct->platform_product_name), $searchLower) !== false) $match = true;
                        if ($product && strpos(strtolower($product->name), $searchLower) !== false) $match = true;
                        if ($product && $product->sku && strpos(strtolower($product->sku), $searchLower) !== false) $match = true;
                        if (!$match) continue;
                    }
                    
                    // Apply category filters (use selected arrays, not Eloquent collections)
                    if (!empty($selectedMainCategories) && !in_array($product->main_category_id, $selectedMainCategories)) continue;
                    if (!empty($selectedBrands) && !in_array($product->brand_id, $selectedBrands)) continue;
                    if (!empty($selectedSubBrands) && !in_array($product->sub_brand_id, $selectedSubBrands)) continue;
                    if (!empty($selectedProductCategories) && !in_array($product->product_category_id, $selectedProductCategories)) continue;
                    if (!empty($selectedProductTypes) && !in_array($product->product_type_id, $selectedProductTypes)) continue;
                    if (!empty($selectedProductSizes) && !in_array($product->product_size_id, $selectedProductSizes)) continue;
                    if (!empty($selectedProductVariants) && !in_array($product->product_variant_id, $selectedProductVariants)) continue;
                    
                    // Use selling price (initial_price with discount applied) from products table
                    $pricelistPrice = $this->calculateSellingPrice($product);
                    
                    // Calculate master product quantity (platform qty × mapping qty)
                    $masterProductQty = $orderItem->quantity * $mapping->quantity;
                    
                    // Calculate total value for this master product using pricelist
                    $masterProductValue = $pricelistPrice * $masterProductQty;
                    $totalOrderValueFromProducts += $masterProductValue;
                    
                    $orderMasterProducts->push([
                        'order_item' => $orderItem,
                        'platform_product' => $platformProduct,
                        'mapping' => $mapping,
                        'product' => $product,
                        'selling_price' => $pricelistPrice,
                        'master_qty' => $masterProductQty,
                        'master_value' => $masterProductValue,
                    ]);
                }
            }
            
            if ($orderMasterProducts->isEmpty()) continue;
            
            // Second pass: calculate revenue distribution and create rows
            foreach ($orderMasterProducts as $masterProductData) {
                $orderItem = $masterProductData['order_item'];
                $platformProduct = $masterProductData['platform_product'];
                $mapping = $masterProductData['mapping'];
                $product = $masterProductData['product'];
                $actualPrice = $masterProductData['selling_price'];
                $masterQty = $masterProductData['master_qty'];
                $masterValue = $masterProductData['master_value'];
                
                // Calculate revenue distribution percentage based on pricelist
                // Persentase = (Harga Pricelist 1 Barang / Total Harga Pricelist 1 Order) × 100%
                $revenueDistributionPercent = $totalOrderValueFromProducts > 0 ? 
                    ($masterValue / $totalOrderValueFromProducts) * 100 : 0;
                
                // Calculate proportional saldo masuk
                $proportionalSaldoMasuk = $totalSaldoMasuk * ($revenueDistributionPercent / 100);
                
                // Get actual modal cost from barang keluar records (more accurate)
                // Calculate total modal directly from barang keluar, not average
                $totalModal = $this->getTotalModalFromBarangKeluar($orderItem->id, $product->id);
                
                // If no barang keluar found, fallback to latest purchase cost
                if ($totalModal == 0) {
                    $modalPerUnit = $this->getLatestPurchaseCost($product->id);
                    $totalModal = $modalPerUnit * $masterQty;
                }
                
                $modalPerUnit = $masterQty > 0 ? $totalModal / $masterQty : 0;
                
                // Calculate gross profit using new formula
                // Gross profit per unit = (saldo masuk / qty) - modal per unit
                $grossProfitPerUnit = $masterQty > 0 ? ($proportionalSaldoMasuk / $masterQty) - $modalPerUnit : 0;
                
                // Gross profit total = total saldo masuk order - modal total order
                // For individual product row, we need to calculate proportional gross profit
                $grossProfitTotal = $proportionalSaldoMasuk - $totalModal;
                
                // Check if product is from package
                $isPackageItem = $platformProduct->mappingBarang->count() > 1;
                $packageInfo = null;
                if ($isPackageItem) {
                    $packageInfo = [
                        'is_package_item' => true,
                        'package_name' => $platformProduct->platform_product_name,
                    ];
                }
                
                $productRows->push([
                    'platform_product_id' => $platformProduct->id,
                    'platform_product_name' => $platformProduct->platform_product_name,
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id, // Added for barang keluar calculation
                    'product_id' => $product->id, // Added for barang keluar calculation
                    'order_number' => $order->order_number,
                    'order_date' => $order->tanggal,
                    'platform' => $order->platform->name,
                    'quantity' => $masterQty, // Master product quantity (platform qty × mapping qty)
                    'platform_quantity' => $orderItem->quantity, // QTY yang diorder di platform
                    'price' => $pricelistPrice, // Pricelist price from products table
                    'revenue' => $proportionalSaldoMasuk, // Proportional saldo masuk (per product)
                    'order_total_payment' => $totalSaldoMasuk, // Total saldo masuk untuk 1 order (sama untuk semua row dalam order)
                    'total_order_value_from_products' => $totalOrderValueFromProducts, // Total nilai order dari products table
                    'capital' => $totalModal, // Total modal (modal per unit × qty)
                    'gross_profit_per_unit' => $grossProfitPerUnit, // (saldo masuk / qty) - modal per unit
                    'gross_profit_total' => $grossProfitTotal, // saldo masuk - modal x qty
                    'margin_percent' => $proportionalSaldoMasuk > 0 ? ($grossProfitTotal / $proportionalSaldoMasuk) * 100 : 0,
                    'proportion_percent' => round($revenueDistributionPercent, 2),
                    'invoice_number' => $invoiceNumber,
                    'product_name' => $product->name,
                    'category' => $product->productCategory ? $product->productCategory->name : 'N/A',
                    'brand' => $product->brand ? $product->brand->name : 'N/A',
                    'sub_brand' => $product->subBrand ? $product->subBrand->name : 'N/A',
                    'product_type' => $product->productType ? $product->productType->name : 'N/A',
                    'product_size' => $product->productSize ? $product->productSize->name : 'N/A',
                    'product_variant' => $product->productVariant ? $product->productVariant->name : 'N/A',
                    'sku' => $product->sku ?? 'N/A',
                    'package_info' => $packageInfo,
                ]);
            }
        }
        
        // Calculate summary
        $totalRevenue = $productRows->sum('revenue');
        $totalCapital = $productRows->sum('capital');
        $totalGrossProfit = $totalRevenue - $totalCapital;
        
        // Calculate total barang keluar count for master products export
        $totalBarangKeluar = 0;
        foreach ($productRows as $row) {
            // Count barang keluar records for this order item and product
            $barangKeluarCount = \App\Models\BarangKeluar::where('order_item_id', $row['order_item_id'] ?? null)
                ->whereHas('warehouseStock', function($query) use ($row) {
                    $query->where('product_id', $row['product_id'] ?? null);
                })
                ->count();
            $totalBarangKeluar += $barangKeluarCount;
        }
        
        $summary = [
            'total_products' => $totalBarangKeluar, // Changed to barang keluar count
            'total_rows' => $productRows->count(),
            'total_revenue' => $totalRevenue,
            'total_capital' => $totalCapital,
            'total_gross_profit' => $totalGrossProfit,
            'total_quantity' => $productRows->sum('quantity'),
            'profit_margin' => $totalRevenue > 0 ? ($totalGrossProfit / $totalRevenue) * 100 : 0,
        ];
        
        // Sort the data
        $productRows = $this->sortProductRows($productRows, $sortBy);
        
        $filename = 'laporan-penjualan-master-produk-' . date('Y-m-d') . '.xlsx';
        
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $selectedPlatform,
            'search' => $searchTerm,
            'order_number' => $orderNumber,
            'sort' => $sortBy
        ];
        
        return Excel::download(new SalesByMasterProductExport($productRows, $summary, $filters), $filename);
    }

    /**
     * Export sales by platform product to Excel
     */
    public function exportSalesByPlatformProduct(Request $request)
    {
        // Use the EXACT same logic as salesByPlatformProductReport method
        $platforms = Platform::all();
        
        // Get product categories
        $productCategories = \App\Models\ProductCategory::orderBy('name')->get();
        
        // Set default date range (last month if not provided)
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonth()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        $selectedPlatform = $request->input('platform_id');
        $search = $request->input('search');
        $orderNumber = $request->input('order_number');
        $sortBy = $request->input('sort', 'revenue_highest');
        
        // Apply quick date range if set
        if ($request->filled('quick_range')) {
            $range = $request->input('quick_range');
            $endDate = now()->format('Y-m-d');
            
            switch ($range) {
                case '7days':
                    $startDate = now()->subDays(7)->format('Y-m-d');
                    break;
                case '2weeks':
                    $startDate = now()->subWeeks(2)->format('Y-m-d');
                    break;
                case '1month':
                    $startDate = now()->subMonth()->format('Y-m-d');
                    break;
                case '3months':
                    $startDate = now()->subMonths(3)->format('Y-m-d');
                    break;
            }
        }
        
        // Build the query for orders
        $query = Order::with([
            'platform',
            'orderItems',
            'orderItems.platformProduct.mappingBarang.product',
            'orderItems.warehouseStock',
            'orderItems.warehouseStock.product',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);
        
        // Apply date filter
        $query->whereBetween('tanggal', [$startDate, $endDate]);
        
        // Apply platform filter if set
        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }
        
        // Apply order number filter if set
        if ($orderNumber) {
            $query->where('order_number', 'like', "%$orderNumber%");
        }
        
        // Get all orders
        $allOrders = $query->get();
        
        // Filter orders to only include those with valid financial transactions (having saldo_masuk)
        $validOrders = $allOrders->filter(function($order) {
            return (
                $order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0 ||
                $order->blibliFinancialTransactions->where('saldo_masuk', '>', 0)->count() > 0
            );
        });
        
        // Process platform products
        $platformProductRows = collect();
        
        foreach ($validOrders as $order) {
            // Get paid amount by platform
            $totalSaldoMasuk = 0;
            $platformName = strtolower($order->platform->name);
            if ($platformName === 'shopee') {
                $totalSaldoMasuk = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'tiktok') {
                $totalSaldoMasuk = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'tokopedia') {
                $totalSaldoMasuk = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            } elseif ($platformName === 'blibli') {
                $totalSaldoMasuk = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                    ->where('saldo_masuk', '>', 0)->sum('saldo_masuk');
            }
            
            $validOrderItems = [];
            $totalOrderValue = 0;
            
            foreach ($order->orderItems as $item) {
                $platformProduct = $item->platformProduct;
                
                // Apply search filter
                if ($search) {
                    $searchLower = strtolower($search);
                    $match = false;
                    if ($platformProduct && strpos(strtolower($platformProduct->platform_product_name), $searchLower) !== false) $match = true;
                    if (!$match) continue;
                }
                
                    $validOrderItems[] = $item;
                    $itemValue = $item->price_after_discount * $item->quantity;
                    $totalOrderValue += $itemValue;
            }
            
            if (count($validOrderItems) === 0) continue;
            
            foreach ($validOrderItems as $item) {
                $itemProportion = 1; // Inisialisasi default
                $platformProduct = $item->platformProduct;
                $itemValue = $item->price_after_discount * $item->quantity;
                $productPrice = $item->price_after_discount;
                
                if (count($validOrderItems) === 1) {
                    $itemProportion = 1;
                    $itemRevenue = $totalSaldoMasuk > 0 ? $totalSaldoMasuk : ($productPrice * $item->quantity);
                } else {
                    $itemProportion = $totalOrderValue > 0 ? $itemValue / $totalOrderValue : 0;
                    $itemRevenue = $totalSaldoMasuk > 0 ? $totalSaldoMasuk * $itemProportion : ($productPrice * $item->quantity);
                }
                
                // Calculate modal (cost)
                $totalModalCost = 0;
                $mappedProducts = [];
                
                // Get all barang_keluar records for this order item
                $barangKeluarItems = \App\Models\BarangKeluar::where('order_item_id', $item->id)
                    ->with(['warehouseStock.penerimaanDetail.penerimaan.taxCategory', 'warehouseStock.product'])
                    ->get();
                
                if ($platformProduct->mappingBarang && $platformProduct->mappingBarang->count() > 0) {
                    // Group barang_keluar by product_id to match with mappings
                    $barangKeluarByProduct = $barangKeluarItems->groupBy(function($barangKeluar) {
                        return $barangKeluar->warehouseStock ? $barangKeluar->warehouseStock->product_id : null;
                    })->filter(function($group, $productId) {
                        return $productId !== null;
                    });
                    
                    foreach ($platformProduct->mappingBarang as $mapping) {
                        if ($mapping->product) {
                            $product = $mapping->product;
                            $productId = $product->id;
                            
                            // Get barang_keluar items for this specific product
                            $productBarangKeluar = $barangKeluarByProduct->get($productId, collect());
                            
                            $totalProductCost = 0;
                            $totalProductQty = 0;
                            
                            foreach ($productBarangKeluar as $barangKeluar) {
                                if ($barangKeluar->warehouseStock && $barangKeluar->warehouseStock->penerimaanDetail) {
                                    $penerimaanDetail = $barangKeluar->warehouseStock->penerimaanDetail;
                                    $hpp = $penerimaanDetail->harga_hpp;
                                    $qty = $barangKeluar->qty;
                                    
                                    // Apply discounts
                                    $hppSetelahDiskon = $hpp;
                                    if ($penerimaanDetail->diskon_persen_1 > 0) {
                                        $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_1 / 100);
                                    }
                                    if ($penerimaanDetail->diskon_persen_2 > 0) {
                                        $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_2 / 100);
                                    }
                                    if ($penerimaanDetail->diskon_persen_3 > 0) {
                                        $hppSetelahDiskon -= ($hppSetelahDiskon * $penerimaanDetail->diskon_persen_3 / 100);
                                    }
                                    
                                    // Ensure price doesn't go negative
                                    $finalHpp = max(0, $hppSetelahDiskon);
                                    
                                    $totalProductCost += $finalHpp * $qty;
                                    $totalProductQty += $qty;
                                }
                            }
                            
                            // Calculate cost per unit for this mapping
                            $costPerUnit = $totalProductQty > 0 ? $totalProductCost / $totalProductQty : 0;
                            
                            // Calculate total cost for this mapping
                            $mappingQty = $mapping->quantity;
                            $totalMappingQty = $item->quantity * $mappingQty;
                            $mappingCost = $costPerUnit * $totalMappingQty;
                            
                            $totalModalCost += $mappingCost;
                            
                            $mappedProducts[] = [
                                'name' => $product->name,
                                'quantity' => $mappingQty,
                                'cost' => $mappingCost
                            ];
                        }
                    }
                }
                
                // Calculate gross profit and margin
                $modalPerUnit = $item->quantity > 0 ? $totalModalCost / $item->quantity : 0;
                $grossProfit = $itemRevenue - $totalModalCost; // Gross profit = saldo masuk - modal x qty
                $marginPercent = $itemRevenue > 0 ? ($grossProfit / $itemRevenue) * 100 : 0; // Margin dari saldo masuk, bukan harga jual
                
                // Get invoice number from financial transactions
                $invoiceNumber = null;
                $platformName = strtolower($order->platform->name);
                if ($platformName === 'shopee') {
                    $invoiceNumber = \App\Models\ShopeeFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                } elseif ($platformName === 'tiktok') {
                    $invoiceNumber = \App\Models\TiktokFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                } elseif ($platformName === 'tokopedia') {
                    $invoiceNumber = \App\Models\TokopediaFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                } elseif ($platformName === 'blibli') {
                    $invoiceNumber = \App\Models\BlibliFinancialTransaction::where('no_order', $order->order_number)
                        ->where('saldo_masuk', '>', 0)
                        ->orderBy('tanggal_masuk_pembayaran')
                        ->value('no_invoice');
                }
                
                // Fallback to order invoice_number if not found in financial transactions
                if (!$invoiceNumber) {
                    $invoiceNumber = $order->invoice_number ?? '-';
                }

                $platformProductRows->push([
                    'platform_product_id' => $platformProduct->id,
                    'platform_product_name' => $platformProduct->platform_product_name,
                    'platform_sku' => $platformProduct->sku,
                    'product_variant' => $platformProduct->variant ?? '-',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'invoice_number' => $invoiceNumber,
                    'order_date' => $order->tanggal,
                    'platform' => $order->platform->name,
                    'quantity' => $item->quantity,
                    'price' => $productPrice,
                    'revenue' => $itemRevenue,
                    'capital' => $totalModalCost,
                    'gross_profit' => $grossProfit,
                    'margin_percent' => $marginPercent,
                    'mapped_products' => $mappedProducts,
                    'has_multiple_items' => count($validOrderItems) > 1,
                    'proportion_percent' => round($itemProportion * 100, 2),
                ]);
            }
        }
        
        // Sort the data
        switch ($sortBy) {
            case 'revenue_highest':
                $platformProductRows = $platformProductRows->sortByDesc('revenue');
                break;
            case 'revenue_lowest':
                $platformProductRows = $platformProductRows->sortBy('revenue');
                break;
            case 'profit_highest':
                $platformProductRows = $platformProductRows->sortByDesc('gross_profit');
                break;
            case 'profit_lowest':
                $platformProductRows = $platformProductRows->sortBy('gross_profit');
                break;
            case 'quantity_highest':
                $platformProductRows = $platformProductRows->sortByDesc('quantity');
                break;
            case 'quantity_lowest':
                $platformProductRows = $platformProductRows->sortBy('quantity');
                break;
            default:
                $platformProductRows = $platformProductRows->sortByDesc('revenue');
        }
        
        // Calculate summary
        // Calculate total unique orders for platform products export
        $totalUniqueOrders = $platformProductRows->unique('order_number')->count();
        
        // Calculate orders after returns for export
        $ordersAfterReturns = 0;
        $uniqueOrderNumbers = $platformProductRows->unique('order_number')->pluck('order_number');
        
        foreach ($uniqueOrderNumbers as $orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
            if ($order) {
                $hasRemainingItems = false;
                
                foreach ($order->orderItems as $item) {
                    // Calculate return quantity for this item
                    $qtyReturIndividual = ReturPenjualanDetail::where('order_item_id', $item->id)
                        ->whereHas('returPenjualan', function($q) { 
                            $q->whereIn('status', ['draft', 'selesai']); 
                        })
                        ->sum('qty');
                    $qtyReturIndividual = (float) $qtyReturIndividual;
                    
                    // Check if this is a package product and get total package quantity
                    $packageQuantity = 1;
                    if ($item->platformProduct && $item->platformProduct->mappingBarang && $item->platformProduct->mappingBarang->count() > 0) {
                        $packageQuantity = $item->platformProduct->mappingBarang->sum('quantity');
                    }
                    
                    // Convert individual retur quantity back to package quantity
                    $qtyRetur = $packageQuantity > 0 ? $qtyReturIndividual / $packageQuantity : $qtyReturIndividual;
                    
                    // Calculate original quantity (current + returned)
                    $currentQty = (float) ($item->quantity ?? 0);
                    $originalQty = $currentQty + $qtyRetur;
                    
                    // Calculate remaining quantity after return
                    $remainingQty = max(0.0, $originalQty - $qtyRetur);
                    
                    if ($remainingQty > 0) {
                        $hasRemainingItems = true;
                        break; // At least one item has remaining quantity
                    }
                }
                
                if ($hasRemainingItems) {
                    $ordersAfterReturns++;
                }
            }
        }
        
        $summary = [
            'total_rows' => $platformProductRows->count(),
            'total_platform_products' => $totalUniqueOrders, // Changed to unique order count
            'total_platform_products_after_returns' => $ordersAfterReturns,
            'total_quantity' => $platformProductRows->sum('quantity'),
            'total_revenue' => $platformProductRows->sum('revenue'),
            'total_capital' => $platformProductRows->sum('capital'),
            'total_gross_profit' => $platformProductRows->sum('gross_profit'),
        ];
        
        $summary['profit_margin'] = $summary['total_revenue'] > 0 ? ($summary['total_gross_profit'] / $summary['total_revenue']) * 100 : 0;
        
        $filename = 'laporan-penjualan-platform-produk-' . date('Y-m-d') . '.xlsx';
        
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform_id' => $selectedPlatform,
            'search' => $search,
            'order_number' => $orderNumber,
            'sort' => $sortBy
        ];
        
        return Excel::download(new SalesByPlatformProductExport($platformProductRows, $summary, $filters), $filename);
    }

    /**
     * Export sales by platform to Excel
     */
    public function exportSalesByPlatform(Request $request)
    {
        // Use the same logic as the view method to ensure consistency
        $platforms = Platform::all();
        
        // Set default date range - Default ke null untuk menampilkan semua data
        $startDate = $request->filled('start_date') ? $request->input('start_date') : null;
        $endDate = $request->filled('end_date') ? $request->input('end_date') : null;
        
        // Build the query for orders - ensure we're only getting online orders (with platform_id)
        $query = Order::withoutGlobalScope('mainCategory')->with([
            'platform',
            'orderItems',
            'orderItems.platformProduct.mappingBarang.product',
        ])->whereNotNull('platform_id'); // Ensure only online orders
        
        // Apply date filter only if provided
        if ($startDate && $endDate) {
            try {
                $startDateCarbon = \Carbon\Carbon::parse($startDate)->startOfDay();
                $endDateCarbon = \Carbon\Carbon::parse($endDate)->endOfDay();
                $query->whereBetween('tanggal', [$startDateCarbon, $endDateCarbon]);
            } catch (\Exception $e) {
                // If date format is invalid, ignore date filter
            }
        }
        
        // Apply platform filter if set
        if ($request->filled('platform_id')) {
            $query->where('platform_id', $request->platform_id);
        }
        
        // Determine sort order
        $sortBy = $request->input('sort', 'date_newest');
        
        // Get the orders without sorting first
        $orders = $query->get();
        
        // Sort orders based on user selection
        switch ($sortBy) {
            case 'value_highest':
                $orders = $orders->sortByDesc(function($order) {
                    return $order->orderItems->sum(function($item) {
                        return $item->price_after_discount * $item->quantity;
                    });
                });
                break;
            case 'value_lowest':
                $orders = $orders->sortBy(function($order) {
                    return $order->orderItems->sum(function($item) {
                        return $item->price_after_discount * $item->quantity;
                    });
                });
                break;
            case 'volume_highest':
                $orders = $orders->sortByDesc(function($order) {
                    return $order->orderItems->sum('quantity');
                });
                break;
            case 'volume_lowest':
                $orders = $orders->sortBy(function($order) {
                    return $order->orderItems->sum('quantity');
                });
                break;
            case 'date_newest':
                $orders = $orders->sortByDesc('tanggal');
                break;
            case 'date_oldest':
                $orders = $orders->sortBy('tanggal');
                break;
            default:
                $orders = $orders->sortByDesc('tanggal');
                break;
        }
        
        // Calculate total value and volume for each order
        $orders = $orders->map(function($order) {
            $order->total_value = $order->orderItems->sum(function($item) {
                return $item->price_after_discount * $item->quantity;
            });
            $order->total_volume = $order->orderItems->sum('quantity');
            return $order;
        });

        // Calculate summary
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_value'),
            'total_volume' => $orders->sum('total_volume'),
        ];
        
        $summary['avg_order_value'] = $summary['total_orders'] > 0 ? $summary['total_value'] / $summary['total_orders'] : 0;
        $summary['avg_order_volume'] = $summary['total_orders'] > 0 ? $summary['total_volume'] / $summary['total_orders'] : 0;
        
        $filename = 'daftar-pesanan-platform-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new SalesByPlatformExport($orders, $summary, $startDate, $endDate, $request->platform_id), $filename);
    }

    /**
     * Export sales by status and day to Excel
     */
    public function exportSalesByStatusDay(Request $request)
    {
        // Mirror logic from salesByStatusAndDayReport so Excel matches the page
        $platforms = Platform::all();
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->subMonth()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->format('Y-m-d');
        $selectedPlatform = $request->input('platform_id');
        $selectedStatus = $request->input('status');

        $query = Order::with([
            'platform',
            'orderItems',
            'shopeeFinancialTransactions',
            'tiktokFinancialTransactions',
            'tokopediaFinancialTransactions',
            'blibliFinancialTransactions',
        ]);

        $query->whereBetween('tanggal', [$startDate, $endDate]);
        if ($selectedPlatform) {
            $query->where('platform_id', $selectedPlatform);
        }
        if ($selectedStatus) {
            $query->where(function($q) use ($selectedStatus) {
                $q->where('status_hari', $selectedStatus)
                  ->orWhere('status_hari', 'LIKE', $selectedStatus . ',%')
                  ->orWhere('status_hari', 'LIKE', '%,' . $selectedStatus . ',%')
                  ->orWhere('status_hari', 'LIKE', '%,' . $selectedStatus);
            });
        }

        $orders = $query->get();

        // Build list of statuses
        $rawStatuses = Order::distinct()->pluck('status_hari')->filter()->values()->toArray();
        $allStatuses = [];
        foreach ($rawStatuses as $status) {
            if (strpos($status, ',') !== false) {
                foreach (array_map('trim', explode(',', $status)) as $s) {
                    if (!empty($s) && !in_array($s, $allStatuses)) $allStatuses[] = $s;
                }
            } else {
                if (!in_array($status, $allStatuses)) $allStatuses[] = $status;
            }
        }
        sort($allStatuses);

        // Init matrix
        $statusDayMatrix = [];
        foreach ($allStatuses as $status) {
            $statusDayMatrix[$status] = [];
            foreach (range(0, 6) as $dayNum) {
                $statusDayMatrix[$status][$dayNum] = [
                    'order_count' => 0,
                    'total_value' => 0,
                    'total_volume' => 0,
                ];
            }
        }

        // Fill matrix based on financial transactions (saldo_masuk, qty)
        foreach ($orders as $order) {
            $dayOfWeek = \Carbon\Carbon::parse($order->tanggal)->dayOfWeek;
            $totalValue = 0; $totalVolume = 0;
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }

            if (!empty($order->status_hari)) {
                if (strpos($order->status_hari, ',') !== false) {
                    foreach (array_map('trim', explode(',', $order->status_hari)) as $s) {
                        if (!empty($s) && isset($statusDayMatrix[$s][$dayOfWeek])) {
                            $statusDayMatrix[$s][$dayOfWeek]['order_count']++;
                            $statusDayMatrix[$s][$dayOfWeek]['total_value'] += $totalValue;
                            $statusDayMatrix[$s][$dayOfWeek]['total_volume'] += $totalVolume;
                        }
                    }
                } else {
                    $s = $order->status_hari;
                    if (isset($statusDayMatrix[$s][$dayOfWeek])) {
                        $statusDayMatrix[$s][$dayOfWeek]['order_count']++;
                        $statusDayMatrix[$s][$dayOfWeek]['total_value'] += $totalValue;
                        $statusDayMatrix[$s][$dayOfWeek]['total_volume'] += $totalVolume;
                    }
                }
            }
        }

        // Build summary identical to view
        $totalValue = 0; $totalVolume = 0;
        foreach ($orders as $order) {
            foreach ($order->shopeeFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
            foreach ($order->tiktokFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
            foreach ($order->tokopediaFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
            foreach ($order->blibliFinancialTransactions->where('saldo_masuk', '>', 0) as $t) { $totalValue += $t->saldo_masuk; $totalVolume += max(0, $t->qty); }
        }
        $summary = [
            'total_orders' => $orders->count(),
            'total_value' => $totalValue,
            'total_volume' => $totalVolume,
        ];

        // Aggregate by status only (sum across all days) for exporter
        $rows = [];
        foreach ($statusDayMatrix as $status => $byDay) {
            $orderCount = 0; $totalVal = 0; $totalVol = 0;
            foreach ($byDay as $data) {
                $orderCount += $data['order_count'] ?? 0;
                $totalVal += $data['total_value'] ?? 0;
                $totalVol += $data['total_volume'] ?? 0;
            }
            $rows[] = [
                'status' => $status,
                'order_count' => $orderCount,
                'total_value' => $totalVal,
                'total_volume' => $totalVol,
                'avg_order_value' => $orderCount > 0 ? $totalVal / $orderCount : 0,
                'avg_order_volume' => $orderCount > 0 ? $totalVol / $orderCount : 0,
            ];
        }

        $platformName = $selectedPlatform ? ($platforms->where('id', $selectedPlatform)->first()->name ?? 'Unknown') : null;
        $filename = 'laporan-penjualan-status-hari-' . date('Y-m-d') . '.xlsx';
        return Excel::download(new SalesByStatusDayExport($rows, $summary, $startDate, $endDate, $platformName, $selectedStatus, $request), $filename);
    }

    private function sortProductRows($productRows, $sortBy)
    {
        // First group by order to keep orders together, then sort by the selected metric
        $groupedByOrder = $productRows->groupBy('order_number');
        
        switch ($sortBy) {
            case 'revenue_highest':
                $sortedGroups = $groupedByOrder->sortByDesc(function($orderRows) {
                    return $orderRows->sum('revenue');
                });
                break;
            case 'revenue_lowest':
                $sortedGroups = $groupedByOrder->sortBy(function($orderRows) {
                    return $orderRows->sum('revenue');
                });
                break;
            case 'profit_highest':
                $sortedGroups = $groupedByOrder->sortByDesc(function($orderRows) {
                    return $orderRows->sum('gross_profit_total');
                });
                break;
            case 'profit_lowest':
                $sortedGroups = $groupedByOrder->sortBy(function($orderRows) {
                    return $orderRows->sum('gross_profit_total');
                });
                break;
            case 'quantity_highest':
                $sortedGroups = $groupedByOrder->sortByDesc(function($orderRows) {
                    return $orderRows->sum('quantity');
                });
                break;
            case 'quantity_lowest':
                $sortedGroups = $groupedByOrder->sortBy(function($orderRows) {
                    return $orderRows->sum('quantity');
                });
                break;
            default:
                $sortedGroups = $groupedByOrder->sortByDesc(function($orderRows) {
                    return $orderRows->sum('revenue');
                });
        }
        
        // Flatten the grouped results back to a collection
        return $sortedGroups->flatten(1);
    }
} 