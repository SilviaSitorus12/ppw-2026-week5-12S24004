<x-layout :title="$kategori->nama_kategori">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('kategori.index') }}" class="link-del">Kategori</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $kategori->nama_kategori }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <span class="badge badge-del mb-1">{{ $kategori->kode_kategori }}</span>
            <h1 class="h3 mb-0">{{ $kategori->nama_kategori }}</h1>
            <p class="text-muted mb-0">{{ $bukus->total() }} buku dalam kategori ini</p>
        </div>
        <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-outline-del">Ubah kategori</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse ($bukus as $buku)
                <a href="{{ route('buku.show', $buku) }}" class="list-group-item list-group-item-action py-3">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">{{ $buku->judul }}</div>
                            <small class="text-muted">{{ $buku->penulis }}, {{ $buku->penerbit }} ({{ $buku->tahun_terbit }})</small>
                        </div>
                        <span class="text-nowrap small {{ $buku->stok > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $buku->stok > 0 ? 'Stok ' . $buku->stok : 'Habis' }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="list-group-item text-center text-muted py-5">
                    Belum ada buku di kategori ini.
                    <a href="{{ route('buku.create') }}" class="link-del">Tambahkan buku</a>.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">
        {{ $bukus->links() }}
    </div>
</x-layout>