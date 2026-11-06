<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * REST Service Consumer: mengambil metadata buku dari OpenLibrary berdasarkan ISBN.
 *
 * GET /api/v1/bukus/{id}/referensi-eksternal[?refresh=1]
 */
class BukuReferensiEksternalController extends Controller
{
    use ApiResponse;

    private const CACHE_TTL_SECONDS = 86400;

    public function show(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->problemResponse(
                title: 'Resource Not Found',
                detail: "Buku dengan ID '{$id}' tidak ditemukan di katalog.",
                status: 404,
                errorSlug: 'buku-not-found'
            );
        }

        $isbn = preg_replace('/[^0-9Xx]/', '', (string) $buku->isbn);

        if ($isbn === '') {
            return $this->problemResponse(
                title: 'Unprocessable Content',
                detail: "Buku \"{$buku->judul}\" tidak memiliki ISBN sehingga referensi eksternal tidak dapat dicari.",
                status: 422,
                errorSlug: 'buku-tanpa-isbn'
            );
        }

        $cacheKey = "openlibrary:isbn:{$isbn}";

        if (! $request->boolean('refresh') && is_array($cached = Cache::get($cacheKey))) {
            return $this->referensiResponse($buku, $cached, 'cache');
        }

        $baseUrl = rtrim((string) config('services.openlibrary.base_url'), '/');
        $timeout = max((int) config('services.openlibrary.timeout', 8), 1);

        try {
            $response = Http::acceptJson()
                ->withUserAgent(config('app.name', 'SIPUS-Del').'/1.0 ('.config('services.openlibrary.contact').')')
                ->connectTimeout(min($timeout, 5))
                ->timeout($timeout)
                ->get("{$baseUrl}/isbn/{$isbn}.json");
        } catch (ConnectionException $e) {
            $pesan     = $e->getMessage();
            $isTimeout = str_contains($pesan, 'cURL error 28') || stripos($pesan, 'timed out') !== false;

            Log::warning('OpenLibrary tidak dapat dihubungi', ['isbn' => $isbn, 'error' => $pesan]);

            return $this->problemResponse(
                title: $isTimeout ? 'Gateway Timeout' : 'Bad Gateway',
                detail: $isTimeout
                    ? "Layanan OpenLibrary tidak merespons dalam {$timeout} detik. Silakan coba beberapa saat lagi."
                    : 'Gagal terhubung ke layanan eksternal OpenLibrary.',
                status: $isTimeout ? 504 : 502,
                errorSlug: $isTimeout ? 'upstream-timeout' : 'upstream-unreachable'
            );
        }

        if ($response->status() === 404) {
            return $this->problemResponse(
                title: 'External Reference Not Found',
                detail: "ISBN {$isbn} tidak terdaftar di OpenLibrary.",
                status: 404,
                errorSlug: 'referensi-eksternal-tidak-ditemukan'
            );
        }

        $data = $response->json();

        if ($response->failed() || ! is_array($data)) {
            Log::warning('OpenLibrary merespons galat', ['isbn' => $isbn, 'status' => $response->status()]);

            return $this->problemResponse(
                title: 'Bad Gateway',
                detail: "Layanan OpenLibrary merespons dengan status {$response->status()}.",
                status: 502,
                errorSlug: 'upstream-error'
            );
        }

        $penerbit = $data['publishers'][0] ?? null;
        $judul    = trim(($data['title'] ?? '').(isset($data['subtitle']) ? ': '.$data['subtitle'] : ''));

        $ringkasan = [
            'judul'          => $judul !== '' ? $judul : null,
            'penerbit'       => is_string($penerbit) ? $penerbit : null,
            'jumlah_halaman' => isset($data['number_of_pages']) ? (int) $data['number_of_pages'] : null,
            'tanggal_terbit' => $data['publish_date'] ?? null,
            'url'            => isset($data['key']) ? $baseUrl.$data['key'] : "{$baseUrl}/isbn/{$isbn}",
            'sampul_url'     => rtrim((string) config('services.openlibrary.covers_url'), '/')."/b/isbn/{$isbn}-M.jpg",
        ];

        Cache::put($cacheKey, $ringkasan, self::CACHE_TTL_SECONDS);

        return $this->referensiResponse($buku, $ringkasan, 'openlibrary');
    }

    private function referensiResponse(Buku $buku, array $ringkasan, string $sumber): JsonResponse
    {
        return $this->successResponse(
            data: [
                'buku' => [
                    'id'    => $buku->id,
                    'isbn'  => $buku->isbn,
                    'judul' => $buku->judul,
                ],
                'referensi_eksternal' => $ringkasan,
                '_links' => [
                    'self' => ['href' => url("/api/v1/bukus/{$buku->id}/referensi-eksternal"), 'method' => 'GET'],
                    'buku' => ['href' => url("/api/v1/bukus/{$buku->id}"), 'method' => 'GET'],
                ],
            ],
            message: 'Referensi eksternal buku berhasil diambil dari OpenLibrary.',
            meta: [
                'provider' => 'OpenLibrary',
                'sumber'   => $sumber,
            ]
        );
    }
}