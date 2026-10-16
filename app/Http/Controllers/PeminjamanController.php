<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class PeminjamanController extends Controller
{
    /**
     * GET /peminjaman - daftar transaksi sirkulasi (eager loading buku, filter, pagination).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $cari   = trim((string) $request->query('cari', ''));

        $peminjamans = Peminjaman::with('buku')
            ->when(in_array($status, ['dipinjam', 'dikembalikan'], true), fn ($q) => $q->where('status', $status))
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($sub) use ($cari) {
                    $sub->where('kode_pinjam', 'like', "%{$cari}%")
                        ->orWhere('nim_peminjam', 'like', "%{$cari}%")
                        ->orWhere('nama_peminjam', 'like', "%{$cari}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('peminjaman.index', compact('peminjamans', 'status', 'cari'));
    }

    /**
     * POST /buku/{id}/pinjam - transaksi ACID peminjaman buku.
     */
    public function pinjam(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'nim_peminjam'  => ['required', 'alpha_num', 'min:6', 'max:15'],
            'nama_peminjam' => ['required', 'string', 'min:3', 'max:150'],
            'durasi_hari'   => ['required', 'integer', 'min:1', 'max:14'],
        ], [
            'required'  => ':attribute wajib diisi.',
            'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
            'integer'   => ':attribute harus berupa bilangan bulat.',
            'min'       => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min hari.'],
            'max'       => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max hari.'],
        ], [
            'nim_peminjam'  => 'NIM peminjam',
            'nama_peminjam' => 'Nama peminjam',
            'durasi_hari'   => 'Lama pinjam',
        ]);

        try {
            $peminjaman = DB::transaction(function () use ($validated, $id) {
                // 1. Kunci baris buku (SELECT ... FOR UPDATE): transaksi lain pada buku ini harus menunggu.
                $buku = Buku::where('id', $id)->lockForUpdate()->firstOrFail();

                // 2. Stok habis -> lempar exception -> seluruh transaksi di-ROLLBACK.
                if ($buku->stok < 1) {
                    throw new RuntimeException("Stok buku \"{$buku->judul}\" habis. Peminjaman dibatalkan (rollback).");
                }

                // 3. NIM yang sama tidak boleh meminjam buku yang sama sebelum mengembalikannya.
                $nim = strtoupper(trim($validated['nim_peminjam']));

                $masihMeminjam = $buku->peminjamans()
                    ->where('nim_peminjam', $nim)
                    ->where('status', 'dipinjam')
                    ->exists();

                if ($masihMeminjam) {
                    throw new RuntimeException("NIM {$nim} masih meminjam buku ini dan belum mengembalikannya.");
                }

                // 4. Kurangi stok dan catat transaksi: keduanya berhasil, atau keduanya batal.
                $buku->decrement('stok');

                return $buku->peminjamans()->create([
                    'kode_pinjam'         => 'PJM-' . now()->format('Ymd') . '-' . Str::upper(Str::random(5)),
                    'nim_peminjam'        => $nim,
                    'nama_peminjam'       => trim($validated['nama_peminjam']),
                    'tanggal_pinjam'      => now()->toDateString(),
                    'tanggal_jatuh_tempo' => now()->addDays((int) $validated['durasi_hari'])->toDateString(),
                    'status'              => 'dipinjam',
                ]);
            }, 3); // dicoba ulang hingga 3 kali bila terjadi deadlock

            return redirect()->route('peminjaman.index')
                ->with('success', "Peminjaman {$peminjaman->kode_pinjam} berhasil dicatat. Stok buku berkurang 1.");
        } catch (ModelNotFoundException $e) {
            abort(404);
        } catch (QueryException $e) {
            // Ditangkap sebelum RuntimeException karena QueryException juga turunan RuntimeException.
            Log::error('[DB EXCEPTION] Peminjaman gagal: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Transaksi peminjaman gagal karena kesalahan database. Tidak ada data yang berubah.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * PATCH /peminjaman/{id}/kembali - transaksi ACID pengembalian buku.
     */
    public function kembali(int $id): RedirectResponse
    {
        try {
            $peminjaman = DB::transaction(function () use ($id) {
                // Kunci baris peminjaman agar tidak bisa dikembalikan dua kali secara bersamaan.
                $peminjaman = Peminjaman::where('id', $id)->lockForUpdate()->firstOrFail();

                if ($peminjaman->status === 'dikembalikan') {
                    throw new RuntimeException("Peminjaman {$peminjaman->kode_pinjam} sudah dikembalikan sebelumnya.");
                }

                // Kunci baris buku sebelum stoknya ditambah.
                $buku = Buku::where('id', $peminjaman->buku_id)->lockForUpdate()->firstOrFail();

                $peminjaman->update([
                    'status'          => 'dikembalikan',
                    'tanggal_kembali' => now()->toDateString(),
                ]);

                $buku->increment('stok');

                return $peminjaman;
            }, 3);

            return back()->with('success', "Buku untuk peminjaman {$peminjaman->kode_pinjam} telah dikembalikan. Stok buku bertambah 1.");
        } catch (ModelNotFoundException $e) {
            abort(404);
        } catch (QueryException $e) {
            Log::error('[DB EXCEPTION] Pengembalian gagal: ' . $e->getMessage());

            return back()->with('error', 'Transaksi pengembalian gagal karena kesalahan database. Tidak ada data yang berubah.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}