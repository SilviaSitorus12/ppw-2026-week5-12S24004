<x-layout :title="$buku->judul">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('buku.index') }}" class="link-del">Buku</a></li>
            <li class="breadcrumb-item active" aria-current="page">Detail</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <a href="{{ route('kategori.show', $buku->kategori) }}" class="badge badge-del text-decoration-none mb-2">
                        {{ $buku->kategori->nama_kategori }}
                    </a>
                    <h1 class="h3 mb-1">{{ $buku->judul }}</h1>
                    <p class="text-muted mb-0">oleh {{ $buku->penulis }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-del">Ubah</a>
                    <form action="{{ route('buku.destroy', $buku) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus buku ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Hapus</button>
                    </form>
                </div>
            </div>

            <dl class="row mb-4">
                <dt class="col-sm-3">ISBN</dt>
                <dd class="col-sm-9"><code>{{ $buku->isbn }}</code></dd>

                <dt class="col-sm-3">Penerbit</dt>
                <dd class="col-sm-9">{{ $buku->penerbit }}</dd>

                <dt class="col-sm-3">Tahun terbit</dt>
                <dd class="col-sm-9">{{ $buku->tahun_terbit }}</dd>

                <dt class="col-sm-3">Stok</dt>
                <dd class="col-sm-9">
                    @if ($buku->stok > 0)
                        {{ $buku->stok }} eksemplar tersedia
                    @else
                        <span class="text-danger">Stok habis</span>
                    @endif
                </dd>
            </dl>

            <h2 class="h5">Sinopsis</h2>
            {{-- e() meng-escape HTML dulu (anti-XSS), baru nl2br mengubah baris baru jadi <br> --}}
            <p class="sinopsis mb-0">{!! $buku->sinopsis ? nl2br(e($buku->sinopsis)) : '<span class="text-muted">Belum ada sinopsis.</span>' !!}</p>
        </div>
    </div>
</x-layout>