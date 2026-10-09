<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Buku extends Model
{
    use HasFactory;

    /**
     * Proteksi mass assignment: hanya kolom ini yang boleh diisi massal.
     */
    protected $fillable = [
        'isbn',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'kategori_id',
        'stok',
        'sinopsis',
    ];

    protected function casts(): array
    {
        return [
            'tahun_terbit' => 'integer',
            'stok'         => 'integer',
        ];
    }

    /**
     * Relasi inverse: setiap buku dimiliki oleh satu kategori.
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }
}