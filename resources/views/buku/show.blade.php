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
                @auth
                <div class="d-flex gap-2">
                    <a href="{{ route('buku.edit', $buku) }}" class="btn btn-outline-del">Ubah</a>
                    <form action="{{ route('buku.destroy', $buku) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus buku ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Hapus</button>
                    </form>
                </div>
                @endauth
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

    @auth
    <div class="row g-4 mt-1">
        {{-- Form peminjaman: diproses PeminjamanController@pinjam dalam DB::transaction --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-1">Pinjam buku ini</h2>
                    <p class="text-muted small mb-3">
                        Stok tersedia: <strong>{{ $buku->stok }}</strong>. Peminjaman diproses dalam satu transaksi database.
                    </p>

                    @if ($buku->stok < 1)
                        <div class="alert alert-warning small py-2">
                            Stok habis. Permintaan peminjaman akan ditolak dan transaksi dibatalkan (rollback).
                        </div>
                    @endif

                    <form action="{{ route('peminjaman.pinjam', $buku->id) }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="nim_peminjam" class="form-label">NIM peminjam</label>
                            <input type="text" id="nim_peminjam" name="nim_peminjam" value="{{ old('nim_peminjam', session('peminjam_terakhir.nim')) }}"
                                   class="form-control text-uppercase @error('nim_peminjam') is-invalid @enderror">
                            @error('nim_peminjam') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nama_peminjam" class="form-label">Nama peminjam</label>
                            <input type="text" id="nama_peminjam" name="nama_peminjam" value="{{ old('nama_peminjam', session('peminjam_terakhir.nama')) }}"
                                   class="form-control @error('nama_peminjam') is-invalid @enderror">
                            @error('nama_peminjam') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="durasi_hari" class="form-label">Lama pinjam (hari)</label>
                            <input type="number" id="durasi_hari" name="durasi_hari" value="{{ old('durasi_hari', 7) }}"
                                   class="form-control @error('durasi_hari') is-invalid @enderror">
                            <div class="form-text">1 sampai 14 hari.</div>
                            @error('durasi_hari') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if (session('peminjam_terakhir'))
                            <div class="form-text mb-2">NIM dan nama terisi dari peminjam terakhir (disimpan di session).</div>
                        @endif
                        <button type="submit" class="btn btn-del w-100">Pinjam buku</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Riwayat: sudah di-eager load di BukuController@show --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Riwayat peminjaman terakhir</h2>

                    @forelse ($buku->peminjamans as $peminjaman)
                        <div class="d-flex justify-content-between align-items-center gap-3 border-bottom py-2">
                            <div>
                                <div class="fw-semibold">
                                    {{ $peminjaman->nama_peminjam }}
                                    <span class="text-muted small">({{ $peminjaman->nim_peminjam }})</span>
                                </div>
                                <small class="text-muted">
                                    {{ $peminjaman->kode_pinjam }}, dipinjam {{ $peminjaman->tanggal_pinjam->format('d M Y') }}
                                </small>
                            </div>
                            @if ($peminjaman->status === 'dikembalikan')
                                <span class="badge text-bg-success">Dikembalikan</span>
                            @else
                                <span class="badge text-bg-warning">Dipinjam</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">Buku ini belum pernah dipinjam.</p>
                    @endforelse

                    <a href="{{ route('peminjaman.index') }}" class="link-del small d-inline-block mt-3">Lihat semua peminjaman</a>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-light border mt-4 mb-0">
            Peminjaman dicatat oleh pustakawan.
            <a href="{{ route('login') }}" class="link-del">Login sebagai pustakawan</a> untuk mencatat peminjaman buku ini.
        </div>
    @endauth
</x-layout>