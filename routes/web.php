<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MappingBarangController;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\TokopediaController;
use App\Http\Controllers\ShopeeController;
use App\Http\Controllers\Shopee2Controller;
use App\Http\Controllers\TiktokController;
use App\Http\Controllers\Tiktok2Controller;
use App\Http\Controllers\LazadaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WarehouseStockController;

use App\Http\Controllers\Finance\PembayaranShopeeController;
use App\Http\Controllers\Finance\PembayaranShopee2Controller;
use App\Http\Controllers\Finance\PembayaranTokopediaController;
use App\Http\Controllers\Finance\PembayaranTiktokController;
use App\Http\Controllers\Finance\PembayaranTiktok2Controller;
use App\Http\Controllers\Finance\PembayaranBlibliController;
use App\Http\Controllers\Finance\ArusKasShopee2Controller;
use App\Http\Controllers\Finance\ArusKasTiktok2Controller;
use App\Http\Controllers\Finance\ManualController;
use App\Http\Controllers\Finance\OfflineInvoiceController;
use App\Http\Controllers\Admin\DatabaseRestoreController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Auth::routes();

// Home route (for authenticated users)
Route::get('/home', [HomeController::class, 'index'])
    ->name('home')
    ->middleware(['auth', 'main.category', 'prevent-back-history', 'under.construction']);

// Maintenance Route
Route::get('/maintenance', function () {
    return view('maintenance');
})->name('maintenance')->middleware(['auth', 'main.category', 'prevent-back-history']);

// Under Construction Route
Route::get('/under-construction', function () {
    return view('under-construction');
})->name('under-construction')->middleware(['auth', 'main.category', 'prevent-back-history']);

// Dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(['auth', 'main.category', 'prevent-back-history']);

// Database Restore routes (only for superadmin - role_id = 1)
Route::middleware(['auth', 'main.category', 'prevent-back-history'])->group(function () {
    Route::get('/database-restore', [DatabaseRestoreController::class, 'index'])->name('database-restore.index');
    Route::post('/database-restore', [DatabaseRestoreController::class, 'restore'])->name('database-restore.restore');
    Route::post('/database-restore/server', [DatabaseRestoreController::class, 'restoreFromServer'])->name('database-restore.server');
    Route::get('/database-restore/download-backup', [DatabaseRestoreController::class, 'downloadBackup'])->name('database-restore.download-backup');
    
    // Chunked upload routes
    Route::post('/chunked-upload', [App\Http\Controllers\Admin\ChunkedUploadController::class, 'uploadChunk'])->name('chunked-upload.chunk');
    Route::post('/chunked-upload/merge', [App\Http\Controllers\Admin\ChunkedUploadController::class, 'mergeChunks'])->name('chunked-upload.merge');
});

// User management routes (only for superadmin)
Route::middleware(['auth', 'role:superadmin', 'main.category', 'prevent-back-history'])->group(function () {
    Route::resource('users', UserController::class);
});

// Admin routes (only for superadmin - simple approach)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'main.category', 'prevent-back-history'])->group(function () {
    // Role Management
    Route::resource('roles', App\Http\Controllers\Admin\RoleController::class)->middleware('permission:roles.view');
    Route::post('roles/{role}/toggle-status', [App\Http\Controllers\Admin\RoleController::class, 'toggleStatus'])->name('roles.toggle-status')->middleware('permission:roles.edit');
    
    // User Management
    Route::resource('users', App\Http\Controllers\Admin\UserManagementController::class)->middleware('permission:users.view');
    Route::post('users/{user}/toggle-status', [App\Http\Controllers\Admin\UserManagementController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('permission:users.edit');
    
    // Permission Management
    Route::resource('permissions', App\Http\Controllers\Admin\PermissionController::class)->middleware('permission:permissions.view');
});

// User profile routes (for all authenticated users)
Route::middleware(['auth', 'main.category', 'prevent-back-history'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('users.profile.update');
});

// Route untuk penerimaan barang
Route::prefix('penerimaan')->middleware(['auth', 'main.category', 'prevent-back-history', 'under.construction', 'increase.upload.limits'])->group(function () {
    Route::get('/', [PenerimaanController::class, 'index'])->name('penerimaan.index')->middleware('permission:warehouse.view');
    Route::get('/create', [PenerimaanController::class, 'create'])->name('penerimaan.create')->middleware('permission:warehouse.create');
    Route::post('/store', [PenerimaanController::class, 'store'])->name('penerimaan.store')->middleware('permission:warehouse.create');
    Route::get('/{id}', [PenerimaanController::class, 'show'])
        ->name('penerimaan.show')
        ->where('id', '[0-9]+')
        ->middleware('permission:warehouse.view');
    Route::get('/{id}/edit', [PenerimaanController::class, 'edit'])
        ->name('penerimaan.edit')
        ->where('id', '[0-9]+')
        ->middleware('permission:warehouse.edit');
    Route::put('/{id}', [PenerimaanController::class, 'update'])
        ->name('penerimaan.update')
        ->where('id', '[0-9]+')
        ->middleware('permission:warehouse.edit');
    Route::delete('/{id}', [PenerimaanController::class, 'destroy'])
        ->name('penerimaan.destroy')
        ->where('id', '[0-9]+')
        ->middleware('permission:warehouse.edit');
    Route::get('/{id}/print', [PenerimaanController::class, 'print'])
        ->name('penerimaan.print')
        ->where('id', '[0-9]+')
        ->middleware('permission:warehouse.view');

    // Export routes
    Route::get('/export', [PenerimaanController::class, 'export'])->name('penerimaan.export')->middleware('permission:warehouse.view');
    Route::get('/export-detail', [PenerimaanController::class, 'exportDetail'])->name('penerimaan.export-detail')->middleware('permission:warehouse.view');
    
    // Perbaiki route ini - pastikan posisinya sebelum route dengan parameter {id}
    Route::get('/get-products', [PenerimaanController::class, 'getProducts'])->name('penerimaan.get-products')->middleware('permission:warehouse.view');
    Route::get('/get-tax-categories', [PenerimaanController::class, 'getTaxCategories'])->name('penerimaan.get-tax-categories')->middleware('permission:warehouse.view');
    
    // AJAX routes untuk batch processing - FULL JSON/AJAX
    Route::post('/create-header', [PenerimaanController::class, 'createHeader'])->name('penerimaan.create-header')->middleware('permission:warehouse.create');
    Route::post('/{id}/store-batch-details', [PenerimaanController::class, 'storeBatchDetails'])->name('penerimaan.store-batch-details')->middleware('permission:warehouse.create');
    Route::post('/{id}/finalize', [PenerimaanController::class, 'finalizePenerimaan'])->name('penerimaan.finalize')->middleware('permission:warehouse.create');
    Route::post('/{id}/update-header', [PenerimaanController::class, 'updateHeader'])->name('penerimaan.update-header')->middleware('permission:warehouse.edit');
    Route::post('/{id}/clear-details', [PenerimaanController::class, 'clearDetails'])->name('penerimaan.clear-details')->middleware('permission:warehouse.edit');
    Route::post('/{id}/finalize-update', [PenerimaanController::class, 'finalizePenerimaanUpdate'])->name('penerimaan.finalize-update')->middleware('permission:warehouse.edit');
});
Route::prefix('warehouse')->middleware(['auth', 'main.category', 'prevent-back-history', 'under.construction'])->group(function () {
    // Pemindahan barang dari Unlocated ke Gudang A
    Route::get('/', [WarehouseController::class, 'index'])->name('warehouse.index')->middleware('permission:warehouse.view');
    Route::get('/create', [WarehouseController::class, 'create'])->name('warehouse.create')->middleware('permission:warehouse.create');
    Route::post('/store', [WarehouseController::class, 'store'])->name('warehouse.store')->middleware('permission:warehouse.create');

    // Daftar stok
    Route::get('/stock/list', [WarehouseStockController::class, 'list'])->name('warehouse.stock.list')->middleware('permission:warehouse.view');
    
    // Daftar stok rusak
    Route::get('/stock/damaged', [WarehouseStockController::class, 'damagedList'])->name('warehouse.stock.damaged')->middleware('permission:warehouse.view');
    
    // Analisis stok (consolidated view)
    Route::get('/stock/analytics', [WarehouseStockController::class, 'analytics'])->name('warehouse.stock.analytics')->middleware('permission:warehouse.view');
    
    // Export stock to Excel (dedicated export permission)
    Route::get('/stock/export', [WarehouseStockController::class, 'export'])->name('warehouse.stock.export')->middleware('permission:exports.warehouse');
    
    // Selected Stock Export (dedicated export permission)
    Route::post('/stock/export-selected', [WarehouseStockController::class, 'exportSelected'])->name('warehouse.stock.export-selected')->middleware('permission:exports.warehouse');
    
    // Export unlocated items to Excel
    Route::get('/export', [WarehouseController::class, 'export'])->name('warehouse.export')->middleware('permission:warehouse.export');
});

