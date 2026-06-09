@php
    $isCancelled  = $sr->cancelled_at !== null;
    $isDraft      = $sr->isDraft();
    $isSelesai    = $sr->status === \App\Enums\PermohonanStatus::SELESAI;
    $progressPct  = \App\Support\WorkflowStatusHelper::progressPercent($sr->status);
    $faIcon       = \App\Support\WorkflowStatusHelper::faIcon($sr->status);
    $statusLabel  = $sr->status_detail?->getLabel() ?? $sr->status->getLabel();
@endphp

<div class="relative overflow-hidden rounded-2xl mb-6
            {{ $isCancelled ? 'bg-gradient-to-br from-red-600 to-red-800' : ($isSelesai ? 'bg-gradient-to-br from-green-600 to-emerald-700' : 'bg-gradient-to-br from-[#1a3a6e] to-[#2F5AA8]') }}
            shadow-lg text-white">
    <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-white opacity-[0.04] -translate-y-1/3 translate-x-1/3"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full bg-white opacity-[0.04] translate-y-1/2 -translate-x-1/4"></div>

    <div class="relative z-10 px-6 py-7 md:px-8 md:py-8 flex flex-col md:flex-row md:items-center justify-between gap-5">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold mb-3
                         {{ $isCancelled ? 'bg-red-500/30 text-red-100 border border-red-400/30' : ($isSelesai ? 'bg-green-500/30 text-green-100 border border-green-400/30' : 'bg-white/15 text-blue-100 border border-white/20') }}">
                <i class="fas {{ $faIcon }} text-[10px]"></i>
                {{ $statusLabel }}
            </div>
            <h1 class="text-xl md:text-2xl font-extrabold tracking-tight">
                {{ str_replace('_', ' ', $sr->jenis_layanan ?? 'Permohonan Layanan') }}
            </h1>
            <p class="text-white/60 text-sm mt-1 font-mono">
                {{ $isDraft ? ($sr->draft_number ?? 'DRAFT') : ($sr->nomor_permohonan ?? '—') }}
            </p>
        </div>

        <div class="flex flex-col items-start md:items-end gap-2">
            @if($sr->daya_baru)
                <div class="text-right">
                    <div class="text-3xl font-black tracking-tight">{{ number_format($sr->daya_baru, 0, ',', '.') }}</div>
                    <div class="text-white/50 text-xs font-medium uppercase tracking-widest">VA</div>
                </div>
            @endif
            @if(!$isDraft && !$isCancelled && !$isSelesai)
                <div class="w-full md:w-48">
                    <div class="flex justify-between text-xs text-white/60 mb-1.5">
                        <span>Progress</span>
                        <span class="font-bold text-white">{{ $progressPct }}%</span>
                    </div>
                    <div class="h-1.5 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white/80 rounded-full transition-all duration-700 ease-out"
                             style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>
            @elseif($isSelesai)
                <div class="flex items-center gap-2 text-green-200 text-sm font-semibold">
                    <i class="fas fa-circle-check text-green-300"></i>
                    Selesai {{ \App\Helpers\WaktuHelper::formatTanggal($sr->completed_at) }}
                </div>
            @endif
        </div>
    </div>
</div>