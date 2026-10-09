<x-layout title="Daftar Buku">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Daftar Buku</h1>
            <p class="text-muted mb-0">{{ $bukus->total() }} judul tercatat di perpustakaan</p>
        </div>
        <a href="{{ route('buku.create') }}" class="btn btn-del">Tambah buku</a>
    </div>

    <form method="GET" action="{{ route('buku.index') }}" class="mb-3" role="search">
        <div class="input-group">
            <input type="search" name="cari" value="{{ $cari }}" class="form-control"
                   placeholder="Cari judul, penulis, atau ISBN" aria-label="Kata kunci pencarian">
            <button class="btn btn-outline-del" type="submit">Cari</button>
            @if ($cari !== '')
                <a href="{{ route('buku.index') }}" class="btn btn-outline-secondary">Hapus filter</a>
            @endif
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
                            <td colspan="8" class="text-center text-muted py-5">
                                @if ($cari !== '')
                                    Tidak ada buku yang cocok dengan "{{ $cari }}". Coba kata kunci lain.
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