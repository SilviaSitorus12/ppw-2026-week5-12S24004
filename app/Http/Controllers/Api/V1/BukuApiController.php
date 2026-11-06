<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BukuResource;
use App\Models\Buku;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * REST Provider katalog buku /api/v1/bukus (RMM Level 2 & 3).
 */
class BukuApiController extends Controller
{
    use ApiResponse;

    /** Whitelist kolom ORDER BY (mencegah SQL injection via ?sort=). */
    private const SORTABLE = ['id', 'judul', 'penulis', 'tahun_terbit', 'stok', 'created_at'];

    /** Jumlah riwayat peminjaman terbaru yang ditampilkan di detail buku. */
    private const RIWAYAT_LIMIT = 20;

    /**
     * GET /api/v1/bukus (publik)
     * ?search= ?kategori_id= ?tersedia=1 ?sort= ?dir= ?per_page= ?page=
     */
    public function index(Request $request): JsonResponse
    {
        $sort    = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'judul';
        $dir     = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $paginator = $this->baseQuery()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($sub) => $sub
                    ->where('judul', 'like', $term)
                    ->orWhere('penulis', 'like', $term)
                    ->orWhere('isbn', 'like', $term));
            })
            ->when($request->filled('kategori_id'), fn ($q) => $q->where('kategori_id', (int) $request->query('kategori_id')))
            ->when($request->boolean('tersedia'), fn ($q) => $q->where('stok', '>', 0))
            ->orderBy($sort, $dir)
            ->orderBy('id') // urutan stabil antar halaman
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse($paginator, BukuResource::class, 'Katalog buku berhasil diambil.');
    }

    /**
     * GET /api/v1/bukus/{id} (publik)
     * Detail + kategori + riwayat peminjaman terbaru.
     */
    public function show(string $id): JsonResponse
    {
        $buku = $this->baseQuery()
            ->with(['peminjamans' => fn ($q) => $q
                ->latest('tanggal_pinjam')
                ->latest('id')
                ->limit(self::RIWAYAT_LIMIT)])
            ->find($id);

        if (! $buku) {
            return $this->bukuNotFound($id);
        }

        return $this->successResponse(
            data: new BukuResource($buku),
            message: 'Detail buku berhasil diambil.'
        );
    }

    /**
     * POST /api/v1/bukus (auth:sanctum) -> 201 Created + Location
     */
    public function store(Request $request): JsonResponse
    {
        $this->normalizeIsbn($request);
        $validated = $request->validate($this->rules());

        $buku = Buku::create($validated);

        return $this->createdResponse(
            data: new BukuResource($this->reload($buku->id)),
            locationUrl: url("/api/v1/bukus/{$buku->id}"),
            message: "Buku \"{$buku->judul}\" berhasil ditambahkan."
        );
    }

    /**
     * PUT /api/v1/bukus/{id} (auth:sanctum): FULL REPLACEMENT.
     * Semua field wajib dikirim; field nullable (sinopsis) yang tidak dikirim di-reset ke null.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->bukuNotFound($id);
        }

        $this->normalizeIsbn($request);
        $validated = $request->validate($this->rules($buku));

        // Bukti semantik PUT: representasi diganti utuh, termasuk mengosongkan field yang tidak dikirim
        $validated['sinopsis'] = $validated['sinopsis'] ?? null;

        $buku->update($validated);

        return $this->successResponse(
            data: new BukuResource($this->reload($buku->id)),
            message: 'Seluruh representasi buku berhasil diganti (PUT).'
        );
    }

    /**
     * PATCH /api/v1/bukus/{id} (auth:sanctum): PARTIAL UPDATE.
     * Hanya field yang dikirim yang divalidasi & diubah; body kosong -> 400.
     */
    public function partialUpdate(Request $request, string $id): JsonResponse
    {
        $buku = Buku::find($id);

        if (! $buku) {
            return $this->bukuNotFound($id);
        }

        $this->normalizeIsbn($request);
        $validated = $request->validate($this->rules($buku, partial: true));

        if ($validated === []) {
            return $this->problemResponse(
                title: 'Bad Request',
                detail: 'Permintaan PATCH wajib menyertakan minimal satu atribut yang ingin diperbarui.',
                status: 400,
                errorSlug: 'empty-patch-payload'
            );
        }

        $buku->update($validated);

        return $this->successResponse(
            data: new BukuResource($this->reload($buku->id)),
            message: 'Atribut buku berhasil diperbarui sebagian (PATCH).',
            meta: ['updated_fields' => array_keys($validated)]
        );
    }

    /**
     * DELETE /api/v1/bukus/{id} (auth:sanctum)
     * 409 jika masih ada peminjaman 'dipinjam'; 204 No Content jika berhasil.
     * Baris buku dikunci agar tidak "balapan" dengan transaksi peminjaman baru.
     */
    public function destroy(string $id): JsonResponse|Response
    {
        return DB::transaction(function () use ($id) {
            $buku = Buku::whereKey($id)->lockForUpdate()->first();

            if (! $buku) {
                return $this->bukuNotFound($id);
            }

            $pinjamanAktif = $buku->peminjamans()->where('status', 'dipinjam')->count();

            if ($pinjamanAktif > 0) {
                return $this->problemResponse(
                    title: 'Conflict — Book Has Active Loans',
                    detail: "Buku \"{$buku->judul}\" tidak dapat dihapus karena masih dipinjam oleh {$pinjamanAktif} peminjam. Selesaikan pengembalian terlebih dahulu.",
                    status: 409,
                    errorSlug: 'buku-has-active-loans'
                );
            }

            $buku->delete();

            return $this->noContentResponse();
        });
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /**
     * Query dasar bebas N+1: kategori di-eager-load, jumlah peminjaman dihitung via subquery.
     */
    private function baseQuery(): Builder
    {
        return Buku::query()
            ->with('kategori')
            ->withCount([
                'peminjamans',
                'peminjamans as peminjaman_aktif_count' => fn ($q) => $q->where('status', 'dipinjam'),
            ]);
    }

    private function reload(int $id): Buku
    {
        return $this->baseQuery()->findOrFail($id);
    }

    /**
     * Aturan validasi bersama POST/PUT (wajib semua) & PATCH (sometimes|required).
     */
    private function rules(?Buku $buku = null, bool $partial = false): array
    {
        $req = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'isbn'         => [...$req, 'string', 'regex:/^\d{13}$/', Rule::unique('bukus', 'isbn')->ignore($buku?->id)],
            'judul'        => [...$req, 'string', 'max:255'],
            'penulis'      => [...$req, 'string', 'max:150'],
            'penerbit'     => [...$req, 'string', 'max:150'],
            'tahun_terbit' => [...$req, 'integer', 'min:1000', 'max:'.(now()->year + 1)],
            'kategori_id'  => [...$req, 'integer', 'exists:kategoris,id'],
            'stok'         => [...$req, 'integer', 'min:0', 'max:100000'],
            'sinopsis'     => $partial
                ? ['sometimes', 'nullable', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * "978-602-..." -> "978602..." agar cocok dengan kolom isbn varchar(13).
     */
    private function normalizeIsbn(Request $request): void
    {
        if (is_string($request->input('isbn'))) {
            $request->merge(['isbn' => preg_replace('/[\s-]+/', '', $request->input('isbn'))]);
        }
    }

    private function bukuNotFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Buku dengan ID '{$id}' tidak ditemukan di katalog.",
            status: 404,
            errorSlug: 'buku-not-found'
        );
    }
}