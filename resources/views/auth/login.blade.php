<x-layout title="Login Pustakawan">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-1">Login Pustakawan</h1>
                    <p class="text-muted small mb-4">Masuk untuk mengelola buku, kategori, dan sirkulasi peminjaman.</p>

                    <form action="{{ route('login.attempt') }}" method="POST" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            {{-- Nilai awal diambil dari cookie sipus_last_email bila ada --}}
                            <input type="email" id="email" name="email" autocomplete="username"
                                   value="{{ old('email', $emailTerakhir) }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @if ($emailTerakhir)
                                <div class="form-text">Email terakhir diingat dari cookie browser.</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" autocomplete="current-password"
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" id="remember" name="remember" value="1" class="form-check-input" @checked(old('remember'))>
                            <label for="remember" class="form-check-label">Ingat saya</label>
                        </div>

                        <button type="submit" class="btn btn-del w-100">Masuk</button>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted small mt-3">Pengunjung tetap dapat melihat katalog buku tanpa login.</p>
        </div>
    </div>
</x-layout>