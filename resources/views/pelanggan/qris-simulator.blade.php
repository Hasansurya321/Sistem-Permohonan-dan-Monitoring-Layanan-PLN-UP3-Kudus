<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PLN Mobile - Pembayaran</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }
        .phone-frame {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .header {
            background: #0066cc;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 { font-size: 18px; font-weight: 600; margin-bottom: 4px; }
        .header p { font-size: 13px; opacity: 0.9; }
        .content { padding: 24px 20px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #666; font-size: 14px; }
        .info-value { font-weight: 600; font-size: 14px; text-align: right; }
        .amount {
            font-size: 28px;
            font-weight: 700;
            color: #0066cc;
            text-align: center;
            padding: 20px 0;
        }
        .timer {
            text-align: center;
            padding: 12px;
            background: #fff3cd;
            border-radius: 10px;
            margin: 16px 0;
            font-size: 16px;
            font-weight: 600;
            color: #856404;
        }
        .timer-expired {
            background: #f8d7da;
            color: #721c24;
        }
        .timer-success {
            background: #d4edda;
            color: #155724;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: #0066cc;
            color: white;
        }
        .btn-primary:hover { background: #0052a3; }
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #e9ecef;
            color: #333;
            margin-top: 12px;
        }
        .success-icon {
            text-align: center;
            padding: 40px 0 20px;
        }
        .success-icon .checkmark {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        .expired-icon {
            text-align: center;
            padding: 40px 0 20px;
        }
        .expired-icon .xmark {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #999; }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading.active { display: block; }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0066cc;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="phone-frame">
        <div class="header">
            <h1>🏢 PLN Mobile</h1>
            <p>Pembayaran Digital</p>
        </div>

        @if ($success)
            {{-- HALAMAN SUKSES --}}
            <div class="content">
                <div class="success-icon">
                    <div class="checkmark">✓</div>
                </div>
                <h2 style="text-align:center; color:#28a745; margin-bottom:8px;">Pembayaran Berhasil</h2>
                <div class="amount">Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</div>
                <div class="info-row">
                    <span class="info-label">No. Permohonan</span>
                    <span class="info-value">{{ $serviceRequest->nomor_permohonan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Bayar</span>
                    <span class="info-value">{{ $payment->paid_at ? \App\Helpers\WaktuHelper::formatLengkap($payment->paid_at) : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ref</span>
                    <span class="info-value">{{ $payment->ref_no ?? '-' }}</span>
                </div>
                <div style="margin-top:20px;">
                    <a href="{{ route('landing') }}" class="btn btn-primary">Kembali ke Dashboard</a>
                </div>
            </div>
        @elseif ($expired)
            {{-- HALAMAN EXPIRED --}}
            <div class="content">
                <div class="expired-icon">
                    <div class="xmark">✕</div>
                </div>
                <h2 style="text-align:center; color:#dc3545; margin-bottom:8px;">Waktu Pembayaran Habis</h2>
                <p style="text-align:center; color:#666; margin-bottom:20px;">
                    Token pembayaran telah kedaluwarsa. Silakan lakukan pembayaran ulang dari dashboard.
                </p>
                <div class="timer timer-expired">
                    ⏰ Token Kedaluwarsa
                </div>
                <a href="{{ route('landing') }}" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        @elseif (isset($final_failed) && $final_failed)
            {{-- HALAMAN GAGAL FINAL (3x attempt) --}}
            <div class="content">
                <div class="expired-icon">
                    <div class="xmark">✕</div>
                </div>
                <h2 style="text-align:center; color:#dc3545; margin-bottom:8px;">Pembayaran Gagal</h2>
                <p style="text-align:center; color:#666; margin-bottom:20px;">
                    {{ $message ?? 'Pembayaran gagal setelah 3 kali percobaan.' }}
                </p>
                <div class="timer timer-expired">
                    ❌ Gagal Final
                </div>
                <a href="{{ route('landing') }}" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        @elseif (isset($failed_attempt) && $failed_attempt > 0)
            {{-- HALAMAN GAGAL (attempt < 3) --}}
            <div class="content">
                <div class="info-row">
                    <span class="info-label">No. Permohonan</span>
                    <span class="info-value">{{ $serviceRequest->nomor_permohonan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Layanan</span>
                    <span class="info-value">{{ $serviceRequest->jenis_layanan ?? '-' }}</span>
                </div>
                <div class="amount">Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</div>

                <div class="timer timer-expired" style="background:#f8d7da; color:#721c24;">
                    ❌ Gagal! Attempt {{ $failed_attempt }}/3 - Sisa: {{ $remaining_attempts ?? 0 }}
                </div>

                <p style="text-align:center; color:#dc3545; margin:16px 0;">
                    {{ $message ?? 'Pembayaran gagal.' }}
                </p>

                <form id="paymentForm" action="{{ route('qris.success', ['token' => $payment->payment_token]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary" id="payButton">
                        💳 COBA LAGI
                    </button>
                </form>

                {{-- TOMBOL GAGAL: Simulasi pembayaran gagal --}}
                <form id="failForm" action="{{ route('qris.fail', ['token' => $payment->payment_token]) }}" method="POST" style="margin-top:8px;">
                    @csrf
                    <button type="submit" class="btn btn-danger" id="failButton" style="background:#dc3545; color:white;">
                        ❌ GAGAL (Attempt {{ ($failed_attempt ?? 0) + 1 }})
                    </button>
                </form>

                <a href="{{ route('landing') }}" class="btn btn-secondary" style="margin-top:12px;">← Kembali</a>
            </div>
        @else
            {{-- HALAMAN BAYAR --}}
            <div class="content">
                <div class="info-row">
                    <span class="info-label">No. Permohonan</span>
                    <span class="info-value">{{ $serviceRequest->nomor_permohonan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Layanan</span>
                    <span class="info-value">{{ $serviceRequest->jenis_layanan ?? '-' }}</span>
                </div>
                <div class="amount">Rp {{ number_format($payment->amount ?? 0, 0, ',', '.') }}</div>

                <div class="timer" id="timer">
                    ⏰ Sisa Waktu: <span id="countdown">02:00</span> (2 menit)
                </div>

                <div id="loading" class="loading">
                    <div class="spinner"></div>
                    <p style="margin-top:12px; color:#666;">Memproses pembayaran...</p>
                </div>

                <form id="paymentForm" action="{{ route('qris.success', ['token' => $payment->payment_token]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary" id="payButton">
                        💳 BAYAR SEKARANG
                    </button>
                </form>

                {{-- TOMBOL GAGAL: Simulasi pembayaran gagal --}}
                <form id="failForm" action="{{ route('qris.fail', ['token' => $payment->payment_token]) }}" method="POST" style="margin-top:8px;">
                    @csrf
                    <button type="submit" class="btn btn-danger" id="failButton" style="background:#dc3545; color:white;">
                        ❌ GAGAL (Test Attempt {{ $serviceRequest->payment_attempt_count ?? 0 }})
                    </button>
                </form>

                <a href="{{ route('landing') }}" class="btn btn-secondary" style="margin-top:12px;">← Kembali</a>
            </div>
        @endif

        <div class="footer">
            PLN UP3 Kudus © {{ date('Y') }}
        </div>
    </div>

    <script>
        // Countdown timer (client-side only untuk display, server tetap validasi)
        @if (!$success && !$expired && $payment->expired_at)
            (function() {
                const expiredAt = new Date("{{ $payment->expired_at->format('Y-m-d H:i:s') }}").getTime();
                const countdownEl = document.getElementById('countdown');
                const timerEl = document.getElementById('timer');
                const payButton = document.getElementById('payButton');
                const loadingEl = document.getElementById('loading');

                function updateTimer() {
                    const now = new Date().getTime();
                    const diff = expiredAt - now;

                    if (diff <= 0) {
                        countdownEl.textContent = '00:00';
                        timerEl.className = 'timer timer-expired';
                        timerEl.innerHTML = '⏰ Waktu Habis';
                        payButton.disabled = true;
                        payButton.textContent = 'Kedaluwarsa';
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

                // Loading state on submit
                document.getElementById('paymentForm').addEventListener('submit', function(e) {
                    payButton.disabled = true;
                    payButton.textContent = 'Memproses...';
                    loadingEl.classList.add('active');
                });
            })();
        @endif
    </script>
</body>
</html>