// ------------------------------------------------------------------------------------------//

Route::prefix('sales')->middleware(['auth', 'main.category', 'prevent-back-history', 'under.construction'])->group(function () {
    // Menu Penjualan
    Route::get('/', [SalesController::class, 'index'])->name('sales.index')->middleware('permission:sales.view');

    // Pilih Tipe Penjualan
    Route::get('/choose-type', [SalesController::class, 'chooseType'])->name('sales.choose-type')->middleware('permission:sales.view');

    // Penjualan Offline
    Route::get('/offline', [SalesController::class, 'offline'])->name('sales.offline')->middleware('permission:sales.offline');
    
    // Offline Sales System
    Route::prefix('offline')->middleware(['increase.upload.limits'])->group(function () {
        Route::get('/list', [SalesController::class, 'offlineSalesList'])->name('sales.offline.list')->middleware('permission:sales.view');
        Route::get('/create', [SalesController::class, 'offlineSaleCreate'])->name('sales.offline.create')->middleware('permission:sales.create');
        Route::post('/store', [SalesController::class, 'offlineSaleStore'])->name('sales.offline.store')->middleware('permission:sales.create');
        Route::get('/{offlineSale}', [SalesController::class, 'offlineSaleShow'])->name('sales.offline.show')->middleware('permission:sales.view');
        Route::get('/{offlineSale}/print/invoice', [SalesController::class, 'offlineSalePrintInvoice'])->name('sales.offline.print.invoice')->middleware('permission:sales.view');
        Route::get('/{offlineSale}/print/sj', [SalesController::class, 'offlineSalePrintSJ'])->name('sales.offline.print.sj')->middleware('permission:sales.view');
        Route::delete('/{offlineSale}', [SalesController::class, 'offlineSaleDestroy'])->name('sales.offline.destroy')->middleware('permission:sales.delete');
        Route::post('/generate-sj-number', [SalesController::class, 'generateSJNumber'])->name('sales.offline.generate-sj-number')->middleware('permission:sales.create');
    });

    // Penjualan Online
    Route::get('/online', [SalesController::class, 'online'])->name('sales.online')->middleware('permission:sales.online');

    // Platform
    Route::get('/platform/{platform}', [SalesController::class, 'platform'])->name('sales.platform')->middleware('permission:sales.view');

    // Input Manual Online
    Route::get('/online-input/{platform}', [SalesController::class, 'onlineInput'])->name('sales.online-input')->middleware('permission:sales.create');
    Route::post('/save-online-transaction', [SalesController::class, 'saveOnlineTransaction'])->name('sales.save-online-transaction')->middleware('permission:sales.create');

    // List/Daftar Penjualan
    Route::get('/list', [SalesController::class, 'list'])->name('sales.list')->middleware('permission:sales.view');
    
    // Order detail and actions
    Route::get('/orders/{order}/detail', [SalesController::class, 'orderDetail'])->name('sales.order.detail')->middleware('permission:sales.view');
    Route::get('/orders/{order}/print', [SalesController::class, 'printOrder'])->name('sales.order.print')->middleware('permission:sales.view');
    Route::delete('/orders/{order}', [SalesController::class, 'destroyOrder'])
        ->name('sales.order.destroy')
        ->middleware('role:superadmin');

    // List Barang Keluar
    Route::get('/outgoing-items', [SalesController::class, 'outgoingItems'])->name('sales.outgoing-items');

    // Input Barang Manual Offline
    Route::get('/manual-input', [SalesController::class, 'manualInput'])->name('sales.manual-input');
    Route::post('/save-transaction', [SalesController::class, 'saveTransaction'])->name('sales.save-transaction');

    // Shopee specific routes
    Route::prefix('shopee')->group(function () {
        Route::get('/import-excel', [ShopeeController::class, 'importExcel'])->name('sales.shopee.import-excel');
        Route::post('/preview-import', [ShopeeController::class, 'previewImport'])
              ->name('sales.shopee.preview-import');
        Route::get('/preview-import', [ShopeeController::class, 'showPreview'])->name('sales.shopee.show-preview');
        Route::post('/process-import', [ShopeeController::class, 'processImport'])
              ->name('sales.shopee.process-import');
    });
    
    // Shopee2 specific routes
    Route::prefix('shopee2')->group(function () {
        Route::get('/import-excel', [Shopee2Controller::class, 'importExcel'])->name('sales.shopee2.import-excel');
        Route::post('/preview-import', [Shopee2Controller::class, 'previewImport'])
              ->name('sales.shopee2.preview-import');
        Route::get('/preview-import', [Shopee2Controller::class, 'showPreview'])->name('sales.shopee2.show-preview');
        Route::post('/process-import', [Shopee2Controller::class, 'processImport'])
              ->name('sales.shopee2.process-import');
    });
    // Tokopedia specific routes
    Route::prefix('tokopedia')->group(function () {
        Route::get('/import-excel', [TokopediaController::class, 'importExcel'])->name('sales.tokopedia.import-excel');
        Route::post('/preview-import', [TokopediaController::class, 'previewImport'])->name('sales.tokopedia.preview-import');
        Route::get('/preview-import', [TokopediaController::class, 'showPreview'])->name('sales.tokopedia.show-preview');
        Route::post('/process-import', [TokopediaController::class, 'processImport'])->name('sales.tokopedia.process-import');
    });
    // TikTok specific routes
    Route::prefix('tiktok')->group(function () {
        // Sales routes
        Route::get('/import-excel', [TiktokController::class, 'importExcel'])->name('sales.tiktok.import-excel');
        Route::post('/preview-import', [TiktokController::class, 'previewImport'])->name('sales.tiktok.preview-import');
        Route::get('/preview-import', [TiktokController::class, 'showPreview'])->name('sales.tiktok.show-preview');
        Route::post('/process-import', [TiktokController::class, 'processImport'])->name('sales.tiktok.process-import');
        
        // Finance routes
        Route::get('/', [PembayaranTiktokController::class, 'index'])->name('sales.tiktok.index');
        Route::get('/import', [PembayaranTiktokController::class, 'importForm'])->name('sales.tiktok.import');
        Route::post('/import/preview', [PembayaranTiktokController::class, 'preview'])->name('sales.tiktok.import-preview-post');
        Route::get('/import/preview', [PembayaranTiktokController::class, 'preview'])->name('sales.tiktok.import-preview-get');
        Route::post('/import/process', [PembayaranTiktokController::class, 'importProcess'])
            ->name('sales.tiktok.import-process');
        Route::get('/manual', [PembayaranTiktokController::class, 'manual'])->name('sales.tiktok.manual');
        Route::post('/manual-store', [PembayaranTiktokController::class, 'storeManual'])->name('sales.tiktok.manual-store');
        Route::delete('/{id}', [PembayaranTiktokController::class, 'delete'])->name('sales.tiktok.delete');
        Route::post('/adjust/{id}', [PembayaranTiktokController::class, 'adjust'])->name('sales.tiktok.adjust');
        Route::get('/print-invoice/{id}', [PembayaranTiktokController::class, 'printInvoice'])->name('sales.tiktok.print-invoice');
        Route::get('/history/{id}', [PembayaranTiktokController::class, 'history'])->name('sales.tiktok.history');
        Route::post('/lock/{id}', [PembayaranTiktokController::class, 'lock'])->name('sales.tiktok.lock');
        Route::post('/unlock/{id}', [PembayaranTiktokController::class, 'unlock'])->name('sales.tiktok.unlock');
    });
    
    // TikTok2 specific routes
    Route::prefix('tiktok2')->group(function () {
        // Sales routes
        Route::get('/import-excel', [Tiktok2Controller::class, 'importExcel'])->name('sales.tiktok2.import-excel');
        Route::post('/preview-import', [Tiktok2Controller::class, 'previewImport'])->name('sales.tiktok2.preview-import');
        Route::get('/preview-import', [Tiktok2Controller::class, 'showPreview'])->name('sales.tiktok2.show-preview');
        Route::post('/process-import', [Tiktok2Controller::class, 'processImport'])->name('sales.tiktok2.process-import');
        
        // Finance routes
        Route::get('/', [PembayaranTiktok2Controller::class, 'index'])->name('sales.tiktok2.index');
        Route::get('/import', [PembayaranTiktok2Controller::class, 'importForm'])->name('sales.tiktok2.import');
        Route::post('/import/preview', [PembayaranTiktok2Controller::class, 'preview'])->name('sales.tiktok2.import-preview-post');
        Route::get('/import/preview', [PembayaranTiktok2Controller::class, 'preview'])->name('sales.tiktok2.import-preview-get');
        Route::post('/import/process', [PembayaranTiktok2Controller::class, 'importProcess'])
            ->name('sales.tiktok2.import-process');
        Route::get('/manual', [PembayaranTiktok2Controller::class, 'manual'])->name('sales.tiktok2.manual');
        Route::post('/manual-store', [PembayaranTiktok2Controller::class, 'storeManual'])->name('sales.tiktok2.manual-store');
        Route::delete('/{id}', [PembayaranTiktok2Controller::class, 'delete'])->name('sales.tiktok2.delete');
        Route::post('/adjust/{id}', [PembayaranTiktok2Controller::class, 'adjust'])->name('sales.tiktok2.adjust');
        Route::get('/print-invoice/{id}', [PembayaranTiktok2Controller::class, 'printInvoice'])->name('sales.tiktok2.print-invoice');
        Route::get('/history/{id}', [PembayaranTiktok2Controller::class, 'history'])->name('sales.tiktok2.history');
        Route::post('/lock/{id}', [PembayaranTiktok2Controller::class, 'lock'])->name('sales.tiktok2.lock');
        Route::post('/unlock/{id}', [PembayaranTiktok2Controller::class, 'unlock'])->name('sales.tiktok2.unlock');
    });
    
    // Lazada specific routes
    Route::prefix('lazada')->group(function () {
        Route::get('/', [LazadaController::class, 'platform'])->name('sales.lazada.platform');
        Route::get('/import', [LazadaController::class, 'import'])->name('sales.lazada.import');
        Route::post('/import/preview', [LazadaController::class, 'previewImport'])->name('sales.lazada.preview-import');
        Route::post('/import/process', [LazadaController::class, 'processImport'])->name('sales.lazada.import.process');
        Route::get('/manual', [LazadaController::class, 'manual'])->name('sales.lazada.manual');
        Route::post('/manual/store', [LazadaController::class, 'storeManual'])->name('sales.lazada.manual.store');
        Route::get('/index', [LazadaController::class, 'index'])->name('sales.lazada.index');
        Route::get('/platform-product', [LazadaController::class, 'platformProduct'])->name('sales.lazada.platform-product');
        Route::post('/platform-product', [LazadaController::class, 'storePlatformProduct'])->name('sales.lazada.platform-product.store');
        Route::get('/mapping', [LazadaController::class, 'mapping'])->name('sales.lazada.mapping');
        Route::post('/mapping', [LazadaController::class, 'storeMapping'])->name('sales.lazada.mapping.store');
        Route::delete('/mapping/{id}', [LazadaController::class, 'deleteMapping'])->name('sales.lazada.mapping.delete');
    });
    
    // Lazada Financial Transactions Routes
    Route::prefix('lazada')->group(function () {
        Route::get('/financial', [App\Http\Controllers\Finance\LazadaFinanceController::class, 'index'])->name('finance.lazada.index');
        Route::get('/financial/import', [App\Http\Controllers\Finance\LazadaFinanceController::class, 'import'])->name('finance.lazada.import');
        Route::post('/financial/preview', [App\Http\Controllers\Finance\LazadaFinanceController::class, 'preview'])->name('finance.lazada.preview');
        Route::post('/financial/process', [App\Http\Controllers\Finance\LazadaFinanceController::class, 'process'])->name('finance.lazada.process');
        Route::get('/financial/{transaction}', [App\Http\Controllers\Finance\LazadaFinanceController::class, 'show'])->name('finance.lazada.show');
        Route::delete('/financial/{transaction}', [App\Http\Controllers\Finance\LazadaFinanceController::class, 'destroy'])->name('finance.lazada.destroy');
    });
});

