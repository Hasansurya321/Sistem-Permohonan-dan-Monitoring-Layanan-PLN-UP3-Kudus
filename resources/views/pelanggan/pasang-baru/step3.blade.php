@extends('layouts.pelanggan')

@section('content')
<div class="max-w-4xl mx-auto py-10">
    <x-stepper :currentStep="3" />

    <div class="bg-white/70 backdrop-blur-lg rounded-3xl border border-white/50 shadow-xl overflow-hidden relative">
        <div class="p-8 md:p-12 relative z-10">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Detail Layanan</h2>
            <p class="text-slate-500 mb-8">Tentukan besaran daya baru yang diinginkan dan jenis peruntukan</p>

            <form action="{{ route('pasang-baru.step3.store') }}" method="POST">
                @csrf
                
                <!-- Pilih Daya (Searchable Select) -->
                <div class="mb-8" x-data="{ search: '', open: false, selected: '{{ old('daya_baru') }}', display: '' }" x-init="display = selected ? selected + ' VA' : ''">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Pilih Daya Baru</label>
                    <input type="hidden" name="daya_baru" x-model="selected">
                    
                    <div class="relative">
                        <input type="text" x-model="display" @click="open = !open" 
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition cursor-pointer bg-white"
                            placeholder="Cari daya..." readonly>
                        <div class="absolute right-4 top-4 text-slate-400 pointer-events-none">
                            <i class="fas fa-chevron-down"></i>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open" @click.away="open = false" 
                            class="absolute z-50 w-full mt-2 bg-white border border-slate-100 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                            <div class="p-2 sticky top-0 bg-white border-b border-slate-50">
                                <input type="text" x-model="search" class="w-full px-3 py-2 bg-slate-50 rounded-lg border border-slate-200 text-sm focus:outline-none focus:border-blue-400" placeholder="Ketik angka daya...">
                            </div>
                            <div class="p-1">
                                @foreach($dayaOptions as $daya)
                                    <div @click="selected = '{{ $daya }}'; display = '{{ number_format($daya,0,',','.') }} VA'; open = false; search = ''" 
                                         x-show="'{{ $daya }}'.includes(search) || '{{ number_format($daya,0,',','.') }}'.includes(search)"
                                         class="px-4 py-2 hover:bg-blue-50 text-slate-700 rounded-lg cursor-pointer transition">
                                        {{ number_format($daya, 0, ',', '.') }} VA
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @error('daya_baru') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Jenis Produk Layanan -->
                <div class="mb-8" id="jenisProdukGroup">
                    <p class="font-bold text-slate-800 mb-3">Jenis Produk Layanan</p>

                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label
                        data-card="jenis_produk"
                        data-value="PASCABAYAR"
                        class="jenis-produk-card cursor-pointer rounded-2xl border p-5 transition border-slate-200 hover:border-slate-300 bg-white"
                        >
                        <div class="flex items-center gap-4">
                            <input
                            type="radio"
                            name="jenis_produk"
                            value="PASCABAYAR"
                            class="h-5 w-5 accent-[#2F5AA8]"
                            {{ old('jenis_produk', $data['jenis_produk'] ?? 'PASCABAYAR') === 'PASCABAYAR' ? 'checked' : '' }}
                            >
                            <div>
                            <div class="text-slate-900 font-semibold">Pascabayar (Tagihan Bulanan)</div>
                            <div class="text-slate-500 text-sm">Pembayaran listrik dibayar tiap bulan</div>
                            </div>
                        </div>
                        </label>

                        <label
                        data-card="jenis_produk"
                        data-value="PRABAYAR"
                        class="jenis-produk-card cursor-pointer rounded-2xl border p-5 transition border-slate-200 hover:border-slate-300 bg-white"
                        >
                        <div class="flex items-center gap-4">
                            <input
                            type="radio"
                            name="jenis_produk"
                            value="PRABAYAR"
                            class="h-5 w-5 accent-[#2F5AA8]"
                            {{ old('jenis_produk', $data['jenis_produk'] ?? 'PASCABAYAR') === 'PRABAYAR' ? 'checked' : '' }}
                            >
                            <div>
                            <div class="text-slate-900 font-semibold">Prabayar (Token Listrik)</div>
                            <div class="text-slate-500 text-sm">Pakai token / pulsa listrik</div>
                            </div>
                        </div>
                        </label>
                    </div>

                    @error('jenis_produk')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Peruntukan Koneksi -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Peruntukan</label>
                    <select name="peruntukan_koneksi" class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-4 focus:ring-blue-100 focus:border-[#2F5AA8] outline-none transition bg-white">
                        <option value="">Pilih Peruntukan</option>
                        <option value="RUMAH_TANGGA" {{ old('peruntukan_koneksi') == 'RUMAH_TANGGA' ? 'selected' : '' }}>Rumah Tangga</option>
                        <option value="BISNIS" {{ old('peruntukan_koneksi') == 'BISNIS' ? 'selected' : '' }}>Bisnis</option>
                        <option value="INDUSTRI" {{ old('peruntukan_koneksi') == 'INDUSTRI' ? 'selected' : '' }}>Industri</option>
                        <option value="SOSIAL" {{ old('peruntukan_koneksi') == 'SOSIAL' ? 'selected' : '' }}>Sosial</option>
                        <option value="PEMERINTAH" {{ old('peruntukan_koneksi') == 'PEMERINTAH' ? 'selected' : '' }}>Pemerintah</option>
                        <option value="RUMAH_IBADAH" {{ old('peruntukan_koneksi') == 'RUMAH_IBADAH' ? 'selected' : '' }}>Rumah Ibadah</option>
                    </select>
                    @error('peruntukan_koneksi') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6 border-t border-slate-100">
                    <a href="{{ route('pasang-baru.step2') }}" class="px-6 py-3 text-slate-500 font-semibold hover:text-slate-800 transition">
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

<!-- Alpine.js for Searchable Select -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
