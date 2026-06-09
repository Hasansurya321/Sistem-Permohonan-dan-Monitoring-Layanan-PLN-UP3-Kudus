<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran QRIS - PLN</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        .card-header {
            background: #0066cc;
            color: white;
            padding: 24px;
            text-align: center;
        }
        .card-header h1 { font-size: 20px; font-weight: 600; }
        .card-header p { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        .card-body { padding: 24px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-label { color: #666; font-size: 14px; }
        .info-value { font-weight: 600; font-size: 14px; }
        .amount {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            color: #0066cc;
            padding: 20px 0;
        }
        .qr-container {
            text-align: center;
            padding: 20px 0;
        }
        .qr-container svg, .qr-container img {
            max-width: 280px;
            height: auto;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px;
        }
        .timer {
            text-align: center;
            padding: 12px;
            background: #fff3cd;
            border-radius: 8px;
            margin: 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: #856404;
        }
        .timer-expired { background: #f8d7da; color: #721c24; }
        .timer-success { background: #d4edda; color: #155724; }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            margin-top: 12px;
        }
        .btn-primary { background: #0066cc; color: white; }
        .btn-primary:hover { background: #0052a3; }
        .btn-secondary { background: #e9ecef; color: #333; }
        .success-badge {
            text-align: center;
            padding: 30px 0;
        }
        .success-badge .check {
            width: 60px; height: 60px;
            background: #28a745;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }
        .expired-badge {
            text-align: center;
            padding: 30px 0;
        }
        .expired-badge .x {
            width: 60px; height: 60px;
            background: #dc3545;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }
        .footer { text-align: center; padding: 16px; font-size: 12px; color: #999; }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .loading { display: none; text-align: center; padding: 10px; }
        .loading.active { display: block; }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0066cc;
            border-radius: 50%;
            width: 24px; height: 24px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .polling-indicator {
            font-size: 12px; color: #999; text-align: center; padding: 8px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>PEMBAYARAN QRIS</h1>
            <p>PLN UP3 Kudus</p>
        </div>

        {{-- Session Messages --}}
        @if (session('success'))
            <div class="alert alert-success" style="margin:16px 24px 0">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" style="margin:16px 24px 0">{{ session('error') }}</div>
        @endif

        <div class="card-body">
            @if ($success)
                {{-- SUKSES --}}
                <div class="success-badge">
                    <div class="check">✓</div>
                </div>
                <h2 style="text-align:center; color:#28a745; margin-bottom:16px;">Pembayaran Berhasil</h2>
                <div class="timer timer-success">✓ Pembayaran telah dikonfirmasi</div>
                <div class="info-row">
                    <span class="info-label">No. Permohonan</span>
                    <span class="info-value">{{ $serviceRequest->nomor_permohonan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value" style="color:#28a745;">Permohonan dilanjutkan ke Unit Konstruksi</span>
                </div>
                <a href="{{ route('landing') }}" class="btn btn-primary">Kembali ke Dashboard</a>

            @elseif ($expired)
                {{-- EXPIRED --}}
                <div class="expired-badge">
                    <div class="x">✕</div>
                </div>
                <h2 style="text-align:center; color:#dc3545; margin-bottom:16px;">Waktu Pembayaran Habis</h2>
                <div class="timer timer-expired">⏰ Token Kedaluwarsa</div>
                <p style="text-align:center; color:#666; margin:16px 0;">
                    Silakan lakukan pembayaran ulang dari dashboard.
                </p>
                <a href="{{ route('landing') }}" class="btn btn-primary">Kembali ke Dashboard</a>

            @else
                {{-- QR DISPLAY --}}
                <div class="info-row">
                    <span class="info-label">No. Permohonan</span>
                    <span class="info-value">{{ $serviceRequest->nomor_permohonan ?? '-' }}</span>
                </div>
                <div class="amount">Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</div>

                @if($qrSvg)
                <div class="qr-container" id="qrContainer">
                    {!! $qrSvg !!}
                    <p style="font-size:12px; color:#999; margin-top:8px;">
                        Scan QR ini menggunakan aplikasi pembayaran
                    </p>
                </div>
                @endif

                <div class="timer" id="timer">
                    ⏰ Sisa Waktu: <span id="countdown">02:00</span> (2 menit)
                </div>

                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <p style="margin-top:8px; color:#666;">Menunggu pembayaran...</p>
                </div>

                <a href="{{ route('landing') }}" class="btn btn-secondary">← Kembali ke Dashboard</a>

                <div class="polling-indicator">
                    ⏳ Menunggu pembayaran... <span id="pollStatus">memeriksa</span>
                </div>
            @endif
        </div>

        <div class="footer">
            PLN UP3 Kudus © {{ date('Y') }}
        </div>
    </div>

    {{-- Polling untuk deteksi status (menggunakan endpoint READ-ONLY /status) --}}
    @if (!$success && !$expired && $payment->payment_token)
    <script>
        (function() {
            const token = "{{ $payment->payment_token }}";
            const expiredAt = new Date("{{ $payment->expired_at->format('Y-m-d H:i:s') }}").getTime();
            const countdownEl = document.getElementById('countdown');
            const timerEl = document.getElementById('timer');
            const pollStatus = document.getElementById('pollStatus');
            const loadingEl = document.getElementById('loading');

            // Countdown timer — CLIENT SIDE ONLY, tidak trigger perubahan state
            function updateTimer() {
                const now = new Date().getTime();
                const diff = expiredAt - now;

                if (diff <= 0) {
                    countdownEl.textContent = '00:00';
                    timerEl.className = 'timer timer-expired';
                    timerEl.innerHTML = '⏰ Waktu Habis';
                    pollStatus.textContent = 'kedaluwarsa';
                    return;
                }

                const minutes = Math.floor(diff / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                countdownEl.textContent = 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
            }
            updateTimer();
            setInterval(updateTimer, 1000);

            // Polling status — READ-ONLY endpoint, tidak mengubah state di server
            function checkStatus() {
                fetch('/pay/' + token + '/status')
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'SUCCESS') {
                            pollStatus.textContent = '✅ pembayaran berhasil';
                            loadingEl.classList.add('active');
                            setTimeout(() => { location.reload(); }, 2000);
                        } else if (data.is_expired) {
                            pollStatus.textContent = '⏰ kedaluwarsa';
                            setTimeout(() => { location.reload(); }, 3000);
                        } else {
                            pollStatus.textContent = '⏳ menunggu pembayaran...';
                        }
                    })
                    .catch(() => {
                        pollStatus.textContent = '⚠️ error';
                    });
            }

            // Cek tiap 5 detik
            checkStatus();
            setInterval(checkStatus, 5000);
        })();
    </script>
    @endif
</body>
</html>