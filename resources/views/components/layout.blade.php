@props(['title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' | ' : '' }}SIPUS-Del, Institut Teknologi Del</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Aset Vite hanya dimuat jika sudah dijalankan `npm run dev` / `npm run build` --}}
    @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --del-purple: #4C1D95;
            --del-purple-dark: #3B0764;
            --del-purple-soft: #EDE9FE;
        }
        .navbar-del { background-color: var(--del-purple); }
        .navbar-del .nav-link.active { font-weight: 600; border-bottom: 2px solid #fff; }
        .btn-del { background-color: var(--del-purple); color: #fff; border-color: var(--del-purple); }
        .btn-del:hover, .btn-del:focus { background-color: var(--del-purple-dark); color: #fff; border-color: var(--del-purple-dark); }
        .btn-outline-del { color: var(--del-purple); border-color: var(--del-purple); }
        .btn-outline-del:hover { background-color: var(--del-purple); color: #fff; }
        .badge-del { background-color: var(--del-purple-soft); color: var(--del-purple); font-weight: 600; }
        .link-del { color: var(--del-purple); }
        .link-del:hover { color: var(--del-purple-dark); }
        .page-link { color: var(--del-purple); }
        .page-item.active .page-link { background-color: var(--del-purple); border-color: var(--del-purple); }
        .form-control:focus, .form-select:focus {
            border-color: var(--del-purple);
            box-shadow: 0 0 0 .2rem rgba(76, 29, 149, .2);
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-dark navbar-del shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('buku.index') }}">SIPUS-Del</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navUtama"
                    aria-controls="navUtama" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navUtama">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('buku.*') ? 'active' : '' }}" href="{{ route('buku.index') }}">Buku</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('kategori.*') ? 'active' : '' }}" href="{{ route('kategori.index') }}">Kategori</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container pb-5 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="border-top bg-white py-3 small text-muted">
        <div class="container">
            Sistem Informasi Perpustakaan Kampus Del, Institut Teknologi Del
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>