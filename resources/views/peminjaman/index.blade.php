<x-layout title="Peminjaman">
    <div class="mb-3">
        <h1 class="h3 mb-0">Sirkulasi Peminjaman</h1>
        <p class="text-muted mb-0">{{ $peminjamans->total() }} transaksi ditemukan. Pinjam buku dari halaman detail buku.</p>
    </div>

    {{-- Filter status --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ ! in_array($status, ['dipinjam', 'dikembalikan'], true) ? 'active bg-del' : 'link-del' }}"
               href="{{ route('peminjaman.index', array_filter(['cari' => $cari])) }}">Semua</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'dipinjam' ? 'active bg-del' : 'link-del' }}"
               href="{{ route('peminjaman.index', array_filter(['status' => 'dipinjam', 'cari' => $cari])) }}">Dipinjam</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'dikembalikan' ? 'active bg-del' : 'link-del' }}"
               href="{{ route('peminjaman.index', array_filter(['status' => 'dikembalikan', 'cari' => $cari])) }}">Dikembalikan</a>
        </li>
    </ul>

    <form method="GET" action="{{ route('peminjaman.index') }}" class="mb-3" role="search">
        @if (in_array($status, ['dipinjam', 'dikembalikan'], true))
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <div class="input-group">
            <input type="search" name="cari" value="{{ $cari }}" class="form-control"
                   placeholder="Cari kode pinjam, NIM, atau nama peminjam" aria-label="Kata kunci pencarian">
            <button class="btn btn-outline-del" type="submit">Cari</button>
            @if ($cari !== '')
                <a href="{{ route('peminjaman.index', array_filter(['status' => $status])) }}" class="btn btn-outline-secondary">Hapus filter</a>
            @endif
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Buku</th>
                        <th scope="col">Peminjam</th>
                        <th scope="col">Tgl pinjam</th>
                        <th scope="col">Jatuh tempo</th>
                        <th scope="col">Tgl kembali</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $peminjaman)
                        <tr>
                            <td><code>{{ $peminjaman->kode_pinjam }}</code></td>
                            <td>
                                <a href="{{ route('buku.show', $peminjaman->buku) }}" class="link-del text-decoration-none">
                                    {{ $peminjaman->buku->judul }}
                                </a>
                            </td>
                            <td>
                                {{ $peminjaman->nama_peminjam }}
                                <div class="small text-muted">{{ $peminjaman->nim_peminjam }}</div>
                            </td>
                            <td>{{ $peminjaman->tanggal_pinjam->format('d M Y') }}</td>
                            <td>{{ $peminjaman->tanggal_jatuh_tempo->format('d M Y') }}</td>
                            <td>{{ $peminjaman->tanggal_kembali?->format('d M Y') ?? '-' }}</td>
                            <td>
                                @if ($peminjaman->status === 'dikembalikan')
                                    <span class="badge text-bg-success">Dikembalikan</span>
                                @elseif ($peminjaman->tanggal_jatuh_tempo->lt(today()))
                                    <span class="badge text-bg-danger">Terlambat</span>
                                @else
                                    <span class="badge text-bg-warning">Dipinjam</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($peminjaman->status === 'dipinjam')
                                    <form action="{{ route('peminjaman.kembali', $peminjaman->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Tandai buku ini sudah dikembalikan?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-del">Kembalikan</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Belum ada transaksi peminjaman yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $peminjamans->links() }}
    </div>
</x-layout>