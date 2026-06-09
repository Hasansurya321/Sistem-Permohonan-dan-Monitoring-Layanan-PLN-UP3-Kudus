@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <!-- Stepper -->
    <x-stepper :currentStep="1" />

    <!-- Form Panel -->
    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <!-- Shine Effect -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-400/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-400/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="p-8 md:p-12 relative z-10">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Persiapan Data Pemohon</h2>
            <p class="text-slate-500 mb-8">Siapa yang mengajukan permohonan pasang baru ini?</p>

            <form action="{{ route('pasang-baru.step1.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Radio Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <!-- Option: Self -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="for_whom" value="self" class="peer sr-only" {{ old('for_whom', data_get($wizard, 'for_whom')) === 'self' ? 'checked' : '' }} onchange="toggleSection('self')">

                         <div class="p-6 rounded-2xl border-2 border-slate-200 peer-checked:border-[#2F5AA8] peer-checked:bg-blue-50/50 hover:border-blue-100 transition-all text-center h-full flex flex-col justify-center items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-100 text-[#2F5AA8] flex items-center justify-center text-xl">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="font-bold text-slate-700 peer-checked:text-[#2F5AA8]">Saya Sendiri</span>
                        </div>
                        <div class="absolute top-4 right-4 text-[#2F5AA8] opacity-0 peer-checked:opacity-100 transition-opacity">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                    </label>

                    <!-- Option: Other -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="for_whom" value="other" class="peer sr-only" {{ old('for_whom', data_get($wizard, 'for_whom')) === 'other' ? 'checked' : '' }} onchange="toggleSection('other')">

                        <div class="p-6 rounded-2xl border-2 border-slate-200 peer-checked:border-[#2F5AA8] peer-checked:bg-blue-50/50 hover:border-blue-100 transition-all text-center h-full flex flex-col justify-center items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="font-bold text-slate-700 peer-checked:text-[#2F5AA8]">Orang Lain</span>
                        </div>
                        <div class="absolute top-4 right-4 text-[#2F5AA8] opacity-0 peer-checked:opacity-100 transition-opacity">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                    </label>
                </div>

                <!-- Section: Self -->
                <div id="section-self" class="hidden space-y-6 animate-fade-in-down">
                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-500 mb-1">Nama Pemohon</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" value="{{ $userProfile->nama_lengkap ?? $user->name }}" readonly class="w-full px-4 py-2 bg-slate-200 border border-slate-300 rounded-xl text-slate-600 font-medium focus:outline-none">
                                    @if(!empty($user->nik))
                                        <i class="fas fa-check-circle text-green-500 text-xl" title="Data Terverifikasi"></i>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-500 mb-1">NIK / No KTP</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" value="{{ $user->nik }}" readonly class="w-full px-4 py-2 bg-slate-200 border border-slate-300 rounded-xl text-slate-600 font-medium focus:outline-none">
                                    @if(!empty($user->nik))
                                        <i class="fas fa-check-circle text-green-500 text-xl" title="Data Terverifikasi"></i>
                                    @endif
                                </div>
                                @if(empty($user->nik))
                                    <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle"></i> Mohon lengkapi NIK pada menu Profil terlebih dahulu.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Box for Self Mode -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="text-blue-600 mt-0.5">
                                <i class="fas fa-info-circle text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm text-blue-800 font-semibold">Pasang Baru - Pelanggan Baru</p>
                                <p class="text-xs text-blue-600 mt-1">Anda akan mengajukan pemasangan listrik baru. ID Pelanggan akan diberikan setelah permohonan disetujui.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Other -->
                <div id="section-other" class="hidden space-y-6 animate-fade-in-down">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">NIK Pemohon (16 Digit)</label>
                        <div class="flex gap-2">
                            <input type="text" name="nik_other" id="nik_other" maxlength="16" value="{{ old('nik_other') }}"
                                class="flex-1 px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition font-medium"
                                placeholder="Masukkan NIK Pelanggan...">
                            <button type="button" onclick="verifyNik()" class="px-6 py-3 bg-slate-800 text-white font-semibold rounded-xl hover:bg-slate-700 transition shadow-lg shadow-slate-900/10 whitespace-nowrap">
                                Verifikasi
                            </button>
                        </div>
                        <div id="nik-error" class="hidden mt-2 text-sm text-red-500 flex items-center gap-1">
                            <i class="fas fa-times-circle"></i> <span>NIK tidak ditemukan / tidak valid.</span>
                        </div>
                        <div id="nik-success" class="hidden mt-3 bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
                            <div class="mt-0.5 text-green-600"><i class="fas fa-check-circle text-lg"></i></div>
                            <div>
                                <p class="text-xs text-green-800 font-bold uppercase tracking-wide">Data Ditemukan & Terverifikasi</p>
                                <p class="text-sm font-bold text-slate-800 mt-1" id="verified-name">Nama Pelanggan</p>
                                <p class="text-xs text-slate-500">Pasang Baru - ID Pelanggan akan diberikan setelah disetujui</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info Box for Other Mode -->
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="text-purple-600 mt-0.5">
                                <i class="fas fa-info-circle text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm text-purple-800 font-semibold">Pasang Baru untuk Orang Lain</p>
                                <p class="text-xs text-purple-600 mt-1">Pastikan Anda memiliki izin dari pemohon untuk mengajukan permohonan pasang baru atas namanya.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="mt-10 flex justify-between pt-6 border-t border-slate-100">
                    @if(!empty(data_get($wizard, 'service_request_id')))

                        <button type="button" onclick="if(confirm('Yakin ingin membatalkan dan menghapus draft permohonan ini?')) document.getElementById('cancel-draft-form').submit();" class="px-6 py-3 text-red-500 font-semibold hover:text-red-700 transition">
                            Batalkan Pengisian
                        </button>
                    @else
                        <a href="{{ route('monitoring') }}" class="px-6 py-3 text-slate-500 font-semibold hover:text-slate-800 transition">
                            Batal
                        </a>
                    @endif
                    <button type="submit" id="btn-next" disabled class="px-8 py-3 bg-slate-300 text-white font-bold rounded-xl cursor-not-allowed transition shadow-none">
                        Lanjutkan <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
            
            @if(!empty(data_get($wizard, 'service_request_id')))

            <form id="cancel-draft-form" action="{{ route('pasang-baru.cancel', data_get($wizard, 'service_request_id')) }}" method="POST" class="hidden">
                @csrf @method('DELETE')
            </form>
            @endif
        </div>
    </div>
</div>

<script>
    let isNikVerified = false;
    // For self, nik is implicitly verified if exists
    let isSelf = {{ old('for_whom', data_get($wizard, 'for_whom')) === 'self' || data_get($wizard, 'for_whom') === null ? 'true' : 'false' }};

    const hasProfileNik = {{ !empty($user->nik) ? 'true' : 'false' }};

    function toggleSection(val) {
        document.getElementById('section-self').classList.add('hidden');
        document.getElementById('section-other').classList.add('hidden');
        
        // Reset verifications when switching context
        isNikVerified = false;
        document.getElementById('nik-success').classList.add('hidden');
        document.getElementById('nik-error').classList.add('hidden');

        if (val === 'self') {
            document.getElementById('section-self').classList.remove('hidden');
            isSelf = true;
        } else if (val === 'other') {
            document.getElementById('section-other').classList.remove('hidden');
            isSelf = false;
        }
        checkValidity();
    }

    async function verifyNik() {
        const nik = document.getElementById('nik_other').value;
        const errorDiv = document.getElementById('nik-error');
        const successDiv = document.getElementById('nik-success');
        
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');
        isNikVerified = false;
        checkValidity();

        if (nik.length !== 16) {
            errorDiv.querySelector('span').innerText = 'NIK harus 16 digit.';
            errorDiv.classList.remove('hidden');
            return;
        }

        try {
            const response = await fetch("{{ route('pasang-baru.check-nik') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ nik: nik })
            });

            const result = await response.json();

            if (response.ok && (result.status === 'ok' || result.status === 'found')) {
                document.getElementById('verified-name').innerText = result.data.nama;
                successDiv.classList.remove('hidden');
                isNikVerified = true;
            } else {
                errorDiv.querySelector('span').innerText = result.message || 'NIK tidak ditemukan.';
                errorDiv.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Verifikasi Error:', error);
            errorDiv.querySelector('span').innerText = 'Terjadi kesalahan sistem.';
            errorDiv.classList.remove('hidden');
        }
        checkValidity();
    }

    function checkValidity() {
        const btn = document.getElementById('btn-next');
        let valid = false;

        if (isSelf) {
            // Self mode: NIK harus ada di profil
            if (hasProfileNik) valid = true;
        } else {
            // Other mode: NIK harus diverifikasi
            if (isNikVerified) valid = true;
        }

        if (valid) {
            btn.disabled = false;
            btn.classList.remove('bg-slate-300', 'cursor-not-allowed', 'shadow-none');
            btn.classList.add('bg-[#2F5AA8]', 'hover:bg-[#274C8E]', 'shadow-lg', 'shadow-blue-900/20');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-slate-300', 'cursor-not-allowed', 'shadow-none');
            btn.classList.remove('bg-[#2F5AA8]', 'hover:bg-[#274C8E]', 'shadow-lg', 'shadow-blue-900/20');
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        const selectedForWhom = document.querySelector('input[name="for_whom"]:checked');
        if (selectedForWhom) toggleSection(selectedForWhom.value);
        else toggleSection('self'); // Default
        
        // Initial check
        checkValidity();
        
        // Prevent Double Submit with proper flag
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('btn-next');
            
            // Only disable once
            if (!btn.hasAttribute('data-submitting')) {
                btn.setAttribute('data-submitting', 'true');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';
            }
        });
    });
</script>

@endsection
