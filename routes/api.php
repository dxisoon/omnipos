<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\HeldCartController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\PaymentController;

// Products
Route::apiResource('products', ProductController::class);
Route::get('products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);

// Categories
Route::apiResource('categories', CategoryController::class);

// Sales
Route::apiResource('sales', SaleController::class);
Route::get('sales/receipt/{token}', [SaleController::class, 'receipt']);
Route::get('sales/export/daily', [SaleController::class, 'exportDaily']);

// Inventory
Route::get('inventory', [InventoryController::class, 'index']);
Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust']);
Route::get('inventory/logs', [InventoryController::class, 'logs']);
Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);

// Held carts
Route::apiResource('held-carts', HeldCartController::class);

// Currency (live exchange rates)
Route::get('currency/rates', [CurrencyController::class, 'rates']);

// Payment simulation
Route::post('payment/process', [PaymentController::class, 'process']);