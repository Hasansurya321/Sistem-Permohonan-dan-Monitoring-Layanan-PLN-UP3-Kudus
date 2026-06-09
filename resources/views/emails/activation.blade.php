<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aktivasi Akun Pelanggan - PLN UP3 Kudus</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #093C5D;
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .content {
            padding: 40px 32px;
        }
        .content h2 {
            font-size: 18px;
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .content p {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-block;
            background-color: #00A3E0;
            color: #ffffff !important;
            padding: 12px 32px;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 163, 224, 0.2);
        }
        .footer {
            background-color: #f1f5f9;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PLN UP3 KUDUS</h1>
        </div>
        <div class="content">
            <h2>Halo, {{ $requestData->full_name }}!</h2>
            <p>Selamat! Permintaan registrasi akun pelanggan Anda telah disetujui oleh admin layanan PLN UP3 Kudus.</p>
            <p>Langkah terakhir untuk mengaktifkan akun Anda adalah dengan melakukan verifikasi email. Silakan klik tombol di bawah ini untuk mengaktifkan akun Anda:</p>
            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ url('/aktivasi/' . $token) }}" class="btn">Aktivasi Akun Saya</a>
            </div>
            <p style="font-size: 13px; color: #64748b;">Link aktivasi ini hanya berlaku selama 24 jam. Jika Anda tidak melakukan aktivasi dalam kurun waktu tersebut, Anda perlu mengajukan permintaan pendaftaran kembali.</p>
            <p>Jika tombol di atas tidak berfungsi, Anda juga dapat menyalin dan menempelkan tautan berikut ke browser Anda:</p>
            <p style="font-size: 13px; word-break: break-all; color: #00A3E0;">{{ url('/aktivasi/' . $token) }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} PLN UP3 Kudus. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</body>
</html>
