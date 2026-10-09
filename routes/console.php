<?php

use App\Models\Buku;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | Bukti audit N+1: membandingkan jumlah kueri Lazy Loading vs Eager Loading.
 | Jalankan: php artisan audit:n-plus-one
 */
Artisan::command('audit:n-plus-one {--limit=20 : Jumlah buku yang diambil}', function () {
    $limit = (int) $this->option('limit');

    // 1) Lazy Loading (cara yang SALAH)
    DB::flushQueryLog();
    DB::enableQueryLog();
    foreach (Buku::latest()->limit($limit)->get() as $buku) {
        $buku->kategori->nama_kategori;          // memicu 1 kueri per buku
    }
    $lazy = DB::getQueryLog();

    // 2) Eager Loading (cara yang BENAR)
    DB::flushQueryLog();
    foreach (Buku::with('kategori')->latest()->limit($limit)->get() as $buku) {
        $buku->kategori->nama_kategori;          // relasi sudah dimuat, tanpa kueri baru
    }
    $eager = DB::getQueryLog();
    DB::disableQueryLog();

    $this->newLine();
    $this->line("Mengambil {$limit} buku beserta kategorinya:");
    $this->error(sprintf('  Lazy Loading  : %d kueri (1 + N)', count($lazy)));
    $this->info(sprintf('  Eager Loading : %d kueri', count($eager)));
    $this->newLine();
    $this->line('Detail kueri Eager Loading:');
    $this->table(
        ['#', 'Waktu (ms)', 'SQL'],
        collect($eager)->map(fn ($q, $i) => [$i + 1, $q['time'], $q['query']])->all()
    );
})->purpose('Membuktikan Eager Loading mencegah N+1 Query Problem');