<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi publik buku (memisahkan struktur tabel dari kontrak JSON).
 * HATEOAS: link 'pinjam_buku' HANYA muncul jika stok > 0.
 *
 * @mixin \App\Models\Buku
 */
class BukuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tersedia = (int) $this->stok > 0;
        $selfUrl  = url("/api/v1/bukus/{$this->id}");

        $links = [
            'self'           => ['href' => $selfUrl, 'method' => 'GET'],
            'kategori'       => ['href' => url("/api/v1/kategoris/{$this->kategori_id}"), 'method' => 'GET'],
            'update_full'    => ['href' => $selfUrl, 'method' => 'PUT'],
            'update_partial' => ['href' => $selfUrl, 'method' => 'PATCH'],
            'delete'         => ['href' => $selfUrl, 'method' => 'DELETE'],
        ];

        if ($tersedia) {
            $links['pinjam_buku'] = [
                'href'   => url('/api/v1/peminjamans'),
                'method' => 'POST',
                'body'   => ['buku_id' => $this->id],
            ];
        }

        return [
            'id'                 => $this->id,
            'isbn'               => $this->isbn,
            'judul'              => $this->judul,
            'penulis'            => $this->penulis,
            'penerbit'           => $this->penerbit,
            'tahun_terbit'       => $this->tahun_terbit,
            'stok'               => (int) $this->stok,
            'tersedia'           => $tersedia,
            'status_label'       => $tersedia ? 'TERSEDIA' : 'HABIS',
            'sinopsis'           => $this->sinopsis,
            'kategori'           => new KategoriResource($this->whenLoaded('kategori')),
            'total_peminjaman'   => $this->whenCounted('peminjamans'),
            'peminjaman_aktif'   => $this->whenCounted('peminjaman_aktif'),
            'riwayat_peminjaman' => PeminjamanResource::collection($this->whenLoaded('peminjamans')),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
            '_links'             => $links,
        ];
    }
}