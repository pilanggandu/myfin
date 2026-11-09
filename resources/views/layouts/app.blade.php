<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - ARUS Keuangan</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="arusBackdrop" class="arus-backdrop d-none" onclick="toggleSidebar()"></div>

    <div class="d-flex">
        <!-- Sidebar Navigasi -->
        <aside id="arusSidebar" class="arus-sidebar" aria-label="Navigasi Utama">
            <div class="arus-brand d-flex align-items-center justify-content-between">
                <div>
                    <div>ARUS</div>
                    <span class="arus-brand-sub">Ruang Keuangan Terarah</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none" onclick="toggleSidebar()" aria-label="Tutup menu navigasi">
                    &times;
                </button>
            </div>

            <!-- Konteks Ruang Aktif -->
            <div class="p-3 border-bottom" style="background-color: var(--arus-bg);">
                <div class="small text-muted mb-1">Ruang Aktif Saat Ini:</div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-truncate" style="color: var(--arus-forest); max-width: 170px;" title="{{ $currentSpace?->name }}">
                        {{ $currentSpace?->name ?? 'Belum ada ruang' }}
                    </span>
                    <span class="badge {{ $currentSpace?->isPersonal() ? 'bg-secondary' : 'bg-success' }}">
                        {{ $currentSpace?->isPersonal() ? 'Personal' : 'Bersama' }}
                    </span>
                </div>
            </div>

            <nav class="py-2 flex-grow-1">
                <a href="{{ route('dashboard') }}" class="arus-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="me-2">📊</span> Dashboard
                </a>
                <a href="{{ route('spaces.index') }}" class="arus-nav-item {{ request()->routeIs('spaces.*') ? 'active' : '' }}">
                    <span class="me-2">🏠</span> Ruang Keuangan
                </a>
                <a href="{{ route('profile.edit') }}" class="arus-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <span class="me-2">👤</span> Profil Pengguna
                </a>

                <div class="px-3 pt-3 pb-1 small text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                    Fase Selanjutnya
                </div>
                <span class="arus-nav-item disabled" title="Tersedia di Fase 2">
                    <span class="me-2">💳</span> Rekening & Wallet <small class="ms-auto text-muted">(Fase 2)</small>
                </span>
                <span class="arus-nav-item disabled" title="Tersedia di Fase 2">
                    <span class="me-2">📝</span> Transaksi <small class="ms-auto text-muted">(Fase 2)</small>
                </span>
                <span class="arus-nav-item disabled" title="Tersedia di Fase 3">
                    <span class="me-2">🎯</span> Anggaran <small class="ms-auto text-muted">(Fase 3)</small>
                </span>
                <span class="arus-nav-item disabled" title="Tersedia di Fase 4">
                    <span class="me-2">📅</span> Tagihan Rutin <small class="ms-auto text-muted">(Fase 4)</small>
                </span>
                <span class="arus-nav-item disabled" title="Tersedia di Fase 5">
                    <span class="me-2">🛡️</span> Kredit & Cicilan <small class="ms-auto text-muted">(Fase 5)</small>
                </span>
            </nav>

            <div class="p-3 border-top mt-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-arus-secondary w-100 justify-content-center">
                        Keluar Akun
                    </button>
                </form>
            </div>
        </aside>

        <!-- Area Konten Utama -->
        <div class="flex-grow-1 d-flex flex-column min-vh-100">
            <!-- Topbar Header -->
            <header class="arus-topbar d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary d-lg-none" onclick="toggleSidebar()" aria-label="Buka menu navigasi" style="min-height: 38px; min-width: 38px;">
                        ☰
                    </button>
                    <span class="d-none d-sm-inline text-muted small">Zona waktu: {{ $currentSpace?->timezone ?? 'Asia/Jakarta' }}</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Dropdown Pemilih Ruang -->
                    <div class="dropdown">
                        <button class="btn btn-arus-secondary dropdown-toggle" type="button" id="spaceDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-1">🏠</span>
                            <span class="d-none d-md-inline">{{ $currentSpace?->name }}</span>
                            <span class="d-inline d-md-none">Ruang</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="spaceDropdown" style="min-width: 240px;">
                            <li><h6 class="dropdown-header">Ganti Ruang Aktif</h6></li>
                            @foreach ($userSpaces as $spaceItem)
                                <li>
                                    <form method="POST" action="{{ route('spaces.switch', $spaceItem) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between {{ (int)$currentSpace?->id === (int)$spaceItem->id ? 'active fw-bold' : '' }}">
                                            <span class="text-truncate" style="max-width: 150px;">{{ $spaceItem->name }}</span>
                                            <span class="badge {{ $spaceItem->isPersonal() ? 'bg-secondary' : 'bg-success' }} ms-2">
                                                {{ $spaceItem->isPersonal() ? 'Personal' : 'Bersama' }}
                                            </span>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('spaces.create') }}">
                                    + Buat Ruang Bersama Baru
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('spaces.index') }}">
                                    Kelola Semua Ruang
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Profil User Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-arus-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-1">👤</span>
                            <span>{{ auth()->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Pengaturan Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Content Body -->
            <main class="flex-grow-1 p-3 p-md-4">
                <div class="container-fluid px-0">
                    <!-- Notifikasi Global -->
                    @if (session('success'))
                        <div class="alert arus-alert-success alert-dismissible fade show mb-4" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert arus-alert-warning alert-dismissible fade show mb-4" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert arus-alert-danger alert-dismissible fade show mb-4" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <footer class="p-3 text-center text-muted small border-top" style="background-color: #FFFFFF;">
                &copy; {{ date('Y') }} ARUS. Ruang Pencatatan Keuangan Terarah.
            </footer>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('arusSidebar');
            const backdrop = document.getElementById('arusBackdrop');
            if (sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                backdrop.classList.add('d-none');
            } else {
                sidebar.classList.add('show');
                backdrop.classList.remove('d-none');
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const sidebar = document.getElementById('arusSidebar');
                const backdrop = document.getElementById('arusBackdrop');
                if (sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    backdrop.classList.add('d-none');
                }
            }
        });
    </script>
</body>
</html>
