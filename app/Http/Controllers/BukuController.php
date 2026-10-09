<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BukuController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari', ''));

        $bukus = Buku::with('kategori')                     // Eager Loading: cegah N+1
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($sub) use ($cari) {
                    $sub->where('judul', 'like', "%{$cari}%")
                        ->orWhere('penulis', 'like', "%{$cari}%")
                        ->orWhere('isbn', 'like', "%{$cari}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('buku.index', compact('bukus', 'cari'));
    }

    public function create(): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        return view('buku.create', compact('kategoris'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages(), $this->attributes());

        Buku::create($validated);

        return redirect()->route('buku.index')
            ->with('success', "Buku \"{$validated['judul']}\" berhasil ditambahkan.");
    }

    public function show(Buku $buku): View
    {
        $buku->load('kategori');

        return view('buku.show', compact('buku'));
    }

    public function edit(Buku $buku): View
    {
        $kategoris = Kategori::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        return view('buku.edit', compact('buku', 'kategoris'));
    }

    public function update(Request $request, Buku $buku): RedirectResponse
    {
        $validated = $request->validate($this->rules($buku), $this->messages(), $this->attributes());

        $buku->update($validated);

        return redirect()->route('buku.show', $buku)
            ->with('success', 'Perubahan data buku berhasil disimpan.');
    }

    public function destroy(Buku $buku): RedirectResponse
    {
        $judul = $buku->judul;
        $buku->delete();

        return redirect()->route('buku.index')
            ->with('success', "Buku \"{$judul}\" berhasil dihapus.");
    }

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
            'min'      => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.'],
            'max'      => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.'],
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