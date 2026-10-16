<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PeminjamanSeeder extends Seeder
{
    /**
     * Sampel transaksi sirkulasi: 2 masih dipinjam (1 di antaranya terlambat) dan 2 sudah dikembalikan.
     */
    public function run(): void
    {
        $sampel = [
            ['judul' => 'Membangun Aplikasi Web dengan Laravel', 'nim' => '12S23001', 'nama' => 'Rina Simanjuntak', 'pinjam' => 3,  'durasi' => 7, 'kembali' => null],
            ['judul' => 'Jaringan Komputer untuk Pemula',        'nim' => '11S23012', 'nama' => 'Daniel Hutapea',   'pinjam' => 12, 'durasi' => 7, 'kembali' => null], // terlambat
            ['judul' => 'Manajemen Basis Data Relasional',       'nim' => '12S22045', 'nama' => 'Grace Panjaitan',  'pinjam' => 14, 'durasi' => 7, 'kembali' => 9],
            ['judul' => 'Statistika Terapan dengan R',           'nim' => '13S22008', 'nama' => 'Yosua Sinaga',     'pinjam' => 20, 'durasi' => 14, 'kembali' => 8],
        ];

        foreach ($sampel as $item) {
            // Setiap sampel dibuat dalam transaksi agar stok dan catatan peminjaman selalu konsisten.
            DB::transaction(function () use ($item) {
                $buku = Buku::where('judul', $item['judul'])->lockForUpdate()->firstOrFail();

                $tanggalPinjam = now()->subDays($item['pinjam']);
                $dikembalikan  = $item['kembali'] !== null;

                $buku->peminjamans()->create([
                    'kode_pinjam'         => 'PJM-' . $tanggalPinjam->format('Ymd') . '-' . Str::upper(Str::random(5)),
                    'nim_peminjam'        => $item['nim'],
                    'nama_peminjam'       => $item['nama'],
                    'tanggal_pinjam'      => $tanggalPinjam->toDateString(),
                    'tanggal_jatuh_tempo' => $tanggalPinjam->copy()->addDays($item['durasi'])->toDateString(),
                    'tanggal_kembali'     => $dikembalikan ? now()->subDays($item['kembali'])->toDateString() : null,
                    'status'              => $dikembalikan ? 'dikembalikan' : 'dipinjam',
                ]);

                // Buku yang masih dipinjam mengurangi stok tersedia.
                // Stok acak dari factory bisa 0, jadi dipastikan minimal 3 dulu agar tidak minus.
                if (! $dikembalikan) {
                    if ($buku->stok < 3) {
                        $buku->update(['stok' => 3]);
                    }
                    $buku->decrement('stok');
                }
            });
        }
    }
}