<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan penting: kategori sebelum buku, buku sebelum peminjaman (foreign key).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            KategoriSeeder::class,
            BukuSeeder::class,
            PeminjamanSeeder::class,
        ]);
    }
}