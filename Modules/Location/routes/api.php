<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\Http\Controllers\Admin\AreaController as AdminAreaController;
use Modules\Location\Http\Controllers\Admin\CityController as AdminCityController;
use Modules\Location\Http\Controllers\Admin\CountryController as AdminCountryController;
use Modules\Location\Http\Controllers\Admin\RegionController as AdminRegionController;

Route::group(['prefix' => 'v1', 'middleware' => ['block.ip']], function () {
  // ADMIN BLOCK
    Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:admin|manager'], 'as' => 'admin.'], function () {
    /* Regions */
        Route::apiResource('regions',  AdminRegionController::class);
        Route::get('region/{id}/active', [AdminRegionController::class, 'changeActive']);
        Route::delete('regions/delete',  [AdminRegionController::class, 'destroy']);
        Route::get('regions/drop/all',   [AdminRegionController::class, 'dropAll']);

        /* Countries */
        Route::apiResource('countries', AdminCountryController::class);
        Route::get('country/{id}/active', [AdminCountryController::class, 'changeActive']);
        Route::delete('countries/delete', [AdminCountryController::class, 'destroy']);
        Route::get('countries/drop/all',  [AdminCountryController::class, 'dropAll']);

        /* Cities */
        Route::apiResource('cities', AdminCityController::class);
        Route::get('city/{id}/active', [AdminCityController::class, 'changeActive']);
        Route::delete('cities/delete', [AdminCityController::class, 'destroy']);
        Route::get('cities/drop/all',  [AdminCityController::class, 'dropAll']);

        /* Areas */
        Route::apiResource('areas',  AdminAreaController::class);
        Route::get('area/{id}/active', [AdminAreaController::class, 'changeActive']);
        Route::delete('areas/delete',  [AdminAreaController::class, 'destroy']);
        Route::get('areas/drop/all',   [AdminAreaController::class, 'dropAll']);
    });
});