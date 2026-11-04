<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Reset Kata Sandi</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f7f6f2;
            margin: 0;
            padding: 40px 20px;
            color: #1a221d;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e3da;
            padding: 36px 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .header {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #234e38;
            margin: 0;
            text-transform: uppercase;
        }
        .title {
            font-size: 18px;
            font-weight: 600;
            color: #1a221d;
            margin-top: 14px;
            margin-bottom: 8px;
        }
        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #4a554f;
            margin: 0 0 20px;
        }
        .otp-box {
            background-color: #f1f4f1;
            border: 1px dashed #234e38;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #234e38;
            margin: 0;
            display: inline-block;
        }
        .note {
            font-size: 12px;
            color: #718076;
            margin-top: 10px;
        }
        .footer {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #e5e3da;
            text-align: center;
            font-size: 12px;
            color: #8c978f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="brand">ARUS</h1>
            <div class="title">Permintaan Reset Kata Sandi</div>
        </div>

        <p class="text">Halo <strong>{{ $name }}</strong>,</p>
        <p class="text">
            Kami menerima permintaan untuk mengatur ulang kata sandi akun ARUS Anda. Gunakan 6 digit kode verifikasi berikut untuk melanjutkan:
        </p>

        <div class="otp-box">
            <div class="otp-code">{{ $otpCode }}</div>
            <div class="note">Kode berlaku selama {{ $expiresMinutes }} menit</div>
        </div>

        <p class="text" style="font-size: 13px; color: #718076;">
            Jika Anda tidak pernah meminta reset kata sandi, akun Anda tetap aman dan Anda dapat mengabaikan email ini. Jangan berikan kode ini kepada siapa pun.
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} ARUS Finance. Sistem Manajemen Arus Kas & Keuangan Pribadi.
        </div>
    </div>
</body>
</html>
