<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun pustakawan uji (lihat README).
     * Password di-hash otomatis oleh cast 'hashed' pada model User.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'pustakawan@del.ac.id'],
            ['name' => 'Pustakawan Del', 'password' => 'password123'],
        );
    }
}