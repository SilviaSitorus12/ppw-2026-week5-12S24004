<x-layout title="Daftar Buku">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Katalog Buku</h1>
            <p class="text-muted mb-0">{{ $bukus->total() }} judul sesuai filter</p>
        </div>
        <a href="{{ route('buku.create') }}" class="btn btn-del">Tambah buku</a>
    </div>

    {{-- Kartu statistik: dihitung dengan Fluent Query Builder DB::table() --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total judul buku</div>
                    <div class="fs-3 fw-bold">{{ number_format($stats['total_judul']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total stok tersedia</div>
                    <div class="fs-3 fw-bold">{{ number_format($stats['total_stok']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Sedang dipinjam</div>
                    <div class="fs-3 fw-bold text-warning">{{ number_format($stats['sedang_dipinjam']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total dikembalikan</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($stats['total_dikembalikan']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter dinamis: kata kunci + kategori (diproses dengan when() di controller) --}}
    <form method="GET" action="{{ route('buku.index') }}" class="mb-3" role="search">
        <div class="row g-2">
            <div class="col-md-7">
                <input type="search" name="cari" value="{{ $cari }}" class="form-control"
                       placeholder="Cari judul, penulis, atau ISBN" aria-label="Kata kunci pencarian">
            </div>
            <div class="col-md-3">
                <select name="kategori" class="form-select" aria-label="Filter kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori->id }}" @selected($kategoriId === $kategori->id)>{{ $kategori->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-del flex-fill" type="submit">Cari</button>
                @if ($cari !== '' || $kategoriId > 0)
                    <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary" title="Hapus filter">Reset</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">ISBN</th>
                        <th scope="col">Judul</th>
                        <th scope="col">Penulis</th>
                        <th scope="col">Kategori</th>
                        <th scope="col" class="text-center">Tahun</th>
                        <th scope="col" class="text-center">Stok</th>
                        <th scope="col" class="text-center">Dipinjam</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukus as $buku)
                        <tr>
                            <td>{{ $bukus->firstItem() + $loop->index }}</td>
                            <td><code>{{ $buku->isbn }}</code></td>
                            <td>
                                <a href="{{ route('buku.show', $buku) }}" class="fw-semibold text-decoration-none link-del">
                                    {{ $buku->judul }}
                                </a>
                            </td>
                            <td>{{ $buku->penulis }}</td>
                            <td><span class="badge badge-del">{{ $buku->kategori->nama_kategori }}</span></td>
                            <td class="text-center">{{ $buku->tahun_terbit }}</td>
                            <td class="text-center">
                                @if ($buku->stok > 0)
                                    <span class="badge text-bg-success">{{ $buku->stok }}</span>
                                @else
                                    <span class="badge text-bg-danger">Habis</span>
                                @endif
                            </td>
                            {{-- peminjamans_count berasal dari withCount('peminjamans') --}}
                            <td class="text-center">{{ $buku->peminjamans_count }}x</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('buku.edit', $buku) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus buku ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                @if ($cari !== '' || $kategoriId > 0)
                                    Tidak ada buku yang cocok dengan filter ini. Coba kata kunci atau kategori lain.
                                @else
                                    Belum ada buku. <a href="{{ route('buku.create') }}" class="link-del">Tambahkan buku pertama</a>.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $bukus->links() }}
    </div>
</x-layout>