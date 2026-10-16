<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('buku.index'));

// Hanya untuk tamu (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
});

// Wajib login: semua rute yang mengubah data (mutasi) dan data sirkulasi
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::resource('buku', BukuController::class)->except(['index', 'show']);
    Route::resource('kategori', KategoriController::class)->except(['index', 'show']);

    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::post('/buku/{id}/pinjam', [PeminjamanController::class, 'pinjam'])->whereNumber('id')->name('peminjaman.pinjam');
    Route::patch('/peminjaman/{id}/kembali', [PeminjamanController::class, 'kembali'])->whereNumber('id')->name('peminjaman.kembali');
});

// Publik: katalog hanya-baca (didaftarkan setelah rute "create" agar tidak bentrok)
Route::resource('buku', BukuController::class)->only(['index', 'show']);
Route::resource('kategori', KategoriController::class)->only(['index', 'show']);