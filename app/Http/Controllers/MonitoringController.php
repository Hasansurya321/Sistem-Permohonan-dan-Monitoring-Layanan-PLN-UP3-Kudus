<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ServiceRequest;
use App\Models\Payment;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->role !== 'pelanggan') {
            return redirect()->route('landing');
        }

        // Determine active tab with smart default
        $tab = $request->get('tab');
        
        // If no tab specified, default to processing, unless empty then fallback to waiting
        if (!$tab) {
            $processingCount = ServiceRequest::where('submitter_user_id', Auth::id())->processing()->count();
            $tab = $processingCount > 0 ? 'processing' : 'waiting';
        }

        // Base query
        $query = ServiceRequest::with(['applicant'])
            ->where('submitter_user_id', Auth::id());

        // Filter by tab
        switch($tab) {
            case 'waiting':
                $requests = $query->waiting()->latest('updated_at')->get();
                break;
            
            case 'processing':
                $requests = $query->processing()->latest('submitted_at')->get();
                break;
            
            case 'done':
                $requests = $query->done()
                    ->orderByRaw('COALESCE(completed_at, cancelled_at, updated_at) DESC')
                    ->get();
                break;
            
            default:
                $requests = $query->processing()->latest('updated_at')->get();
                $tab = 'processing';
        }

        $counts = [
            'waiting' => ServiceRequest::where('submitter_user_id', Auth::id())->waiting()->count(),
            'processing' => ServiceRequest::where('submitter_user_id', Auth::id())->processing()->count(),
            'done' => ServiceRequest::where('submitter_user_id', Auth::id())->done()->count(),
        ];

        return view('pelanggan.monitoring.index', compact('requests', 'tab', 'counts'));
    }

    public function show($id)
    {
        $req = ServiceRequest::with(['applicant'])
            ->where('submitter_user_id', Auth::id())
            ->findOrFail($id);

        // Build stepper data (only for processing/completed requests)
        $steps = PermohonanStatus::getStepperLabels();
        $currentStepIndex = $req->status->getStepIndex();
        
        // Safe default: if step index is null but request is processing/completed, default to step 0 (DITERIMA_PLN)
        if ($currentStepIndex === null && ($req->isProcessing() || $req->status === PermohonanStatus::SELESAI)) {
            $currentStepIndex = 0;
        }
        
        $shouldShowStepper = $req->isProcessing() || $req->status === PermohonanStatus::SELESAI;
        
        // Check if payment is needed (only if not already paid)
        $showPaymentCTA = ($req->status === PermohonanStatus::MENUNGGU_PEMBAYARAN) && 
                          ($req->status_detail !== PermohonanDetailStatus::PEMBAYARAN_SELESAI);

        $payload = $req->payload_json ?? [];
        $lokasi  = data_get($payload, 'lokasi', []);

        return view('pelanggan.monitoring.show', compact('req', 'steps', 'currentStepIndex', 'shouldShowStepper', 'showPaymentCTA', 'payload', 'lokasi'));
    }
    public function simulatePayment($id)
    {
        $req = ServiceRequest::where('submitter_user_id', Auth::id())
            ->where('status', PermohonanStatus::MENUNGGU_PEMBAYARAN)
            ->findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($req) {
            // Success simulation
            $req->transitionTo(
                PermohonanStatus::MENUNGGU_PEMBAYARAN,
                PermohonanDetailStatus::PEMBAYARAN_SELESAI
            );

            // Create Payment record
            Payment::updateOrCreate(
                ['service_request_id' => $req->id],
                [
                    'amount' => 500000, // Dummy
                    'status' => 'SUKSES',
                    'transaction_id' => 'TRX-' . time() . '-' . $req->id
                ]
            );
        });

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi (Simulasi).');
    }
}
