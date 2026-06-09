<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use App\Enums\PaymentFailureReason;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrisSimulatorController extends Controller
{
    /**
     * Tampilkan halaman QR Code (desktop) atau simulator (mobile).
     * Deteksi user-agent: jika mobile → tampilkan simulator, jika desktop → QR display.
     * Validasi expired di server.
     */
    public function show(string $token, Request $request)
    {
        $payment = Payment::where('payment_token', $token)
            ->with('serviceRequest.applicant')
            ->firstOrFail();

        $serviceRequest = $payment->serviceRequest;

        // 🔴 FIX: Validasi EXPIRED — jika expired, update status & tampilkan halaman expired
        // (bukan redirect) agar user melihat pesan expired yang jelas
        if ($payment->isExpired()) {
            $this->handleExpired($payment, $serviceRequest);
            $payment = $payment->fresh();
        }

        // Cek apakah sudah sukses
        $isSuccess = $payment->status === 'SUCCESS';
        $isExpired = $payment->status === 'EXPIRED' || $payment->isExpired();

        // 🔴 FIX: Jika expired atau success, langsung tampilkan view yang sesuai
        // daripada redirect (agar user dapat feedback visual yang jelas)
        if ($isExpired || $isSuccess) {
            $userAgent = $request->header('User-Agent', '');
            $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS/i', $userAgent);

            if ($isMobile || $request->has('simulator')) {
                return view('pelanggan.qris-simulator', [
                    'payment' => $payment,
                    'serviceRequest' => $serviceRequest,
                    'expired' => $isExpired,
                    'success' => $isSuccess,
                ]);
            }

            return view('pelanggan.qr-payment', [
                'payment' => $payment,
                'serviceRequest' => $serviceRequest,
                'qrSvg' => null,
                'expired' => $isExpired,
                'success' => $isSuccess,
            ]);
        }

        // Deteksi mobile vs desktop
        $userAgent = $request->header('User-Agent', '');
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS/i', $userAgent);

        if ($isMobile || $request->has('simulator')) {
            // Mobile → tampilkan simulator
            return view('pelanggan.qris-simulator', [
                'payment' => $payment,
                'serviceRequest' => $serviceRequest,
                'expired' => $isExpired,
                'success' => $isSuccess,
            ]);
        }

        // Desktop → tampilkan QR display
        // QR berisi URL auto-success: saat discan HP, langsung trigger sukses
        // SELALU gunakan NGROK_URL agar HP bisa akses dari luar (internet)
        $baseUrl = config('app.ngrok_url', 'https://immovably-legroom-jolly.ngrok-free.dev');
        $paymentUrl = $baseUrl . '/pay/' . $token . '/success';
        
        $qrSvg = QrCode::size(280)
            ->generate($paymentUrl);

        return view('pelanggan.qr-payment', [
            'payment' => $payment,
            'serviceRequest' => $serviceRequest,
            'qrSvg' => $qrSvg,
            'expired' => $isExpired,
            'success' => $isSuccess,
        ]);
    }

    /**
     * READ-ONLY endpoint untuk polling JavaScript.
     * Tidak mengubah state apa pun — hanya mengembalikan JSON status payment.
     * Ini mencegah polling memanggil show() yang mengandung side effect (handleExpired).
     */
    public function checkStatus(string $token)
    {
        $payment = Payment::where('payment_token', $token)
            ->select('id', 'status', 'expired_at')
            ->firstOrFail();

        $isExpired = $payment->expired_at && now()->greaterThan($payment->expired_at);

        return response()->json([
            'status' => $payment->status,
            'is_expired' => $isExpired,
            'expired_at' => $payment->expired_at?->toIso8601String(),
        ]);
    }

    /**
     * GET endpoint — auto-trigger sukses saat URL dibuka (hasil scan QR).
     * Ini adalah inti simulasi QRIS: scan QR → buka URL → auto sukses.
     * 
     * BLUEPRINT: Urutan Guard WAJIB:
     * 1. Guard 1: cek paid (idempotency) — cek DULU sebelum apapun
     * 2. Guard 2: cek attempts >= 3 (batas percobaan) — cek KEDUA
     * 3. Proses pembayaran — baru proses setelah 2 guard lolos
     * 
     * FIX: Auto-forward ke Unit Konstruksi & Unit Penyalaan setelah pembayaran sukses.
     */
    public function successByGet(string $token)
    {
        try {
            // Find Payment first (payment_token is on payments table, not service_requests)
            $payment = Payment::where('payment_token', $token)
                ->with('serviceRequest')
                ->firstOrFail();
            
            $serviceRequest = $payment->serviceRequest;

            // Guard 1: Sudah selesai diproses (IDEMPOTENCY - paling kuat)
            // Jika status sudah SELESAI atau sudah di luar PEMBAYARAN, return sukses
            if ($serviceRequest->status === PermohonanStatus::SELESAI) {
                return view('pelanggan.payment-success', [
                    'serviceRequest' => $serviceRequest
                ]);
            }
            
            // Guard 2: Sudah pernah bayar (IDEMPOTENCY)
            // Jika payment_status sudah 'paid', return sukses tanpa ubah DB
            if ($serviceRequest->payment_status === 'paid') {
                return view('pelanggan.payment-success', [
                    'serviceRequest' => $serviceRequest
                ]);
            }

            // Guard 2: Sudah habis 3x percobaan (BATAS PERCOBAAN)
            // Jika attempts >= 3, TOLAK dan tampilkan halaman gagal
            if ($serviceRequest->payment_attempt_count >= 3) {
                return view('pelanggan.payment-failed', [
                    'serviceRequest' => $serviceRequest,
                    'message' => 'Batas percobaan pembayaran telah habis.'
                ]);
            }

            // Guard 3: Cek Payment record PENDING
            $payment = \App\Models\Payment::where('payment_token', $token)
                ->where('status', 'PENDING')
                ->first();
            
            if (!$payment) {
                return view('pelanggan.payment-failed', [
                    'serviceRequest' => $serviceRequest,
                    'message' => 'Tagihan tidak ditemukan atau sudah tidak aktif.'
                ]);
            }

            // PROSES PEMBAYARAN dalam TRANSACTION
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $serviceRequest, $token) {
                // Update Payment record
                $payment->update([
                    'status' => 'SUCCESS',
                    'paid_at' => now(),
                    'ref_no' => 'QR-' . strtoupper(substr(uniqid(), -8)),
                ]);

                // Update ServiceRequest - payment fields
                $serviceRequest->update([
                    'payment_status' => 'paid',
                    'paid_at'        => now(),
                ]);

                // FIX: Auto-advance FULL workflow - dari Konstruksi sampai Selesai
                // Step 1: Pembayaran Sukses
                $serviceRequest->transitionToSystem(
                    PermohonanStatus::PEMBAYARAN,
                    PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                    'Pembayaran berhasil melalui QRIS.'
                );

                // Step 2-6: Unit Konstruksi (otomatis)
                $konstruksiSteps = [
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI, 'Diterima Unit Konstruksi'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN, 'Konstruksi dijadwalkan'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::PEMBANGUNAN_JARINGAN, 'Pembangunan jaringan dimulai'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_BERHASIL, 'Konstruksi berhasil'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_SELESAI, 'Konstruksi selesai'],
                ];

                foreach ($konstruksiSteps as $index => [$status, $detail, $note]) {
                    $serviceRequest->transitionToSystem($status, $detail, $note);
                }

                // Step 7-10: Unit Penyalaan (otomatis)
                $penyalaanSteps = [
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::DITERIMA_UNIT_PENYALAAN, 'Diterima Unit Penyalaan'],
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_DIJADWALKAN, 'Penyalaan dijadwalkan'],
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_BERHASIL, 'Penyalaan berhasil'],
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_SELESAI, 'Penyalaan selesai'],
                ];

                foreach ($penyalaanSteps as $index => [$status, $detail, $note]) {
                    $serviceRequest->transitionToSystem($status, $detail, $note);
                }

                // Step 11: Transisi final - tutup permohonan dengan SELESAI + CLOSE
                $serviceRequest->transitionToSystem(
                    PermohonanStatus::SELESAI,
                    PermohonanDetailStatus::CLOSE,
                    'Seluruh proses selesai - permohonan ditutup.'
                );

                // Step 12: Tandai selesai - update completed_at
                $serviceRequest->update([
                    'completed_at' => now(),
                ]);
            });

            return view('pelanggan.payment-success', [
                'serviceRequest' => $serviceRequest->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Payment callback: token tidak ditemukan - ' . $token);
            return response('Token pembayaran tidak valid.', 404);
        } catch (\Exception $e) {
            \Log::error('Payment callback error: ' . $e->getMessage());
            return response('Terjadi kesalahan sistem. Hubungi admin.', 500);
        }
    }

    /**
     * Callback sukses dari simulator pembayaran (POST — tombol klik).
     * Validasi expired di server sebelum proses.
     */
    public function success(string $token)
    {
        $payment = Payment::where('payment_token', $token)
            ->with('serviceRequest')
            ->firstOrFail();

        $serviceRequest = $payment->serviceRequest;

        // VALIDASI EXPIRED — SERVER SIDE
        if ($payment->expired_at && now()->greaterThan($payment->expired_at)) {
            $this->handleExpired($payment, $serviceRequest);
            return redirect()->route('landing')
                ->with('error', 'Pembayaran sudah kedaluwarsa.');
        }

        // Cek apakah sudah SUCCESS (idempotent)
        if ($payment->status === 'SUCCESS') {
            return redirect()->route('landing')
                ->with('success', 'Pembayaran sudah berhasil sebelumnya.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $serviceRequest) {
                // Update payment record
                $payment->update([
                    'status' => 'SUCCESS',
                    'paid_at' => now(),
                    'ref_no' => 'QR-' . strtoupper(substr(uniqid(), -8)),
                ]);

                // Auto-advance FULL workflow - dari Konstruksi sampai Selesai
                // Step 1: Pembayaran Sukses
                $serviceRequest->transitionToSystem(
                    PermohonanStatus::PEMBAYARAN,
                    PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                    'Pembayaran berhasil melalui QRIS Simulator.'
                );

                // Step 2-7: Unit Konstruksi (otomatis)
                $konstruksiSteps = [
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI, 'Diterima Unit Konstruksi'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN, 'Konstruksi dijadwalkan'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::PEMBANGUNAN_JARINGAN, 'Pembangunan jaringan dimulai'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_BERHASIL, 'Konstruksi berhasil'],
                    [PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_SELESAI, 'Konstruksi selesai'],
                ];

                foreach ($konstruksiSteps as $index => [$status, $detail, $note]) {
                    $serviceRequest->transitionToSystem($status, $detail, $note);
                }

                // Step 8-11: Unit Penyalaan (otomatis)
                $penyalaanSteps = [
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::DITERIMA_UNIT_PENYALAAN, 'Diterima Unit Penyalaan'],
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_DIJADWALKAN, 'Penyalaan dijadwalkan'],
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_BERHASIL, 'Penyalaan berhasil'],
                    [PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_SELESAI, 'Penyalaan selesai'],
                ];

                foreach ($penyalaanSteps as $index => [$status, $detail, $note]) {
                    $serviceRequest->transitionToSystem($status, $detail, $note);
                }

                // Step 12: Transisi final - tutup permohonan dengan SELESAI + CLOSE
                $serviceRequest->transitionToSystem(
                    PermohonanStatus::SELESAI,
                    PermohonanDetailStatus::CLOSE,
                    'Seluruh proses selesai - permohonan ditutup.'
                );

                // Step 13: Tandai selesai - update completed_at
                $serviceRequest->update([
                    'completed_at' => now(),
                ]);
            });

            return view('pelanggan.qris-simulator', [
                'payment' => $payment->fresh(),
                'serviceRequest' => $serviceRequest->fresh(),
                'expired' => false,
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('QRIS payment success callback failed', [
                'payment_id' => $payment->id,
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('landing')
                ->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Callback gagal dari simulator.
     * BLUEPRINT: Increment attempts, cek apakah sudah 3x gagal.
     * Jika attempts >= 3 → tampilkan payment-failed.blade.php
     */
    public function fail(string $token)
    {
        try {
            // Find Payment first (payment_token is on payments table, not service_requests)
            $payment = Payment::where('payment_token', $token)
                ->with('serviceRequest')
                ->firstOrFail();
            
            $serviceRequest = $payment->serviceRequest;

            // Guard: Jika sudah paid atau attempts >= 3, tolak
            if ($serviceRequest->payment_status === 'paid') {
                return view('pelanggan.payment-success', [
                    'serviceRequest' => $serviceRequest
                ]);
            }

            if ($serviceRequest->payment_attempt_count >= 3) {
                return view('pelanggan.payment-failed', [
                    'serviceRequest' => $serviceRequest,
                    'message' => 'Batas percobaan pembayaran telah habis.'
                ]);
            }

            // Increment attempt_count
            $serviceRequest->increment('payment_attempt_count');
            $serviceRequest->refresh();

            $attemptAfter = $serviceRequest->payment_attempt_count;

            // Jika sudah 3x gagal → status = PERMOHONAN_GAGAL, payment_status = failed
            if ($attemptAfter >= 3) {
                $serviceRequest->update([
                    'status' => 'SELESAI',
                    'status_detail' => 'PERMOHONAN_GAGAL',
                    'payment_status' => 'failed',
                ]);

                return view('pelanggan.payment-failed', [
                    'serviceRequest' => $serviceRequest->fresh(),
                    'message' => 'Batas percobaan pembayaran telah habis. Permohonan dinyatakan gagal.',
                ]);
            }

            // Gagal tapi belum 3x → tampilkan info sisa percobaan
            // Return JSON untuk API call (browser-based)
            return response()->json([
                'success' => false,
                'attempts' => $attemptAfter,
                'remaining_attempts' => 3 - $attemptAfter,
                'is_final' => false,
                'message' => "Pembayaran gagal. Sisa percobaan: " . (3 - $attemptAfter),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Payment fail: token tidak ditemukan - ' . $token);
            return response('Token pembayaran tidak valid.', 404);
        } catch (\Exception $e) {
            \Log::error('Payment fail error: ' . $e->getMessage());
            return response('Terjadi kesalahan sistem.', 500);
        }
    }

    /**
     * Handle expired payment session.
     * Update status, increment attempt.
     * Jika attempt >= 3, auto menjadi SELESAI + PERMOHONAN_GAGAL (via incrementPaymentAttempt).
     */
    private function handleExpired(Payment $payment, ServiceRequest $serviceRequest): void
    {
        if ($payment->status === 'EXPIRED') {
            return; // Sudah diproses, idempotent
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $serviceRequest) {
            // Update payment status ke EXPIRED
            $payment->update([
                'status' => 'EXPIRED',
            ]);

            // Hanya increment attempt, biarkan status_detail tetap MENUNGGU_PEMBAYARAN
            // transitionToSystem dengan status sama tidak diizinkan, jadi cukup increment
            $serviceRequest->incrementPaymentAttempt(
                PaymentFailureReason::PAYMENT_EXPIRED
            );
        });
    }
}