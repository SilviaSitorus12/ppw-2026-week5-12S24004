<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PeminjamanResource;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * REST API transaksi sirkulasi /api/v1/peminjamans (seluruhnya wajib Bearer Token).
 *
 * Jaminan ACID:
 * - Setiap mutasi dibungkus DB::transaction().
 * - Baris buku dikunci dengan lockForUpdate() (SELECT ... FOR UPDATE) agar dua request
 *   yang datang bersamaan tidak bisa sama-sama "melihat stok 1" lalu sama-sama meminjam.
 * - Semua pengecekan (404/409/422) dilakukan SEBELUM stok diubah -> tidak ada kebocoran stok.
 * - Urutan kunci selalu: buku -> peminjaman (mencegah deadlock).
 */
class PeminjamanApiController extends Controller
{
    use ApiResponse;

    /** Durasi pinjam bawaan jika durasi_hari tidak dikirim (sama dengan web Minggu 6). */
    private const DEFAULT_DURASI_HARI = 7;

    /**
     * GET /api/v1/peminjamans?status=dipinjam|dikembalikan&nim=...&buku_id=...&per_page=...
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status'  => 'nullable|in:dipinjam,dikembalikan',
            'nim'     => 'nullable|string|max:15',
            'buku_id' => 'nullable|integer|min:1',
        ]);

        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $paginator = Peminjaman::query()
            ->with('buku') // bebas N+1: ringkasan buku dimuat sekali untuk seluruh halaman
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['nim'] ?? null, fn ($q, $nim) => $q->where('nim_peminjam', strtoupper(trim($nim))))
            ->when($filters['buku_id'] ?? null, fn ($q, $bukuId) => $q->where('buku_id', $bukuId))
            ->latest('tanggal_pinjam')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse($paginator, PeminjamanResource::class, 'Daftar transaksi peminjaman berhasil diambil.');
    }

    /**
     * GET /api/v1/peminjamans/{id}
     */
    public function show(string $id): JsonResponse
    {
        $peminjaman = Peminjaman::with('buku')->find($id);

        if (! $peminjaman) {
            return $this->peminjamanNotFound($id);
        }

        return $this->successResponse(
            data: new PeminjamanResource($peminjaman),
            message: 'Detail transaksi peminjaman berhasil diambil.'
        );
    }

