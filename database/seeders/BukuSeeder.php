<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class BukuSeeder extends Seeder
{
    /**
     * Judul disesuaikan dengan kategorinya agar data uji terlihat realistis.
     * Total 30 buku (spesifikasi minimal 20).
     */
    private const JUDUL_PER_KATEGORI = [
        'KOM' => [
            'Algoritma dan Struktur Data dengan Python',
            'Pengantar Kecerdasan Buatan',
            'Sistem Operasi: Konsep dan Praktik',
            'Jaringan Komputer untuk Pemula',
            'Keamanan Siber dan Etika Digital',
        ],
        'SI' => [
            'Analisis dan Perancangan Sistem Informasi',
            'Manajemen Basis Data Relasional',
            'Enterprise Resource Planning di Indonesia',
            'Tata Kelola Teknologi Informasi',
            'Business Intelligence dan Data Warehouse',
        ],
        'WEB' => [
            'Membangun Aplikasi Web dengan Laravel',
            'Dasar-Dasar HTML, CSS, dan JavaScript',
            'Pengujian Perangkat Lunak Berbasis Web',
            'RESTful API: Desain dan Implementasi',
            'Pemrograman PHP Berorientasi Objek',
        ],
        'MAT' => [
            'Kalkulus untuk Mahasiswa Teknik',
            'Statistika Terapan dengan R',
            'Matematika Diskrit dan Aplikasinya',
            'Aljabar Linear Elementer',
            'Probabilitas dan Proses Stokastik',
        ],
        'MNJ' => [
            'Manajemen Proyek Teknologi Informasi',
            'Kewirausahaan Digital',
            'Pengantar Ilmu Ekonomi',
            'Strategi Pemasaran di Era Digital',
            'Akuntansi untuk Non-Akuntan',
        ],
        'SAS' => [
            'Senja di Tepian Danau Toba',
            'Surat dari Laguboti',
            'Jejak Langkah di Tanah Batak',
            'Rumah di Ujung Jalan Sitoluama',
            'Lagu untuk Ompung',
        ],
    ];

    public function run(): void
    {
        $kategoris = Kategori::all()->keyBy('kode_kategori');

        foreach (self::JUDUL_PER_KATEGORI as $kode => $daftarJudul) {
            $kategori = $kategoris->get($kode);

            if (! $kategori) {
                continue;
            }

            foreach ($daftarJudul as $judul) {
                Buku::factory()
                    ->for($kategori)                              // isi kategori_id via relasi
                    ->denganJudul($judul, fiksi: $kode === 'SAS')
                    ->create();
            }
        }
    }
}