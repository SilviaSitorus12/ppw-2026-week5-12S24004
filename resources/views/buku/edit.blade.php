<x-layout title="Ubah Buku">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('buku.index') }}" class="link-del">Buku</a></li>
            <li class="breadcrumb-item"><a href="{{ route('buku.show', $buku) }}" class="link-del">{{ $buku->judul }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ubah</li>
        </ol>
    </nav>

    <h1 class="h3 mb-3">Ubah Buku</h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('buku.update', $buku) }}" method="POST" novalidate>
                @csrf
                @method('PUT')
                @include('buku._form', ['buku' => $buku])

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-del">Simpan perubahan</button>
                    <a href="{{ route('buku.show', $buku) }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>