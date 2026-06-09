@php
    $lokasi = $lokasi ?? data_get($sr->payload_json, 'lokasi', []);
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