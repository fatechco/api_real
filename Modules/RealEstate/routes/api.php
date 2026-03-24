<?php

use Illuminate\Support\Facades\Route;
use Modules\RealEstate\Http\Controllers\Frontend\PropertyController;

use Modules\RealEstate\Http\Controllers\Frontend\AmenityController;
use Modules\RealEstate\Http\Controllers\Frontend\ProjectFileController;
use Modules\RealEstate\Http\Controllers\Frontend\PropertyCategoryController;
use Modules\RealEstate\Http\Controllers\Frontend\PropertyFileController;
use Modules\RealEstate\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use Modules\RealEstate\Http\Controllers\Admin\PropertyCategoryController as AdminPropertyCategoryController;
use Modules\RealEstate\Http\Controllers\Admin\AmenityController as AdminAmenityController;

// Public routes
Route::group(['prefix' => 'v1'], function () {
    Route::prefix('rest')->group(function () {
        Route::prefix('properties')->group(function () {
            Route::get('/', [PropertyController::class, 'index']);
            Route::get('/search', [PropertyController::class, 'search']);
            Route::get('/featured', [PropertyController::class, 'featured']);
            Route::get('/{uuid}', [PropertyController::class, 'show']);
            Route::get('/{property}/similar', [PropertyController::class, 'similar']);
        });

        // Property Category routes (public)
        Route::prefix('categories')->group(function () {
            Route::get('/', [PropertyCategoryController::class, 'index']);
            Route::get('/root', [PropertyCategoryController::class, 'root']);
            Route::get('/{slug}', [PropertyCategoryController::class, 'show']);
            Route::get('/{slug}/properties', [PropertyCategoryController::class, 'properties']);
        });
        
        // Amenity routes (public)
        Route::prefix('amenities')->group(function () {
            Route::get('/', [AmenityController::class, 'index']);
            Route::get('/popular', [AmenityController::class, 'popular']);
            Route::get('/search', [AmenityController::class, 'search']);
            Route::get('/{id}', [AmenityController::class, 'show']);
            Route::get('/{slug}/properties', [AmenityController::class, 'properties']);
        });
    });

    // Public file access
    /*Route::get('/files/{file}/download', [FileController::class, 'download'])
        ->name('files.download');

    Route::get('/files/private/{token}', [FileController::class, 'privateAccess'])
    ->name('files.private');*/
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

        // Property files
        Route::prefix('properties/{property}/files')->group(function () {
            Route::post('/', [PropertyFileController::class, 'upload'])
                ->middleware(['package.permission:listing.create'])
                ->name('properties.files.upload');
            
            Route::get('/', [PropertyFileController::class, 'index'])
                ->name('properties.files.index');
            
            Route::get('/{file}', [PropertyFileController::class, 'show'])
                ->name('properties.files.show');
            
            Route::delete('/{file}', [PropertyFileController::class, 'destroy'])
                ->name('properties.files.destroy');
            
            Route::post('/reorder', [PropertyFileController::class, 'reorder'])
                ->name('properties.files.reorder');
            
            Route::post('/{file}/set-primary', [PropertyFileController::class, 'setPrimary'])
                ->name('properties.files.set-primary');
        });

        // Project files
        Route::prefix('projects/{project}/files')->group(function () {
            Route::post('/', [ProjectFileController::class, 'upload'])
                ->middleware(['can.create.project'])
                ->name('projects.files.upload');
            
            Route::get('/', [ProjectFileController::class, 'index'])
                ->name('projects.files.index');
            
            Route::delete('/{file}', [ProjectFileController::class, 'destroy'])
                ->name('projects.files.destroy');
        });

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

        // Admin Property Category routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [AdminPropertyCategoryController::class, 'index']);
        Route::get('/tree', [AdminPropertyCategoryController::class, 'tree']);
        Route::post('/', [AdminPropertyCategoryController::class, 'store']);
        Route::get('/{id}', [AdminPropertyCategoryController::class, 'show']);
        Route::put('/{id}', [AdminPropertyCategoryController::class, 'update']);
        Route::delete('/{id}', [AdminPropertyCategoryController::class, 'destroy']);
        Route::post('/reorder', [AdminPropertyCategoryController::class, 'reorder']);
        Route::post('/{id}/toggle-active', [AdminPropertyCategoryController::class, 'toggleActive']);
    });
    
    // Admin Amenity routes
    Route::prefix('amenities')->group(function () {
        Route::get('/', [AdminAmenityController::class, 'index']);
        Route::post('/', [AdminAmenityController::class, 'store']);
        Route::get('/{id}', [AdminAmenityController::class, 'show']);
        Route::put('/{id}', [AdminAmenityController::class, 'update']);
        Route::delete('/{id}', [AdminAmenityController::class, 'destroy']);
        Route::post('/reorder', [AdminAmenityController::class, 'reorder']);
        Route::post('/{id}/toggle-active', [AdminAmenityController::class, 'toggleActive']);
        Route::post('/bulk-delete', [AdminAmenityController::class, 'bulkDelete']);
    });
    
});