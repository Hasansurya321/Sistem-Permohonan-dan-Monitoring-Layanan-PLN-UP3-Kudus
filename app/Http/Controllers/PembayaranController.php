<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class PembayaranController extends Controller
{
    public function index()
    {
        // Ensure user is 'pelanggan'
        if (Auth::guard('web')->user()->role !== 'pelanggan') {
            return redirect()->route('landing');
        }

        $user = Auth::guard('web')->user();

        // ONE SOURCE OF TRUTH: Dapatkan NIK dari master_pelanggan.
        // Fallback ke users.nik untuk backward compatibility (akun lama sebelum migration).
        $masterNik = $user->masterPelanggan?->nik;
        $nik = $masterNik ?? $user->nik;

        // If user has no NIK (neither master nor users), show empty state
        if (!$nik) {
            return view('pelanggan.pembayaran-empty');
        }

        // Check if any payments exist for this user
        // Query mencari Payment melalui dua jalur:
        // 1. submitter_user_id (user yang login)
        // 2. applicant_nik (NIK pemohon — fallback jika NIK berbeda dengan submitter)
        $hasPayments = \App\Models\Payment::whereHas('serviceRequest', function ($q) use ($user, $nik) {
            $q->where('submitter_user_id', $user->id)
              ->orWhere('applicant_nik', $nik);
        })->exists();

        if (!$hasPayments) {
            return view('pelanggan.pembayaran-empty');
        }

        return view('pelanggan.pembayaran');
    }

    /**
     * Retry payment — hanya generate session QR baru.
     * Status_detail TIDAK diubah ke PEMBAYARAN_PENDING di sini (tetap MENUNGGU_PEMBAYARAN).
     * PEMBAYARAN_PENDING hanya di-set setelah QR benar-benar expired (handleExpired).
     * PEMBAYARAN_SUKSES hanya di-set setelah scan berhasil (success callback).
     * Attempt_count TIDAK berubah di sini.
     */
    public function retryPayment($id)
    {
        $sr = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
            ->where('status', PermohonanStatus::PEMBAYARAN)
            ->findOrFail($id);

        if (!$sr->canRetryPayment()) {
            return back()->with('error', 'Tidak dapat melakukan pembayaran ulang.');
        }

        // 🔴 FIX: Tidak perlu transisi status — status tetap MENUNGGU_PEMBAYARAN.
        // Cukup catat di log bahwa user mengakses retry (akan redirect ke QRIS dari view).
        Log::info('Pelanggan mengakses retry payment', [
            'service_request_id' => $id,
            'attempt_count' => $sr->payment_attempt_count,
        ]);

        return back()->with('success', 'Silakan lakukan pembayaran.');
    }

    /**
     * Tampilkan halaman detail pembayaran dengan rincian tagihan lengkap.
     */
    public function showDetail($id)
    {
        $user = Auth::guard('web')->user();

        $sr = ServiceRequest::with(['applicant', 'payments'])
            ->where('submitter_user_id', $user->id)
            ->findOrFail($id);

        // Ambil billing dari payload_json
        $payload = $sr->payload_json ?? [];
        $billing = data_get($payload, 'dummy_billing', []);
        $lokasi = data_get($payload, 'lokasi', []);

        // Payment aktif (PENDING)
        $activePayment = $sr->payments->where('status', 'PENDING')->first();

        // Mapping peruntukan
        $peruntukanLabels = [
            'RUMAH_TANGGA' => 'Rumah Tangga',
            'BISNIS'       => 'Bisnis',
            'INDUSTRI'     => 'Industri',
            'SOSIAL'       => 'Sosial',
            'PEMERINTAH'   => 'Pemerintah',
            'RUMAH_IBADAH' => 'Rumah Ibadah',
        ];

        return view('pelanggan.pembayaran-detail', compact(
            'sr', 'billing', 'lokasi', 'activePayment', 'peruntukanLabels', 'user'
        ));
    }

    /**
     * Batalkan permohonan pembayaran oleh pelanggan.
     */
    public function cancelRequest($id)
    {
        $sr = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
            ->where('status', PermohonanStatus::PEMBAYARAN)
            ->findOrFail($id);

        try {
            $sr->cancelByCustomer();
            return back()->with('success', 'Permohonan pembayaran dibatalkan.');
        } catch (\Throwable $e) {
            Log::error('Cancel payment failed', [
                'service_request_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal membatalkan permohonan: ' . $e->getMessage());
        }
    }
}