Route::prefix('master')->middleware(['auth', 'main.category', 'prevent-back-history', 'under.construction', 'permission:master.view'])->group(function () {
    // Bank Accounts Routes
    Route::resource('bank-accounts', App\Http\Controllers\Master\BankAccountController::class);
    Route::post('bank-accounts/{bankAccount}/set-active', [App\Http\Controllers\Master\BankAccountController::class, 'setActive'])->name('bank-accounts.set-active');
    
    // Master Barang Platform Routes
    Route::resource('barang-platform', App\Http\Controllers\MasterBarangPlatformController::class);
    Route::get('barang-platform/by-platform/{platformId}', [App\Http\Controllers\MasterBarangPlatformController::class, 'getByPlatform'])->name('barang-platform.by-platform');
    Route::post('barang-platform/create-from-mapping', [App\Http\Controllers\MasterBarangPlatformController::class, 'createFromMapping'])->name('barang-platform.create-from-mapping');
    
    Route::prefix('mapping')->name('master.mapping.')->group(function () {
        // Check unmapped products
        Route::get('/check/{platform}', [MappingBarangController::class, 'checkUnmappedProducts'])->name('check');

        // Direct auto-mapping route - new route to be added here
        // Support both platform ID and platform name for backward compatibility
        // Variant can be passed as query parameter for better accuracy
        Route::get('/auto-create/{platform}/{productName}', [MappingBarangController::class, 'autoCreateMapping'])
            ->where('productName', '.*')
            ->name('auto-create');
        
        // Show mapping for a specific product
        Route::get('/product/{platform}/{productName}', [MappingBarangController::class, 'showMapping'])->name('show');
        
        // Edit platform product (for unmapped products)
        Route::get('/edit-product/{platformProductId}', [MappingBarangController::class, 'editProduct'])->name('edit-product');
        
        // Save mapping
        Route::post('/save', [MappingBarangController::class, 'saveMapping'])->name('save');

        // Route untuk menghapus mapping
        Route::delete('/delete/{id}', [MappingBarangController::class, 'deleteMapping'])->name('delete');

        // Route untuk index mapping (daftar mapping)
        Route::get('/', [MappingBarangController::class, 'index'])->name('index');

        // Route untuk halaman tambah mapping
        Route::get('/create', [MappingBarangController::class, 'create'])->name('create');

        Route::post('/add-product', [MappingBarangController::class, 'addProduct'])->name('add-product');

        // Route untuk menyimpan mapping baru
        Route::post('/store', [MappingBarangController::class, 'store'])->name('store');

        // Route untuk halaman edit mapping
        Route::get('/edit/{id}', [MappingBarangController::class, 'edit'])->name('edit');

        // Route untuk update mapping
        Route::put('/update/{id}', [MappingBarangController::class, 'update'])->name('update');

        // Route untuk API detail mapping (AJAX) - harus sebelum route yang lebih umum
        Route::get('/details/{platformProductId}', [MappingBarangController::class, 'getMappingDetails'])->name('details');
        
        // Route untuk menghapus mapping (alternatif)
        Route::delete('/destroy/{id}', [MappingBarangController::class, 'destroy'])->name('destroy');
        Route::delete('/destroy-all/{platformProductId}', [MappingBarangController::class, 'destroyAll'])->name('destroy-all');
        
        // Route untuk riwayat versi
        Route::get('/version-history/{platformProductId}', [MappingBarangController::class, 'versionHistory'])->name('version-history');
        Route::get('/version-detail/{platformProductId}/{version}', [MappingBarangController::class, 'versionDetail'])->name('version-detail');
    });
});

