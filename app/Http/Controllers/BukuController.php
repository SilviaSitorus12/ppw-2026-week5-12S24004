<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BukuController extends Controller
{
    /**
     * GET /buku - katalog buku: eager loading, withCount, filter dinamis, statistik Query Builder.
     */
    public function index(Request $request): View
    {
        $cari       = trim((string) $request->query('cari', ''));
        $kategoriId = $request->integer('kategori');   // 0 jika tidak dipilih

        // Eloquent: relasi kategori di-eager load, jumlah peminjaman dihitung lewat subquery (bebas N+1).
        $bukus = Buku::with('kategori')
            ->withCount('peminjamans')
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($sub) use ($cari) {
                    $sub->where('judul', 'like', "%{$cari}%")      // parameter binding -> aman dari SQLi
                        ->orWhere('penulis', 'like', "%{$cari}%")
                        ->orWhere('isbn', 'like', "%{$cari}%");
                });
            })
            ->when($kategoriId > 0, fn ($query) => $query->where('kategori_id', $kategoriId))
            ->latest()
            ->paginate(10)
            ->withQueryString();                              // filter tetap terbawa saat pindah halaman

        // Fluent Query Builder (DB::table): agregasi cepat tanpa membuat objek model.
        $stats = [
            'total_judul'        => DB::table('bukus')->count(),
            'total_stok'         => (int) DB::table('bukus')->sum('stok'),
            'sedang_dipinjam'    => DB::table('peminjamans')->where('status', 'dipinjam')->count(),
            'total_dikembalikan' => DB::table('peminjamans')->where('status', 'dikembalikan')->count(),
        ];

        $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        return view('buku.index', compact('bukus', 'cari', 'kategoriId', 'kategoris', 'stats'));
    }

    /**
     * GET /buku/create - form tambah buku.
     */
    public function create(): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        return view('buku.create', compact('kategoris'));
    }

    /**
     * POST /buku - simpan buku baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages(), $this->attributes());

        Buku::create($validated);

        return redirect()->route('buku.index')
            ->with('success', "Buku \"{$validated['judul']}\" berhasil ditambahkan.");
    }

    /**
     * GET /buku/{buku} - detail buku (Implicit Route Model Binding).
     */
    public function show(Buku $buku): View
    {
        // Eager load kategori + 5 riwayat peminjaman terbaru buku ini.
        $buku->load([
            'kategori',
            'peminjamans' => fn ($q) => $q->latest()->limit(5),
        ]);

        return view('buku.show', compact('buku'));
    }

    /**
     * GET /buku/{buku}/edit - form ubah buku.
     */
    public function edit(Buku $buku): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        return view('buku.edit', compact('buku', 'kategoris'));
    }

    /**
     * PUT/PATCH /buku/{buku} - perbarui data buku.
     */
    public function update(Request $request, Buku $buku): RedirectResponse
    {
        $validated = $request->validate($this->rules($buku), $this->messages(), $this->attributes());

        $buku->update($validated);

        return redirect()->route('buku.show', $buku)
            ->with('success', 'Perubahan data buku berhasil disimpan.');
    }

    /**
     * DELETE /buku/{buku} - hapus buku.
     */
    public function destroy(Buku $buku): RedirectResponse
    {
        $judul = $buku->judul;
        $buku->delete();

        return redirect()->route('buku.index')
            ->with('success', "Buku \"{$judul}\" berhasil dihapus.");
    }

    /**
     * Aturan validasi server-side. Saat update, ISBN milik buku itu sendiri
     * diabaikan dari pengecekan unique.
     */
    private function rules(?Buku $buku = null): array
    {
        return [
            'isbn'         => ['required', 'digits:13', Rule::unique('bukus', 'isbn')->ignore($buku?->id)],
            'judul'        => ['required', 'string', 'min:5', 'max:255'],
            'penulis'      => ['required', 'string', 'min:3', 'max:150'],
            'penerbit'     => ['required', 'string', 'min:2', 'max:150'],
            'tahun_terbit' => ['required', 'integer', 'min:1900', 'max:' . now()->year],
            'kategori_id'  => ['required', 'integer', 'exists:kategoris,id'],
            'stok'         => ['required', 'integer', 'min:0', 'max:9999'],
            'sinopsis'     => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'digits'   => ':attribute harus terdiri dari tepat :digits digit angka.',
            'unique'   => ':attribute sudah terdaftar pada buku lain.',
            'integer'  => ':attribute harus berupa bilangan bulat.',
            'exists'   => ':attribute yang dipilih tidak ditemukan.',
            'min'      => [
                'string'  => ':attribute minimal :min karakter.',
                'numeric' => ':attribute minimal :min.',
            ],
            'max'      => [
                'string'  => ':attribute maksimal :max karakter.',
                'numeric' => ':attribute maksimal :max.',
            ],
        ];
    }

    private function attributes(): array
    {
        return [
            'isbn'         => 'ISBN',
            'judul'        => 'Judul buku',
            'penulis'      => 'Nama penulis',
            'penerbit'     => 'Penerbit',
            'tahun_terbit' => 'Tahun terbit',
            'kategori_id'  => 'Kategori',
            'stok'         => 'Stok',
            'sinopsis'     => 'Sinopsis',
        ];
    }
}