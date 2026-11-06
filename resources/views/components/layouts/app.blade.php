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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex flex-col bg-ivory text-charcoal antialiased">
    <div id="sidebarBackdrop" class="fixed inset-0 bg-charcoal/40 backdrop-blur-xs z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>

    <div class="flex flex-1 min-h-screen">
        <aside id="appSidebar" class="fixed inset-y-0 left-0 z-50 w-64 max-w-[85vw] bg-sidebar border-r border-line flex flex-col py-4 px-3.5 overflow-y-auto overscroll-contain transform -translate-x-full transition-transform duration-200 ease-in-out lg:translate-x-0 lg:sticky lg:inset-auto lg:top-0 lg:h-screen lg:flex-shrink-0" aria-label="Navigasi Utama">
            <div class="flex items-center gap-2.5 px-1 pb-3.5 border-b border-line flex-shrink-0">
                <span class="brandmark">a.</span>
                <div class="min-w-0 flex-1">
                    <span class="text-base font-bold text-forest tracking-tight block leading-tight">ARUS</span>
                    <span class="block text-[9.5px] font-bold tracking-[1.2px] text-muted uppercase">MANAJEMEN KEUANGAN</span>
                </div>
                <button type="button" class="lg:hidden ml-auto w-8 h-8 inline-flex items-center justify-center rounded-lg text-muted hover:text-charcoal hover:bg-forest-light transition-colors text-base" onclick="toggleSidebar()" aria-label="Tutup navigasi">
                    <span class="text-lg leading-none" aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="mt-3 px-2.5 py-2 rounded-lg bg-panel border border-line flex-shrink-0">
                <div id="sidebarLiveDate" class="text-[11px] font-medium text-muted leading-tight">
                    {{ now()->translatedFormat('l, d M Y') }}
                </div>
                <div id="sidebarLiveTime" class="text-xl font-bold tracking-tight text-charcoal font-mono leading-none mt-1">
                    {{ now()->format('H:i:s') }}
                </div>
            </div>

            <div class="nav-section-label">RUANG AKTIF</div>
            <div class="relative flex-shrink-0" id="workspaceDropdownContainer">
                <button type="button" onclick="toggleDropdown('workspaceDropdown')" class="w-full workspace-card text-left cursor-pointer focus-visible:outline-none min-h-[38px]">
                    <span class="avatar-circle w-6 h-6 text-[10px]">
                        {{ strtoupper(substr($currentSpace?->name ?? 'A', 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-charcoal leading-tight">{{ $currentSpace?->name }}</span>
                        <span class="block text-[10.5px] text-muted font-normal leading-tight">
                            {{ $currentSpace?->isPersonal() ? 'Ruang Personal' : 'Ruang Bersama' }}
                        </span>
                    </div>
                    <span class="text-[10px] text-muted ml-auto font-normal">▼</span>
                </button>

                <div id="workspaceDropdown" class="hidden absolute left-0 right-0 mt-1.5 rounded-lg shadow-lg bg-panel border border-line z-30 py-1.5">
                    <div class="px-3 py-1 text-[10px] font-bold text-muted uppercase tracking-wider">
                        Ganti Ruang Aktif
                    </div>
                    <div class="max-h-44 overflow-y-auto py-1">
                        @foreach ($userSpaces ?? [] as $spaceItem)
                            <form method="POST" action="{{ route('spaces.switch', $spaceItem) }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-3 py-1.5 text-xs flex items-center justify-between hover:bg-forest-light transition cursor-pointer {{ (int)$currentSpace?->id === (int)$spaceItem->id ? 'bg-forest-light font-bold text-forest' : 'text-charcoal' }}">
                                    <span class="truncate max-w-[130px]">{{ $spaceItem->name }}</span>
                                    <span class="text-[9.5px] px-1.5 py-0.5 rounded {{ $spaceItem->isPersonal() ? 'bg-cream text-charcoal' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' }}">
                                        {{ $spaceItem->isPersonal() ? 'Personal' : 'Bersama' }}
                                    </span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                    <div class="border-t border-line pt-1 mt-1 px-1">
                        <a href="{{ route('spaces.create') }}" class="block px-2.5 py-1 text-xs text-forest hover:bg-forest-light rounded font-bold">
                            + Buat Ruang Bersama
                        </a>
                        <a href="{{ route('spaces.index') }}" class="block px-2.5 py-1 text-xs text-muted hover:bg-cream rounded">
                            Kelola Semua Ruang
                        </a>
                    </div>
                </div>
            </div>

            <div class="nav-section-label">JENIS RUANG</div>
            <div class="px-2.5 py-1.5 rounded-lg bg-panel border border-line flex items-center justify-between text-xs flex-shrink-0">
                <span class="font-medium text-charcoal flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ $currentSpace?->isPersonal() ? 'bg-blue-500' : 'bg-emerald-500' }}"></span>
                    {{ $currentSpace?->isPersonal() ? 'Personal' : 'Bersama' }}
                </span>
                <a href="{{ route('spaces.index') }}" class="text-[11px] text-forest font-semibold hover:underline">
                    Kelola
                </a>
            </div>

            <div class="nav-section-label">MENU</div>
            <nav class="space-y-0.5 flex-1">
                <a href="{{ route('dashboard') }}" class="sidebar-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('transactions.index') }}" class="sidebar-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 17.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <span>Transaksi</span>
                </a>
                <a href="{{ route('accounts.index') }}" class="sidebar-nav-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-6.75 3h18a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                    </svg>
                    <span>Rekening & Wallet</span>
                </a>
                <a href="{{ route('categories.index') }}" class="sidebar-nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.386l5.228-3.076c.827-.486 1.164-1.503.744-2.316L17.72 6.84a2.25 2.25 0 0 0-1.591-.659H11.5" />
                    </svg>
                    <span>Kategori</span>
                </a>
                <a href="{{ route('budgets.index') }}" class="sidebar-nav-item {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>Anggaran</span>
                </a>
                <a href="{{ route('reports.index') }}" class="sidebar-nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    <span>Laporan Keuangan</span>
                </a>
                <a href="{{ route('recurring.index') }}" class="sidebar-nav-item {{ request()->routeIs('recurring.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Jadwal Berulang</span>
                </a>
                <a href="{{ route('credit.index') }}" class="sidebar-nav-item {{ request()->routeIs('credit.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                    <span>Kredit & Cicilan</span>
                </a>
                <a href="{{ route('goals.index') }}" class="sidebar-nav-item {{ request()->routeIs('goals.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.497m5.003 0a3 3 0 0 0 2.997-3.188l-.34-4.425a3 3 0 0 0-2.993-2.762H8.836a3 3 0 0 0-2.993 2.762l-.34 4.425A3 3 0 0 0 8.497 14.25" />
                    </svg>
                    <span>Target & Bersama</span>
                </a>
                <a href="{{ route('reminders.index') }}" class="sidebar-nav-item {{ request()->routeIs('reminders.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span>Pengingat</span>
                </a>
                @if ($currentSpace && !$currentSpace->isPersonal())
                    <a href="{{ route('members.index') }}" class="sidebar-nav-item {{ request()->routeIs('members.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                        <span>Member</span>
                    </a>
                @endif
                <a href="{{ route('currencies.index') }}" class="sidebar-nav-item {{ request()->routeIs('currencies.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>Mata Uang</span>
                </a>
                <a href="{{ route('spaces.index') }}" class="sidebar-nav-item {{ request()->routeIs('spaces.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Ruang Keuangan</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="sidebar-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <span>Profil Pengguna</span>
                </a>
            </nav>

            <div class="mt-auto pt-3 border-t border-line flex-shrink-0">
                <div class="flex items-center justify-between p-2 rounded-lg bg-panel border border-line mb-2.5">
                    <div class="flex items-center gap-2">
                        <span id="currentThemeIcon" class="text-sm">💻</span>
                        <span class="text-xs font-semibold text-charcoal" id="currentThemeLabel">Sistem</span>
                    </div>
                    <div class="inline-flex rounded-md p-0.5 bg-cream/70 border border-line text-[11px]">
                        <button type="button" onclick="setArusTheme('light')" class="px-2 py-0.5 rounded cursor-pointer hover:bg-white text-charcoal" data-theme-value="light" title="Mode Terang">☀️</button>
                        <button type="button" onclick="setArusTheme('dark')" class="px-2 py-0.5 rounded cursor-pointer hover:bg-white text-charcoal" data-theme-value="dark" title="Mode Gelap">🌙</button>
                        <button type="button" onclick="setArusTheme('system')" class="px-2 py-0.5 rounded cursor-pointer hover:bg-white text-charcoal" data-theme-value="system" title="Ikuti Sistem">💻</button>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 px-1 mb-2">
                    <span class="avatar-circle">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold text-charcoal leading-tight">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-[11px] text-muted leading-tight">{{ auth()->user()->email }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-center py-2 text-xs font-bold text-red hover:bg-red/10 rounded-lg transition cursor-pointer inline-flex items-center justify-center">
                        Keluar Akun
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="app-topbar px-4 sm:px-8 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <button type="button" class="lg:hidden w-9 h-9 inline-flex items-center justify-center rounded-lg text-charcoal hover:bg-forest-light focus:outline-none transition-colors flex-shrink-0" onclick="toggleSidebar()" aria-label="Buka menu navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div class="relative max-w-xs sm:max-w-md w-full hidden md:block">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </span>
                        <input 
                            type="search" 
                            id="globalSearchInput"
                            placeholder="Cari transaksi, rekening, atau catatan... ⌘K" 
                            class="field-input pl-9 pr-12 text-xs h-8.5 rounded-lg w-full bg-panel"
                            style="padding-left: 34px !important; padding-right: 48px !important;"
                            onkeydown="if(event.key==='Enter'){ window.location.href='{{ route('transactions.index') }}?search=' + encodeURIComponent(this.value); }"
                        />
                        <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                            <kbd class="text-[9.5px] font-mono px-1 py-0.5 rounded bg-cream text-muted border border-line">⌘K</kbd>
                        </div>
                    </div>

                    <div class="text-xs text-muted truncate md:hidden">
                        <strong class="text-charcoal font-semibold">{{ $title ?? 'Dashboard' }}</strong>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-2.5 flex-shrink-0 ml-3">
                    <button 
                        type="button" 
                        onclick="window.location.reload()" 
                        class="w-8.5 h-8.5 rounded-lg border border-line bg-panel text-muted hover:text-charcoal hover:bg-cream transition inline-flex items-center justify-center cursor-pointer"
                        title="Segarkan Halaman"
                        aria-label="Segarkan halaman"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </button>

                    <button 
                        type="button" 
                        id="masterPrivacyToggle"
                        onclick="toggleMasterPrivacy()" 
                        class="master-privacy-toggle h-8.5 inline-flex items-center gap-1.5 px-2.5 border border-line rounded-lg text-xs font-semibold text-charcoal bg-panel hover:bg-cream transition cursor-pointer" 
                        title="Sembunyikan / Tampilkan Semua Saldo (Master, Tekan 'H')"
                        aria-label="Sembunyikan atau Tampilkan Semua Saldo"
                    >
                        <span class="privacy-toggle-icon text-muted flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </span>
                        <span class="privacy-toggle-text hidden xl:inline text-xs">Semua Saldo</span>
                        <kbd class="hidden xl:inline-block text-[9.5px] font-mono px-1 py-0.2 rounded bg-cream text-muted border border-line/60">H</kbd>
                    </button>

                    <button 
                        type="button" 
                        onclick="openQuickTransactionModal()" 
                        class="h-8.5 inline-flex items-center gap-1.5 px-3 bg-forest text-white rounded-lg text-xs font-bold hover:bg-forest-hover transition cursor-pointer shadow-xs" 
                        title="Catat Cepat Transaksi (Tekan 'C' untuk membuka kapan saja)"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span class="hidden sm:inline">Catat Cepat</span>
                        <kbd class="hidden md:inline-block text-[9.5px] font-mono px-1 py-0.2 rounded bg-white/20 text-white/90">C</kbd>
                    </button>

                    <a 
                        href="{{ route('transactions.create') }}" 
                        class="h-8.5 inline-flex items-center gap-1 px-3 border border-line bg-panel text-charcoal hover:bg-cream rounded-lg text-xs font-semibold transition hidden sm:inline-flex"
                        title="Form Transaksi Lengkap"
                    >
                        + Transaksi
                    </a>

                    <a href="{{ route('profile.edit') }}" class="avatar-circle w-8.5 h-8.5 text-xs hover:ring-2 hover:ring-forest transition focus-visible:outline-none inline-flex items-center justify-center ml-1" title="Pengaturan Profil">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </a>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-5">
                @if (session('success'))
                    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 p-3.5 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between" role="alert">
                        <div>{{ session('success') }}</div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-400 font-bold ml-4 text-sm">&times;</button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-3.5 text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between" role="alert">
                        <div>{{ session('warning') }}</div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-amber-700 dark:text-amber-400 font-bold ml-4 text-sm">&times;</button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 p-3.5 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between" role="alert">
                        <div>{{ session('error') }}</div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 dark:text-rose-400 font-bold ml-4 text-sm">&times;</button>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="px-6 py-3.5 text-center text-xs text-muted border-t border-line bg-sidebar/50">
                &copy; {{ date('Y') }} ARUS. Ruang Pencatatan Keuangan Terarah.
            </footer>
        </div>
    </div>

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

        window.addEventListener('click', function(e) {
            if (!document.getElementById('workspaceDropdownContainer')?.contains(e.target)) {
                document.getElementById('workspaceDropdown')?.classList.add('hidden');
            }
        });

        const PRIVACY_ICON_OPEN = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>';
        const PRIVACY_ICON_SLASH = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';

        function isMasterHidden() {
            return localStorage.getItem('arus_hide_balances') === 'true';
        }

        function maskElement(el) {
            if (!el.hasAttribute('data-raw-value')) {
                el.setAttribute('data-raw-value', el.textContent.trim());
            }
            const raw = el.getAttribute('data-raw-value');
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
        }

        function unmaskElement(el) {
            if (el.hasAttribute('data-raw-value')) {
                el.textContent = el.getAttribute('data-raw-value');
            }
            el.classList.remove('privacy-masked');
        }

        function updateToggleBtnUI(btn, isHidden, isMaster = false) {
            if (!btn) return;
            const icon = btn.querySelector('.privacy-toggle-icon');
            const text = btn.querySelector('.privacy-toggle-text');
            if (icon) {
                icon.innerHTML = isHidden ? PRIVACY_ICON_SLASH : PRIVACY_ICON_OPEN;
            }
            if (text && !text.classList.contains('hidden-label-keep')) {
                text.textContent = isHidden ? 'Tampilkan' : 'Sembunyikan';
            }
            if (isMaster) {
                btn.setAttribute('title', isHidden ? 'Tampilkan Semua Saldo (Master, Tekan H)' : 'Sembunyikan Semua Saldo (Master, Tekan H)');
                btn.setAttribute('aria-label', isHidden ? 'Tampilkan Semua Saldo' : 'Sembunyikan Semua Saldo');
            } else {
                btn.setAttribute('title', isHidden ? 'Tampilkan Saldo Bagian Ini' : 'Sembunyikan Saldo Bagian Ini');
                btn.setAttribute('aria-label', isHidden ? 'Tampilkan Saldo Bagian Ini' : 'Sembunyikan Saldo Bagian Ini');
            }
        }

        function toggleMasterPrivacy() {
            setMasterPrivacy(!isMasterHidden());
        }
        window.toggleMasterPrivacy = toggleMasterPrivacy;
        window.toggleBalanceVisibility = toggleMasterPrivacy;

        function setMasterPrivacy(hide) {
            localStorage.setItem('arus_hide_balances', hide ? 'true' : 'false');

            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('arus_scope_privacy_')) {
                    localStorage.removeItem(key);
                }
            });

            document.querySelectorAll('.privacy-balance').forEach(el => {
                if (hide) {
                    maskElement(el);
                } else {
                    unmaskElement(el);
                }
            });

            document.querySelectorAll('.master-privacy-toggle, #masterPrivacyToggle').forEach(btn => {
                updateToggleBtnUI(btn, hide, true);
            });

            document.querySelectorAll('.privacy-card-toggle, [data-privacy-card-toggle]').forEach(btn => {
                updateToggleBtnUI(btn, hide, false);
            });
        }

        function toggleCardPrivacy(btn, explicitScope) {
            let container = null;
            if (explicitScope) {
                container = typeof explicitScope === 'string'
                    ? (document.getElementById(explicitScope) || document.querySelector(`[data-privacy-scope="${explicitScope}"]`) || document.querySelector(explicitScope))
                    : explicitScope;
            }
            if (!container && btn) {
                container = btn.closest('[data-privacy-scope], .privacy-scope, .panel-card, .metric-card, .stat-folder-card') || btn.parentElement;
            }
            if (!container) return;

            const balances = container.querySelectorAll('.privacy-balance');
            if (balances.length === 0) return;

            const isCurrentlyMasked = Array.from(balances).some(el => el.classList.contains('privacy-masked'));
            const willHide = !isCurrentlyMasked;

            balances.forEach(el => {
                if (willHide) {
                    maskElement(el);
                } else {
                    unmaskElement(el);
                }
            });

            const cardBtns = container.querySelectorAll('.privacy-card-toggle, [data-privacy-card-toggle]');
            cardBtns.forEach(b => updateToggleBtnUI(b, willHide, false));
            if (btn && !container.contains(btn)) {
                updateToggleBtnUI(btn, willHide, false);
            }

            const scopeId = container.getAttribute('data-privacy-scope') || container.id;
            if (scopeId) {
                localStorage.setItem('arus_scope_privacy_' + scopeId, willHide ? 'hidden' : 'shown');
            }
        }
        window.toggleCardPrivacy = toggleCardPrivacy;

        function initPrivacySystem() {
            const masterHidden = isMasterHidden();

            document.querySelectorAll('.privacy-balance').forEach(el => {
                if (masterHidden) {
                    maskElement(el);
                } else {
                    unmaskElement(el);
                }
            });

            document.querySelectorAll('[data-privacy-scope], .privacy-scope').forEach(container => {
                const scopeId = container.getAttribute('data-privacy-scope') || container.id;
                if (scopeId) {
                    const saved = localStorage.getItem('arus_scope_privacy_' + scopeId);
                    if (saved === 'hidden') {
                        container.querySelectorAll('.privacy-balance').forEach(maskElement);
                    } else if (saved === 'shown') {
                        container.querySelectorAll('.privacy-balance').forEach(unmaskElement);
                    }
                }
            });

            document.querySelectorAll('.master-privacy-toggle, #masterPrivacyToggle').forEach(btn => {
                updateToggleBtnUI(btn, masterHidden, true);
            });

            document.querySelectorAll('.privacy-card-toggle, [data-privacy-card-toggle], [onclick*="toggleCardPrivacy"]').forEach(btn => {
                const container = btn.closest('[data-privacy-scope], .privacy-scope, .panel-card, .metric-card, .stat-folder-card') || btn.parentElement;
                if (container) {
                    const hasMasked = container.querySelector('.privacy-masked') !== null;
                    updateToggleBtnUI(btn, hasMasked, false);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initPrivacySystem();
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
            if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                const searchEl = document.getElementById('globalSearchInput');
                if (searchEl) {
                    searchEl.focus();
                    searchEl.select();
                }
            }
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

    <x-quick-transaction-modal />

    @stack('scripts')
</body>
</html>
