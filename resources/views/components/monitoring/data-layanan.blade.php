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