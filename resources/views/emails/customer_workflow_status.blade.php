<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status Permohonan - PLN UP3 Kudus</title>
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
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #093C5D 0%, #0e5a8a 100%);
            padding: 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0 0 4px 0;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        .header p {
            color: rgba(255,255,255,0.75);
            font-size: 13px;
            margin: 0;
        }
        .status-badge {
            display: inline-block;
            background-color: #00A3E0;
            color: #ffffff;
            padding: 6px 20px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            margin: 24px 0 0 0;
            letter-spacing: 0.03em;
        }
        .content {
            padding: 36px 32px;
        }
        .content h2 {
            font-size: 18px;
            color: #0f172a;
            margin: 0 0 12px 0;
        }
        .content p {
            font-size: 15px;
            line-height: 1.7;
            color: #475569;
            margin: 0 0 20px 0;
        }
        .info-card {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 20px 24px;
            margin: 24px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { color: #0f172a; font-weight: 600; text-align: right; }
        .action-box {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 20px 24px;
            margin: 20px 0;
        }
        .action-box p {
            margin: 0;
            font-size: 14px;
            color: #0369a1;
            font-weight: 500;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #00A3E0, #0284c7);
            color: #ffffff !important;
            padding: 13px 32px;
            border-radius: 9999px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,163,224,0.3);
            margin: 4px 0;
        }
        .footer {
            background: #f1f5f9;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
        .footer a { color: #00A3E0; text-decoration: none; }
        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PLN UP3 KUDUS</h1>
            <p>Sistem Monitoring Layanan Pelanggan</p>
            <div class="status-badge">{{ $statusLabel }}</div>
        </div>

        <div class="content">
            <h2>Halo, {{ $userName }}!</h2>
            <p>Terdapat pembaruan status untuk permohonan layanan Anda. Berikut informasi terbaru:</p>

            <div class="info-card">
                <div class="info-row">
                    <span class="info-label">No. Permohonan</span>
                    <span class="info-value">{{ $nomorPermohonan }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jenis Layanan</span>
                    <span class="info-value">{{ $jenisLayanan }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Terbaru</span>
                    <span class="info-value" style="color: #00A3E0;">{{ $statusLabel }}</span>
                </div>
                @if($statusDetail)
                <div class="info-row">
                    <span class="info-label">Detail Status</span>
                    <span class="info-value">{{ $statusDetail }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Diperbarui Pada</span>
                    <span class="info-value">{{ $updatedAt }}</span>
                </div>
            </div>

            @if($contextMessage)
            <div class="action-box">
                <p>{{ $contextMessage }}</p>
            </div>
            @endif

            @if($note)
            <p style="font-size: 13px; color: #64748b; font-style: italic;">
                <strong>Catatan:</strong> {{ $note }}
            </p>
            @endif

            <div class="divider"></div>

            <p style="font-size: 13px; color: #64748b;">
                Anda dapat memantau perkembangan permohonan secara real-time melalui portal pelanggan PLN UP3 Kudus.
            </p>

            <div style="text-align: center; margin: 24px 0;">
                <a href="{{ $monitoringUrl }}" class="btn">Pantau Status Permohonan</a>
            </div>

            <p style="font-size: 12px; color: #94a3b8; text-align: center;">
                Jika ada pertanyaan, hubungi kami di <a href="tel:123" style="color:#00A3E0;">123</a> atau kunjungi kantor PLN UP3 Kudus.
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} PLN UP3 Kudus. Hak Cipta Dilindungi.</p>
            <p>Email ini dikirim secara otomatis, harap tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