    /**
     * POST /api/v1/peminjamans
     * Body: buku_id, nim_peminjam, nama_peminjam, durasi_hari (opsional, default 7)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'buku_id'       => 'required|integer|min:1',
            'nim_peminjam'  => 'required|string|max:15',
            'nama_peminjam' => 'required|string|max:150',
            'durasi_hari'   => 'nullable|integer|min:1|max:30',
        ]);

        $nim    = strtoupper(trim($validated['nim_peminjam']));
        $durasi = (int) ($validated['durasi_hari'] ?? self::DEFAULT_DURASI_HARI);

        return DB::transaction(function () use ($validated, $nim, $durasi) {
            // 1. Kunci baris buku (SELECT ... FOR UPDATE)
            $buku = Buku::whereKey($validated['buku_id'])->lockForUpdate()->first();

            if (! $buku) {
                return $this->problemResponse(
                    title: 'Resource Not Found',
                    detail: "Buku dengan ID '{$validated['buku_id']}' tidak ditemukan di katalog.",
                    status: 404,
                    errorSlug: 'buku-not-found'
                );
            }

            // 2. NIM yang sama masih meminjam buku yang sama -> 409 Conflict
            $masihMeminjam = Peminjaman::where('buku_id', $buku->id)
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->exists();

            if ($masihMeminjam) {
                return $this->problemResponse(
                    title: 'Duplicate Loan Conflict',
                    detail: "Mahasiswa dengan NIM '{$nim}' masih meminjam buku \"{$buku->judul}\" dan belum mengembalikannya.",
                    status: 409,
                    errorSlug: 'duplicate-active-loan'
                );
            }

            // 3. Stok habis -> 422 Unprocessable Content
            if ($buku->stok < 1) {
                return $this->problemResponse(
                    title: 'Unprocessable Content',
                    detail: "Stok buku sedang habis. Buku \"{$buku->judul}\" belum dapat dipinjam.",
                    status: 422,
                    errorSlug: 'stok-habis',
                    errors: ['buku_id' => ['Stok buku sedang habis.']]
                );
            }

            // 4. Lolos semua pengecekan: stok -1 & catat peminjaman (atomik)
            $buku->decrement('stok');

            $peminjaman = Peminjaman::create([
                'kode_pinjam'         => $this->generateKodePinjam(),
                'buku_id'             => $buku->id,
                'nim_peminjam'        => $nim,
                'nama_peminjam'       => trim($validated['nama_peminjam']),
                'tanggal_pinjam'      => today(),
                'tanggal_jatuh_tempo' => today()->addDays($durasi),
                'status'              => 'dipinjam',
            ]);

            $peminjaman->load('buku');

            return $this->createdResponse(
                data: new PeminjamanResource($peminjaman),
                locationUrl: url("/api/v1/peminjamans/{$peminjaman->id}"),
                message: "Peminjaman berhasil dicatat. Sisa stok \"{$buku->judul}\": {$buku->stok}."
            );
        });
    }

    /**
     * PATCH /api/v1/peminjamans/{id}/kembalikan
     */
    public function kembalikan(string $id): JsonResponse
    {
        // Baca tanpa kunci hanya untuk mengetahui buku_id (agar urutan kunci tetap buku -> peminjaman)
        $awal = Peminjaman::find($id);

        if (! $awal) {
            return $this->peminjamanNotFound($id);
        }

        return DB::transaction(function () use ($id, $awal) {
            // 1. Kunci buku dulu, lalu kunci & baca ulang peminjaman (state terbaru)
            $buku       = Buku::whereKey($awal->buku_id)->lockForUpdate()->first();
            $peminjaman = Peminjaman::whereKey($id)->lockForUpdate()->first();

            // 2. Sudah dikembalikan -> 409 Conflict (stok tidak disentuh)
            if ($peminjaman->status === 'dikembalikan') {
                $tgl = $peminjaman->tanggal_kembali?->toDateString() ?? '-';

                return $this->problemResponse(
                    title: 'Loan Already Returned',
                    detail: "Peminjaman {$peminjaman->kode_pinjam} sudah dikembalikan pada {$tgl}.",
                    status: 409,
                    errorSlug: 'loan-already-returned'
                );
            }

            $hariTerlambat = $peminjaman->tanggal_jatuh_tempo->lt(today())
                ? (int) $peminjaman->tanggal_jatuh_tempo->diffInDays(today())
                : 0;

            // 3. Ubah status, isi tanggal_kembali, stok +1 (atomik)
            $peminjaman->update([
                'status'          => 'dikembalikan',
                'tanggal_kembali' => today(),
            ]);

            $buku?->increment('stok');

            $peminjaman->load('buku');

            return $this->successResponse(
                data: new PeminjamanResource($peminjaman),
                message: "Buku berhasil dikembalikan. Stok \"{$buku?->judul}\" kini: {$buku?->stok}.",
                meta: [
                    'terlambat'      => $hariTerlambat > 0,
                    'hari_terlambat' => $hariTerlambat,
                ]
            );
        });
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /**
     * Format sama dengan web Minggu 6: PJM-YYYYMMDD-XXXXX (unik).
     */
    private function generateKodePinjam(): string
    {
        do {
            $kode = 'PJM-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (Peminjaman::where('kode_pinjam', $kode)->exists());

        return $kode;
    }

    private function peminjamanNotFound(string $id): JsonResponse
    {
        return $this->problemResponse(
            title: 'Resource Not Found',
            detail: "Transaksi peminjaman dengan ID '{$id}' tidak ditemukan.",
            status: 404,
            errorSlug: 'peminjaman-not-found'
        );
    }
}