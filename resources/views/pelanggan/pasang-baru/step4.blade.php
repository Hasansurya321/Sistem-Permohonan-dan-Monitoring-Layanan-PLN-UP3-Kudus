@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <x-stepper :currentStep="4" />

    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <div class="p-8 md:p-12 relative z-10">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Data SLO</h2>
            <p class="text-slate-500 mb-8">Masukkan data Sertifikat Laik Operasi (SLO)</p>
            
            <form action="{{ route('pasang-baru.step4.store') }}" method="POST" id="sloForm">
                @csrf
                
                {{-- No Registrasi SLO --}}
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">No. Registrasi SLO</label>
                    <div class="flex gap-4">
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                name="slo_no_registrasi" 
                                id="slo_no_registrasi" 
                                value="{{ old('slo_no_registrasi', $wizard['slo_no_registrasi'] ?? '') }}"
                                placeholder="Contoh: SLO-REG-2026-000241"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition uppercase"
                            >
                            {{-- Validation Icon --}}
                            <div id="icon-reg" class="absolute right-4 top-3.5 hidden">
                                <i class="fas"></i>
                            </div>
                        </div>
                        <button type="button" onclick="checkReg()" class="px-5 py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition border border-slate-200 text-sm whitespace-nowrap">
                            Periksa
                        </button>
                    </div>
                    <p id="msg-reg" class="mt-2 text-sm text-slate-500"></p>
                    @error('slo_no_registrasi') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- No Sertifikat SLO --}}
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">No. Sertifikat SLO</label>
                    <div class="flex gap-4">
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                name="slo_no_sertifikat" 
                                id="slo_no_sertifikat" 
                                value="{{ old('slo_no_sertifikat', $wizard['slo_no_sertifikat'] ?? '') }}"
                                placeholder="Contoh: SLO-CERT-2026-KUDUS-12001"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition uppercase"
                            >
                             {{-- Validation Icon --}}
                             <div id="icon-cert" class="absolute right-4 top-3.5 hidden">
                                <i class="fas"></i>
                            </div>
                        </div>
                        <button type="button" onclick="checkCert()" class="px-5 py-3 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition border border-slate-200 text-sm whitespace-nowrap">
                            Periksa
                        </button>
                    </div>
                    <p id="msg-cert" class="mt-2 text-sm text-slate-500"></p>
                    @error('slo_no_sertifikat') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Alert Box for Pair Check --}}
                <div id="pair-alert" class="hidden mb-8 p-4 rounded-xl border">
                    <div class="flex items-start gap-3">
                        <i id="pair-icon" class="fas text-lg mt-0.5"></i>
                        <div>
                            <h4 id="pair-title" class="font-bold"></h4>
                            <p id="pair-desc" class="text-sm"></p>
                        </div>
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="flex justify-between pt-6 border-t border-slate-100">
                    <a href="{{ route('pasang-baru.step3') }}" class="px-6 py-3 text-slate-500 font-semibold hover:text-slate-800 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <button type="submit" id="btn-next" disabled class="px-8 py-3 bg-slate-300 text-white font-bold rounded-xl cursor-not-allowed transition shadow-none">
                        Lanjutkan <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    let validReg = false;
    let validCert = false;
    let validPair = false;

    async function checkReg() {
        const val = document.getElementById('slo_no_registrasi').value.toUpperCase();
        const msgEl = document.getElementById('msg-reg');
        const iconEl = document.getElementById('icon-reg');
        const iconI = iconEl.querySelector('i');

        msgEl.innerText = 'Memeriksa...';
        msgEl.className = 'mt-2 text-sm text-slate-500';
        iconEl.classList.add('hidden');
        validReg = false;
        updateNextButton();

        try {
            const res = await fetch("{{ route('pasang-baru.check-slo') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({ type: 'reg', value: val })
            });
            const data = await res.json();

            if (data.status === 'found') {
                msgEl.innerText = 'No. Registrasi Valid & Ditemukan.';
                msgEl.className = 'mt-2 text-sm text-green-600 font-medium';
                iconEl.classList.remove('hidden');
                iconI.className = 'fas fa-check-circle text-green-500';
                validReg = true;
            } else if (data.status === 'invalid_format') {
                msgEl.innerText = data.message;
                msgEl.className = 'mt-2 text-sm text-amber-600';
                iconEl.classList.remove('hidden');
                iconI.className = 'fas fa-exclamation-circle text-amber-500';
            } else {
                msgEl.innerText = 'Data tidak ditemukan di database.';
                msgEl.className = 'mt-2 text-sm text-red-600';
                iconEl.classList.remove('hidden');
                iconI.className = 'fas fa-times-circle text-red-500';
            }
        } catch (e) {
            msgEl.innerText = 'Terjadi kesalahan sistem.';
            msgEl.className = 'mt-2 text-sm text-red-600';
        }
        checkPair();
    }

    async function checkCert() {
        const val = document.getElementById('slo_no_sertifikat').value.toUpperCase();
        const msgEl = document.getElementById('msg-cert');
        const iconEl = document.getElementById('icon-cert');
        const iconI = iconEl.querySelector('i');

        msgEl.innerText = 'Memeriksa...';
        msgEl.className = 'mt-2 text-sm text-slate-500';
        iconEl.classList.add('hidden');
        validCert = false;
        updateNextButton();

        try {
            const res = await fetch("{{ route('pasang-baru.check-slo') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                body: JSON.stringify({ type: 'cert', value: val })
            });
            const data = await res.json();

            if (data.status === 'found') {
                msgEl.innerText = 'No. Sertifikat Valid & Ditemukan.';
                msgEl.className = 'mt-2 text-sm text-green-600 font-medium';
                iconEl.classList.remove('hidden');
                iconI.className = 'fas fa-check-circle text-green-500';
                validCert = true;
            } else if (data.status === 'invalid_format') {
                msgEl.innerText = data.message;
                msgEl.className = 'mt-2 text-sm text-amber-600';
                iconEl.classList.remove('hidden');
                iconI.className = 'fas fa-exclamation-circle text-amber-500';
            } else {
                msgEl.innerText = 'Data tidak ditemukan di database.';
                msgEl.className = 'mt-2 text-sm text-red-600';
                iconEl.classList.remove('hidden');
                iconI.className = 'fas fa-times-circle text-red-500';
            }
        } catch (e) {
            msgEl.innerText = 'Terjadi kesalahan sistem.';
            msgEl.className = 'mt-2 text-sm text-red-600';
        }
        checkPair();
    }

    async function checkPair() {
        validPair = false;
        const alertBox = document.getElementById('pair-alert');
        alertBox.classList.add('hidden');
        updateNextButton();

        if (validReg && validCert) {
            const reg = document.getElementById('slo_no_registrasi').value.toUpperCase();
            const cert = document.getElementById('slo_no_sertifikat').value.toUpperCase();

            try {
                const res = await fetch("{{ route('pasang-baru.check-slo') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ type: 'pair', reg: reg, cert: cert })
                });
                const data = await res.json();

                alertBox.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-800', 'bg-green-50', 'border-green-200', 'text-green-800', 'bg-amber-50', 'border-amber-200', 'text-amber-800');
                const title = document.getElementById('pair-title');
                const desc = document.getElementById('pair-desc');
                const icon = document.getElementById('pair-icon');

                if (data.status === 'valid') {
                    alertBox.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
                    title.innerText = '✓ SLO Terverifikasi';
                    desc.innerHTML = `<strong>Sukses verifikasi SLO atas nama: ${data.data.nama_pemilik}</strong><br>` +
                                     `NIK: ${data.data.nik_pemilik}<br>` +
                                     `Lembaga: ${data.data.nama_lembaga || '-'}`;
                    icon.className = 'fas fa-check-circle text-green-600 text-lg mt-0.5';
                    validPair = true;
                } else if (data.status === 'not_found') {
                    alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                    title.innerText = '✗ Data SLO Tidak Ditemukan';
                    desc.innerText = data.message || 'Nomor Registrasi dan Sertifikat tidak cocok atau tidak terdaftar.';
                    icon.className = 'fas fa-times-circle text-red-600 text-lg mt-0.5';
                    validPair = false;
                } else {
                    alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                    title.innerText = '✗ Verifikasi Gagal';
                    desc.innerText = data.message || 'Terjadi kesalahan.';
                    icon.className = 'fas fa-times-circle text-red-600 text-lg mt-0.5';
                    validPair = false;
                }
            } catch (e) {
                alertBox.classList.remove('hidden');
                alertBox.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                document.getElementById('pair-title').innerText = 'Error Sistem';
                document.getElementById('pair-desc').innerText = 'Terjadi kesalahan koneksi.';
                document.getElementById('pair-icon').className = 'fas fa-exclamation-circle text-red-600 text-lg mt-0.5';
            }
            updateNextButton();
        }
    }

    function updateNextButton() {
        const btn = document.getElementById('btn-next');
        if (validReg && validCert && validPair) {
            btn.disabled = false;
            btn.classList.remove('bg-slate-300', 'cursor-not-allowed', 'shadow-none');
            btn.classList.add('bg-[#2F5AA8]', 'hover:bg-[#274C8E]', 'shadow-lg', 'shadow-blue-900/20');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-slate-300', 'cursor-not-allowed', 'shadow-none');
            btn.classList.remove('bg-[#2F5AA8]', 'hover:bg-[#274C8E]', 'shadow-lg', 'shadow-blue-900/20');
        }
    }

    document.querySelector('#sloForm').addEventListener('submit', function() {
        const btn = document.getElementById('btn-next');
        if(!btn.disabled) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
        }
    });
</script>
@endsection
