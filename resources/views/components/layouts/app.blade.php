<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - ARUS Keuangan</title>

    <script>
        (function() {
            try {
                var theme = localStorage.getItem('arus-theme') || 'system';
                var isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col bg-ivory text-charcoal antialiased">
    <!-- Backdrop Mobile Drawer -->
    <div id="sidebarBackdrop" class="fixed inset-0 bg-charcoal/40 backdrop-blur-xs z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <div class="flex flex-1 min-h-screen">
        <!-- Sidebar Navigation -->
        <aside id="appSidebar" class="fixed inset-y-0 left-0 z-50 w-64 max-w-[85vw] bg-sidebar border-r border-line flex flex-col py-6 px-4 overflow-y-auto overscroll-contain transform -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:inset-auto lg:top-0 lg:h-screen lg:flex-shrink-0" aria-label="Navigasi Utama">
            <!-- Brand -->
            <div class="flex items-center gap-2.5 px-1 pb-6 border-b border-line flex-shrink-0">
                <span class="brandmark">a.</span>
                <div>
                    <span class="font-serif text-2xl text-forest tracking-tight block leading-none">arus</span>
                    <span class="block text-[10px] font-bold tracking-[1.5px] text-muted uppercase mt-0.5">PERSONAL FINANCE</span>
                </div>
                <button type="button" class="lg:hidden ml-auto w-11 h-11 inline-flex items-center justify-center rounded-lg text-muted hover:text-charcoal hover:bg-forest-light transition-colors text-base" onclick="toggleSidebar()" aria-label="Tutup navigasi">
                    <span class="text-xl leading-none" aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Workspace Card Selector -->
            <div class="mt-4 relative flex-shrink-0" id="workspaceDropdownContainer">
                <button type="button" onclick="toggleDropdown('workspaceDropdown')" class="w-full workspace-card text-left cursor-pointer focus-visible:outline-none min-h-[44px]">
                    <span class="avatar-circle">
                        {{ strtoupper(substr($currentSpace?->name ?? 'A', 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-charcoal">{{ $currentSpace?->name }}</span>
                        <span class="block text-[11px] text-muted font-normal">
                            {{ $currentSpace?->isPersonal() ? 'Ruang Personal' : 'Ruang Bersama' }}
                        </span>
                    </div>
                    <span class="text-xs text-muted ml-auto font-normal">⌄</span>
                </button>

                <!-- Workspace Switcher Menu -->
                <div id="workspaceDropdown" class="hidden absolute left-0 right-0 mt-2 rounded-xl shadow-lg bg-panel border border-line z-30 py-2">
                    <div class="px-3 py-1 text-[11px] font-bold text-muted uppercase tracking-wider">
                        Ganti Ruang Aktif
                    </div>
                    <div class="max-h-48 overflow-y-auto py-1">
                        @foreach ($userSpaces ?? [] as $spaceItem)
                            <form method="POST" action="{{ route('spaces.switch', $spaceItem) }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-forest-light transition cursor-pointer {{ (int)$currentSpace?->id === (int)$spaceItem->id ? 'bg-forest-light font-bold text-forest' : 'text-charcoal' }}">
                                    <span class="truncate max-w-[130px]">{{ $spaceItem->name }}</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded {{ $spaceItem->isPersonal() ? 'bg-cream text-charcoal' : 'bg-emerald-950/40 text-emerald-400' }}">
                                        {{ $spaceItem->isPersonal() ? 'Personal' : 'Bersama' }}
                                    </span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                    <div class="border-t border-line pt-1 mt-1 px-1">
                        <a href="{{ route('spaces.create') }}" class="block px-2.5 py-1.5 text-xs text-forest hover:bg-forest-light rounded font-bold">
                            + Buat Ruang Bersama
                        </a>
                        <a href="{{ route('spaces.index') }}" class="block px-2.5 py-1.5 text-xs text-muted hover:bg-cream rounded">
                            Kelola Semua Ruang
                        </a>
                    </div>
                </div>
            </div>

            <!-- Navigation Links (Grouped & Collapsible) -->
            @php
                $isKasActive = request()->routeIs(['transactions.*', 'accounts.*']);
                $isPlanningActive = request()->routeIs(['budgets.*', 'credit.*', 'recurring.*', 'reports.*']);
                $isSettingsActive = request()->routeIs(['categories.*', 'members.*', 'currencies.*', 'reminders.*', 'spaces.*', 'profile.*']);
                
                // Server-side default open states
                $isKasOpen = true;
                $isPlanningOpen = true;
                $isSettingsOpen = $isSettingsActive; // Settings collapsed by default unless active
            @endphp

            <div class="space-y-1.5 flex-1 mt-4">
                <!-- Dashboard Utama -->
                <div>
                    <a href="{{ route('dashboard') }}" class="sidebar-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="w-5 text-center text-sm">📊</span>
                        <span class="font-bold">Dashboard</span>
                    </a>
                </div>

                <!-- Grup 1: Kas & Transaksi -->
                <div class="menu-group" data-group="kas">
                    <button 
                        type="button" 
                        id="btn-group-kas"
                        onclick="toggleNavGroup('kas')" 
                        class="sidebar-nav-parent group {{ $isKasActive ? 'text-forest' : '' }}"
                        aria-expanded="{{ $isKasOpen ? 'true' : 'false' }}"
                        aria-controls="nav-group-kas"
                    >
                        <span class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-5 h-5 flex-shrink-0 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            <span class="truncate whitespace-nowrap font-bold">Kas & Transaksi</span>
                        </span>
                        <div class="flex items-center gap-1.5 flex-shrink-0 ml-1">
                            @if ($isKasActive)
                                <span class="w-1.5 h-1.5 rounded-full bg-forest" title="Halaman aktif di grup ini"></span>
                            @endif
                            <svg id="chevron-kas" class="w-4 h-4 text-muted group-hover:text-charcoal transition-transform duration-200 {{ $isKasOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>
                    <nav id="nav-group-kas" class="ml-3.5 pl-2.5 border-l-2 border-line/60 space-y-0.5 my-1 {{ $isKasOpen ? '' : 'hidden' }}">
                        <a href="{{ route('transactions.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">📝</span>
                            <span class="truncate">Transaksi</span>
                        </a>
                        <a href="{{ route('accounts.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">💳</span>
                            <span class="truncate">Rekening & Wallet</span>
                        </a>
                    </nav>
                </div>

                <!-- Grup 2: Perencanaan & Kredit -->
                <div class="menu-group" data-group="planning">
                    <button 
                        type="button" 
                        id="btn-group-planning"
                        onclick="toggleNavGroup('planning')" 
                        class="sidebar-nav-parent group {{ $isPlanningActive ? 'text-forest' : '' }}"
                        aria-expanded="{{ $isPlanningOpen ? 'true' : 'false' }}"
                        aria-controls="nav-group-planning"
                    >
                        <span class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-5 h-5 flex-shrink-0 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m1-3l1-3m-1 3H6m0 0l-1 3m1-3l1-3m10.5-6.75l-4.5 4.5-3-3-3.75 3.75" />
                            </svg>
                            <span class="truncate whitespace-nowrap font-bold">Perencanaan & Kredit</span>
                        </span>
                        <div class="flex items-center gap-1.5 flex-shrink-0 ml-1">
                            @if ($isPlanningActive)
                                <span class="w-1.5 h-1.5 rounded-full bg-forest" title="Halaman aktif di grup ini"></span>
                            @endif
                            <svg id="chevron-planning" class="w-4 h-4 text-muted group-hover:text-charcoal transition-transform duration-200 {{ $isPlanningOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>
                    <nav id="nav-group-planning" class="ml-3.5 pl-2.5 border-l-2 border-line/60 space-y-0.5 my-1 {{ $isPlanningOpen ? '' : 'hidden' }}">
                        <a href="{{ route('budgets.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">🎯</span>
                            <span class="truncate">Anggaran</span>
                        </a>
                        <a href="{{ route('credit.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('credit.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">🛡️</span>
                            <span class="truncate">Kredit & Cicilan</span>
                        </a>
                        <a href="{{ route('recurring.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('recurring.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">🔄</span>
                            <span class="truncate">Jadwal Berulang</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">📈</span>
                            <span class="truncate">Laporan Keuangan</span>
                        </a>
                    </nav>
                </div>

                <!-- Grup 3: Master Data & Pengaturan -->
                <div class="menu-group" data-group="settings">
                    <button 
                        type="button" 
                        id="btn-group-settings"
                        onclick="toggleNavGroup('settings')" 
                        class="sidebar-nav-parent group {{ $isSettingsActive ? 'text-forest' : '' }}"
                        aria-expanded="{{ $isSettingsOpen ? 'true' : 'false' }}"
                        aria-controls="nav-group-settings"
                    >
                        <span class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-5 h-5 flex-shrink-0 text-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <span class="truncate whitespace-nowrap font-bold">Pengaturan & Ruang</span>
                        </span>
                        <div class="flex items-center gap-1.5 flex-shrink-0 ml-1">
                            @if ($isSettingsActive)
                                <span class="w-1.5 h-1.5 rounded-full bg-forest" title="Halaman aktif di grup ini"></span>
                            @endif
                            <svg id="chevron-settings" class="w-4 h-4 text-muted group-hover:text-charcoal transition-transform duration-200 {{ $isSettingsOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>
                    <nav id="nav-group-settings" class="ml-3.5 pl-2.5 border-l-2 border-line/60 space-y-0.5 my-1 {{ $isSettingsOpen ? '' : 'hidden' }}">
                        <a href="{{ route('categories.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">🏷️</span>
                            <span class="truncate">Kategori</span>
                        </a>
                        <a href="{{ route('members.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('members.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">👥</span>
                            <span class="truncate">Member</span>
                        </a>
                        <a href="{{ route('reminders.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('reminders.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">🔔</span>
                            <span class="truncate">Pengingat</span>
                        </a>
                        <a href="{{ route('currencies.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('currencies.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">💱</span>
                            <span class="truncate">Mata Uang</span>
                        </a>
                        <a href="{{ route('spaces.index') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('spaces.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">🏠</span>
                            <span class="truncate">Ruang Keuangan</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="sidebar-nav-child sidebar-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <span class="w-5 text-center text-sm">👤</span>
                            <span class="truncate">Profil Pengguna</span>
                        </a>
                    </nav>
                </div>

                <!-- Grup 4: Fase Selanjutnya -->
                <div class="pt-2 border-t border-line/40">
                    <div class="px-2.5 py-1 text-[11px] font-bold tracking-wider text-muted/70 uppercase">Fase Selanjutnya</div>
                    <nav class="space-y-0.5 mt-0.5">
                        <span class="sidebar-nav-item disabled" title="Tersedia di Fase 6">
                            <span class="w-5 text-center text-sm opacity-50">🎯</span>
                            <span class="truncate">Target & Bersama</span>
                            <span class="ml-auto text-[10px] font-bold text-muted bg-cream px-2 py-0.5 rounded">F6</span>
                        </span>
                    </nav>
                </div>
            </div>

            <!-- Bottom User Profile Card -->
            <div class="mt-auto pt-6 border-t border-line flex-shrink-0 pb-8 lg:pb-0">
                <div class="flex items-center gap-2.5 p-1 mb-2">
                    <span class="avatar-circle">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-charcoal">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-muted">{{ auth()->user()->email }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-center min-h-[44px] py-2.5 text-xs font-bold text-red hover:bg-red/10 rounded-lg transition cursor-pointer inline-flex items-center justify-center">
                        Keluar Akun
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar Header -->
            <header class="app-topbar px-4 sm:px-10 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center gap-2.5 min-w-0">
                    <button type="button" class="lg:hidden w-11 h-11 inline-flex items-center justify-center rounded-lg text-charcoal hover:bg-forest-light focus:outline-none transition-colors" onclick="toggleSidebar()" aria-label="Buka menu navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="text-xs text-muted truncate">
                        <span class="hidden sm:inline">{{ $currentSpace?->name }}</span>
                        <span class="mx-2 text-line hidden sm:inline">/</span>
                        <strong class="text-charcoal font-semibold">{{ $title ?? 'Dashboard' }}</strong>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    <!-- Tombol Pintas Catat Cepat Global -->
                    <button 
                        type="button" 
                        onclick="openQuickTransactionModal()" 
                        class="min-h-[44px] inline-flex items-center gap-1.5 px-3 py-2 border border-forest/30 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs" 
                        title="Catat Cepat Transaksi (Tekan 'C' untuk membuka kapan saja)"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span class="hidden sm:inline">Catat Cepat</span>
                        <kbd class="hidden md:inline-block text-[10px] font-mono px-1 py-0.5 rounded bg-white/20 text-white/90">C</kbd>
                    </button>

                    <!-- Tombol Sembunyikan / Tampilkan Saldo (Privacy Mode) -->
                    <button 
                        type="button" 
                        onclick="toggleBalanceVisibility()" 
                        class="privacy-toggle-btn min-h-[44px] inline-flex items-center gap-1.5 px-3 py-2 border border-line rounded-lg text-xs font-semibold text-charcoal bg-panel hover:bg-cream transition cursor-pointer" 
                        title="Sembunyikan / Tampilkan Saldo (Tekan 'H')"
                        aria-label="Sembunyikan atau Tampilkan Saldo"
                    >
                        <span class="privacy-toggle-icon text-muted flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </span>
                        <span class="privacy-toggle-text hidden xl:inline text-xs">
                            Saldo
                        </span>
                        <kbd class="hidden xl:inline-block text-[10px] font-mono px-1 py-0.5 rounded bg-cream/80 text-muted border border-line/60">H</kbd>
                    </button>

                    <!-- Dropdown Tema (Dark Mode Switcher) -->
                    <div class="relative inline-block text-left" id="themeDropdownContainer">
                        <button type="button" onclick="toggleDropdown('themeMenu')" class="min-h-[44px] inline-flex items-center gap-1.5 px-3 py-2 border border-line rounded-lg text-xs font-medium text-charcoal bg-panel hover:bg-cream transition cursor-pointer" aria-label="Pilih tema tampilan" aria-haspopup="true">
                            <span id="currentThemeIcon" class="text-sm">💻</span>
                            <span id="currentThemeLabel" class="hidden sm:inline font-semibold">Sistem</span>
                            <span class="text-[10px] text-muted font-normal">⌄</span>
                        </button>

                        <div id="themeMenu" class="hidden absolute right-0 mt-2 w-44 rounded-xl shadow-lg bg-panel border border-line z-30 py-1.5 focus:outline-none">
                            <div class="px-3 py-1 text-[11px] font-bold text-muted uppercase tracking-wider border-b border-line">
                                Tema Tampilan
                            </div>
                            <button type="button" onclick="setArusTheme('light')" data-theme-value="light" class="w-full text-left px-3 py-2 text-xs flex items-center justify-between text-charcoal hover:bg-forest-light transition cursor-pointer">
                                <span class="flex items-center gap-2">☀️ <span>Terang</span></span>
                            </button>
                            <button type="button" onclick="setArusTheme('dark')" data-theme-value="dark" class="w-full text-left px-3 py-2 text-xs flex items-center justify-between text-charcoal hover:bg-forest-light transition cursor-pointer">
                                <span class="flex items-center gap-2">🌙 <span>Gelap</span></span>
                            </button>
                            <button type="button" onclick="setArusTheme('system')" data-theme-value="system" class="w-full text-left px-3 py-2 text-xs flex items-center justify-between text-charcoal hover:bg-forest-light transition cursor-pointer">
                                <span class="flex items-center gap-2">💻 <span>Ikuti Sistem</span></span>
                            </button>
                        </div>
                    </div>

                    <span class="hidden md:inline-flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-forest bg-cream px-2.5 py-1 rounded-full uppercase">
                        <span class="w-1.5 h-1.5 rounded-full bg-forest inline-block"></span>
                        {{ $currentSpace?->isPersonal() ? 'RUANG PERSONAL' : 'RUANG BERSAMA' }}
                    </span>

                    <a href="{{ route('profile.edit') }}" class="avatar-circle w-10 h-10 sm:w-9 sm:h-9 text-xs hover:ring-2 hover:ring-sage transition focus-visible:outline-none inline-flex items-center justify-center" title="Pengaturan Profil">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </a>
                </div>
            </header>

            <!-- Main Page Content -->
            <main class="flex-1 p-6 sm:p-10 max-w-6xl w-full mx-auto space-y-6">
                <!-- Notifications -->
                @if (session('success'))
                    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 p-4 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between" role="alert">
                        <div>{{ session('success') }}</div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-400 font-bold ml-4 text-sm">&times;</button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-4 text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between" role="alert">
                        <div>{{ session('warning') }}</div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-amber-700 dark:text-amber-400 font-bold ml-4 text-sm">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 p-4 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between" role="alert">
                        <div>{{ session('error') }}</div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 dark:text-rose-400 font-bold ml-4 text-sm">&times;</button>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="px-6 py-4 text-center text-xs text-muted border-t border-line bg-sidebar/50">
                &copy; {{ date('Y') }} ARUS. Ruang Pencatatan Keuangan Terarah.
            </footer>
        </div>
    </div>

    <!-- Client Scripts for Dropdowns and Mobile Drawer -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
            }
        }

        function toggleDropdown(menuId) {
            const menu = document.getElementById(menuId);
            const isHidden = menu.classList.contains('hidden');
            
            document.querySelectorAll('#workspaceDropdown, #themeMenu').forEach(el => {
                if (el.id !== menuId) el.classList.add('hidden');
            });

            if (isHidden) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        }

        function toggleNavGroup(groupId) {
            const content = document.getElementById('nav-group-' + groupId);
            const chevron = document.getElementById('chevron-' + groupId);
            const btn = document.getElementById('btn-group-' + groupId) || document.querySelector(`button[onclick="toggleNavGroup('${groupId}')"]`);
            if (!content) return;

            const isHidden = content.classList.contains('hidden');
            if (isHidden) {
                content.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'true');
                try { localStorage.setItem('arus_nav_group_' + groupId, 'open'); } catch(e) {}
            } else {
                content.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                try { localStorage.setItem('arus_nav_group_' + groupId, 'closed'); } catch(e) {}
            }
        }

        window.addEventListener('click', function(e) {
            if (!document.getElementById('workspaceDropdownContainer')?.contains(e.target)) {
                document.getElementById('workspaceDropdown')?.classList.add('hidden');
            }
            if (!document.getElementById('themeDropdownContainer')?.contains(e.target)) {
                document.getElementById('themeMenu')?.classList.add('hidden');
            }
        });

        function isBalanceHidden() {
            return localStorage.getItem('arus_hide_balances') === 'true';
        }

        function toggleBalanceVisibility() {
            setBalanceVisibility(!isBalanceHidden());
        }

        function setBalanceVisibility(hide) {
            localStorage.setItem('arus_hide_balances', hide ? 'true' : 'false');
            applyPrivacyMask(hide);
        }

        function applyPrivacyMask(hide) {
            document.querySelectorAll('.privacy-balance').forEach(el => {
                if (!el.hasAttribute('data-raw-value')) {
                    el.setAttribute('data-raw-value', el.textContent.trim());
                }
                const raw = el.getAttribute('data-raw-value');
                if (hide) {
                    if (raw.startsWith('+Rp ') || raw.startsWith('+ Rp ') || raw.startsWith('+Rp')) {
                        el.textContent = '+Rp ••••••••';
                    } else if (raw.startsWith('-Rp ') || raw.startsWith('- Rp ') || raw.startsWith('-Rp')) {
                        el.textContent = '-Rp ••••••••';
                    } else if (raw.startsWith('⇄ Rp ') || raw.startsWith('⇄Rp ')) {
                        el.textContent = '⇄ Rp ••••••••';
                    } else if (raw.startsWith('Rp ') || raw.startsWith('Rp. ') || raw.startsWith('Rp')) {
                        el.textContent = 'Rp ••••••••';
                    } else if (raw.includes('Rp ') || raw.includes('Rp.')) {
                        el.textContent = raw.replace(/Rp\.?\s?[0-9.,]+/g, 'Rp ••••••••');
                    } else {
                        el.textContent = '••••••••';
                    }
                    el.classList.add('privacy-masked');
                } else {
                    el.textContent = raw;
                    el.classList.remove('privacy-masked');
                }
            });

            const eyeOpen = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
            const eyeSlash = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';

            document.querySelectorAll('.privacy-toggle-btn').forEach(btn => {
                const icon = btn.querySelector('.privacy-toggle-icon');
                const text = btn.querySelector('.privacy-toggle-text');
                if (icon) {
                    icon.innerHTML = hide ? eyeSlash : eyeOpen;
                }
                if (text && !text.classList.contains('hidden-label-keep')) {
                    text.textContent = hide ? 'Tampilkan' : 'Sembunyikan';
                }
                btn.setAttribute('title', hide ? 'Tampilkan Saldo (Tekan H)' : 'Sembunyikan Saldo (Tekan H)');
                btn.setAttribute('aria-label', hide ? 'Tampilkan Saldo' : 'Sembunyikan Saldo');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            applyPrivacyMask(isBalanceHidden());

            // Pulihkan status buka/tutup grup menu navigasi dari localStorage
            ['kas', 'planning', 'settings'].forEach(function(groupId) {
                const content = document.getElementById('nav-group-' + groupId);
                const chevron = document.getElementById('chevron-' + groupId);
                const btn = document.getElementById('btn-group-' + groupId) || document.querySelector(`button[onclick="toggleNavGroup('${groupId}')"]`);
                if (!content) return;

                // Jika ada menu aktif di dalam grup ini, selalu biarkan terbuka
                if (content.querySelector('.sidebar-nav-item.active')) {
                    content.classList.remove('hidden');
                    if (chevron) chevron.classList.add('rotate-180');
                    if (btn) btn.setAttribute('aria-expanded', 'true');
                    return;
                }

                try {
                    const saved = localStorage.getItem('arus_nav_group_' + groupId);
                    if (saved === 'open') {
                        content.classList.remove('hidden');
                        if (chevron) chevron.classList.add('rotate-180');
                        if (btn) btn.setAttribute('aria-expanded', 'true');
                    } else if (saved === 'closed') {
                        content.classList.add('hidden');
                        if (chevron) chevron.classList.remove('rotate-180');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                } catch(e) {}
            });
        });

        document.addEventListener('focusin', function(e) {
            const target = e.target;
            if (target && target.matches && (
                target.matches('#openingBalanceDisplay, #amountDisplay, #quickModalAmountDisplay, #budget_limit_amount, #edit_budget_limit_amount, #recurringAmount, #editAmount, #modalAmount') ||
                target.matches('input[inputmode="numeric"], input[name="opening_balance"], input[name="amount"], input[name="limit_amount"]')
            )) {
                setTimeout(() => {
                    if (document.activeElement === target && typeof target.select === 'function') {
                        target.select();
                    }
                }, 10);
            }
        });

        document.addEventListener('click', function(e) {
            const target = e.target;
            if (target && target.matches && (
                target.matches('#openingBalanceDisplay, #amountDisplay, #quickModalAmountDisplay, #budget_limit_amount, #edit_budget_limit_amount, #recurringAmount, #editAmount, #modalAmount') ||
                target.matches('input[inputmode="numeric"], input[name="opening_balance"], input[name="amount"], input[name="limit_amount"]')
            )) {
                if (typeof target.select === 'function') {
                    target.select();
                }
            }
        });

        document.addEventListener('keydown', function(e) {
            if ((e.key === 'h' || e.key === 'H') && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
                e.preventDefault();
                toggleBalanceVisibility();
            }
            if (e.key === 'Escape') {
                document.getElementById('workspaceDropdown')?.classList.add('hidden');
                document.getElementById('themeMenu')?.classList.add('hidden');
                const sidebar = document.getElementById('appSidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                if (!sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
                }
            }
        });
    </script>

    <!-- Global Quick Transaction Modal -->
    <x-quick-transaction-modal />

    @stack('scripts')
</body>
</html>
