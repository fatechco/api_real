<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\Admin\DistrictController as AdminDistrictController;
use Modules\Location\Http\Controllers\Admin\ProvinceController as AdminProvinceController;
use Modules\Location\Http\Controllers\Admin\CountryController as AdminCountryController;
use Modules\Location\Http\Controllers\Admin\WardController as AdminWardController;
use Modules\Location\Http\Controllers\Frontend\CountryController;
use Modules\Location\Http\Controllers\Frontend\DistrictController;
use Modules\Location\Http\Controllers\Frontend\ProvinceController;
use Modules\Location\Http\Controllers\Frontend\WardController;

Route::group(['prefix' => 'v1', 'middleware' => ['block.ip']], function () {

    // REST API - Public endpoints
    Route::group(['prefix' => 'rest'], function () {
        // Countries
        Route::get('countries/all', [CountryController::class, 'all']); // Get all countries without pagination
        Route::apiResource('countries', CountryController::class)->only(['index', 'show']);
        Route::get('countries/{countryId}/provinces', [CountryController::class, 'allProvinces']); // Get provinces by country
        
        
        // Provinces
        Route::apiResource('provinces', ProvinceController::class)->only(['index', 'show']);
        Route::get('provinces/{provinceId}/districts', [ProvinceController::class, 'allDistricts']); // Get districts by province

        // Districts
        Route::apiResource('districts', DistrictController::class)->only(['index', 'show']);
        Route::get('districts/{districtId}/wards', [DistrictController::class, 'allWards']); // Get wards by district
        
        
        // Wards
        Route::apiResource('wards', WardController::class)->only(['index', 'show']);
        
        // Search
        Route::get('locations/search', [CountryController::class, 'search']); // Search locations by keyword
        
        // Check country
        Route::get('check/countries/{id}', [CountryController::class, 'checkCountry']);
    });
    
    // ADMIN BLOCK
    Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:admin|manager'], 'as' => 'admin.'], function () {
    
        /* Countries */
        Route::apiResource('countries', AdminCountryController::class);
        Route::get('country/{id}/active', [AdminCountryController::class, 'changeActive']);
        Route::delete('countries/delete', [AdminCountryController::class, 'destroy']);
        Route::get('countries/drop/all', [AdminCountryController::class, 'dropAll']);
        Route::post('countries/reorder', [AdminCountryController::class, 'reorder']);
        
        /* Provinces */
        Route::apiResource('provinces', AdminProvinceController::class);
        Route::get('province/{id}/active', [AdminProvinceController::class, 'changeActive']);
        Route::delete('provinces/delete', [AdminProvinceController::class, 'destroy']);
        Route::get('provinces/drop/all', [AdminProvinceController::class, 'dropAll']);
        Route::post('provinces/reorder', [AdminProvinceController::class, 'reorder']);
        Route::post('provinces/bulk-import', [AdminProvinceController::class, 'bulkImport']);
        
        /* Districts */
        Route::apiResource('districts', AdminDistrictController::class);
        Route::get('district/{id}/active', [AdminDistrictController::class, 'changeActive']);
        Route::delete('districts/delete', [AdminDistrictController::class, 'destroy']);
        Route::get('districts/drop/all', [AdminDistrictController::class, 'dropAll']);
        Route::post('districts/reorder', [AdminDistrictController::class, 'reorder']);
        Route::post('districts/bulk-import', [AdminDistrictController::class, 'bulkImport']);
        
        /* Wards */
        Route::apiResource('wards', AdminWardController::class);
        Route::get('ward/{id}/active', [AdminWardController::class, 'changeActive']);
        Route::delete('wards/delete', [AdminWardController::class, 'destroy']);
        Route::get('wards/drop/all', [AdminWardController::class, 'dropAll']);
        Route::post('wards/reorder', [AdminWardController::class, 'reorder']);
        Route::post('wards/bulk-import', [AdminWardController::class, 'bulkImport']);
    });
});