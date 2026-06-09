@php
    $backUrl = $backUrl ?? route('monitoring');
    $showPaymentCTA = $showPaymentCTA ?? false;
    $events = $events ?? $sr->events;
    $lokasi = $lokasi ?? data_get($sr->payload_json, 'lokasi', []);
    $steps = $steps ?? \App\Enums\PermohonanStatus::getStepperLabels();
    $currentStepIndex = $currentStepIndex ?? $sr->status?->getStepIndex() ?? 0;
    $shouldShowStepper = $shouldShowStepper ?? ($sr->isProcessing() || $sr->status === \App\Enums\PermohonanStatus::SELESAI);

    $isCancelled  = $sr->cancelled_at !== null;
    $isDraft      = $sr->isDraft();
    $isSelesai    = $sr->status === \App\Enums\PermohonanStatus::SELESAI;
    $progressPct  = \App\Support\WorkflowStatusHelper::progressPercent($sr->status);
    $faIcon       = \App\Support\WorkflowStatusHelper::faIcon($sr->status);
    $statusLabel  = $sr->status_detail?->getLabel() ?? $sr->status->getLabel();
@endphp

<div class="max-w-5xl mx-auto">
    {{-- ═══ BACK + BREADCRUMB ════════════════════════════════════════════════ --}}
    <div class="mb-5">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#2F5AA8] font-medium transition-colors group">
            <i class="fas fa-arrow-left text-xs group-hover:-translate-x-0.5 transition-transform"></i>
            Kembali ke Monitoring
        </a>
    </div>

    {{-- ═══ HERO HEADER ═══════════════════════════════════════════════════════ --}}
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
                    @switch($sr->jenis_layanan)
                        @case('TAMBAH_DAYA')
                            Tambah Daya
                            @break
                        @case('PASANG_BARU')
                            Pasang Baru
                            @break
                        @default
                            {{ str_replace('_', ' ', $sr->jenis_layanan ?? 'Permohonan Layanan') }}
                    @endswitch
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

    {{-- ═══ PAYMENT CTA ════════════════════════════════════════════════════════ --}}
    @if($showPaymentCTA)
    <div class="mb-6 rounded-2xl overflow-hidden border-2 border-orange-300 shadow-orange-100 shadow-lg">
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 text-white">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-credit-card text-sm"></i>
                </div>
                <h3 class="font-bold text-lg">Tagihan Menunggu Pembayaran</h3>
            </div>
            <p class="text-orange-100 text-sm ml-11">Selesaikan pembayaran untuk melanjutkan proses pemasangan instalasi listrik Anda.</p>
        </div>
        <div class="bg-orange-50 px-6 py-4 flex items-center justify-between flex-wrap gap-3">
            <div class="text-sm text-orange-700">
                <i class="fas fa-info-circle mr-1"></i>
                Klik tombol berikut untuk melakukan simulasi pembayaran
            </div>
            <a href="{{ route('pembayaran') }}"
               class="inline-flex items-center gap-2 px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-xl transition shadow-md shadow-orange-200 text-sm">
                <i class="fas fa-credit-card"></i>
                Bayar Sekarang
            </a>
        </div>
    </div>
    @endif

    {{-- ═══ CANCELLATION NOTICE ════════════════════════════════════════════════ --}}
    @if($isCancelled)
    <div class="mb-6 rounded-2xl overflow-hidden border border-red-200 bg-red-50">
        <div class="bg-red-500 text-white px-6 py-3 flex items-center gap-2">
            <i class="fas fa-times-circle"></i>
            <span class="font-bold">Permohonan Dibatalkan</span>
        </div>
        <div class="px-6 py-4">
            <p class="text-red-700 text-sm">
                Permohonan ini dibatalkan oleh admin pada
                <strong>{{ \App\Helpers\WaktuHelper::formatLengkap($sr->cancelled_at) }}</strong>.
            </p>
            @if($sr->cancellation_reason)
            <div class="mt-3 p-3 bg-white rounded-lg border border-red-200 text-sm text-slate-700">
                <strong class="text-red-700">Alasan:</strong> {{ $sr->cancellation_reason }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ═══ TWO-COLUMN LAYOUT: TIMELINE + DETAILS ══════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- ── LEFT: Timeline Stepper ─────────────────────────────────────── --}}
        @if($shouldShowStepper && $currentStepIndex !== null)
        <div class="lg:col-span-2">
            <x-monitoring.stepper
                :steps="$steps"
                :currentIndex="$currentStepIndex"
                :isCancelled="$isCancelled"
            />

            {{-- Selesai celebration card --}}
            @if($isSelesai)
            <div class="mt-4 p-5 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 text-center">
                <div class="w-14 h-14 rounded-full bg-green-500 text-white flex items-center justify-center text-2xl mx-auto mb-3 shadow-md shadow-green-200">
                    <i class="fas fa-bolt"></i>
                </div>
                <h4 class="font-bold text-green-800 text-lg mb-1">Daya Aktif!</h4>
                <p class="text-green-600 text-sm">Sambungan listrik Anda berhasil dipasang dan telah aktif.</p>
            </div>
            @endif
        </div>
        @endif

        {{-- ── RIGHT: Info + History ─────────────────────────────────────── --}}
        <div class="{{ $shouldShowStepper && $currentStepIndex !== null ? 'lg:col-span-3' : 'lg:col-span-5' }} space-y-5">

            {{-- ── Workflow History (Vertical Feed) ─────────────────────── --}}
            @if($events && $events->count() > 0)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
                 x-data="{ expanded: true }">
                <button @click="expanded = !expanded"
                        class="w-full flex items-center justify-between px-6 py-4 border-b border-slate-100 hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                        <span class="text-sm font-bold text-slate-700 uppercase tracking-widest">Riwayat Proses</span>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs rounded-full font-bold">
                            {{ $events->count() }} event
                        </span>
                    </div>
                    <i class="fas text-xs text-slate-400 transition-transform duration-200"
                       :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>

                <div x-show="expanded" x-collapse>
                    <div class="px-6 py-5">
                        <div class="relative">
                            @if($events->count() > 1)
                            <div class="absolute left-4 top-5 bottom-5 w-px bg-slate-100"></div>
                            @endif

                            <div class="space-y-5">
                                @foreach($events as $event)
                                @php
                                    $isFirst   = $loop->first;
                                    $evtLabel  = $event->status_detail?->getLabel() ?? $event->status->getLabel();
                                    $evtColor  = $isFirst ? 'bg-[#2F5AA8]' : 'bg-slate-200';
                                    $roleLabel = config('internal_roles.' . $event->updated_by_role . '.label', ucfirst(str_replace('_', ' ', $event->updated_by_role ?? 'Sistem')));
                                @endphp
                                <div class="relative flex items-start gap-4">
                                    <div class="relative z-10 shrink-0 w-8 h-8 rounded-full {{ $evtColor }} flex items-center justify-center shadow-sm">
                                        @if($isFirst)
                                            <i class="fas fa-star text-white text-[9px]"></i>
                                        @else
                                            <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                        @endif
                                    </div>

                                    <div class="flex-1 bg-{{ $isFirst ? 'blue-50 border-blue-100' : 'slate-50 border-slate-100' }} rounded-xl border p-4">
                                        <div class="flex items-start justify-between flex-wrap gap-2">
                                            <div>
                                                <p class="font-bold text-sm {{ $isFirst ? 'text-blue-800' : 'text-slate-700' }}">
                                                    {{ $evtLabel }}
                                                </p>
                                                @if($event->status_detail && $event->status_detail->getLabel() !== $event->status->getLabel())
                                                <p class="text-xs text-slate-400 mt-0.5">
                                                    {{ $event->status->getLabel() }}
                                                </p>
                                                @endif
                                            </div>
                                            <span class="text-xs font-mono text-slate-400 whitespace-nowrap">
                                                {{ \App\Helpers\WaktuHelper::formatLengkap($event->occurred_at) }}
                                            </span>
                                        </div>

                                        @if($event->note)
                                        <div class="mt-2 flex items-start gap-2 text-xs text-slate-500">
                                            <i class="fas fa-quote-left text-slate-300 mt-0.5 shrink-0"></i>
                                            <span class="italic">{{ $event->note }}</span>
                                        </div>
                                        @endif

                                        <div class="mt-2 flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                                                <i class="fas fa-user text-[8px] text-slate-500"></i>
                                            </div>
                                            <span class="text-xs text-slate-500">
                                                <span class="font-medium">{{ $event->updated_by_name ?? 'Sistem' }}</span>
                                                @if($event->updated_by_role)
                                                <span class="ml-1 px-1.5 py-0.5 bg-slate-200 text-slate-600 rounded text-[10px] font-medium">
                                                    {{ $roleLabel }}
                                                </span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Data Pemohon ────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Data Pemohon</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Nama Lengkap</p>
                        <p class="font-semibold text-slate-800">{{ $sr->applicant?->nama_lengkap ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">NIK</p>
                        <p class="font-mono text-slate-700">{{ $sr->applicant_nik }}</p>
                    </div>
                    @if($sr->applicant?->no_hp)
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">No. HP</p>
                        <p class="font-semibold text-slate-800">{{ $sr->applicant->no_hp }}</p>
                    </div>
                    @endif
                    @if($sr->submitted_at)
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Tanggal Submit</p>
                        <p class="font-semibold text-slate-800">{{ \App\Helpers\WaktuHelper::formatLengkap($sr->submitted_at) }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── Data Layanan ────────────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Data Layanan</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Jenis Layanan</p>
                        <p class="font-semibold text-slate-800">
                            @switch($sr->jenis_layanan)
                                @case('TAMBAH_DAYA')
                                    Tambah Daya
                                    @break
                                @case('PASANG_BARU')
                                    Pasang Baru
                                    @break
                                @default
                                    {{ str_replace('_', ' ', $sr->jenis_layanan ?? '—') }}
                            @endswitch
                        </p>
                    </div>
                    @if($sr->daya_baru)
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Daya Baru</p>
                        <p class="font-bold text-xl text-[#2F5AA8]">{{ number_format($sr->daya_baru, 0, ',', '.') }} <span class="text-sm font-semibold text-slate-500">VA</span></p>
                    </div>
                    @endif
                    @if($sr->jenis_produk)
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Produk</p>
                        <p class="font-semibold text-slate-800">{{ $sr->jenis_produk }}</p>
                    </div>
                    @endif
                    @if($sr->peruntukan_koneksi)
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Peruntukan</p>
                        <p class="font-semibold text-slate-800">{{ $sr->peruntukan_koneksi }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── Lokasi ───────────────────────────────────────────────────── --}}
            @php
                $hasLokasi = !empty($lokasi) && (
                    !empty($lokasi['provinsi'] ?? null) ||
                    !empty($lokasi['kab_kota'] ?? null) ||
                    !empty($lokasi['kecamatan'] ?? null) ||
                    !empty($lokasi['kelurahan'] ?? null)
                );
            @endphp
            @if($hasLokasi)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Lokasi Instalasi</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    @if(!empty($lokasi['provinsi']))
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Provinsi</p>
                        <p class="font-semibold text-slate-800">{{ $lokasi['provinsi'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['kab_kota']))
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Kab/Kota</p>
                        <p class="font-semibold text-slate-800">{{ $lokasi['kab_kota'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['kecamatan']))
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Kecamatan</p>
                        <p class="font-semibold text-slate-800">{{ $lokasi['kecamatan'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['kelurahan']))
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Kelurahan</p>
                        <p class="font-semibold text-slate-800">{{ $lokasi['kelurahan'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['rt']) || !empty($lokasi['rw']))
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">RT / RW</p>
                        <p class="font-semibold text-slate-800">{{ $lokasi['rt'] ?? '—' }} / {{ $lokasi['rw'] ?? '—' }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['koordinat']))
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Koordinat</p>
                        <p class="font-mono text-slate-600 text-xs">{{ $lokasi['koordinat'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['alamat_detail']))
                    <div class="col-span-2 sm:col-span-3">
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Detail Alamat</p>
                        <p class="font-semibold text-slate-800">{{ $lokasi['alamat_detail'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── SLO Data ─────────────────────────────────────────────────── --}}
            @if($sr->slo_no_registrasi)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Data SLO</h3>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700 font-bold">Terverifikasi</span>
                </div>
                <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">No. Registrasi</p>
                        <p class="font-mono text-slate-700">{{ $sr->slo_no_registrasi }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">No. Sertifikat</p>
                        <p class="font-mono text-slate-700">{{ $sr->slo_no_sertifikat }}</p>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- /.right --}}
    </div>{{-- /.grid --}}
</div>