<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: kategori harus ada sebelum buku (foreign key).
     */
    public function run(): void
    {
        $this->call([
            KategoriSeeder::class,
            BukuSeeder::class,
        ]);
    }
}