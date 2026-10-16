<?php

use App\Http\Controllers\BukuController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\PeminjamanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('buku.index'));

// CRUD buku & kategori (7 rute RESTful masing-masing)
Route::resource('buku', BukuController::class);
Route::resource('kategori', KategoriController::class);

// Sirkulasi peminjaman (transaksi ACID)
Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
Route::post('/buku/{id}/pinjam', [PeminjamanController::class, 'pinjam'])->whereNumber('id')->name('peminjaman.pinjam');
Route::patch('/peminjaman/{id}/kembali', [PeminjamanController::class, 'kembali'])->whereNumber('id')->name('peminjaman.kembali');