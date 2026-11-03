<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Masuk' }} - ARUS Keuangan</title>

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
<body class="min-h-full flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 bg-ivory text-charcoal antialiased relative">
    <!-- Guest Theme Toggle Button -->
    <div class="absolute top-5 right-5">
        <button type="button" onclick="toggleGuestTheme()" id="guestThemeBtn" class="p-2 rounded-lg border border-line bg-panel text-charcoal hover:bg-cream transition cursor-pointer text-xs flex items-center gap-1.5" aria-label="Ganti tema tampilan">
            <span id="guestThemeIcon">🌙</span>
        </button>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Brand Header matching prototype -->
        <div class="flex items-center justify-center gap-3 mb-8">
            <span class="brandmark">a.</span>
            <div>
                <span class="font-serif text-3xl text-forest tracking-tight block leading-none">arus</span>
                <span class="block text-[9px] font-bold tracking-[2px] text-muted uppercase mt-1">PERSONAL FINANCE</span>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 p-4 text-xs text-emerald-800 dark:text-emerald-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-4 text-xs text-amber-800 dark:text-amber-300" role="alert">
                {{ session('warning') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 p-4 text-xs text-rose-800 dark:text-rose-300" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="panel-card shadow-xs">
            {{ $slot }}
        </div>

        <footer class="mt-8 text-center text-xs text-muted">
            &copy; {{ date('Y') }} ARUS. Ruang Keuangan Tenang dan Terarah.
        </footer>
    </div>

    <script>
        function toggleGuestTheme() {
            var current = localStorage.getItem('arus-theme') || 'system';
            var next = current === 'dark' ? 'light' : 'dark';
            if (window.setArusTheme) {
                window.setArusTheme(next);
            }
            updateGuestIcon();
        }

        function updateGuestIcon() {
            var isDark = document.documentElement.classList.contains('dark');
            var icon = document.getElementById('guestThemeIcon');
            if (icon) {
                icon.textContent = isDark ? '☀️' : '🌙';
            }
        }

        document.addEventListener('DOMContentLoaded', updateGuestIcon);
    </script>
</body>
</html>
