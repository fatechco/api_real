<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Auth\Http\Controllers\VerifyAuthController;
use Modules\User\Http\Controllers\Admin\UserController as AdminUserController;
//use Modules\User\Http\Controllers\UserController;

Route::group(['prefix' => 'v1', 'middleware' => ['block.ip']], function () {

// Methods without AuthCheck
    Route::post('/auth/register',                       [RegisterController::class, 'register'])
        ->middleware('sessions');

    Route::post('/auth/login',                          [LoginController::class, 'login'])
        ->middleware('sessions');

    Route::post('/auth/check/phone',                    [LoginController::class, 'checkPhone'])
        ->middleware('sessions');

    Route::post('/auth/logout',                         [LoginController::class, 'logout'])
        ->middleware('sessions');

    Route::post('/auth/verify/phone',                   [VerifyAuthController::class, 'verifyPhone'])
        ->middleware('sessions');

    Route::post('/auth/resend-verify',                  [VerifyAuthController::class, 'resendVerify'])
        ->middleware('sessions');

    Route::get('/auth/verify/{hash}',                   [VerifyAuthController::class, 'verifyEmail'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password',                [LoginController::class, 'forgetPassword'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password/before',        [LoginController::class, 'forgetPasswordBefore'])
        ->middleware('sessions');

    Route::post('/auth/forgot/password/confirm',        [LoginController::class, 'forgetPasswordVerify'])
        ->middleware('sessions');

    Route::post('/auth/forgot/email-password',          [LoginController::class, 'forgetPasswordEmail'])
        ->middleware('sessions');

    Route::post('/auth/forgot/email-password/{hash}',   [LoginController::class, 'forgetPasswordVerifyEmail'])
        ->middleware('sessions');

//    Route::get('/login/{provider}',                   [LoginController::class,'redirectToProvider']);
    Route::post('/auth/{provider}/callback',        [LoginController::class, 'handleProviderCallback']);


    // ADMIN BLOCK
    Route::group(['prefix' => 'admin', 'middleware' => ['sanctum.check', 'role:admin|manager'], 'as' => 'admin.'], function () {
    /* Users */
        Route::get('users/search',                  [AdminUserController::class, 'usersSearch']);
        Route::get('users/paginate',                [AdminUserController::class, 'paginate']);
        Route::get('users/drop/all',                [AdminUserController::class, 'dropAll']);
        Route::post('users/{uuid}/role/update',     [AdminUserController::class, 'updateRole']);
       // Route::get('users/{uuid}/wallets/history',  [AdminUserController::class, 'walletHistories']);
       // Route::post('users/{uuid}/wallets',         [AdminUserController::class, 'topUpWallet']);
        Route::post('users/{uuid}/active',          [AdminUserController::class, 'setActive']);
        Route::post('users/{uuid}/password',        [AdminUserController::class, 'passwordUpdate']);
        Route::get('users/{uuid}/login-as',         [AdminUserController::class, 'loginAsUser']);
        Route::apiResource('users', AdminUserController::class)->except(['index']);
        Route::delete('users/delete',               [AdminUserController::class, 'destroy']);
    });

    
});