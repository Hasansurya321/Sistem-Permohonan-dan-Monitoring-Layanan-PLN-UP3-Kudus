@extends('layouts.admin-monitoring')

@section('content')
    @include('components.monitoring.detail-content', [
        'sr' => $sr,
        'backUrl' => $backUrl,
        'showPaymentCTA' => $showPaymentCTA,
        'events' => $events,
        'lokasi' => $lokasi,
    ])
@endsection