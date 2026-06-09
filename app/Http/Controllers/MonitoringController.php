<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::guard('web')->user()->role !== 'pelanggan') {
            return redirect()->route('landing');
        }

        $userId = Auth::guard('web')->id();

        // Determine active tab with smart default
        $tab = $request->get('tab');
        
        // If no tab specified, default to processing, unless empty then fallback to waiting
        if (!$tab) {
            $processingCount = ServiceRequest::where('submitter_user_id', $userId)->processing()->count();
            $tab = $processingCount > 0 ? 'processing' : 'waiting';
        }

        // Base query — fresh dari DB tanpa cache
        $query = ServiceRequest::with(['applicant'])
            ->where('submitter_user_id', $userId);

        // Filter by tab
        // Aturan filter:
        // - "Sedang Diproses" (processing): semua status global yang bukan SELESAI
        //   (termasuk VERIFIKASI_DATA, UNIT_SURVEY, UNIT_PERENCANAAN, PEMBAYARAN, UNIT_KONSTRUKSI, UNIT_PENYALAAN)
        // - "Selesai" (done): hanya status global SELESAI (100% selesai, gagal revisi, gagal bayar, batal)
        // - "Menunggu" (waiting): khusus VERIFIKASI_DATA + MENUNGGU_VERIFIKASI_DATA
        switch($tab) {
            case 'waiting':
                $requests = $query->waiting()->latest('updated_at')->get();
                break;
            
            case 'processing':
                $requests = $query->processing()->latest('submitted_at')->get();
                break;
            
            case 'done':
                $requests = $query->done()->orderByRaw('COALESCE(completed_at, status_changed_at, updated_at) DESC')->get();
                break;
            
            default:
                $requests = $query->processing()->latest('updated_at')->get();
                $tab = 'processing';
        }

        $counts = [
            'waiting' => ServiceRequest::where('submitter_user_id', $userId)->waiting()->count(),
            'processing' => ServiceRequest::where('submitter_user_id', $userId)
                ->processing()->count(),
            'done' => ServiceRequest::where('submitter_user_id', $userId)
                ->done()->count(),
        ];

        return view('pelanggan.monitoring.index', compact('requests', 'tab', 'counts'));
    }

    public function show($id)
    {
        // Paksa query fresh dari DB, jangan pakai cache
        $req = ServiceRequest::with(['applicant', 'events' => fn($q) => $q->orderBy('occurred_at', 'desc'), 'payments'])
            ->where('submitter_user_id', Auth::guard('web')->id())
            ->findOrFail($id);

        // Pastikan events ter-load fresh (refresh dari DB)
        $req->load(['events' => fn($q) => $q->orderBy('occurred_at', 'desc')]);
        
        // Buat initial event jika belum ada (untuk timeline)
        $req->ensureInitialEvent();

        // Log current state untuk debugging
        \Illuminate\Support\Facades\Log::info('MonitoringController::show', [
            'service_request_id' => $req->id,
            'status' => $req->status?->value,
            'status_detail' => $req->status_detail?->value,
            'revision_count' => $req->revision_count,
            'submitted_at' => $req->submitted_at?->toIso8601String(),
            'events_count' => $req->events->count(),
        ]);

        // Build stepper data (only for processing/completed requests)
        $steps = PermohonanStatus::getStepperLabels();
        $currentStepIndex = $req->status->getStepIndex();
        
        // Safe default: if step index is null but request is processing/completed, default to step 0
        if ($currentStepIndex === null && ($req->isProcessing() || $req->status === PermohonanStatus::SELESAI)) {
            $currentStepIndex = 0;
        }
        
        $shouldShowStepper = $req->isProcessing() || $req->status === PermohonanStatus::SELESAI;
        
        // Check if payment is needed (only if not already paid)
        $showPaymentCTA = ($req->status === PermohonanStatus::PEMBAYARAN) && 
                          ($req->status_detail !== PermohonanDetailStatus::PEMBAYARAN_SUKSES);

        $payload = $req->payload_json ?? [];
        $lokasi  = data_get($payload, 'lokasi', []);
        
        // Fetch timeline events safely (Fallback if table not exists)
        $events = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('service_request_events')) {
            $events = $req->events;
        }

        return view('pelanggan.monitoring.show', compact('req', 'steps', 'currentStepIndex', 'shouldShowStepper', 'showPaymentCTA', 'payload', 'lokasi', 'events'));
    }

    /**
     * Generate payment session (QR token) dan redirect ke halaman QRIS.
     * TIDAK langsung memproses pembayaran sukses.
     * Pembayaran sukses hanya dari callback scan QR (QrisSimulatorController).
     */
    public function simulatePayment($id)
    {
        $sr = ServiceRequest::where('submitter_user_id', Auth::guard('web')->id())
            ->where('status', PermohonanStatus::PEMBAYARAN)
            ->findOrFail($id);

        if (!$sr->canRetryPayment()) {
            return back()->with('error', 'Tidak dapat melakukan pembayaran.');
        }

        try {
            $token = null;

            \Illuminate\Support\Facades\DB::transaction(function () use ($sr, &$token) {
                // 🔴 FIX: Nonaktifkan payment record lama (jika ada) dengan null-kan token-nya
                // agar tidak conflict dengan record baru
                \App\Models\Payment::where('service_request_id', $sr->id)
                    ->where('status', '!=', 'SUCCESS')
                    ->update([
                        'payment_token' => null,
                        'status' => 'EXPIRED',
                    ]);

                // Generate UUID token
                $token = (string) \Illuminate\Support\Str::uuid();

                // Gunakan amount dari billing yang sudah digenerate oleh adminAccept()
                $payload = $sr->payload_json ?? [];
                $billingTotal = data_get($payload, 'dummy_billing.total', 0);

                // 🔴 FIX: Buat record BARU, bukan update record lama (firstOrNew)
                // Agar tidak ada sisa data expired yang mengganggu
                $payment = \App\Models\Payment::create([
                    'service_request_id' => $sr->id,
                    'payment_token' => $token,
                    'status' => 'PENDING',
                    'amount' => $billingTotal > 0 ? $billingTotal : 500000,
                    'ref_no' => null,
                    'paid_at' => null,
                    'expired_at' => now()->addSeconds(120), // 120 detik (2 menit)
                ]);
            });

            // Redirect ke halaman QRIS
            return redirect()->route('qris.show', ['token' => $token]);
        } catch (\Throwable $e) {
            Log::error('simulatePayment generate session failed', [
                'service_request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuat session pembayaran: ' . $e->getMessage());
        }
    }
}