// Finance routes
Route::prefix('finance')->name('finance.')->middleware(['auth', 'main.category', 'prevent-back-history', 'under.construction', 'permission:finance.view'])->group(function () {
    Route::get('/', [App\Http\Controllers\Finance\FinanceController::class, 'index'])->name('index');
    Route::get('/choose', [App\Http\Controllers\Finance\FinanceController::class, 'choose'])->name('choose');
    
    // Shopee Finance Routes
    Route::prefix('shopee')->name('shopee.')->group(function () {
        Route::get('/', [PembayaranShopeeController::class, 'index'])->name('index');
        Route::get('/import', [PembayaranShopeeController::class, 'importForm'])->name('import');
        Route::post('/import/preview', [PembayaranShopeeController::class, 'preview'])->name('import-preview');
        Route::get('/import/preview', [PembayaranShopeeController::class, 'preview'])->name('import-preview-get');
        Route::post('/import/process', [PembayaranShopeeController::class, 'importProcess'])
            ->name('import-process');
        Route::get('/manual', [PembayaranShopeeController::class, 'manual'])->name('manual');
        Route::post('/manual-store', [PembayaranShopeeController::class, 'storeManual'])->name('manual-store');
        Route::delete('/{id}', [PembayaranShopeeController::class, 'delete'])->name('delete');
        Route::post('/adjust/{id}', [PembayaranShopeeController::class, 'adjust'])->name('adjust');
        Route::get('/print-invoice/{id}', [PembayaranShopeeController::class, 'printInvoice'])->name('print-invoice');
        Route::get('/history/{id}', [PembayaranShopeeController::class, 'history'])->name('history');
        Route::post('/lock/{id}', [PembayaranShopeeController::class, 'lock'])->name('lock');
        Route::post('/unlock/{id}', [PembayaranShopeeController::class, 'unlock'])->name('unlock');
        Route::get('/export/excel', [PembayaranShopeeController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PembayaranShopeeController::class, 'exportPdf'])->name('export.pdf');
    });
    
    // Shopee2 Finance Routes
    Route::prefix('shopee2')->name('shopee2.')->group(function () {
        Route::get('/', [PembayaranShopee2Controller::class, 'index'])->name('index');
        Route::get('/import', [PembayaranShopee2Controller::class, 'importForm'])->name('import');
        Route::post('/import/preview', [PembayaranShopee2Controller::class, 'preview'])->name('import-preview');
        Route::get('/import/preview', [PembayaranShopee2Controller::class, 'preview'])->name('import-preview-get');
        Route::post('/import/process', [PembayaranShopee2Controller::class, 'importProcess'])
            ->name('import-process');
        Route::get('/manual', [PembayaranShopee2Controller::class, 'manual'])->name('manual');
        Route::post('/manual-store', [PembayaranShopee2Controller::class, 'storeManual'])->name('manual-store');
        Route::delete('/{id}', [PembayaranShopee2Controller::class, 'delete'])->name('delete');
        Route::post('/adjust/{id}', [PembayaranShopee2Controller::class, 'adjust'])->name('adjust');
        Route::get('/print-invoice/{id}', [PembayaranShopee2Controller::class, 'printInvoice'])->name('print-invoice');
        Route::get('/history/{id}', [PembayaranShopee2Controller::class, 'history'])->name('history');
        Route::post('/lock/{id}', [PembayaranShopee2Controller::class, 'lock'])->name('lock');
        Route::post('/unlock/{id}', [PembayaranShopee2Controller::class, 'unlock'])->name('unlock');
        Route::get('/export/excel', [PembayaranShopee2Controller::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PembayaranShopee2Controller::class, 'exportPdf'])->name('export.pdf');
    });
    
    // Tokopedia Finance Routes
    Route::prefix('tokopedia')->name('tokopedia.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'importForm'])->name('import');
        Route::post('/import/preview', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'preview'])->name('import-preview');
        Route::get('/import/preview', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'preview'])->name('import-preview-get');
        Route::post('/import/process', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'importProcess'])->name('import-process');
        Route::get('/{id}/edit', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'destroy'])->name('destroy');
        Route::get('/manual', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'manual'])->name('manual');
        Route::post('/manual-store', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'storeManual'])->name('manual-store');
        Route::get('/print-invoice/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'printInvoice'])->name('print-invoice');
        Route::get('/history/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'history'])->name('history');
        Route::post('/lock/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'lock'])->name('lock');
        Route::post('/unlock/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'unlock'])->name('unlock');
        Route::post('/adjust/{id}', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'adjust'])->name('adjust');
        Route::get('/export/excel', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [App\Http\Controllers\Finance\PembayaranTokopediaController::class, 'exportPdf'])->name('export.pdf');
    });
    
    // TikTok Finance Routes
    Route::prefix('tiktok')->name('tiktok.')->group(function () {
        Route::get('/', [PembayaranTiktokController::class, 'index'])->name('index');
        Route::get('/import', [PembayaranTiktokController::class, 'importForm'])->name('import');
        Route::post('/import/preview', [PembayaranTiktokController::class, 'preview'])->name('import-preview');
        Route::get('/import/preview', [PembayaranTiktokController::class, 'preview'])->name('import-preview-get');
        Route::post('/import/process', [PembayaranTiktokController::class, 'importProcess'])
            ->name('import-process');
        Route::get('/manual', [PembayaranTiktokController::class, 'manual'])->name('manual');
        Route::post('/manual-store', [PembayaranTiktokController::class, 'storeManual'])->name('manual-store');
        Route::delete('/{id}', [PembayaranTiktokController::class, 'delete'])->name('delete');
        Route::post('/adjust/{id}', [PembayaranTiktokController::class, 'adjust'])->name('adjust');
        Route::get('/print-invoice/{id}', [PembayaranTiktokController::class, 'printInvoice'])->name('print-invoice');
        Route::get('/history/{id}', [PembayaranTiktokController::class, 'history'])->name('history');
        Route::post('/lock/{id}', [PembayaranTiktokController::class, 'lock'])->name('lock');
        Route::post('/unlock/{id}', [PembayaranTiktokController::class, 'unlock'])->name('unlock');
        Route::get('/export/excel', [PembayaranTiktokController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PembayaranTiktokController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/sync-order-dates', [PembayaranTiktokController::class, 'syncOrderDates'])->name('sync-order-dates');
        Route::get('/export/cash-flow', [PembayaranTiktokController::class, 'exportCashFlow'])->name('export.cash-flow');
    });
    
    // TikTok2 Finance Routes
    Route::prefix('tiktok2')->name('tiktok2.')->group(function () {
        Route::get('/', [PembayaranTiktok2Controller::class, 'index'])->name('index');
        Route::get('/import', [PembayaranTiktok2Controller::class, 'importForm'])->name('import');
        Route::post('/import/preview', [PembayaranTiktok2Controller::class, 'preview'])->name('import-preview');
        Route::get('/import/preview', [PembayaranTiktok2Controller::class, 'preview'])->name('import-preview-get');
        Route::post('/import/process', [PembayaranTiktok2Controller::class, 'importProcess'])
            ->name('import-process');
        Route::get('/manual', [PembayaranTiktok2Controller::class, 'manual'])->name('manual');
        Route::post('/manual-store', [PembayaranTiktok2Controller::class, 'storeManual'])->name('manual-store');
        Route::delete('/{id}', [PembayaranTiktok2Controller::class, 'delete'])->name('delete');
        Route::post('/adjust/{id}', [PembayaranTiktok2Controller::class, 'adjust'])->name('adjust');
        Route::get('/print-invoice/{id}', [PembayaranTiktok2Controller::class, 'printInvoice'])->name('print-invoice');
        Route::get('/history/{id}', [PembayaranTiktok2Controller::class, 'history'])->name('history');
        Route::post('/lock/{id}', [PembayaranTiktok2Controller::class, 'lock'])->name('lock');
        Route::post('/unlock/{id}', [PembayaranTiktok2Controller::class, 'unlock'])->name('unlock');
        Route::get('/export/excel', [PembayaranTiktok2Controller::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [PembayaranTiktok2Controller::class, 'exportPdf'])->name('export.pdf');
        Route::post('/sync-order-dates', [PembayaranTiktok2Controller::class, 'syncOrderDates'])->name('sync-order-dates');
        Route::get('/export/cash-flow', [PembayaranTiktok2Controller::class, 'exportCashFlow'])->name('export.cash-flow');
    });
    
    // Blibli Finance Routes
    Route::prefix('blibli')->name('blibli.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'importForm'])->name('import');
        Route::post('/import/preview', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'preview'])->name('import-preview');
        Route::get('/import/preview', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'preview'])->name('import-preview-get');
        Route::post('/import/process', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'importProcess'])->name('import-process');
        Route::get('/manual', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'manual'])->name('manual');
        Route::post('/manual-store', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'storeManual'])->name('manual-store');
        Route::get('/edit/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'delete'])->name('destroy');
        Route::post('/adjust/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'adjust'])->name('adjust');
        Route::get('/preview', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'preview'])->name('preview');
        Route::get('/print-invoice/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'printInvoice'])->name('print-invoice');
        Route::get('/history/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'history'])->name('history');
        Route::post('/lock/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'lock'])->name('lock');
        Route::post('/unlock/{id}', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'unlock'])->name('unlock');
        Route::get('/export/excel', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [App\Http\Controllers\Finance\PembayaranBlibliController::class, 'exportPdf'])->name('export.pdf');
    });
    
    // Arus Kas Shopee
    Route::prefix('aruskasshopee')->name('aruskasshopee.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\ArusKasShopeeController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\Finance\ArusKasShopeeController::class, 'import'])->name('import');
        Route::post('/preview', [App\Http\Controllers\Finance\ArusKasShopeeController::class, 'preview'])->name('preview');
        Route::post('/process', [App\Http\Controllers\Finance\ArusKasShopeeController::class, 'process'])->name('process');
    });
    
    // Arus Kas Tokopedia
    Route::prefix('aruskastokopedia')->name('aruskastokopedia.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\ArusKasTokopediaController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\Finance\ArusKasTokopediaController::class, 'import'])->name('import');
        Route::post('/preview', [App\Http\Controllers\Finance\ArusKasTokopediaController::class, 'preview'])->name('preview');
        Route::post('/process', [App\Http\Controllers\Finance\ArusKasTokopediaController::class, 'process'])->name('process');
    });
    
    // Arus Kas Tiktok
    Route::prefix('aruskastiktok')->name('aruskastiktok.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\ArusKasTiktokController::class, 'index'])->name('index');
        Route::get('/import', [App\Http\Controllers\Finance\ArusKasTiktokController::class, 'import'])->name('import');
        Route::post('/preview', [App\Http\Controllers\Finance\ArusKasTiktokController::class, 'preview'])->name('preview');
        Route::post('/process', [App\Http\Controllers\Finance\ArusKasTiktokController::class, 'process'])->name('process');
    });
    
    // Arus Kas Shopee2
    Route::prefix('aruskasshopee2')->name('aruskasshopee2.')->group(function () {
        Route::get('/', [ArusKasShopee2Controller::class, 'index'])->name('index');
        Route::get('/import', [ArusKasShopee2Controller::class, 'import'])->name('import');
        Route::post('/preview', [ArusKasShopee2Controller::class, 'preview'])->name('preview');
        Route::post('/process', [ArusKasShopee2Controller::class, 'process'])->name('process');
    });
    
    // Arus Kas Tiktok2
    Route::prefix('aruskastiktok2')->name('aruskastiktok2.')->group(function () {
        Route::get('/', [ArusKasTiktok2Controller::class, 'index'])->name('index');
        Route::get('/import', [ArusKasTiktok2Controller::class, 'import'])->name('import');
        Route::post('/preview', [ArusKasTiktok2Controller::class, 'preview'])->name('preview');
        Route::post('/process', [ArusKasTiktok2Controller::class, 'process'])->name('process');
    });
    
    // Arus Kas Blibli - Direct routes here instead of loading from separate file
    Route::prefix('aruskasblibli')->name('aruskasblibli.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'store'])->name('store');
        Route::get('/{transaction}/edit', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'destroy'])->name('destroy');
        Route::get('/import', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'import'])->name('import');
        Route::post('/preview', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'preview'])->name('preview');
        Route::post('/process', [App\Http\Controllers\Finance\ArusKasBlibliController::class, 'process'])->name('process');
    });
    
    // Offline
    Route::prefix('offline')->name('offline.')->group(function () {
        Route::get('/', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'index'])->name('index');
        Route::get('/invoices', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'listInvoices'])->name('invoices');
        Route::get('/export', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'exportInvoices'])->name('export');
        Route::post('/pay/{id}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'markAsPaid'])->name('pay');
        Route::post('/adjust-payment/{id}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'adjustPayment'])->name('adjust-payment');
        Route::get('/generate-invoice/{saleId}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'generateInvoice'])->name('generate-invoice');
        Route::get('/print-invoice/{id}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'printInvoice'])
            ->middleware('check.print.permission')
            ->name('print-invoice');
        Route::get('/print-invoice-after-return/{invoiceNumber}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'printInvoiceAfterReturn'])
            ->name('print-invoice-after-return')
            ->where('invoiceNumber', '.*');
        Route::get('/print-return-invoice/{invoiceNumber}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'printReturnInvoice'])
            ->name('print-return-invoice')
            ->where('invoiceNumber', '.*');
        Route::post('/approve-reprint/{id}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'approveReprint'])
            ->middleware('superadmin')
            ->name('approve-reprint');
        // Delete a payment record (superadmin only)
        Route::delete('/delete-payment/{paymentId}', [App\Http\Controllers\Finance\FinanceOfflineController::class, 'deletePayment'])
            ->name('delete-payment');
    });
});

// Finance Offline routes - sudah dipindah ke dalam grup finance utama
// Route ini dihapus untuk menghindari duplikasi dan celah keamanan

// Analytics routes
Route::prefix('analytics')
    ->name('analytics.')
    ->middleware(['auth', 'prevent-back-history', 'under.construction', 'permission:analytics.view'])
    ->group(function () {
        // Online Sales Analytics
        Route::get('/', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesValueReport'])->name('index');
        Route::get('/sales-value-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesValueReport'])->name('sales-value-report');
        Route::get('/sales-volume-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesVolumeReport'])->name('sales-volume-report');
        Route::get('/gross-profit-report', [App\Http\Controllers\Analytics\GrossProfitAnalyticsController::class, 'grossProfitReport'])->name('gross-profit-report');
        Route::get('/single-item-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'singleItemReport'])->name('single-item-report');
        Route::get('/multiple-item-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'multipleItemReport'])->name('multiple-item-report');
        Route::get('/daily-sales-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'dailySalesReport'])->name('daily-sales-report');
        Route::get('/discount-analysis-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'discountAnalysisReport'])->name('discount-analysis-report');
        Route::get('/sales-by-platform', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesByPlatformReport'])->name('sales-by-platform');
        Route::get('/sales-detail-report', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesDetailReport'])->name('sales-detail-report');
        Route::get('/internal-product-sales', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'internalProductSalesReport'])->name('internal-product-sales');
        Route::get('/sales-by-day-of-week', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesByDayOfWeekReport'])->name('sales-by-day-of-week');
        Route::get('/sales-by-date-number', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesByDateNumberReport'])->name('sales-by-date-number');
        Route::get('/sales-by-status-day', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'salesByStatusAndDayReport'])->name('sales-by-status-day');
        Route::get('/monthly-sales-summary', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'monthlySalesSummaryReport'])->name('monthly-sales-summary');
        Route::get('/sales-by-master-product', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'salesByMasterProductReport'])->name('sales-by-master-product');
        Route::get('/sales-by-master-product-special', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'salesByMasterProductSpecialReport'])->name('sales-by-master-product-special');
        Route::get('/sales-by-master-product/subbrands', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'getSubBrands'])->name('get-subbrands');
        Route::get('/sales-by-master-product/product-types', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'getProductTypes'])->name('get-product-types');
        Route::get('/sales-by-master-product/product-sizes', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'getProductSizes'])->name('get-product-sizes');
        Route::get('/sales-by-master-product/product-variants', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'getProductVariants'])->name('get-product-variants');
        Route::get('/sales-by-master-product/product-categories', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'getProductCategories'])->name('get-product-categories');
        Route::get('/sales-by-platform-product', [App\Http\Controllers\Analytics\GrossProfitAnalyticsController::class, 'salesByPlatformProductReport'])->name('sales-by-platform-product');
        Route::get('/produk-platform-terlaris', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'produkPlatformTerlaris'])->name('produk-platform-terlaris');
        Route::get('/produk-internal-terlaris', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'produkInternalTerlaris'])->name('produk-internal-terlaris');
        Route::get('/produk-platform-terlaris/export', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'exportProdukPlatformTerlaris'])->name('produk-platform-terlaris.export');
        Route::get('/produk-internal-terlaris/export', [App\Http\Controllers\Analytics\ProductAnalyticsController::class, 'exportProdukInternalTerlaris'])->name('produk-internal-terlaris.export');
        
        // Export routes for analytics
        Route::get('/monthly-sales-summary/export', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'exportMonthlySalesSummary'])->name('monthly-sales-summary.export');
        Route::get('/sales-by-day-of-week/export', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'exportSalesByDayOfWeek'])->name('sales-by-day-of-week.export');
        Route::get('/sales-by-date-number/export', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'exportSalesByDateNumber'])->name('sales-by-date-number.export');
        Route::get('/sales-detail-report/export', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'exportSalesDetailReport'])->name('sales-detail-report.export');
        Route::get('/sales-by-master-product/export', [App\Http\Controllers\Analytics\GrossProfitAnalyticsController::class, 'exportSalesByMasterProduct'])->name('sales-by-master-product.export');
        Route::get('/sales-by-platform-product/export', [App\Http\Controllers\Analytics\GrossProfitAnalyticsController::class, 'exportSalesByPlatformProduct'])->name('sales-by-platform-product.export');
        Route::get('/sales-by-platform/export', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'exportSalesByPlatform'])->name('sales-by-platform.export');
        Route::get('/sales-by-status-day/export', [App\Http\Controllers\Analytics\SalesAnalyticsController::class, 'exportSalesByStatusDay'])->name('sales-by-status-day.export');
        
        // Finance Analytics Routes
        Route::prefix('finance')->name('finance.')->group(function () {
            Route::get('/shopee', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'shopeeAnalytics'])->name('shopee');
            Route::get('/shopee/export', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'exportShopeeAnalytics'])->name('shopee.export');
            Route::get('/tokopedia', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'tokopediaAnalytics'])->name('tokopedia');
            Route::get('/tokopedia/export', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'exportTokopediaAnalytics'])->name('tokopedia.export');
            Route::get('/tiktok', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'tiktokAnalytics'])->name('tiktok');
            Route::get('/tiktok/export', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'exportTiktokAnalytics'])->name('tiktok.export');
            Route::get('/blibli', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'blibliAnalytics'])->name('blibli');
            Route::get('/blibli/export', [App\Http\Controllers\Analytics\FinanceAnalyticsController::class, 'exportBlibliAnalytics'])->name('blibli.export');
        });
        
        // Offline Sales Analytics
        Route::prefix('offline')->name('offline.')->middleware(['auth', 'prevent-back-history'])->group(function () {
            Route::get('/', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'offlineSalesDetailReport'])->name('index');
            Route::get('/monthly-sales-summary', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'offlineMonthlySalesSummaryReport'])->name('monthly-sales-summary');
            Route::get('/sales-by-customer', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'offlineSalesByCustomerReport'])->name('sales-by-customer');
            Route::get('/sales-detail-report', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'offlineSalesDetailReport'])->name('sales-detail-report');
            Route::get('/sales-by-product', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'offlineSalesByProductReport'])->name('sales-by-product');
            Route::get('/gross-profit', [App\Http\Controllers\Analytics\GrossProfitAnalyticsController::class, 'grossProfitOfflineReport'])->name('gross-profit');
            
            // Export routes for offline analytics
            Route::get('/monthly-sales-summary/export', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'exportOfflineMonthlySales'])->name('monthly-sales-summary.export');
            Route::get('/sales-by-customer/export', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'exportOfflineSalesByCustomer'])->name('sales-by-customer.export');
            Route::get('/sales-by-product/export', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'exportOfflineSalesByProduct'])->name('sales-by-product.export');
            Route::get('/sales-detail-report/export', [App\Http\Controllers\Analytics\OfflineSalesAnalyticsController::class, 'exportOfflineSalesDetailReport'])->name('sales-detail-report.export');
            Route::get('/gross-profit/export', [App\Http\Controllers\Analytics\GrossProfitAnalyticsController::class, 'exportGrossProfitOffline'])->name('gross-profit.export');
        });
    });

