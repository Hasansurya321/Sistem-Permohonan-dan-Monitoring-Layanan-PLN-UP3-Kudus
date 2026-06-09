@extends('layouts.pelanggan')

@section('content')
    @include('components.monitoring.detail-content', [
        'sr' => $req,
        'backUrl' => route('monitoring'),
        'showPaymentCTA' => ($req->status === \App\Enums\PermohonanStatus::PEMBAYARAN) &&
                            ($req->status_detail !== \App\Enums\PermohonanDetailStatus::PEMBAYARAN_SUKSES),
        'events' => $req->events,
        'lokasi' => data_get($req->payload_json, 'lokasi', []),
        'steps' => \App\Enums\PermohonanStatus::getStepperLabels(),
        'currentStepIndex' => $req->status->getStepIndex(),
        'shouldShowStepper' => $req->isProcessing() || $req->status === \App\Enums\PermohonanStatus::SELESAI,
    ])
@endsection