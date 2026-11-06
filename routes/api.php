<?php

use App\Http\Controllers\Api\V1\ApiRootController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SIPUS-Del REST API v1 (stateless, prefix otomatis /api)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Titik masuk API
    Route::get('/', ApiRootController::class)->name('root');

    // Endpoint auth, buku, dan peminjaman ditambahkan pada tahap berikutnya.
});