<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\User\PropertyController;
use Modules\RealEstate\Http\Controllers\Admin\PropertyController as AdminPropertyController;

// Public routes
Route::group(['prefix' => 'v1'], function () {
    
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/search', [PropertyController::class, 'search']);
        Route::get('/featured', [PropertyController::class, 'featured']);
        Route::get('/{uuid}', [PropertyController::class, 'show']);
        Route::get('/{property}/similar', [PropertyController::class, 'similar']);
    });
});

// User routes (require auth)
Route::group(['prefix' => 'v1', 'middleware' => ['auth:sanctum']], function () {
    
    Route::prefix('user/properties')->group(function () {
        Route::get('/', [PropertyController::class, 'myProperties']);
        Route::post('/', [PropertyController::class, 'store']);
        Route::put('/{property}', [PropertyController::class, 'update']);
        Route::delete('/', [PropertyController::class, 'destroy']);
        Route::post('/{property}/images', [PropertyController::class, 'uploadImages']);
        Route::delete('/{property}/images/{imageId}', [PropertyController::class, 'deleteImage']);
        Route::post('/{property}/images/{imageId}/primary', [PropertyController::class, 'setPrimaryImage']);
    });
});

// Admin routes (require auth + admin role)
Route::group(['prefix' => 'v1/admin', 'middleware' => ['auth:sanctum', 'role:admin|manager']], function () {
    
    Route::prefix('properties')->group(function () {
        Route::get('/', [AdminPropertyController::class, 'index']);
        Route::get('/{id}', [AdminPropertyController::class, 'show']);
        Route::put('/{property}', [AdminPropertyController::class, 'update']);
        Route::delete('/', [AdminPropertyController::class, 'destroy']);
        Route::post('/{id}/approve', [AdminPropertyController::class, 'approve']);
        Route::post('/{id}/reject', [AdminPropertyController::class, 'reject']);
        Route::post('/{id}/toggle-feature', [AdminPropertyController::class, 'toggleFeature']);
    });
});