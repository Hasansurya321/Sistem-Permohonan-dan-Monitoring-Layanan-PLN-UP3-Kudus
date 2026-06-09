@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <!-- Stepper indicator -->
    <x-stepper :currentStep="3.5" />

    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <div class="p-8 md:p-12 relative z-10">
            <!-- Header -->
            <div class="text-center mb-8 pb-8 border-b border-slate-100">
                <div class="flex items-center justify-center gap-3 mb-2">
                    <div class="h-8 w-auto">
                        <img src="{{ asset('images/pln-logo2.png') }}" alt="PLN Logo" class="h-full object-contain">
                    </div>
                    <span class="text-2xl font-bold text-slate-800">PLN UP3 KUDUS</span>
                </div>
                <h1 class="text-3xl font-extrabold text-[#2F5AA8] mt-4">NOTA PERMOHONAN TAMBAH DAYA</h1>
                <p class="text-slate-500 text-sm mt-1">Dummy / Simulasi Tagihan — BUKAN TAGIHAN RESMI</p>
            </div>

            <!-- Informasi Permohonan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 p-5 bg-slate-50 rounded-2xl">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Nomor Permohonan</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['nomor_permohonan'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Tanggal Permohonan</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['tanggal_permohonan'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Nama Pelanggan</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['nama_pelanggan'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">No Pelanggan</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['no_pelanggan'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Peruntukan</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['peruntukan_label'] ?? $invoice['peruntukan'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Daya Dipilih</p>
                    <p class="text-sm font-bold text-slate-800">{{ number_format($invoice['daya'], 0, ',', '.') }} VA</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Status Permohonan</p>
                    <span class="inline-block mt-1 px-3 py-1 text-xs font-bold rounded-lg bg-yellow-100 text-yellow-800">MENUNGGU PEMBAYARAN</span>
                </div>
            </div>

            <!-- Rincian Tagihan -->
            <h3 class="text-lg font-bold text-slate-800 mb-4">Rincian Tagihan</h3>
            <div class="space-y-3 mb-8">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Biaya Dasar Peruntukan ({{ $invoice['peruntukan_label'] ?? $invoice['peruntukan'] }})</span>
                    <span class="font-semibold text-slate-800">{{ App\Services\DummyTambahDayaBillingService::formatRupiah($billing['biaya_dasar']) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Biaya Kapasitas Daya ({{ number_format($invoice['daya'], 0, ',', '.') }} VA)</span>
                    <span class="font-semibold text-slate-800">{{ App\Services\DummyTambahDayaBillingService::formatRupiah($billing['biaya_daya']) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Biaya Administrasi</span>
                    <span class="font-semibold text-slate-800">{{ App\Services\DummyTambahDayaBillingService::formatRupiah($billing['biaya_admin']) }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">PPN 11%</span>
                    <span class="font-semibold text-slate-800">{{ App\Services\DummyTambahDayaBillingService::formatRupiah($billing['ppn']) }}</span>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="bg-[#2F5AA8]/5 rounded-2xl p-5 mb-8">
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-600 font-medium">Subtotal</span>
                    <span class="font-semibold text-slate-800">{{ App\Services\DummyTambahDayaBillingService::formatRupiah($billing['subtotal']) }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-t border-[#2F5AA8]/10">
                    <span class="text-lg font-bold text-slate-800">Total Tagihan</span>
                    <span class="text-2xl font-extrabold text-[#2F5AA8]">{{ App\Services\DummyTambahDayaBillingService::formatRupiah($billing['total']) }}</span>
                </div>
            </div>

            <!-- Informasi Pembayaran -->
            <h3 class="text-lg font-bold text-slate-800 mb-4">Informasi Pembayaran</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 p-5 bg-slate-50 rounded-2xl">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Nomor Invoice</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['nomor_invoice'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Tanggal Terbit</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['tanggal_terbit'] ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Jatuh Tempo</p>
                    <p class="text-sm font-bold text-slate-800">{{ $invoice['tanggal_jatuh_tempo'] ?? '-' }}</p>
                </div>
                <div class="md:col-span-3">
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Status Pembayaran</p>
                    <span class="inline-block mt-1 px-3 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-700">BELUM DIBAYAR</span>
                </div>
            </div>

            <!-- Catatan -->
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

            <!-- Navigation Buttons -->
            <div class="flex justify-between pt-6 border-t border-slate-100">
                <a href="{{ route('tambah-daya.step3') }}" class="px-6 py-3 text-slate-500 font-semibold hover:text-slate-800 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali
                </a>
                <a href="{{ route('tambah-daya.step4') }}" class="px-8 py-3 bg-[#2F5AA8] text-white font-bold rounded-xl hover:bg-[#274C8E] transition shadow-lg shadow-blue-900/20 hover:shadow-blue-900/30">
                    Lanjutkan ke Data SLO <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection