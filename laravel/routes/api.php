<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Auth\GoogleController; // Import GoogleController

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('inventory-items', InventoryItemController::class);
    Route::post('inventory-items/{inventory_item}/photos', [App\Http\Controllers\Api\PhotoController::class, 'store']);
    Route::delete('photos/{photo}', [App\Http\Controllers\Api\PhotoController::class, 'destroy']);
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::apiResource('sales', SaleController::class);
});

// Google OAuth routes
// Moved to web.php

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
