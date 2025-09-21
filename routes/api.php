<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\SalesController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes for penerimaan-related API endpoints
Route::get('/tax-categories', [PenerimaanController::class, 'getTaxCategories']);
Route::get('/products', [PenerimaanController::class, 'getProducts']);

// Routes for sales-related API endpoints
Route::get('/products/{product}/stock-info', [SalesController::class, 'getProductStockInfo']);
