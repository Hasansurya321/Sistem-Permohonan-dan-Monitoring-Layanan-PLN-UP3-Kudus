<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Penolakan Akun - PLN UP3 Kudus</title>
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
            background-color: #B91C1C;
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
        .reason-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .reason-box p {
            margin: 0;
            font-size: 14px;
            color: #991b1b;
        }
        .reason-box strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #7f1d1d;
        }
        .btn {
            display: inline-block;
            background-color: #093C5D;
            color: #ffffff !important;
            padding: 12px 32px;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            <h2>Yth. {{ $requestData->full_name }},</h2>
            <p>Kami informasikan bahwa permohonan registrasi akun pelanggan Anda <strong>tidak dapat disetujui</strong> oleh admin layanan PLN UP3 Kudus.</p>
            
            <div class="reason-box">
                <strong>Alasan Penolakan:</strong>
                <p>{{ $rejectionReason }}</p>
            </div>

            <p>Silakan melakukan registrasi ulang apabila diperlukan dengan menggunakan data yang benar dan sesuai ketentuan.</p>
            <p>Kami mohon maaf atas ketidaknyamanan ini. Jika Anda memiliki pertanyaan lebih lanjut, silakan hubungi layanan pelanggan PLN UP3 Kudus.</p>
            
            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ url('/pelanggan/register') }}" class="btn">Registrasi Ulang</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} PLN UP3 Kudus. Hak Cipta Dilindungi.</p>
        </div>
    </div>
</body>
</html>