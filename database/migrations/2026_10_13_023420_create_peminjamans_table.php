<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel transaksi sirkulasi: setiap baris adalah satu peminjaman buku.
     */
    public function up(): void
    {
        Schema::create('peminjamans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pinjam', 20)->unique();          // contoh: PJM-20261013-0001
            $table->foreignId('buku_id')
                  ->constrained('bukus')
                  ->cascadeOnDelete();                           // sesuai spesifikasi modul
            $table->string('nim_peminjam', 15);
            $table->string('nama_peminjam', 150);
            $table->date('tanggal_pinjam');
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_kembali')->nullable();         // kosong selama buku belum kembali
            $table->enum('status', ['dipinjam', 'dikembalikan'])->default('dipinjam');
            $table->timestamps();

            // Mempercepat pengecekan "NIM ini masih meminjam buku ini?"
            $table->index(['buku_id', 'nim_peminjam', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjamans');
    }
};