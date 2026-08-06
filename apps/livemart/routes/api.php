<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SalesApiController;
use App\Http\Controllers\Api\FinanceApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\BrandApiController;
use App\Http\Controllers\Api\MappingApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\PenerimaanApiController;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\Master\BrandController;
use App\Http\Controllers\Master\SubBrandController;
use App\Http\Controllers\Master\ProductCategoryController;
use App\Http\Controllers\Master\ProductTypeController;
use App\Http\Controllers\Master\ProductSizeController;
use App\Http\Controllers\Master\ProductVariantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData']);
    Route::get('/dashboard/recent-transactions', [DashboardController::class, 'recentTransactions']);
    Route::get('/dashboard/low-stock', [DashboardController::class, 'lowStock']);
    
    // Sales
    Route::prefix('sales')->group(function () {
        Route::get('/orders', [SalesApiController::class, 'getOrders']);
        Route::get('/orders/{id}', [SalesApiController::class, 'getOrderById']);
        Route::delete('/orders/{id}', [SalesApiController::class, 'deleteOrder']);
        
        // Offline Sales
        Route::post('/offline/store', [SalesApiController::class, 'storeOfflineSale']);
        
        // Online Sales
        Route::post('/{platform}/preview-import', [SalesApiController::class, 'previewImport']);
        Route::post('/{platform}/process-import', [SalesApiController::class, 'processImport']);
        Route::post('/online/store', [SalesApiController::class, 'storeOnlineManual']);
        
        // Utilities
        Route::post('/generate-sj-number', [SalesApiController::class, 'generateSJNumber']);
        Route::post('/check-order', [SalesApiController::class, 'checkDuplicateOrder']);
    });
    
    // Finance
    Route::prefix('finance')->group(function () {
        Route::get('/{platform}', [FinanceApiController::class, 'getByPlatform']);
        Route::post('/{platform}/import/preview', [FinanceApiController::class, 'previewImport']);
        Route::post('/{platform}/import/process', [FinanceApiController::class, 'processImport']);
        Route::post('/{platform}/manual-store', [FinanceApiController::class, 'manualStore']);
        Route::post('/{platform}/lock/{id}', [FinanceApiController::class, 'lock']);
        Route::post('/{platform}/unlock/{id}', [FinanceApiController::class, 'unlock']);
        Route::delete('/{platform}/{id}', [FinanceApiController::class, 'delete']);
        Route::get('/{platform}/print-invoice/{id}', [FinanceApiController::class, 'printInvoice']);
        Route::get('/{platform}/history/{id}', [FinanceApiController::class, 'getHistory']);
        Route::get('/unpaid-orders', [FinanceApiController::class, 'getUnpaidOrders']);
    });
    
    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductApiController::class, 'index']);
        Route::post('/', [ProductApiController::class, 'store']);
        Route::get('/{id}', [ProductApiController::class, 'show']);
        Route::put('/{id}', [ProductApiController::class, 'update']);
        Route::delete('/{id}', [ProductApiController::class, 'destroy']);
        Route::get('/export/{format}', [ProductApiController::class, 'export']);
        Route::get('/{id}/stock-info', [SalesController::class, 'getProductStockInfo']);
    });
    
    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerApiController::class, 'index']);
        Route::post('/', [CustomerApiController::class, 'store']);
        Route::get('/{id}', [CustomerApiController::class, 'show']);
        Route::put('/{id}', [CustomerApiController::class, 'update']);
        Route::delete('/{id}', [CustomerApiController::class, 'destroy']);
    });
    
    // Brands
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandApiController::class, 'index']);
        Route::post('/', [BrandApiController::class, 'store']);
        Route::get('/{id}', [BrandApiController::class, 'show']);
        Route::put('/{id}', [BrandApiController::class, 'update']);
        Route::delete('/{id}', [BrandApiController::class, 'destroy']);
    });
    
    // Mapping
    Route::prefix('mapping')->group(function () {
        Route::get('/', [MappingApiController::class, 'index']);
        Route::post('/', [MappingApiController::class, 'store']);
        Route::post('/auto-create/{platform}/{productName}', [MappingApiController::class, 'autoCreate']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/sales-value', [AnalyticsApiController::class, 'salesValue']);
        Route::get('/sales-volume', [AnalyticsApiController::class, 'salesVolume']);
        Route::get('/gross-profit', [AnalyticsApiController::class, 'grossProfit']);
        Route::get('/monthly-summary', [AnalyticsApiController::class, 'monthlySummary']);
        Route::get('/sales-by-platform', [AnalyticsApiController::class, 'salesByPlatform']);
        Route::get('/sales-detail', [AnalyticsApiController::class, 'salesDetail']);
        
        // Exports
        Route::post('/exports/dispatch', [AnalyticsApiController::class, 'dispatchExport']);
        Route::get('/exports/list', [AnalyticsApiController::class, 'listExports']);
        Route::get('/exports/{id}/download', [AnalyticsApiController::class, 'downloadExport']);
    });
    
    // Penerimaan
    Route::prefix('penerimaan')->group(function () {
        Route::get('/', [PenerimaanApiController::class, 'index']);
        Route::get('/{id}', [PenerimaanApiController::class, 'show']);
        Route::post('/create-header', [PenerimaanApiController::class, 'createHeader']);
        Route::post('/{id}/store-batch-details', [PenerimaanApiController::class, 'storeBatchDetails']);
        Route::delete('/{id}', [PenerimaanApiController::class, 'destroy']);
        Route::get('/tax-categories', [PenerimaanApiController::class, 'getTaxCategories']);
        Route::get('/products', [PenerimaanApiController::class, 'getProducts']);
        Route::get('/satuans', [PenerimaanApiController::class, 'getSatuans']);
        Route::get('/main-categories', [PenerimaanApiController::class, 'getMainCategories']);
    });
});

// Legacy routes (keep for compatibility)
Route::get('/tax-categories', [PenerimaanController::class, 'getTaxCategories']);
Route::get('/products', [PenerimaanController::class, 'getProducts']);
Route::get('/products/{product}/stock-info', [SalesController::class, 'getProductStockInfo']);

Route::post('/brands', [BrandController::class, 'storeApi']);
Route::post('/sub-brands', [SubBrandController::class, 'storeApi']);
Route::post('/product-categories', [ProductCategoryController::class, 'storeApi']);
Route::post('/product-types', [ProductTypeController::class, 'storeApi']);
Route::post('/product-sizes', [ProductSizeController::class, 'storeApi']);
Route::post('/product-variants', [ProductVariantController::class, 'storeApi']);
