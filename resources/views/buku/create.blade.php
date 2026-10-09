<x-layout title="Tambah Buku">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('buku.index') }}" class="link-del">Buku</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah</li>
        </ol>
    </nav>

    <h1 class="h3 mb-3">Tambah Buku</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            {{-- novalidate: mematikan validasi HTML5 agar validasi server-side yang bekerja --}}
            <form action="{{ route('buku.store') }}" method="POST" novalidate>
                @csrf
                @include('buku._form', ['buku' => null])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-del">Simpan buku</button>
                    <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>