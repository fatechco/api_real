<?php

use Illuminate\Support\Facades\Route;
use Modules\Package\Http\Controllers\PackageController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('packages', PackageController::class)->names('package');
});
