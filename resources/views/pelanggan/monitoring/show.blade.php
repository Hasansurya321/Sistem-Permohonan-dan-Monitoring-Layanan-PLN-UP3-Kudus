@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    {{-- Back Button --}}
    <a href="{{ route('monitoring') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-800 mb-6 font-medium">
        <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
    </a>

    {{-- Status Badge --}}
    <div class="mb-6">
        <span class="px-4 py-2 rounded-full text-sm font-bold border inline-flex items-center gap-2
                     {{ $req->isDraft() ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : '' }}
                     {{ $req->isProcessing() ? 'bg-blue-50 text-[#2F5AA8] border-blue-200' : '' }}
                     {{ $req->isDone() ? 'bg-green-50 text-green-700 border-green-200' : '' }}">
            {{-- Static SVG Icon --}}
            @if($req->isDraft())
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M16.862 3.487a2.5 2.5 0 0 1 3.536 3.536L7.5 19.92l-4.5 1 1-4.5L16.862 3.487Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            @elseif($req->isProcessing())
                @if($req->status === App\Enums\PermohonanStatus::MENUNGGU_PEMBAYARAN)
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M7 14h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                @else
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                @endif
            @else
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                </svg>
            @endif
            {{ $req->status_detail ? $req->status_detail->getLabel() : $req->status->getLabel() }}
        </span>
    </div>

    {{-- Payment CTA (if applicable) --}}
    @if($showPaymentCTA)
        <div class="mb-6 p-6 bg-amber-50 border border-amber-200 rounded-xl">
            <div class="flex items-start gap-4">
                <i class="fas fa-exclamation-circle text-2xl text-amber-600 mt-1"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-amber-900 mb-1">Pembayaran Diperlukan</h3>
                    <p class="text-amber-700 text-sm mb-3">Permohonan Anda sedang menunggu pembayaran. Silakan selesaikan pembayaran untuk melanjutkan proses.</p>
                    <form action="{{ route('monitoring.pay', $req->id) }}" method="POST" onsubmit="return confirm('Mulai simulasi pembayaran?')">
                        @csrf
                        <button type="submit" class="px-6 py-3 bg-amber-400 text-amber-900 font-bold rounded-lg hover:bg-amber-500 transition shadow-md">
                            <i class="fas fa-credit-card mr-2"></i> Bayar Sekarang (Simulasi)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Stepper (only for processing/completed requests) --}}
    @if($shouldShowStepper && $currentStepIndex !== null)
        <x-monitoring.stepper :steps="$steps" :currentIndex="$currentStepIndex" />
        <div class="h-6"></div>
    @endif

    {{-- Cancellation Notice (if cancelled) --}}
    @if($req->cancelled_at)
        <div class="mb-6 p-6 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-start gap-4">
                <i class="fas fa-times-circle text-2xl text-red-600 mt-1"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-red-900 mb-1">Permohonan Dibatalkan</h3>
                    <p class="text-red-700 text-sm mb-2">Permohonan ini telah dibatalkan oleh admin pada {{ $req->cancelled_at->translatedFormat('d F Y, H:i') }}</p>
                    @if($req->cancellation_reason)
                        <div class="mt-3 p-3 bg-white rounded border border-red-200">
                            <p class="text-sm text-slate-700"><strong>Alasan:</strong> {{ $req->cancellation_reason }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Data Sections -->
    <div class="space-y-6">
        <!-- 1. Data Pemohon -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Data Pemohon</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block text-slate-500 mb-1">Nama Lengkap</label>
                    <div class="font-semibold">{{ $req->applicant?->nama_lengkap ?? '-' }}</div>
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">NIK</label>
                    <div class="font-semibold">{{ $req->applicant_nik }}</div>
                </div>
            </div>
        </div>

        <!-- 2. Lokasi -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Lokasi</h3>
            <div class="text-sm">
                @php
                    $hasLokasi = !empty($lokasi) && (
                        !empty($lokasi['provinsi'] ?? null) ||
                        !empty($lokasi['kab_kota'] ?? null) ||
                        !empty($lokasi['kecamatan'] ?? null) ||
                        !empty($lokasi['kelurahan'] ?? null) ||
                        !empty($lokasi['rt'] ?? null) ||
                        !empty($lokasi['rw'] ?? null) ||
                        !empty($lokasi['koordinat'] ?? null) ||
                        !empty($lokasi['alamat_detail'] ?? null)
                    );
                @endphp

                @if($hasLokasi)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs text-slate-500">Koordinat</div>
                            <div class="text-slate-800 font-semibold">{{ $lokasi['koordinat'] ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Provinsi</div>
                            <div class="text-slate-800 font-semibold">{{ $lokasi['provinsi'] ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Kab/Kota</div>
                            <div class="text-slate-800 font-semibold">{{ $lokasi['kab_kota'] ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Kecamatan</div>
                            <div class="text-slate-800 font-semibold">{{ $lokasi['kecamatan'] ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Kelurahan/Desa</div>
                            <div class="text-slate-800 font-semibold">{{ $lokasi['kelurahan'] ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">RT / RW</div>
                            <div class="text-slate-800 font-semibold">
                                {{ $lokasi['rt'] ?? '-' }} / {{ $lokasi['rw'] ?? '-' }}
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="text-xs text-slate-500">Detail tambahan</div>
                            <div class="text-slate-800 font-semibold">{{ $lokasi['alamat_detail'] ?? '-' }}</div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-col items-center py-4 text-slate-400 italic">
                        <i class="fas fa-map-marked-alt text-2xl mb-2"></i>
                        <span>Detail lokasi belum diisi atau tidak tersedia.</span>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- 3. Layanan -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Data Layanan</h3>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <label class="block text-slate-500 mb-1">Jenis Layanan</label>
                    <div class="font-semibold">{{ $req->jenis_layanan }}</div>
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Daya Baru</label>
                    <div class="font-semibold text-lg text-[#2F5AA8]">{{ number_format($req->daya_baru, 0, ',', '.') }} VA</div>
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Produk</label>
                    <div class="font-semibold">{{ $req->jenis_produk }}</div>
                </div>
                <div>
                    <label class="block text-slate-500 mb-1">Peruntukan</label>
                    <div class="font-semibold">{{ $req->peruntukan_koneksi }}</div>
                </div>
            </div>
        </div>
        
        <!-- SLO Info (If Available) -->
        @if($req->slo_no_registrasi)
         <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Data SLO</h3>
            <div class="text-sm">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div>
                        <label class="block text-slate-500 mb-1">No Registrasi</label>
                        <div class="font-mono text-slate-700">{{ $req->slo_no_registrasi }}</div>
                     </div>
                     <div>
                        <label class="block text-slate-500 mb-1">No Sertifikat</label>
                        <div class="font-mono text-slate-700">{{ $req->slo_no_sertifikat }}</div>
                     </div>
                 </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
