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