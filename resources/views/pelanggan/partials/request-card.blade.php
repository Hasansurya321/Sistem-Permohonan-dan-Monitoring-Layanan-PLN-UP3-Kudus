@props(['req'])

@php
    $isDraft     = $req->isDraft();
    $isCancelled = $req->cancelled_at !== null;
    $isSelesai   = $req->status === \App\Enums\PermohonanStatus::SELESAI;
    $isMenungguBayar = $req->status === \App\Enums\PermohonanStatus::PEMBAYARAN
                       && $req->status_detail !== \App\Enums\PermohonanDetailStatus::PEMBAYARAN_SELESAI;

    // Status display
    if ($isCancelled) {
        $badgeClass = 'bg-red-50 text-red-700 border-red-200';
        $faIcon     = 'fa-circle-xmark';
        $statusLabel = 'Dibatalkan';
    } elseif ($isSelesai) {
        $badgeClass = 'bg-green-50 text-green-700 border-green-200';
        $faIcon     = 'fa-circle-check';
        $statusLabel = 'Selesai';
    } elseif ($isDraft) {
        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
        $faIcon     = 'fa-pencil';
        $statusLabel = 'Draft';
    } else {
        $badgeClass  = \App\Support\WorkflowStatusHelper::tailwindBadgeClass($req->status);
        $faIcon      = \App\Support\WorkflowStatusHelper::faIcon($req->status);
        $detailLabel = $req->status_detail?->getLabel();
        $statusLabel = $detailLabel ?? $req->status->getLabel();
    }

    $progressPct = !$isDraft && !$isCancelled
        ? \App\Support\WorkflowStatusHelper::progressPercent($req->status)
        : 0;

    $displayDate = $isCancelled
        ? $req->cancelled_at
        : ($isDraft ? ($req->last_saved_at ?? $req->updated_at) : $req->submitted_at);
    $dateLabel = $isCancelled ? 'Dibatalkan:' : ($isDraft ? 'Disimpan:' : 'Submit:');
@endphp

<div class="group relative bg-white rounded-2xl border transition-all duration-200 shadow-sm overflow-hidden
            {{ $isCancelled ? 'border-red-100 hover:border-red-200 hover:shadow-red-50 hover:shadow-md'
               : ($isSelesai ? 'border-green-100 hover:border-green-200 hover:shadow-green-50 hover:shadow-md'
               : ($isMenungguBayar ? 'border-orange-200 hover:shadow-orange-50 hover:shadow-md ring-1 ring-orange-200'
               : 'border-slate-200 hover:border-blue-200 hover:shadow-blue-50 hover:shadow-md')) }}">

    {{-- Progress bar strip on top --}}
    @if(!$isDraft && !$isCancelled)
    <div class="h-1 w-full bg-slate-100">
        <div class="h-full transition-all duration-700 rounded-r-full
                    {{ $isSelesai ? 'bg-green-500' : 'bg-gradient-to-r from-[#2F5AA8] to-blue-400' }}"
             style="width: {{ $progressPct }}%"></div>
    </div>
    @endif

    {{-- Payment Alert Banner --}}
    @if($isMenungguBayar)
    <div class="bg-orange-50 border-b border-orange-200 px-5 py-2 flex items-center gap-2">
        <i class="fas fa-exclamation-triangle text-orange-500 text-xs"></i>
        <span class="text-xs font-bold text-orange-700">Tagihan menunggu pembayaran Anda</span>
    </div>
    @endif

    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">

        {{-- Left: Info utama --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center flex-wrap gap-2 mb-2">
                {{-- Status badge --}}
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                    <i class="fas {{ $faIcon }} text-[10px]"></i>
                    {{ $statusLabel }}
                </span>
                {{-- Request number --}}
                <span class="text-xs font-mono text-slate-400">
                    {{ $isDraft ? ($req->draft_number ?? 'DRAFT') : ($req->nomor_permohonan ?? 'REQ-???') }}
                </span>
            </div>

            <h3 class="font-bold text-slate-800 text-base truncate">
                @switch($req->jenis_layanan)
                    @case('TAMBAH_DAYA')
                        Tambah Daya
                        @break
                    @case('PASANG_BARU')
                        Pasang Baru
                        @break
                    @default
                        {{ str_replace('_', ' ', $req->jenis_layanan ?? 'Permohonan') }}
                @endswitch
            </h3>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-slate-400">
                @if($req->applicant?->nama_lengkap)
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-user text-slate-300"></i>
                    {{ $req->applicant->nama_lengkap }}
                </span>
                @endif
                @if($displayDate)
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-calendar text-slate-300"></i>
                    {{ $dateLabel }}
                    {{ $displayDate instanceof \Carbon\Carbon ? \App\Helpers\WaktuHelper::formatPendek($displayDate) : $displayDate }}
                </span>
                @endif
                @if($req->daya_baru && !$isDraft)
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-bolt text-slate-300"></i>
                    {{ number_format($req->daya_baru, 0, ',', '.') }} VA
                </span>
                @endif
            </div>
        </div>

        {{-- Right: Action --}}
        <div class="flex items-center gap-3 shrink-0">
            @if($isDraft)
                <form action="{{ route('tambah-daya.cancel', $req->id) }}" method="POST"
                      onsubmit="return confirm('Hapus draft ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs font-medium text-red-400 hover:text-red-600 transition-colors px-3 py-1.5">
                        Hapus
                    </button>
                </form>
                <a href="{{ route('tambah-daya.resume', $req->id) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#2F5AA8] text-white text-sm font-bold rounded-xl hover:bg-[#274C8E] transition shadow-sm shadow-blue-200">
                    Lanjutkan <i class="fas fa-arrow-right text-xs"></i>
                </a>
            @elseif($isCancelled)
                <a href="{{ route('monitoring.show', $req->id) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-200 transition border border-slate-200">
                    Lihat Detail
                </a>
            @else
                <a href="{{ route('monitoring.show', $req->id) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-xl transition shadow-sm
                          {{ $isMenungguBayar
                             ? 'bg-orange-500 text-white hover:bg-orange-600 shadow-orange-200'
                             : 'bg-[#2F5AA8] text-white hover:bg-[#274C8E] shadow-blue-200' }}">
                    {{ $isMenungguBayar ? 'Bayar Sekarang' : 'Lihat Tracking' }}
                    <i class="fas {{ $isMenungguBayar ? 'fa-credit-card' : 'fa-arrow-right' }} text-xs"></i>
                </a>
            @endif
        </div>
    </div>

    {{-- Mini stepper dots (only for processing) --}}
    @if(!$isDraft && !$isCancelled && !$isSelesai && $req->status->getStepIndex() !== null)
    @php
        $stepLabels = \App\Enums\PermohonanStatus::getStepperLabels();
        $currentIdx = $req->status->getStepIndex();
    @endphp
    <div class="px-5 pb-4">
        <div class="flex items-center gap-1">
            @foreach($stepLabels as $i => $label)
                <div class="flex-1 h-1 rounded-full
                            {{ $i < $currentIdx ? 'bg-green-400' : ($i === $currentIdx ? 'bg-[#2F5AA8]' : 'bg-slate-100') }}"
                     title="{{ $label }}"></div>
            @endforeach
        </div>
        <div class="flex justify-between mt-1">
            <span class="text-[10px] text-slate-400">Diterima PLN</span>
            <span class="text-[10px] text-slate-400">Selesai</span>
        </div>
    </div>
    @elseif($isSelesai)
    <div class="px-5 pb-4">
        <div class="h-1 w-full bg-green-400 rounded-full"></div>
        <p class="text-[10px] text-green-600 mt-1 font-medium text-center">
            <i class="fas fa-check-circle"></i> Semua tahap selesai
        </p>
    </div>
    @endif
</div>
