<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;

class MonitoringController extends Controller
{
    public function showDetail($id)
    {
        $sr = ServiceRequest::with([
            'applicant',
            'events' => fn($q) => $q->orderBy('occurred_at', 'desc'),
            'payments',
            'submitter.masterPelanggan'
        ])->findOrFail($id);

        // Pastikan minimal ada 1 event (sama dengan controller pelanggan)
        $sr->ensureInitialEvent();

        return view('admin.monitoring.detail', [
            'sr' => $sr,
            'backUrl' => url('/internal/admin-layanan/monitoring'),
            'showPaymentCTA' => false,
            'events' => $sr->events,
            'lokasi' => data_get($sr->payload_json, 'lokasi', []),
        ]);
    }
}