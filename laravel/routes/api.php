<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\UserController; // Import UserController
use App\Http\Controllers\Auth\GoogleController; // Import GoogleController
use App\Http\Controllers\Api\StorageLocationController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']); // Admin routes, limited for now

    Route::apiResource('inventory-items', InventoryItemController::class);
    Route::post('inventory-items/{inventory_item}/photos', [App\Http\Controllers\Api\PhotoController::class, 'store']);
    Route::delete('photos/{photo}', [App\Http\Controllers\Api\PhotoController::class, 'destroy']);
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('purchases', PurchaseController::class);
    Route::apiResource('sales', SaleController::class);
    Route::apiResource('storage-locations', StorageLocationController::class);
    
    // AI Routes (with rate limiting)
    Route::middleware('rate-limit-ai')->group(function () {
        Route::post('/ai/image-identify', [App\Http\Controllers\Api\AiController::class, 'identify']);
        Route::post('/ai/market-analyze', [App\Http\Controllers\Api\AiController::class, 'marketAnalyze']);
        Route::post('/ai/facebook-analyze', [App\Http\Controllers\Api\AiController::class, 'facebookAnalyze']);
        Route::post('/ai/etsy-analyze', [App\Http\Controllers\Api\AiController::class, 'etsyAnalyze']);
        Route::post('/ai/shotgun-scan', [App\Http\Controllers\Api\AiController::class, 'shotgunScan']);
    });
});

// Google OAuth routes
// Moved to web.php

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
