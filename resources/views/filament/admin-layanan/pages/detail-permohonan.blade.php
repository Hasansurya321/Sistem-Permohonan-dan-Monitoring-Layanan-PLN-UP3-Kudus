<x-filament-panels::page>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Detail Permohonan {{ $detailLayanan['jenis_layanan'] }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No. Permohonan: <span class="font-mono font-semibold">{{ $record->nomor_permohonan }}</span>
                </p>
            </div>
            <a href="{{ $backUrl }}" class="filament-button filament-button-outline-secondary inline-flex items-center gap-1 text-sm">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    {{-- Status Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Status Saat Ini</span>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $statusSekarang }}</h3>
            </div>
            <div class="flex items-center gap-2">
                @if($record->revision_count > 0)
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        {{ $isBatasRevisi ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
                        Revisi {{ $revisiKeBerapa }}
                    </span>
                @endif
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                    {{ $detailLayanan['jenis_layanan'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- Tombol Aksi (hanya jika status MENUNGGU_VERIFIKASI_DATA) --}}
    @if($isMenungguVerifikasi)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Aksi Admin</h3>
            <div class="flex flex-wrap gap-3">
                {{-- Tombol Verifikasi Data --}}
                <form wire:submit.prevent="verifikasiData">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-success-600 hover:bg-success-500 text-white font-medium rounded-lg text-sm transition-colors duration-150">
                        <x-heroicon-o-check-circle class="w-5 h-5" />
                        Verifikasi Data
                    </button>
                </form>

                {{-- Tombol Kembalikan ke Pelanggan (jika revisi < 2) --}}
                @if(!$isBatasRevisi)
                    <div x-data="{ open: false }">
                        <button @click="open = !open"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-warning-600 hover:bg-warning-500 text-white font-medium rounded-lg text-sm transition-colors duration-150">
                            <x-heroicon-o-arrow-uturn-left class="w-5 h-5" />
                            Kembalikan ke Pelanggan (Revisi {{ ($record->revision_count ?? 0) + 1 }}/2)
                        </button>

                        {{-- Modal Catatan Perbaikan --}}
                        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             style="display: none;">
                            <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 w-full max-w-lg">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kembalikan ke Pelanggan</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                    Berikan catatan perbaikan untuk pelanggan.
                                    <br>Sisa kesempatan revisi: <strong>{{ 2 - ($record->revision_count ?? 0) }}/2</strong>
                                </p>
                                <form wire:submit.prevent="kembalikanKePelanggan(note)">
                                    <div class="mb-4">
                                        <label for="revision_note" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Catatan Perbaikan <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            id="revision_note"
                                            wire:model="note"
                                            rows="4"
                                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="Upload KTP yang lebih jelas"
                                            required
                                        ></textarea>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" @click="open = false"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                            Batal
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-warning-600 rounded-lg hover:bg-warning-500 transition-colors">
                                            Kirim ke Pelanggan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Tombol Tolak (jika revisi >= 2) --}}
                @if($isBatasRevisi)
                    <form wire:submit.prevent="tolakPermohonan"
                          onsubmit="return confirm('Yakin akan menolak permohonan ini? Permohonan telah melampaui batas revisi ({{ $revisiKeBerapa }}).')">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-danger-600 hover:bg-danger-500 text-white font-medium rounded-lg text-sm transition-colors duration-150">
                            <x-heroicon-o-x-circle class="w-5 h-5" />
                            Tolak Permohonan
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Grid Data --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Data Pelanggan --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-user class="w-5 h-5 text-primary-500" />
                Data Pelanggan
            </h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Nama Lengkap</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $dataPelanggan['nama_lengkap'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">NIK</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right font-mono">{{ $dataPelanggan['nik'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">No. HP</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $dataPelanggan['no_hp'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $dataPelanggan['email'] ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Detail Layanan --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-document-text class="w-5 h-5 text-primary-500" />
                Detail Layanan
            </h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Jenis Layanan</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $detailLayanan['jenis_layanan'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ID Meter</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right font-mono">{{ $detailLayanan['id_meter'] ?? '-' }}</dd>
                </div>
                @if(isset($detailLayanan['daya_lama']))
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Daya Lama</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $detailLayanan['daya_lama'] }} VA</dd>
                </div>
                @endif
                @if(isset($detailLayanan['daya_baru']))
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Daya Baru</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ $detailLayanan['daya_baru'] }} VA</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Tanggal Diajukan</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right">
                        {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d/m/Y H:i') : '-' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Data KTP --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-identification class="w-5 h-5 text-primary-500" />
            Data KTP
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">NIK</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $dataKtp['nik'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Nama</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataKtp['nama'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Tempat Lahir</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataKtp['tempat_lahir'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Tanggal Lahir</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataKtp['tanggal_lahir'] ?? '-' }}</dd>
                </div>
            </dl>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Jenis Kelamin</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataKtp['jenis_kelamin'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Alamat KTP</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right max-w-xs">{{ $dataKtp['alamat_ktp'] ?? '-' }}</dd>
                </div>
                @if(!empty($dataKtp['foto_ktp']) && $dataKtp['foto_ktp'] !== '-')
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Foto KTP</dt>
                    <dd class="text-sm">
                        <a href="{{ $dataKtp['foto_ktp'] }}" target="_blank" class="text-primary-600 hover:text-primary-500 underline text-sm">
                            Lihat Foto
                        </a>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Data KK --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-users class="w-5 h-5 text-primary-500" />
            Data Kartu Keluarga (KK)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">No. KK</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $dataKk['no_kk'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Kepala Keluarga</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataKk['kepala_keluarga'] ?? '-' }}</dd>
                </div>
            </dl>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Alamat KK</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right max-w-xs">{{ $dataKk['alamat_kk'] ?? '-' }}</dd>
                </div>
                @if(!empty($dataKk['foto_kk']) && $dataKk['foto_kk'] !== '-')
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Foto KK</dt>
                    <dd class="text-sm">
                        <a href="{{ $dataKk['foto_kk'] }}" target="_blank" class="text-primary-600 hover:text-primary-500 underline text-sm">
                            Lihat Foto
                        </a>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Data SLO --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-document-check class="w-5 h-5 text-primary-500" />
            Data SLO (Sertifikat Laik Operasi)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Nomor SLO</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $dataSlo['nomor_slo'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Daya SLO</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataSlo['daya_slo'] ?? '-' }} VA</dd>
                </div>
            </dl>
            <dl class="space-y-3">
                @if(!empty($dataSlo['foto_slo']) && $dataSlo['foto_slo'] !== '-')
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Foto SLO</dt>
                    <dd class="text-sm">
                        <a href="{{ $dataSlo['foto_slo'] }}" target="_blank" class="text-primary-600 hover:text-primary-500 underline text-sm">
                            Lihat Foto
                        </a>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Alamat --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-map-pin class="w-5 h-5 text-primary-500" />
            Alamat Pemasangan
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Alamat Detail</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white text-right max-w-xs">{{ $dataAlamat['alamat_detail'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">RT / RW</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataAlamat['rt'] ?? '-' }} / {{ $dataAlamat['rw'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Kelurahan</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataAlamat['kelurahan'] ?? '-' }}</dd>
                </div>
            </dl>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Kecamatan</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataAlamat['kecamatan'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Kabupaten/Kota</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataAlamat['kab_kota'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Provinsi</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $dataAlamat['provinsi'] ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Koordinat</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $dataAlamat['koordinat'] ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Riwayat Revisi --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-clock class="w-5 h-5 text-primary-500" />
            Riwayat Revisi
        </h3>
        @if(count($riwayatRevisi) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                            <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400 font-medium">Catatan</th>
                            <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400 font-medium">Tanggal</th>
                            <th class="text-left py-2 px-3 text-gray-500 dark:text-gray-400 font-medium">Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riwayatRevisi as $item)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                            <td class="py-2 px-3">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    {{ $item['status'] === 'DIKEMBALIKAN_DENGAN_REVISI' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                    {{ $item['label'] }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-gray-700 dark:text-gray-300 max-w-xs">{{ $item['note'] }}</td>
                            <td class="py-2 px-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $item['occurred_at'] }}</td>
                            <td class="py-2 px-3 text-gray-500 dark:text-gray-400">{{ $item['performed_by'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-400 dark:text-gray-500 italic">Belum ada riwayat revisi.</p>
        @endif
    </div>

    {{-- Catatan Revisi Sebelumnya --}}
    @if($catatanRevisiTerakhir)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-500" />
            Catatan Revisi Sebelumnya
        </h3>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/30 rounded-lg p-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-200 whitespace-pre-wrap">{{ $catatanRevisiTerakhir }}</p>
            @if($record->last_revision_at)
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2">
                    Revisi {{ $revisiKeBerapa }} &middot; {{ \Carbon\Carbon::parse($record->last_revision_at)->format('d/m/Y H:i') }}
                </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Lampiran --}}
    @if(count($lampiran) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-paper-clip class="w-5 h-5 text-primary-500" />
            Lampiran
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($lampiran as $index => $file)
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <x-heroicon-o-document class="w-6 h-6 text-gray-400" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                            {{ is_string($file) ? basename($file) : ('Lampiran ' . ($index + 1)) }}
                        </p>
                        @if(is_string($file))
                        <a href="{{ $file }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-500 underline">
                            Lihat File
                        </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Footer: Tombol Kembali --}}
    <div class="text-center">
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg text-sm transition-colors duration-150">
            &larr; Kembali ke Daftar Permohonan
        </a>
    </div>
</x-filament-panels::page>