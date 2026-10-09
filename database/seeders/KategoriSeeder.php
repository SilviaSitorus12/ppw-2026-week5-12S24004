<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        // 6 kategori tetap (spesifikasi minimal 5) dibuat melalui factory.
        Kategori::factory()->createMany([
            ['kode_kategori' => 'KOM', 'nama_kategori' => 'Ilmu Komputer'],
            ['kode_kategori' => 'SI',  'nama_kategori' => 'Sistem Informasi'],
            ['kode_kategori' => 'WEB', 'nama_kategori' => 'Pemrograman Web'],
            ['kode_kategori' => 'MAT', 'nama_kategori' => 'Matematika & Statistika'],
            ['kode_kategori' => 'MNJ', 'nama_kategori' => 'Manajemen & Bisnis'],
            ['kode_kategori' => 'SAS', 'nama_kategori' => 'Sastra & Fiksi'],
        ]);
    }
}