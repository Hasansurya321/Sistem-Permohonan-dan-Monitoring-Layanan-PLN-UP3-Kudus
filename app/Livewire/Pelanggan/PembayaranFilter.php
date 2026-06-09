<?php

namespace App\Livewire\Pelanggan;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class PembayaranFilter extends Component
{
    public string $activeTab = 'menunggu';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * "Bayar Sekarang" / "Coba Bayar Lagi" — generate payment session dengan QR token,
     * lalu redirect ke halaman QRIS.
     * Attempt_count TIDAK berubah di sini.
     */
    public function generatePaymentSession(int $serviceRequestId): void
    {
        $sr = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
            ->where('status', PermohonanStatus::PEMBAYARAN)
            ->findOrFail($serviceRequestId);

        if (!$sr->canRetryPayment()) {
            session()->flash('error', 'Tidak dapat melakukan pembayaran.');
            return;
        }

        try {
            $token = null;

            \Illuminate\Support\Facades\DB::transaction(function () use ($sr, &$token) {
                // Generate UUID token
                $token = (string) \Illuminate\Support\Str::uuid();

                // Create or update payment record
                $payment = \App\Models\Payment::firstOrNew([
                    'service_request_id' => $sr->id,
                ]);

                // Gunakan amount dari billing yang sudah digenerate oleh adminAccept()
                $payload = $sr->payload_json ?? [];
                $billingTotal = data_get($payload, 'dummy_billing.total', 0);

                $payment->fill([
                    'payment_token' => $token,
                    'status' => 'PENDING',
                    'amount' => $billingTotal > 0 ? $billingTotal : ($payment->amount ?? 500000),
                    'ref_no' => null,
                    'paid_at' => null,
                    'expired_at' => now()->addSeconds(120), // 120 detik (2 menit)
                ]);
                $payment->save();

                // 🔴 FIX: Jangan ubah status_detail — tetap MENUNGGU_PEMBAYARAN
                // Pembayaran belum diproses, hanya session QR yang baru dibuat.
                // Status hanya berubah menjadi PEMBAYARAN_PENDING jika benar-benar expired
                // (di-handle oleh QrisSimulatorController::handleExpired())
                // atau PEMBAYARAN_SUKSES jika scan berhasil (QrisSimulatorController::success()).
                // Tidak perlu transisi status di sini karena status sudah MENUNGGU_PEMBAYARAN.
                // Cukup update payment record dan redirect ke QRIS.
            });

            // 🔄 Redirect ke halaman QRIS setelah session berhasil dibuat
            $this->redirectRoute('qris.show', ['token' => $token]);
        } catch (\Throwable $e) {
            Log::error('Generate payment session failed', [
                'service_request_id' => $serviceRequestId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Gagal membuat session pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * "Batalkan Permohonan" — cancel by customer.
     */
    public function cancelRequest(int $serviceRequestId): void
    {
        $sr = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
            ->where('status', PermohonanStatus::PEMBAYARAN)
            ->findOrFail($serviceRequestId);

        try {
            $sr->cancelByCustomer();
            session()->flash('success', 'Permohonan pembayaran dibatalkan.');
            $this->dispatch('$refresh');
        } catch (\Throwable $e) {
            Log::error('Cancel payment failed', [
                'service_request_id' => $serviceRequestId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Gagal membatalkan permohonan: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::guard('web')->user();

        // Query mencari ServiceRequest melalui dua jalur:
        // 1. submitter_user_id (user yang login sebagai pemohon)
        // 2. applicant_nik (NIK pemohon — fallback jika NIK berbeda)
        $query = ServiceRequest::query()
            ->where(function ($q) use ($user) {
                $q->where('submitter_user_id', $user->id)
                  ->orWhere('applicant_nik', $user->nik);
            })
            ->submitted()
            ->with(['applicant', 'payments']);

        $serviceRequests = match ($this->activeTab) {
            'menunggu' => (clone $query)
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN)
                ->where(function ($q) {
                    // 🔴 FIX: Exclude request yang sudah gagal (attempt > 0)
                    // Ini mencegah request muncul di 2 filter sekaligus
                    // Request gagal akan muncul di filter "Pending" bukan "Menunggu"
                    $q->where('payment_attempt_count', 0)
                      ->orWhereNull('payment_attempt_count');
                })
                ->whereNull('cancelled_at')
                ->latest()
                ->get(),

            'pending'  => (clone $query)
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->whereIn('status_detail', [
                    PermohonanDetailStatus::MENUNGGU_PEMBAYARAN,
                    PermohonanDetailStatus::PEMBAYARAN_PENDING,
                ])
                ->where('payment_attempt_count', '>', 0)
                ->where('payment_attempt_count', '<', 3)
                ->whereNull('cancelled_at')
                ->latest()
                ->get(),

            'selesai'  => (clone $query)
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        // PEMBAYARAN_SUKSES / PEMBAYARAN_SELESAI (masih PEMBAYARAN status)
                        $q2->where('status', PermohonanStatus::PEMBAYARAN)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                                PermohonanDetailStatus::PEMBAYARAN_SELESAI,
                            ]);
                    })->orWhere(function ($q2) {
                        // PEMBAYARAN_GAGAL atau PERMOHONAN_GAGAL final (SELESAI status)
                        $q2->where('status', PermohonanStatus::SELESAI)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::PEMBAYARAN_GAGAL,
                                PermohonanDetailStatus::PERMOHONAN_GAGAL,
                            ]);
                    })->orWhere(function ($q2) {
                        // ✅ Status akhir setelah pembayaran sukses: SELESAI + CLOSE
                        $q2->where('status', PermohonanStatus::SELESAI)
                            ->where('status_detail', PermohonanDetailStatus::CLOSE);
                    });
                })
                ->latest()
                ->get(),

