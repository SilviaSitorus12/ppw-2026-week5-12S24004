<x-layout title="Ubah Kategori">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('kategori.index') }}" class="link-del">Kategori</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ubah</li>
        </ol>
    </nav>

    <h1 class="h3 mb-3">Ubah Kategori</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('kategori.update', $kategori) }}" method="POST" novalidate>
                @csrf
                @method('PUT')
                @include('kategori._form', ['kategori' => $kategori])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-del">Simpan perubahan</button>
                    <a href="{{ route('kategori.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>