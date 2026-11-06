<?php

use App\Http\Controllers\Api\V1\ApiRootController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BukuApiController;
use App\Http\Controllers\Api\V1\KategoriApiController;
use App\Http\Controllers\Api\V1\PeminjamanApiController;
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

    // Katalog baca-saja
    Route::get('/kategoris', [KategoriApiController::class, 'index'])->name('kategoris.index');
    Route::get('/kategoris/{id}', [KategoriApiController::class, 'show'])->whereNumber('id')->name('kategoris.show');

    Route::get('/bukus', [BukuApiController::class, 'index'])->name('bukus.index');
    Route::get('/bukus/{id}', [BukuApiController::class, 'show'])->whereNumber('id')->name('bukus.show');

    // =====================================================================
    // 2. ENDPOINT TERPROTEKSI BEARER TOKEN
    // =====================================================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('auth.logout');

        // Mutasi katalog buku
        Route::post('/bukus', [BukuApiController::class, 'store'])->name('bukus.store');
        Route::put('/bukus/{id}', [BukuApiController::class, 'update'])->whereNumber('id')->name('bukus.update');
        Route::patch('/bukus/{id}', [BukuApiController::class, 'partialUpdate'])->whereNumber('id')->name('bukus.partial-update');
        Route::delete('/bukus/{id}', [BukuApiController::class, 'destroy'])->whereNumber('id')->name('bukus.destroy');

        // Transaksi sirkulasi (ACID)
        Route::get('/peminjamans', [PeminjamanApiController::class, 'index'])->name('peminjamans.index');
        Route::post('/peminjamans', [PeminjamanApiController::class, 'store'])->name('peminjamans.store');
        Route::get('/peminjamans/{id}', [PeminjamanApiController::class, 'show'])->whereNumber('id')->name('peminjamans.show');
        Route::patch('/peminjamans/{id}/kembalikan', [PeminjamanApiController::class, 'kembalikan'])->whereNumber('id')->name('peminjamans.kembalikan');
    });
});