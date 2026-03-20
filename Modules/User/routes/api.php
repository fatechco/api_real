<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Admin\UserController as AdminUserController;
//use Modules\User\Http\Controllers\UserController;

Route::group(['prefix' => 'v2', 'middleware' => ['block.ip']], function () {

    // ADMIN BLOCK
    Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:admin|manager'], 'as' => 'admin.'], function () {
    /* Users */
        Route::get('users/search',                  [AdminUserController::class, 'usersSearch']);
        Route::get('users/paginate',                [AdminUserController::class, 'paginate']);
        Route::get('users/drop/all',                [AdminUserController::class, 'dropAll']);
        Route::post('users/{uuid}/role/update',     [AdminUserController::class, 'updateRole']);
        Route::get('users/{uuid}/wallets/history',  [AdminUserController::class, 'walletHistories']);
        Route::post('users/{uuid}/wallets',         [AdminUserController::class, 'topUpWallet']);
        Route::post('users/{uuid}/active',          [AdminUserController::class, 'setActive']);
        Route::post('users/{uuid}/password',        [AdminUserController::class, 'passwordUpdate']);
        Route::get('users/{uuid}/login-as',         [AdminUserController::class, 'loginAsUser']);
        Route::apiResource('users', AdminUserController::class)->except(['index']);
        Route::delete('users/delete',               [AdminUserController::class, 'destroy']);
    });

    
});