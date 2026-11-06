<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi publik kategori buku.
 *
 * @mixin \App\Models\Kategori
 */
class KategoriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'kode_kategori' => $this->kode_kategori,
            'nama_kategori' => $this->nama_kategori,
            // Hanya muncul jika controller memanggil withCount('bukus')
            'jumlah_buku'   => $this->whenCounted('bukus'),
            '_links'        => [
                'self'  => ['href' => url("/api/v1/kategoris/{$this->id}"), 'method' => 'GET'],
                'bukus' => ['href' => url("/api/v1/bukus?kategori_id={$this->id}"), 'method' => 'GET'],
            ],
        ];
    }
}