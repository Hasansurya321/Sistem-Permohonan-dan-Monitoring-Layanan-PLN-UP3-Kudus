@extends('layouts.pelanggan')

@php
    use App\Enums\PermohonanStatus;
@endphp

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- ═══ PAGE HEADER ═══════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900">Monitoring Layanan</h1>
            <p class="text-slate-400 text-sm mt-1">Pantau status permohonan layanan listrik Anda secara real-time</p>
        </div>
        <a href="{{ route('tambah-daya.step1') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2F5AA8] text-white font-bold rounded-xl hover:bg-[#274C8E] transition shadow-md shadow-blue-200 text-sm shrink-0">
            <i class="fas fa-plus"></i> Ajukan Layanan
        </a>
    </div>

    {{-- ═══ FLASH SUCCESS ══════════════════════════════════════════════════════ --}}
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 flex items-center gap-3">
        <i class="fas fa-circle-check text-xl text-green-500"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ═══ STATS STRIP ════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 text-center">
            <p class="text-2xl font-extrabold text-amber-600">{{ $counts['waiting'] }}</p>
            <p class="text-xs text-slate-400 font-medium mt-1">Draft</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $counts['processing'] > 0 ? 'border-blue-200 ring-1 ring-blue-100' : 'border-slate-200' }} shadow-sm p-4 text-center">
            <p class="text-2xl font-extrabold {{ $counts['processing'] > 0 ? 'text-[#2F5AA8]' : 'text-slate-300' }}">{{ $counts['processing'] }}</p>
            <p class="text-xs text-slate-400 font-medium mt-1">Sedang Proses</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $counts['done'] > 0 ? 'border-green-200' : 'border-slate-200' }} shadow-sm p-4 text-center">
            <p class="text-2xl font-extrabold {{ $counts['done'] > 0 ? 'text-green-600' : 'text-slate-300' }}">{{ $counts['done'] }}</p>
            <p class="text-xs text-slate-400 font-medium mt-1">Selesai</p>
        </div>
    </div>

    {{-- ═══ TABS NAVIGATION ════════════════════════════════════════════════════ --}}
    <x-monitoring.tabs :activeTab="$tab" :counts="$counts" />

    {{-- ═══ REQUEST LIST ════════════════════════════════════════════════════════ --}}
    <div class="space-y-4">
        @forelse($requests as $req)
            @include('pelanggan.partials.request-card', ['req' => $req])
        @empty
            @if($tab === 'waiting')
                <div class="flex flex-col items-center justify-center py-20 bg-amber-50/50 rounded-2xl border border-dashed border-amber-200">
                    <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <i class="fas fa-pencil text-2xl text-amber-400"></i>
                    </div>
                    <p class="text-amber-800 font-bold text-lg mb-1">Tidak ada draft</p>
                    <p class="text-amber-600 text-sm text-center max-w-xs">Draft akan muncul saat Anda mulai mengisi permohonan baru dan belum menyelesaikannya.</p>
                </div>
            @elseif($tab === 'processing')
                <div class="flex flex-col items-center justify-center py-20 bg-blue-50/50 rounded-2xl border border-dashed border-blue-200">
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                        <i class="fas fa-spinner fa-spin text-2xl text-blue-400"></i>
                    </div>
                    <p class="text-blue-800 font-bold text-lg mb-1">Belum ada permohonan aktif</p>
                    <p class="text-blue-600 text-sm text-center max-w-xs mb-4">Ajukan permohonan layanan listrik Anda sekarang.</p>
                    <a href="{{ route('tambah-daya.step1') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#2F5AA8] text-white font-bold rounded-xl hover:bg-[#274C8E] transition text-sm">
                        <i class="fas fa-plus"></i> Ajukan Sekarang
                    </a>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-20 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                        <i class="fas fa-clipboard-check text-2xl text-slate-400"></i>
                    </div>
                    <p class="text-slate-600 font-bold text-lg mb-1">Belum ada yang selesai</p>
                    <p class="text-slate-400 text-sm text-center max-w-xs">Riwayat permohonan yang telah selesai akan tampil di sini.</p>
                </div>
            @endif
        @endforelse
    </div>

</div>
@endsection
