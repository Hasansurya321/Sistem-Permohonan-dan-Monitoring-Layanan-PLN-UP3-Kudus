@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <x-stepper :currentStep="5" />

    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <div class="p-8 md:p-12 relative z-10 w-full">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Lengkapi Data Pelanggan</h2>
            <p class="text-slate-500 mb-8">Mohon lengkapi data administratif berikut untuk menyelesaikan permohonan.</p>

            <form id="formStep5" action="{{ route('tambah-daya.step5.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Ringkasan Verifikasi (Readonly) -->
                <div class="bg-blue-50/50 rounded-xl p-6 border border-blue-100 mb-8">
                    <h3 class="text-blue-800 font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-check-circle text-blue-600"></i> Hasil Verifikasi Awal
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">NIK Pemohon</label>
                            <div class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                {{ $wizard['applicant_nik'] }}
                                <i class="fas fa-check-circle text-green-500 text-sm" title="Terverifikasi"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Pemohon</label>
                            <div class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                {{ $wizard['applicant_name'] }}
                                <i class="fas fa-check-circle text-green-500 text-sm" title="Terverifikasi"></i>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">ID Pelanggan (Target)</label>
                            <div class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                {{ $wizard['id_pelanggan_val'] }}
                                <i class="fas fa-check-circle text-green-500 text-sm" title="Terverifikasi"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-8">
                    
                    <!-- No KK with Verification -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nomor Kartu Keluarga (KK) <span class="text-red-500">*</span></label>
                        <div class="flex gap-3">
                            <input type="text" id="no_kk" name="no_kk" value="{{ old('no_kk') }}" maxlength="16" 
                                   inputmode="numeric"
                                   class="flex-1 px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition" 
                                    placeholder="16 Digit Angka Nomor Kartu Keluarga">
                            <button type="button" id="btn-verify-kk" 
                                    class="px-6 py-3 bg-[#2F5AA8] text-white rounded-xl font-bold hover:bg-[#274C8E] transition shadow-lg">
                                Verifikasi
                            </button>
                        </div>
                                <p class="text-xs text-slate-500 mt-1">* Masukkan 16 digit Nomor Kartu Keluarga Anda</p>
                        <div id="kk-status" class="hidden mt-2 p-3 rounded-lg text-sm"></div>
                        @error('no_kk') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- No HP with Strict Format -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nomor Handphone (HP) <span class="text-red-500">*</span></label>
                        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp') }}" maxlength="12" 
                               inputmode="numeric"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition" 
                               placeholder="Format: 628123456789 (12 digit)">
                        <p class="text-xs text-slate-500 mt-1">* Harus diawali 62 dan total 12 digit</p>
                        @error('no_hp') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- NPWP with Verification (Conditional) -->
                    @if(isset($wajibNPWP) && $wajibNPWP)
                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Nomor NPWP <span class="text-red-500">*</span></label>
                        <p class="text-xs text-amber-600 mb-4 font-semibold"><i class="fas fa-info-circle"></i> NPWP Wajib diisi (Format baru: 16 digit)</p>
                        
                        <input type="hidden" name="has_npwp" value="1">

                        <div class="flex gap-3">
                            <input type="text" id="npwp" name="npwp" value="{{ old('npwp') }}" maxlength="16" 
                                   inputmode="numeric"
                                   class="flex-1 px-4 py-2 rounded-lg border border-slate-300 focus:outline-none focus:border-blue-500" 
                                   placeholder="16 Digit Angka (Tanpa titik/dash)">
                            <button type="button" id="btn-verify-npwp" 
                                    class="px-6 py-2 bg-[#2F5AA8] text-white rounded-xl font-bold hover:bg-[#274C8E] transition shadow-lg">
                                Verifikasi
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">* Format baru NPWP: 16 digit tanpa pemisah</p>
                        <div id="npwp-status" class="hidden mt-2 p-3 rounded-lg text-sm"></div>
                        @error('npwp') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    <!-- Photo Uploads (REQUIRED) -->
                    <div class="bg-amber-50 rounded-xl p-6 border border-amber-200">
                        <h3 class="font-bold text-amber-800 mb-4"><i class="fas fa-camera text-amber-600"></i> Upload Foto (Wajib)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Foto Bangunan -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Foto Bangunan (Tampak Depan) <span class="text-red-500">*</span></label>
                                <input type="file" id="foto_bangunan" name="foto_bangunan" accept="image/png, image/jpeg" 
                                       class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 rounded-xl border border-slate-300 p-2">
                                <span id="bangunan-status" class="text-xs text-slate-500"></span>
                                @error('foto_bangunan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Foto Diri KTP -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Foto Diri dengan KTP <span class="text-red-500">*</span></label>
                                <input type="file" id="foto_ktp_selfie" name="foto_ktp_selfie" accept="image/png, image/jpeg" 
                                       class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 rounded-xl border border-slate-300 p-2">
                                <span id="ktp-status" class="text-xs text-slate-500"></span>
                                @error('foto_ktp_selfie') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-10">
                    <a href="{{ route('tambah-daya.step4') }}" class="px-8 py-3 rounded-xl font-bold bg-slate-200 text-slate-700 hover:bg-slate-300 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <button type="submit" id="btn-submit" disabled 
                            class="px-8 py-3 rounded-xl font-bold bg-slate-300 text-slate-500 cursor-not-allowed transition">
                        <i class="fas fa-spinner fa-spin mr-2 hidden" id="loading-icon"></i>
                        <span id="submit-text">Lanjutkan</span> <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// State variables
let kkVerified = false;
let npwpVerified = {{ isset($wajibNPWP) && $wajibNPWP ? 'false' : 'true' }}; // Auto true if not required
let photosBangunanUploaded = false;
let photosKTPUploaded = false;

// DOM Ready - Setup all event listeners
document.addEventListener('DOMContentLoaded', function() {
    
    // KK Input Filter - Only digits, max 16
    const kkInput = document.getElementById('no_kk');
    kkInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, ''); // Only digits
        if (this.value.length > 16) {
            this.value = this.value.substr(0, 16);
        }
    });

    // HP Input Filter - Only digits, max 12
    const hpInput = document.getElementById('no_hp');
    hpInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, ''); // Only digits
        if (this.value.length > 12) {
            this.value = this.value.substr(0, 12);
        }
    });

    // NPWP Input Filter - Only digits, max 16
    const npwpInput = document.getElementById('npwp');
    if (npwpInput) {
        npwpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, ''); // Only digits
            if (this.value.length > 16) {
                this.value = this.value.substr(0, 16);
            }
        });
    }

    // KK Verify Button
    document.getElementById('btn-verify-kk').addEventListener('click', verifyKK);

    // NPWP Verify Button
    const npwpBtn = document.getElementById('btn-verify-npwp');
    if (npwpBtn) {
        npwpBtn.addEventListener('click', verifyNPWP);
    }

    // Photo upload listeners
    document.getElementById('foto_bangunan').addEventListener('change', checkPhotos);
    document.getElementById('foto_ktp_selfie').addEventListener('change', checkPhotos);

    // Form submit handler
    document.getElementById('formStep5').addEventListener('submit', function() {
        const btn = document.getElementById('btn-submit');
        btn.disabled = true;
        document.getElementById('loading-icon').classList.remove('hidden');
        document.getElementById('submit-text').textContent = 'Memproses...';
    });
});

