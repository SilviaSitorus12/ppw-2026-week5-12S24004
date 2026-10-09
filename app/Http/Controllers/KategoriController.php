<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function index(): View
    {
        $kategoris = Kategori::withCount('bukus')      // hitung buku tanpa N+1
            ->orderBy('nama_kategori')
            ->paginate(10);

        return view('kategori.index', compact('kategoris'));
    }

    public function create(): View
    {
        return view('kategori.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $validated['kode_kategori'] = strtoupper($validated['kode_kategori']);

        Kategori::create($validated);

        return redirect()->route('kategori.index')
            ->with('success', "Kategori \"{$validated['nama_kategori']}\" berhasil ditambahkan.");
    }

    public function show(Kategori $kategori): View
    {
        $bukus = $kategori->bukus()->latest()->paginate(10);

        return view('kategori.show', compact('kategori', 'bukus'));
    }

    public function edit(Kategori $kategori): View
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori): RedirectResponse
    {
        $validated = $request->validate($this->rules($kategori), $this->messages(), $this->attributes());
        $validated['kode_kategori'] = strtoupper($validated['kode_kategori']);

        $kategori->update($validated);

        return redirect()->route('kategori.index')
            ->with('success', 'Perubahan kategori berhasil disimpan.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        if ($kategori->bukus()->exists()) {
            return redirect()->route('kategori.index')
                ->with('error', "Kategori \"{$kategori->nama_kategori}\" masih memiliki buku. Pindahkan atau hapus bukunya terlebih dahulu.");
        }

        $kategori->delete();

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function rules(?Kategori $kategori = null): array
    {
        return [
            'kode_kategori' => ['required', 'alpha_dash', 'min:2', 'max:10', Rule::unique('kategoris', 'kode_kategori')->ignore($kategori?->id)],
            'nama_kategori' => ['required', 'string', 'min:3', 'max:100', Rule::unique('kategoris', 'nama_kategori')->ignore($kategori?->id)],
        ];
    }

    private function messages(): array
    {
        return [
            'required'   => ':attribute wajib diisi.',
            'unique'     => ':attribute sudah digunakan kategori lain.',
            'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'min'        => ['string' => ':attribute minimal :min karakter.'],
            'max'        => ['string' => ':attribute maksimal :max karakter.'],
        ];
    }

    private function attributes(): array
    {
        return [
            'kode_kategori' => 'Kode kategori',
            'nama_kategori' => 'Nama kategori',
        ];
    }
}