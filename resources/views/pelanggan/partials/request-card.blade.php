@props(['req'])

@php
    $isDraft = $req->isDraft();
    $isCancelled = $req->cancelled_at !== null;
    
    if ($isCancelled) {
        $statusLabel = 'Dibatalkan';
        $statusClasses = 'bg-red-50 text-red-700 border-red-200';
        $statusIcon = 'x-circle';
    } else {
        $mainStatus = $req->status->getLabel();
        $detailEnum = $req->status_detail; // Already tryFrom via accessor
        $detailLabel = $detailEnum?->getLabel();
        
        // If detail is null but we have a raw value in the DB (accessed via getRawOriginal or if we need to see dirty data)
        // However, with the accessor it's safer.
        $statusLabel = $detailLabel ? "{$mainStatus} : {$detailLabel}" : $mainStatus;
        $statusClasses = $isDraft 
            ? 'bg-yellow-50 text-yellow-700 border-yellow-200' 
            : 'bg-blue-50 text-[#2F5AA8] border-blue-200';
        
        // Determine icon based on status
        if ($isDraft) {
            $statusIcon = 'pencil';
        } elseif ($req->status === App\Enums\PermohonanStatus::MENUNGGU_PEMBAYARAN) {
            $statusIcon = 'credit-card';
        } else {
            $statusIcon = 'clock';
        }
    }
    
    $displayDate = $isCancelled 
        ? $req->cancelled_at 
        : ($isDraft ? ($req->last_saved_at ?? $req->updated_at) : $req->submitted_at);
    $dateLabel = $isCancelled 
        ? 'Dibatalkan pada:' 
        : ($isDraft ? 'Terakhir disimpan:' : 'Tanggal Submit:');
@endphp

<div class="bg-white rounded-xl border {{ $isDraft ? 'border-yellow-200' : 'border-slate-200' }} p-6 shadow-sm hover:shadow-md transition group relative overflow-hidden">
    @if($isDraft)
        <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-yellow-100 to-transparent -mr-8 -mt-8 rounded-full blur-xl opacity-50"></div>
    @endif

    <div class="flex flex-col md:flex-row justify-between md:items-center gap-6 relative z-10">
        
        <!-- Info Utama -->
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-3 mb-1">
                <span class="px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-2 {{ $statusClasses }}">
                    {{-- Static SVG Icons --}}
                    @if($statusIcon === 'pencil')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16.862 3.487a2.5 2.5 0 0 1 3.536 3.536L7.5 19.92l-4.5 1 1-4.5L16.862 3.487Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @elseif($statusIcon === 'credit-card')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 7h18v10H3V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M3 10h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M7 14h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    @elseif($statusIcon === 'clock')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    @elseif($statusIcon === 'x-circle')
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 9l6 6m0-6l-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    @endif
                    {{ $statusLabel }}
                </span>
                <span class="text-xs text-slate-400 font-medium font-mono">
                    {{ $isDraft ? ($req->draft_number ?? 'DRAFT') : ($req->nomor_permohonan ?? 'REQ-???') }}
                </span>
            </div>
            
            <h3 class="font-bold text-slate-800 text-lg md:text-xl">
                {{ str_replace('_', ' ', $req->jenis_layanan ?? 'Tambah Daya') }}
            </h3>
            
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-slate-500">
                <div class="flex items-center gap-2" title="Nama Pemohon">
                    <i class="fas fa-user-circle text-slate-400"></i>
                    {{ $req->applicant?->nama_lengkap ?? ($req->applicant_nik . ' (Draft)') }}
                </div>
                @if($displayDate)
                <div class="flex items-center gap-2" title="{{ $dateLabel }}">
                    <i class="fas fa-calendar-day text-slate-400"></i>
                    {{ $displayDate instanceof \Carbon\Carbon ? $displayDate->translatedFormat('d M Y, H:i') : $displayDate }}
                </div>
                @endif
            </div>
        </div>

        <!-- Detail Teknis & Aksi -->
        <div class="flex flex-col md:items-end gap-4">
            @if(!$isDraft)
             <div class="text-left md:text-right">
                 <div class="font-bold text-slate-700 text-lg">
                    {{ number_format($req->daya_baru, 0, ',', '.') }} VA
                 </div>
                 <div class="text-slate-500 text-xs">
                    {{ $req->lokasi_kecamatan ?? '-' }}, {{ $req->lokasi_kab_kota ?? '-' }}
                 </div>
             </div>
            @endif

            <div class="flex items-center gap-3">
                @if($isDraft)
                    <form action="{{ route('tambah-daya.cancel', $req->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan dan menghapus draft ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-red-500 font-semibold text-sm hover:underline">
                            Hapus
                        </button>
                    </form>
                    <a href="{{ route('tambah-daya.resume', $req->id) }}" class="px-5 py-2.5 bg-[#2F5AA8] text-white font-bold rounded-lg hover:bg-[#274C8E] transition shadow-lg shadow-blue-900/20 text-sm flex items-center gap-2">
                        Lanjutkan <i class="fas fa-arrow-right"></i>
                    </a>
                @elseif($isCancelled)
                    <a href="{{ route('monitoring.show', $req->id) }}" class="px-5 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-lg hover:bg-slate-200 transition border border-slate-200 text-sm">
                        Lihat Detail
                    </a>
                @else
                    <a href="{{ route('monitoring.show', $req->id) }}" class="px-5 py-2.5 bg-[#2F5AA8] text-white font-bold rounded-lg hover:bg-[#274C8E] transition shadow-lg text-sm">
                        Lihat Tracking
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
