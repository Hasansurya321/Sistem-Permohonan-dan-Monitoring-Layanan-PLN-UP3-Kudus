@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <x-stepper :currentStep="2" />

    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <div class="p-8 md:p-12 relative z-10">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Detail Lokasi</h2>
            <p class="text-slate-500 mb-8">Lengkapi alamat lokasi pemasangan</p>

            <form action="{{ route('pasang-baru.step2.store') }}" method="POST">
                @csrf
                
                <!-- Coordinate Input -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Titik Koordinat</label>
                    <input type="text" name="koordinat" value="{{ old('koordinat', $wizard['lokasi']['koordinat_display'] ?? '') }}" 
                        placeholder="Contoh: -6.8250269, 110.8334822"
                        class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition placeholder-slate-400">
                    <p class="mt-2 text-sm text-slate-500">
                        <a href="{{ route('tutorial.titik-koordinat') }}" class="text-[#2F5AA8] hover:underline inline-flex items-center gap-1">
                            <i class="fas fa-info-circle"></i> Cara melihat titik koordinat saya, klik disini
                        </a>
                    </p>
                    @error('koordinat')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Provinsi (Fixed) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Provinsi</label>
                        <select name="provinsi" class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-600 focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] transition">
                            <option value="" disabled {{ old('provinsi', $prefill['provinsi'] ?? '') ? '' : 'selected' }}>Pilih Provinsi</option>
                            <option value="Jawa Tengah" {{ old('provinsi', $prefill['provinsi'] ?? '') == 'Jawa Tengah' ? 'selected' : '' }}>Jawa Tengah</option>
                        </select>
                        @error('provinsi') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Kab/Kota (Fixed) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Kabupaten/Kota</label>
                        <select name="kab_kota" class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-slate-600 focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] transition">
                            <option value="" disabled {{ old('kab_kota', $prefill['kab_kota'] ?? '') ? '' : 'selected' }}>Pilih Kabupaten/Kota</option>
                            <option value="Kudus" {{ old('kab_kota', $prefill['kab_kota'] ?? '') == 'Kudus' ? 'selected' : '' }}>Kudus</option>
                        </select>
                        @error('kab_kota') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Kecamatan -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Kecamatan</label>
                        <select name="kecamatan" id="kecamatan" onchange="updateKelurahan()" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition bg-white">
                            <option value="">Pilih Kecamatan</option>
                            @foreach(config('wilayah_kudus') as $kec => $desaList)
                                <option value="{{ $kec }}" {{ old('kecamatan', $prefill['kecamatan'] ?? '') == $kec ? 'selected' : '' }}>{{ $kec }}</option>
                            @endforeach
                        </select>
                        @error('kecamatan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Kelurahan -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Kelurahan/Desa</label>
                        <select name="kelurahan" id="kelurahan" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition bg-white">
                            <option value="">Pilih Kelurahan</option>
                            <!-- Populated by JS -->
                        </select>
                        @error('kelurahan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- RT/RW -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">RT</label>
                            <input type="text" name="rt" value="{{ old('rt', $prefill['rt'] ?? '') }}" 
                                inputmode="numeric" maxlength="3" pattern="^\d{1,3}$"
                                oninput="this.value = this.value.replace(/[^\d]/g,'').slice(0,3)"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition">
                            @error('rt') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">RW</label>
                            <input type="text" name="rw" value="{{ old('rw', $prefill['rw'] ?? '') }}" 
                                inputmode="numeric" maxlength="3" pattern="^\d{1,3}$"
                                oninput="this.value = this.value.replace(/[^\d]/g,'').slice(0,3)"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition">
                            @error('rw') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Detail Tambahan -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Detail Tambahan (Opsional)</label>
                    <textarea name="alamat_detail" rows="3" maxlength="200" placeholder="Nama jalan, patokan rumah, warna pagar, dll" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition">{{ old('alamat_detail', $prefill['alamat_detail'] ?? '') }}</textarea>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6 border-t border-slate-100">
                    <a href="{{ route('pasang-baru.step1') }}" class="px-6 py-3 text-slate-500 font-semibold hover:text-slate-800 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <button type="submit" class="px-8 py-3 bg-[#2F5AA8] text-white font-bold rounded-xl hover:bg-[#274C8E] transition shadow-lg shadow-blue-900/20 hover:shadow-blue-900/30">
                        Lanjutkan <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const wilayahData = @json(config('wilayah_kudus'));
    const prefillKelurahan = "{{ old('kelurahan', $prefill['kelurahan'] ?? '') }}";

    function updateKelurahan() {
        const kecSelect = document.getElementById('kecamatan');
        const kelSelect = document.getElementById('kelurahan');
        const selectedKec = kecSelect.value;

        kelSelect.innerHTML = '<option value="">Pilih Kelurahan</option>';

        if (selectedKec && wilayahData[selectedKec]) {
            wilayahData[selectedKec].forEach(desa => {
                const option = document.createElement('option');
                option.value = desa;
                option.text = desa;
                if (desa === prefillKelurahan) {
                    option.selected = true;
                }
                kelSelect.appendChild(option);
            });
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        updateKelurahan();
    });
</script>
@endsection
