<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;

    /**
     * Atribut yang boleh diisi lewat mass assignment (create/update).
     * Kolom id & timestamps sengaja tidak dimasukkan.
     */
    protected $fillable = ['kode_kategori', 'nama_kategori'];

    /**
     * Relasi One-to-Many: satu kategori memiliki banyak buku.
     */
    public function bukus(): HasMany
    {
        return $this->hasMany(Buku::class);
    }
}