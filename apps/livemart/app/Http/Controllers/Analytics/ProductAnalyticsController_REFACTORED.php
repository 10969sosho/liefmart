<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Platform;
use App\Models\Brand;
use App\Models\SubBrand;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ProductSize;
use App\Models\ProductVariant;
use App\Services\Analytics\ProductAnalyticsService;
use Illuminate\Http\Request;

/**
 * ProductAnalyticsController - REFACTORED VERSION
 * 
 * Thin controller - hanya menerima input & return view
 */
class ProductAnalyticsController extends Controller
{
    protected $service;
    
    public function __construct(ProductAnalyticsService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Produk Platform Terlaris
     */
    public function produkPlatformTerlaris(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'quantity_highest'),
        ];
        
        $perPage = $request->input('limit', 100);
        $page = $request->input('page', 1);
        
        $data = $this->service->getBestSellingPlatform($filters, $perPage, $page);
        
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['products']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.produk_platform_terlaris', [
            'paginator' => $products,
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'search' => $filters['search'],
            'sortBy' => $filters['sort'],
            'limit' => $perPage,
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Produk Internal Terlaris
     */
    public function produkInternalTerlaris(Request $request)
    {
        $platforms = Platform::all();
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', 'quantity_highest'),
        ];
        
        $perPage = $request->input('limit', 100);
        $page = $request->input('page', 1);
        
        $data = $this->service->getBestSellingInternal($filters, $perPage, $page);
        
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['products']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.produk_internal_terlaris', [
            'paginator' => $products,
            'platforms' => $platforms,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'search' => $filters['search'],
            'sortBy' => $filters['sort'],
            'limit' => $perPage,
            'summary' => $data['summary'],
        ]);
    }
    
    /**
     * Sales by Master Product Report
     */
    public function salesByMasterProductReport(Request $request)
    {
        $platforms = Platform::all();
        $productCategories = ProductCategory::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $subBrands = SubBrand::orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        $productSizes = ProductSize::orderBy('name')->get();
        $productVariants = ProductVariant::orderBy('name')->get();
        
        $selectedBrands = (array) $request->input('brands', []);
        if (!empty($selectedBrands)) {
            $subBrands = SubBrand::whereIn('brand_id', $selectedBrands)->orderBy('name')->get();
        }
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'order_number' => $request->input('order_number'),
            'search' => $request->input('search'),
            'brands' => $selectedBrands,
            'sub_brands' => (array) $request->input('sub_brands', []),
            'product_categories' => (array) $request->input('product_categories', []),
            'product_types' => (array) $request->input('product_types', []),
            'product_sizes' => (array) $request->input('product_sizes', []),
            'product_variants' => (array) $request->input('product_variants', []),
            'sort' => $request->input('sort', 'revenue_highest'),
        ];
        
        $perPage = 10;
        $page = $request->input('page', 1);
        
        $data = $this->service->getMasterProductSales($filters, $perPage, $page);
        
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['products']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.sales_by_master_product_new', [
            'productRows' => $products,
            'platforms' => $platforms,
            'productCategories' => $productCategories,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
            'brands' => $brands,
            'subBrands' => $subBrands,
            'productTypes' => $productTypes,
            'productSizes' => $productSizes,
            'productVariants' => $productVariants,
            'selectedBrands' => $selectedBrands,
            'selectedSubBrands' => $filters['sub_brands'],
            'selectedProductCategories' => $filters['product_categories'],
            'selectedProductTypes' => $filters['product_types'],
            'selectedProductSizes' => $filters['product_sizes'],
            'selectedProductVariants' => $filters['product_variants'],
        ]);
    }
    
    /**
     * Sales by Master Product Special Report
     */
    public function salesByMasterProductSpecialReport(Request $request)
    {
        $platforms = Platform::all();
        $productCategories = ProductCategory::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $subBrands = SubBrand::orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        $productSizes = ProductSize::orderBy('name')->get();
        $productVariants = ProductVariant::orderBy('name')->get();
        
        $selectedBrands = (array) $request->input('brands', []);
        if (!empty($selectedBrands)) {
            $subBrands = SubBrand::whereIn('brand_id', $selectedBrands)->orderBy('name')->get();
        }
        
        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'platform_id' => $request->input('platform_id'),
            'order_number' => $request->input('order_number'),
            'search' => $request->input('search'),
            'brands' => $selectedBrands,
            'sub_brands' => (array) $request->input('sub_brands', []),
            'product_categories' => (array) $request->input('product_categories', []),
            'product_types' => (array) $request->input('product_types', []),
            'product_sizes' => (array) $request->input('product_sizes', []),
            'product_variants' => (array) $request->input('product_variants', []),
            'sort' => $request->input('sort', 'revenue_highest'),
        ];
        
        $perPage = 10;
        $page = $request->input('page', 1);
        
        $data = $this->service->getMasterProductSpecialSales($filters, $perPage, $page);
        
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($data['products']),
            $data['total'],
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('analytics.sales_by_master_product_special', [
            'productRows' => $products,
            'platforms' => $platforms,
            'productCategories' => $productCategories,
            'startDate' => $filters['start_date'] ?? now()->format('Y-m-d'),
            'endDate' => $filters['end_date'] ?? now()->format('Y-m-d'),
            'selectedPlatform' => $filters['platform_id'],
            'sortBy' => $filters['sort'],
            'summary' => $data['summary'],
            'brands' => $brands,
            'subBrands' => $subBrands,
            'productTypes' => $productTypes,
            'productSizes' => $productSizes,
            'productVariants' => $productVariants,
            'selectedBrands' => $selectedBrands,
            'selectedSubBrands' => $filters['sub_brands'],
            'selectedProductCategories' => $filters['product_categories'],
            'selectedProductTypes' => $filters['product_types'],
            'selectedProductSizes' => $filters['product_sizes'],
            'selectedProductVariants' => $filters['product_variants'],
        ]);
    }
}

