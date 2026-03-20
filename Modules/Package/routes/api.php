<?php

use Illuminate\Support\Facades\Route;
use Modules\Package\Http\Controllers\Admin\PackageController as AdminPackageController;
use Modules\Package\Http\Controllers\Frontend\PackageController;
use Modules\Package\Http\Controllers\Frontend\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Package API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no auth required)
Route::group(['prefix' => 'v1/packages'], function () {
    Route::get('/', [PackageController::class, 'index']);
    Route::get('/{id}', [PackageController::class, 'show']);
    Route::get('/role/{roleName}', [PackageController::class, 'getByRole']);
});

// User routes (require auth)
Route::group(['prefix' => 'v1', 'middleware' => ['auth:sanctum']], function () {
    
    // Subscription routes
    Route::prefix('subscription')->group(function () {
        Route::get('/current', [SubscriptionController::class, 'current']);
        Route::get('/history', [SubscriptionController::class, 'history']);
    });

    // Credit routes
    Route::prefix('credits')->group(function () {
        Route::get('/balance', [SubscriptionController::class, 'creditBalance']);
        Route::get('/transactions', [SubscriptionController::class, 'creditTransactions']);
        Route::post('/purchase', [SubscriptionController::class, 'purchaseCredits']);
    });
});

// Admin routes (require admin role)
Route::group(['prefix' => 'v1/admin', 'middleware' => ['auth:sanctum', 'role:admin|manager']], function () {
    
    Route::prefix('packages')->group(function () {
        Route::get('/', [AdminPackageController::class, 'index']);
        Route::post('/', [AdminPackageController::class, 'store']);
        Route::get('/{package}', [AdminPackageController::class, 'show']);
        Route::put('/{package}', [AdminPackageController::class, 'update']);
        Route::delete('/', [AdminPackageController::class, 'destroy']);
        Route::post('/{id}/change-active', [AdminPackageController::class, 'changeActive']);
        Route::post('/reorder', [AdminPackageController::class, 'reorder']);
    });

    Route::post('/packages/drop-all', [AdminPackageController::class, 'dropAll']);
});