// Verify KK - Validasi format 16 digit
async function verifyKK() {
    const input = document.getElementById('no_kk').value.trim();
    const statusDiv = document.getElementById('kk-status');
    
    // Frontend validation
    if (!input || input.length !== 16) {
        statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800';
        statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> No KK harus tepat 16 digit angka';
        statusDiv.classList.remove('hidden');
        kkVerified = false;
        updateSubmitButton();
        return;
    }

    try {
        const res = await fetch("{{ route('tambah-daya.verify-kk') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ no_kk: input })
        });
        const data = await res.json();

        if (data.status === 'valid') {
            statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-green-50 border border-green-200 text-green-800';
            statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            kkVerified = true;
        } else {
            statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800';
            statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
            kkVerified = false;
        }
        statusDiv.classList.remove('hidden');
        updateSubmitButton();
    } catch (e) {
        console.error('KK Verification Error:', e);
        statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800';
        statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error koneksi. Coba lagi.';
        statusDiv.classList.remove('hidden');
    }
}

// Verify NPWP - Must match master_pelanggan
async function verifyNPWP() {
    const input = document.getElementById('npwp').value.trim();
    const statusDiv = document.getElementById('npwp-status');
    
    // Frontend validation
    if (!input || input.length !== 16) {
        statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800';
        statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> NPWP harus tepat 16 digit angka';
        statusDiv.classList.remove('hidden');
        npwpVerified = false;
        updateSubmitButton();
        return;
    }

    try {
        const res = await fetch("{{ route('tambah-daya.verify-npwp') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ npwp: input })
        });
        const data = await res.json();

        if (data.status === 'valid') {
            statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-green-50 border border-green-200 text-green-800';
            statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            npwpVerified = true;
        } else {
            statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800';
            statusDiv.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
            npwpVerified = false;
        }
        statusDiv.classList.remove('hidden');
        updateSubmitButton();
    } catch (e) {
        console.error('NPWP Verification Error:', e);
        statusDiv.className = 'mt-2 p-3 rounded-lg text-sm bg-red-50 border border-red-200 text-red-800';
        statusDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error koneksi. Coba lagi.';
        statusDiv.classList.remove('hidden');
    }
}

// Check Photos
function checkPhotos() {
    const bangunan = document.getElementById('foto_bangunan').files.length > 0;
    const ktp = document.getElementById('foto_ktp_selfie').files.length > 0;
    
    photosBangunanUploaded = bangunan;
    photosKTPUploaded = ktp;
    
    document.getElementById('bangunan-status').textContent = bangunan ? '✓ File dipilih' : '';
    document.getElementById('bangunan-status').className = bangunan ? 'text-xs text-green-600 font-semibold' : 'text-xs text-slate-500';
    
    document.getElementById('ktp-status').textContent = ktp ? '✓ File dipilih' : '';
    document.getElementById('ktp-status').className = ktp ? 'text-xs text-green-600 font-semibold' : 'text-xs text-slate-500';
    
    updateSubmitButton();
}

// Update Submit Button State
function updateSubmitButton() {
    const btn = document.getElementById('btn-submit');
    const allValid = kkVerified && npwpVerified && photosBangunanUploaded && photosKTPUploaded;
    
    if (allValid) {
        btn.disabled = false;
        btn.className = 'px-8 py-3 rounded-xl font-bold bg-[#2F5AA8] text-white hover:bg-[#274C8E] transition shadow-lg';
    } else {
        btn.disabled = true;
        btn.className = 'px-8 py-3 rounded-xl font-bold bg-slate-300 text-slate-500 cursor-not-allowed transition';
    }
}
</script>
@endsection
