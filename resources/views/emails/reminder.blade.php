<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectTitle ?? 'Pengingat Keuangan ARUS' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f7f6f2;
            color: #1e2420;
            margin: 0;
            padding: 24px 16px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e3e0d8;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .header {
            background-color: #1a3329;
            color: #ffffff;
            padding: 24px;
            text-align: left;
        }
        .brand {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #d1fae5;
            text-transform: uppercase;
        }
        .header-title {
            font-size: 20px;
            font-weight: 600;
            margin-top: 6px;
            margin-bottom: 0;
            color: #ffffff;
        }
        .content {
            padding: 28px 24px;
            line-height: 1.6;
            font-size: 14px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .message-box {
            background-color: #f4f3ee;
            border-left: 4px solid #1a3329;
            padding: 14px 16px;
            border-radius: 8px;
            margin: 18px 0;
            font-size: 14px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        .details-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #edebe4;
        }
        .details-table td:first-child {
            color: #6b726c;
            width: 40%;
            font-weight: 500;
        }
        .details-table td:last-child {
            font-weight: 600;
            color: #1e2420;
            text-align: right;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0 16px;
        }
        .btn {
            display: inline-block;
            background-color: #1a3329;
            color: #ffffff !important;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        .footer {
            background-color: #f7f6f2;
            padding: 16px 24px;
            text-align: center;
            font-size: 11px;
            color: #8a918a;
            border-top: 1px solid #e8e5dc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">ARUS</div>
            <h1 class="header-title">{{ $subjectTitle ?? 'Pengingat Keuangan' }}</h1>
        </div>

        <div class="content">
            <div class="greeting">Halo, {{ $userName ?? 'Pengguna ARUS' }}!</div>
            
            <p style="margin: 0 0 12px 0;">
                Ini adalah pesan pengingat keuangan otomatis dari akun <strong>ARUS</strong> Anda:
            </p>

            <div class="message-box">
                {{ $messageText ?? 'Pastikan untuk memeriksa catatan keuangan Anda secara rutin.' }}
            </div>

            @if (!empty($details))
                <table class="details-table">
                    @foreach ($details as $label => $value)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <div class="btn-wrapper">
                <a href="{{ $actionUrl ?? url('/dashboard') }}" class="btn">
                    Buka Aplikasi ARUS
                </a>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} ARUS. Ruang Pencatatan Keuangan Terarah.<br>
            Pesan ini dikirimkan otomatis sesuai jadwal pengingat yang Anda atur.
        </div>
    </div>
</body>
</html>
