<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\KategoriResource;
use App\Models\Kategori;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint baca-saja kategori: tujuan link HATEOAS 'kategori' pada BukuResource.
 */
class KategoriApiController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/kategoris
     */
    public function index(): JsonResponse
    {
        $kategoris = Kategori::withCount('bukus')->orderBy('nama_kategori')->get();

        return $this->successResponse(
            data: KategoriResource::collection($kategoris),
            message: 'Daftar kategori berhasil diambil.',
            meta: ['total_items' => $kategoris->count()]
        );
    }

    /**
     * GET /api/v1/kategoris/{id}
     */
    public function show(string $id): JsonResponse
    {
        $kategori = Kategori::withCount('bukus')->find($id);

        if (! $kategori) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Kategori dengan ID '{$id}' tidak ditemukan.",
                status: 404,
                errorSlug: 'kategori-not-found'
            );
        }

        return $this->successResponse(
            data: new KategoriResource($kategori),
            message: 'Detail kategori berhasil diambil.'
        );
    }
}