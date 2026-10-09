<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel bukus yang berelasi Many-to-One ke kategoris.
     */
    public function up(): void
    {
        Schema::create('bukus', function (Blueprint $table) {
            $table->id();
            $table->string('isbn', 13)->unique();              // ISBN-13 tanpa tanda hubung
            $table->string('judul', 255)->index();             // index: dipakai untuk pencarian
            $table->string('penulis', 150)->index();
            $table->string('penerbit', 150);
            $table->unsignedSmallInteger('tahun_terbit');
            $table->foreignId('kategori_id')
                  ->constrained('kategoris')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();                       // kategori tidak bisa dihapus jika masih punya buku
            $table->unsignedInteger('stok')->default(0);       // unsigned: stok tidak mungkin negatif
            $table->text('sinopsis')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukus');
    }
};