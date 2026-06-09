@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-6">
    {{-- Back button --}}
    <div class="mb-5">
        <a href="{{ route('pembayaran') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#2F5AA8] font-medium transition-colors group">
            <i class="fas fa-arrow-left text-xs group-hover:-translate-x-0.5 transition-transform"></i>
            Kembali ke Pembayaran
        </a>
    </div>

    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <div class="p-8 md:p-12 relative z-10">
            {{-- Header --}}
            <div class="text-center mb-8 pb-8 border-b border-slate-100">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <div class="h-8 w-auto">
                        <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                    </div>
                    <span class="text-2xl font-bold text-slate-800">PLN UP3 KUDUS</span>
                </div>
                <h1 class="text-3xl font-extrabold text-[#2F5AA8] mt-4">DETAIL PEMBAYARAN</h1>
                <p class="text-slate-500 text-sm mt-1">Simulasi Tagihan — BUKAN TAGIHAN RESMI</p>
            </div>

            {{-- Status Badge --}}
            @if($sr->status_detail)
            <div class="flex justify-center mb-6">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold
                    @if($sr->status_detail === \App\Enums\PermohonanDetailStatus::MENUNGGU_PEMBAYARAN || $sr->status_detail === \App\Enums\PermohonanDetailStatus::TAGIHAN_TERBIT)
                        bg-orange-100 text-orange-700 border border-orange-200
                    @elseif($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_PENDING)
                        bg-blue-100 text-blue-700 border border-blue-200
                    @elseif($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_SUKSES || $sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_SELESAI)
                        bg-green-100 text-green-700 border border-green-200
                    @elseif($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_GAGAL)
                        bg-red-100 text-red-700 border border-red-200
                    @else
                        bg-slate-100 text-slate-700 border border-slate-200
                    @endif">
                    @if($sr->status_detail === \App\Enums\PermohonanDetailStatus::MENUNGGU_PEMBAYARAN || $sr->status_detail === \App\Enums\PermohonanDetailStatus::TAGIHAN_TERBIT)
                        <i class="fas fa-clock text-orange-500"></i>
                    @elseif($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_PENDING)
                        <i class="fas fa-spinner text-blue-500"></i>
                    @elseif($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_SUKSES || $sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_SELESAI)
                        <i class="fas fa-check-circle text-green-500"></i>
                    @elseif($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_GAGAL)
                        <i class="fas fa-times-circle text-red-500"></i>
                    @endif
                    {{ $sr->status_detail->getLabel() }}
                </span>
            </div>
            @endif

            {{-- ═══ INFORMASI PERMOHONAN ═════════════════════════════════════ --}}
            <div class="bg-slate-50 rounded-2xl p-5 mb-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-file-alt text-[#2F5AA8]"></i>
                    Informasi Permohonan
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Kode Permohonan</p>
                        <p class="text-sm font-bold text-slate-800">{{ $sr->nomor_permohonan ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Jenis Layanan</p>
                        <p class="text-sm font-bold text-slate-800">{{ str_replace('_', ' ', $sr->jenis_layanan ?? '-') }}</p>
                    </div>
                    @if($sr->status !== \App\Enums\PermohonanStatus::SELESAI || $sr->status_detail !== \App\Enums\PermohonanDetailStatus::PERMOHONAN_GAGAL)
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Daya Baru</p>
                        <p class="text-sm font-bold text-slate-800">{{ number_format($sr->daya_baru ?? 0, 0, ',', '.') }} VA</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Tanggal Pengajuan</p>
                        <p class="text-sm font-bold text-slate-800">{{ $sr->submitted_at ? \App\Helpers\WaktuHelper::formatTanggal($sr->submitted_at) : '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- ═══ INFORMASI PEMOHON ═════════════════════════════════════════ --}}
            @if($sr->applicant)
            <div class="bg-slate-50 rounded-2xl p-5 mb-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-user text-[#2F5AA8]"></i>
                    Informasi Pemohon
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Nama Pemohon</p>
                        <p class="text-sm font-bold text-slate-800">{{ $sr->applicant->nama_lengkap ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">NIK</p>
                        <p class="text-sm font-mono text-slate-800">{{ $sr->applicant_nik ?? '-' }}</p>
                    </div>
                    @if($sr->applicant->no_hp)
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">No HP</p>
                        <p class="text-sm font-bold text-slate-800">{{ $sr->applicant->no_hp }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ═══ INFORMASI LOKASI ══════════════════════════════════════════ --}}
            @if(!empty($lokasi))
            <div class="bg-slate-50 rounded-2xl p-5 mb-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-[#2F5AA8]"></i>
                    Informasi Lokasi
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if(!empty($lokasi['provinsi']))
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Provinsi</p>
                        <p class="text-sm font-bold text-slate-800">{{ $lokasi['provinsi'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['kab_kota']))
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Kabupaten</p>
                        <p class="text-sm font-bold text-slate-800">{{ $lokasi['kab_kota'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['kecamatan']))
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Kecamatan</p>
                        <p class="text-sm font-bold text-slate-800">{{ $lokasi['kecamatan'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['kelurahan']))
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Kelurahan</p>
                        <p class="text-sm font-bold text-slate-800">{{ $lokasi['kelurahan'] }}</p>
                    </div>
                    @endif
                    @if(!empty($lokasi['rt']) || !empty($lokasi['rw']))
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">RT/RW</p>
                        <p class="text-sm font-bold text-slate-800">{{ $lokasi['rt'] ?? '-' }} / {{ $lokasi['rw'] ?? '-' }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ═══ RINCIAN TAGIHAN ══════════════════════════════════════════ --}}
            @if(!empty($billing))
            <div class="bg-slate-50 rounded-2xl p-5 mb-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-receipt text-[#2F5AA8]"></i>
                    Rincian Tagihan
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">Biaya Dasar Peruntukan ({{ $peruntukanLabels[$sr->peruntukan_koneksi] ?? $sr->peruntukan_koneksi }})</span>
                        <span class="font-semibold text-slate-800">{{ \App\Services\DummyTambahDayaBillingService::formatRupiah($billing['biaya_dasar'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">Biaya Kapasitas Daya ({{ number_format($sr->daya_baru ?? 0, 0, ',', '.') }} VA)</span>
                        <span class="font-semibold text-slate-800">{{ \App\Services\DummyTambahDayaBillingService::formatRupiah($billing['biaya_daya'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">Biaya Administrasi</span>
                        <span class="font-semibold text-slate-800">{{ \App\Services\DummyTambahDayaBillingService::formatRupiah($billing['biaya_admin'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                        <span class="text-slate-600">PPN 11%</span>
                        <span class="font-semibold text-slate-800">{{ \App\Services\DummyTambahDayaBillingService::formatRupiah($billing['ppn'] ?? 0) }}</span>
                    </div>
                </div>

                {{-- Total --}}
                <div class="bg-[#2F5AA8]/5 rounded-2xl p-4 mt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-slate-800">Total Tagihan</span>
                        <span class="text-2xl font-extrabold text-[#2F5AA8]">
                            {{ \App\Services\DummyTambahDayaBillingService::formatRupiah($billing['total'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
            @else
            {{-- Fallback jika billing tidak ditemukan --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-yellow-600 mt-1"></i>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">Rincian Tagihan Belum Tersedia</p>
                        <p class="text-xs text-yellow-700 mt-1">
                            Rincian biaya sedang diproses oleh sistem. Silakan coba lagi beberapa saat.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ═══ INFORMASI PEMBAYARAN ═══════════════════════════════════════ --}}
            <div class="bg-slate-50 rounded-2xl p-5 mb-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fas fa-credit-card text-[#2F5AA8]"></i>
                    Informasi Pembayaran
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Status Pembayaran</p>
                        <p class="text-sm font-bold text-slate-800">{{ $sr->status_detail?->getLabel() ?? '-' }}</p>
                    </div>
                    @if($activePayment && $activePayment->expired_at)
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Batas Waktu Pembayaran</p>
                        <p class="text-sm font-bold {{ $activePayment->isExpired() ? 'text-red-600' : 'text-slate-800' }}">
                            {{ \App\Helpers\WaktuHelper::formatLengkap($activePayment->expired_at) }}
                            @if($activePayment->isExpired())
                                <span class="text-xs text-red-500">(Kadaluwarsa)</span>
                            @endif
                        </p>
                    </div>
                    @endif
                    @if($sr->payment_attempt_count !== null)
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Percobaan Pembayaran</p>
                        <p class="text-sm font-bold text-slate-800">{{ $sr->payment_attempt_count ?? 0 }} / 3</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Catatan --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 mb-8">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-yellow-600 mt-1"></i>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">Informasi</p>
                        <p class="text-xs text-yellow-700 mt-1">
                            Tagihan ini adalah <strong>simulasi / dummy</strong> untuk keperluan testing dan pengembangan sistem.
                            BUKAN tagihan resmi PLN. Semua nilai tarif, biaya, dan perhitungan adalah data simulasi.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ═══ TOMBOL AKSI ═══════════════════════════════════════════════ --}}
            <div class="flex flex-col sm:flex-row justify-between gap-3 pt-6 border-t border-slate-100">
                <a href="{{ route('pembayaran') }}" class="px-6 py-3 text-slate-500 font-semibold hover:text-slate-800 transition text-center">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>

                <div class="flex gap-3">
                    @if($sr->status === \App\Enums\PermohonanStatus::PEMBAYARAN && 
                        $sr->canRetryPayment())
                    <form action="{{ route('monitoring.pay', $sr->id) }}" method="POST"
                          onsubmit="return confirm('Lanjutkan ke halaman pembayaran QRIS?')">
                        @csrf
                        <button type="submit"
                            class="w-full sm:w-auto px-8 py-3 bg-[#2F5AA8] text-white font-bold rounded-xl hover:bg-[#274C8E] transition shadow-lg shadow-blue-900/20">
                            <i class="fas fa-bolt mr-2"></i> Bayar Sekarang
                        </button>
                    </form>
                    @endif

                    @if($sr->status === \App\Enums\PermohonanStatus::PEMBAYARAN)
                    <form action="{{ route('pembayaran.cancel', $sr->id) }}" method="POST"
                          onsubmit="return confirm('Yakin akan membatalkan permohonan ini?')">
                        @csrf
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 text-red-600 font-semibold border-2 border-red-200 rounded-xl hover:bg-red-50 transition">
                            <i class="fas fa-times mr-2"></i> Batalkan
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection