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