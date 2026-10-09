<x-layout title="Kategori Buku">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Kategori Buku</h1>
            <p class="text-muted mb-0">{{ $kategoris->total() }} kategori</p>
        </div>
        <a href="{{ route('kategori.create') }}" class="btn btn-del">Tambah kategori</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama kategori</th>
                        <th scope="col" class="text-center">Jumlah buku</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kategoris as $kategori)
                        <tr>
                            <td>{{ $kategoris->firstItem() + $loop->index }}</td>
                            <td><span class="badge badge-del">{{ $kategori->kode_kategori }}</span></td>
                            <td>
                                <a href="{{ route('kategori.show', $kategori) }}" class="fw-semibold text-decoration-none link-del">
                                    {{ $kategori->nama_kategori }}
                                </a>
                            </td>
                            <td class="text-center">{{ $kategori->bukus_count }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('kategori.edit', $kategori) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                <form action="{{ route('kategori.destroy', $kategori) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                Belum ada kategori. <a href="{{ route('kategori.create') }}" class="link-del">Tambahkan kategori pertama</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $kategoris->links() }}
    </div>
</x-layout>