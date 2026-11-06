<?php

namespace App\Traits;

use App\Support\ProblemDetails;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Amplop respons JSON seragam untuk seluruh controller API v1.
 *
 * Sukses : { success, message, data, meta? }
 * Galat  : RFC 7807 { type, title, status, detail, instance, errors? }
 */
trait ApiResponse
{
    /**
     * Respons sukses standar (default 200 OK).
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Permintaan berhasil diproses.',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json(
            $payload,
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * 201 Created + header Location menuju resource baru.
     */
    protected function createdResponse(
        mixed $data,
        string $locationUrl,
        string $message = 'Resource baru berhasil dibuat.'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201)
            ->header('Location', $locationUrl);
    }

    /**
     * 204 No Content: benar-benar tanpa body.
     */
    protected function noContentResponse(): Response
    {
        return response()->noContent();
    }

    /**
     * Koleksi terpaginasi + blok meta (dipakai katalog buku & daftar peminjaman).
     *
     * @param  class-string<\Illuminate\Http\Resources\Json\JsonResource>  $resourceClass
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'Koleksi data berhasil diambil.'
    ): JsonResponse {
        return $this->successResponse(
            data: $resourceClass::collection($paginator->items()),
            message: $message,
            meta: [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total_items'  => $paginator->total(),
                'total_pages'  => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'links'        => [
                    'first' => $paginator->url(1),
                    'prev'  => $paginator->previousPageUrl(),
                    'next'  => $paginator->nextPageUrl(),
                    'last'  => $paginator->url($paginator->lastPage()),
                ],
            ]
        );
    }

    /**
     * Galat RFC 7807 (application/problem+json).
     */
    protected function problemResponse(
        string $title,
        string $detail,
        int $status,
        string $errorSlug = 'general-error',
        array $errors = []
    ): JsonResponse {
        return ProblemDetails::make($status, $detail, $title, $errorSlug, $errors);
    }
}