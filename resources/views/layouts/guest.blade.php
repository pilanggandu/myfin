<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Masuk' }} - ARUS Keuangan</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center justify-content-center py-5">
    <main class="container" style="max-width: 440px;">
        <div class="text-center mb-4">
            <h1 class="h2 mb-1" style="font-family: Georgia, serif; color: var(--arus-forest); font-weight: 700; letter-spacing: 0.05em;">ARUS</h1>
            <p class="text-muted small">Ruang Pencatatan Keuangan Tenang dan Terarah</p>
        </div>

        @if (session('success'))
            <div class="alert arus-alert-success mb-3" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert arus-alert-warning mb-3" role="alert">
                {{ session('warning') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert arus-alert-danger mb-3" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="arus-card">
            <div class="arus-card-body p-4">
                {{ $slot }}
            </div>
        </div>

        <footer class="text-center mt-4">
            <p class="small text-muted mb-0">&copy; {{ date('Y') }} ARUS. Kendali Keuangan di Tangan Anda.</p>
        </footer>
    </main>
</body>
</html>