// Retur Pembelian Routes
Route::prefix('retur-pembelian')
    ->name('retur-pembelian.')
    ->middleware(['auth', 'prevent-back-history', 'permission:warehouse.view'])
    ->group(function () {
    Route::get('/', [App\Http\Controllers\ReturPembelianController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\ReturPembelianController::class, 'create'])->name('create');
    Route::post('/store', [App\Http\Controllers\ReturPembelianController::class, 'store'])->name('store');
    Route::get('/export', [App\Http\Controllers\ReturPembelianController::class, 'export'])->name('export');
    Route::get('/{id}', [App\Http\Controllers\ReturPembelianController::class, 'show'])->name('show');
    Route::delete('/{id}', [App\Http\Controllers\ReturPembelianController::class, 'destroy'])->name('destroy');
    Route::get('/get-penerimaan/{id}', [App\Http\Controllers\ReturPembelianController::class, 'getPenerimaan'])->name('get-penerimaan');
});

// Retur Penjualan Routes
Route::prefix('retur-penjualan')
    ->name('retur-penjualan.')
    ->middleware(['auth', 'prevent-back-history', 'permission:sales.view'])
    ->group(function () {
    Route::get('/', [App\Http\Controllers\ReturPenjualanController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\ReturPenjualanController::class, 'create'])->name('create');
    Route::post('/store', [App\Http\Controllers\ReturPenjualanController::class, 'store'])->name('store');
    Route::get('/get-order/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'getOrder'])->name('get-order');
    Route::get('/export', [App\Http\Controllers\ReturPenjualanController::class, 'export'])->name('export');
    Route::get('/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [App\Http\Controllers\ReturPenjualanController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'update'])->name('update');
    Route::put('/{id}/process', [App\Http\Controllers\ReturPenjualanController::class, 'process'])->name('process');
    Route::put('/{id}/cancel', [App\Http\Controllers\ReturPenjualanController::class, 'cancel'])->name('cancel');
    Route::put('/{id}/reverse', [App\Http\Controllers\ReturPenjualanController::class, 'reverseReturn'])->name('reverse');
    Route::get('/{id}/print', [App\Http\Controllers\ReturPenjualanController::class, 'print'])->name('print');
    
    // Finance routes for online returns
    Route::get('/{id}/finance', [App\Http\Controllers\ReturFinanceController::class, 'showFinanceForm'])->defaults('type', 'online')->name('finance.form');
    Route::post('/{id}/finance', [App\Http\Controllers\ReturFinanceController::class, 'processFinance'])->defaults('type', 'online')->name('finance.process');
    Route::post('/{id}/finance/reprocess', [App\Http\Controllers\ReturFinanceController::class, 'reprocessFinance'])->defaults('type', 'online')->name('finance.reprocess');
});

// Retur Offline Sales Routes
Route::prefix('retur-offline')
    ->name('retur-offline.')
    ->middleware(['auth', 'prevent-back-history', 'permission:sales.offline'])
    ->group(function () {
    Route::get('/', [App\Http\Controllers\ReturOfflineSaleController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\ReturOfflineSaleController::class, 'create'])->name('create');
    Route::post('/store', [App\Http\Controllers\ReturOfflineSaleController::class, 'store'])->name('store');
    Route::get('/get-offline-sale/{id}', [App\Http\Controllers\ReturOfflineSaleController::class, 'getOfflineSale'])->name('get-offline-sale');
    Route::get('/export', [App\Http\Controllers\ReturOfflineSaleController::class, 'export'])->name('export');
    Route::get('/{id}', [App\Http\Controllers\ReturOfflineSaleController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [App\Http\Controllers\ReturOfflineSaleController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\ReturOfflineSaleController::class, 'update'])->name('update');
    Route::put('/{id}/process', [App\Http\Controllers\ReturOfflineSaleController::class, 'process'])->name('process');
    Route::put('/{id}/cancel', [App\Http\Controllers\ReturOfflineSaleController::class, 'cancel'])->name('cancel');
    Route::put('/{id}/reverse', [App\Http\Controllers\ReturOfflineSaleController::class, 'reverseReturn'])->name('reverse');
    Route::get('/{id}/print', [App\Http\Controllers\ReturOfflineSaleController::class, 'print'])->name('print');
    
    // Finance routes for offline returns
    Route::get('/{id}/finance', [App\Http\Controllers\ReturFinanceController::class, 'showFinanceForm'])->defaults('type', 'offline')->name('finance.form');
    Route::post('/{id}/finance', [App\Http\Controllers\ReturFinanceController::class, 'processFinance'])->defaults('type', 'offline')->name('finance.process');
    Route::post('/{id}/finance/reprocess', [App\Http\Controllers\ReturFinanceController::class, 'reprocessFinance'])->defaults('type', 'offline')->name('finance.reprocess');
});

// Master routes
Route::group(['prefix' => 'master', 'as' => 'master.', 'middleware' => ['auth', 'prevent-back-history']], function () {
    // New master routes
});

// Product-related routes (apply middleware)
Route::middleware(['auth', 'prevent-back-history', 'under.construction', 'permission:master.view'])->group(function () {
    Route::resource('brands', App\Http\Controllers\Master\BrandController::class);
    Route::get('/fix-brands', [App\Http\Controllers\Master\BrandController::class, 'fixMissingCategories'])->name('fix.brands');
    Route::get('/test-brand', [App\Http\Controllers\Master\BrandController::class, 'test'])->name('test.brand');
    Route::resource('subbrands', App\Http\Controllers\Master\SubBrandController::class);
    Route::get('/fix-subbrands', [App\Http\Controllers\Master\SubBrandController::class, 'fixMissingBrands'])->name('fix.subbrands');
    Route::resource('product-categories', App\Http\Controllers\Master\ProductCategoryController::class);
    Route::resource('product-types', App\Http\Controllers\Master\ProductTypeController::class);
    Route::resource('product-sizes', App\Http\Controllers\Master\ProductSizeController::class);
    Route::resource('product-variants', App\Http\Controllers\Master\ProductVariantController::class);
    Route::resource('products', App\Http\Controllers\Master\ProductController::class);

    // AJAX routes for cascading dropdowns
    Route::get('/get-subbrands', [App\Http\Controllers\Master\ProductController::class, 'getSubBrands'])->name('get-subbrands');
    Route::get('/get-product-categories', [App\Http\Controllers\Master\ProductController::class, 'getProductCategories'])->name('get-product-categories');
    Route::get('/get-product-types', [App\Http\Controllers\Master\ProductController::class, 'getProductTypes'])->name('get-product-types');

    // Customer routes
    Route::resource('customers', App\Http\Controllers\CustomerController::class);
});

// API endpoints
Route::prefix('api')->group(function () {
    Route::get('/check-order', [SalesController::class, 'checkOrderExists']);
    Route::get('/tax-categories', [PenerimaanController::class, 'getTaxCategories']);
    Route::get('/products', [PenerimaanController::class, 'getProducts']);
});

// Blibli Routes
Route::prefix('sales/blibli')->name('sales.blibli.')->middleware(['auth', 'prevent-back-history', 'permission:sales.blibli'])->group(function () {
    Route::get('/import-excel', [\App\Http\Controllers\BlibliController::class, 'importExcel'])->name('import-excel');
    Route::post('/preview-import', [\App\Http\Controllers\BlibliController::class, 'previewImport'])->name('preview-import');
    Route::get('/preview-import', [\App\Http\Controllers\BlibliController::class, 'showPreviewImport'])->name('show-preview-import');
    Route::post('/process-import', [\App\Http\Controllers\BlibliController::class, 'processImport'])->name('process-import');
    
    // Print routes for Blibli orders (outside under.construction middleware)
    Route::get('/orders/{id}/print', [SalesController::class, 'printOrder'])->name('order.print');
});

// Table fixed scrollbar demo route
Route::get('/table-demo', function() {
    return view('table-demo');
});

// Unpaid Orders (Belum Ada Pembayaran)
Route::prefix('financial')->middleware(['auth', 'permission:finance.view'])->group(function () {
    Route::get('unpaid-orders', [\App\Http\Controllers\Finance\UnpaidOrdersController::class, 'index'])->name('finance.unpaid-orders.index');
    Route::get('unpaid-orders/export/excel', [\App\Http\Controllers\Finance\UnpaidOrdersController::class, 'exportExcel'])->name('finance.unpaid-orders.export.excel');
    Route::get('unpaid-orders/export/pdf', [\App\Http\Controllers\Finance\UnpaidOrdersController::class, 'exportPdf'])->name('finance.unpaid-orders.export.pdf');
});