            default    => (clone $query)
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN)
                ->whereNull('cancelled_at')
                ->latest()
                ->get(),
        };

        $counts = [
            'menunggu' => (clone $query)
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN)
                ->where(function ($q) {
                    // 🔴 FIX: Exclude request yang sudah gagal (attempt > 0)
                    // Sinkron dengan data query
                    $q->where('payment_attempt_count', 0)
                      ->orWhereNull('payment_attempt_count');
                })
                ->whereNull('cancelled_at')
                ->count(),
            'pending'  => (clone $query)
                ->where('status', PermohonanStatus::PEMBAYARAN)
                ->whereIn('status_detail', [
                    PermohonanDetailStatus::MENUNGGU_PEMBAYARAN,
                    PermohonanDetailStatus::PEMBAYARAN_PENDING,
                ])
                ->where('payment_attempt_count', '>', 0)
                ->where('payment_attempt_count', '<', 3)
                ->whereNull('cancelled_at')
                ->count(),
            'selesai'  => (clone $query)
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('status', PermohonanStatus::PEMBAYARAN)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                                PermohonanDetailStatus::PEMBAYARAN_SELESAI,
                            ]);
                    })->orWhere(function ($q2) {
                        $q2->where('status', PermohonanStatus::SELESAI)
                            ->whereIn('status_detail', [
                                PermohonanDetailStatus::PEMBAYARAN_GAGAL,
                                PermohonanDetailStatus::PERMOHONAN_GAGAL,
                            ]);
                    })->orWhere(function ($q2) {
                        // ✅ Status akhir setelah pembayaran sukses: SELESAI + CLOSE
                        $q2->where('status', PermohonanStatus::SELESAI)
                            ->where('status_detail', PermohonanDetailStatus::CLOSE);
                    });
                })
                ->count(),
        ];

        return view('livewire.pelanggan.pembayaran-filter', [
            'serviceRequests' => $serviceRequests,
            'counts'   => $counts,
            'activeTab' => $this->activeTab,
        ]);
    }
}