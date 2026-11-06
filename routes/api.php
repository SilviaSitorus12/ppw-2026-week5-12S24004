<?php

use App\Http\Controllers\Api\V1\ApiRootController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SIPUS-Del REST API v1 (stateless, prefix otomatis /api)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // =====================================================================
    // 1. ENDPOINT PUBLIK
    // =====================================================================
    Route::get('/', ApiRootController::class)->name('root');

    // Login dibatasi 5 percobaan/menit per IP (mencegah brute force -> 429)
    Route::post('/auth/login', [AuthTokenController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    // =====================================================================
    // 2. ENDPOINT TERPROTEKSI BEARER TOKEN
    // =====================================================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('auth.logout');
    });
});