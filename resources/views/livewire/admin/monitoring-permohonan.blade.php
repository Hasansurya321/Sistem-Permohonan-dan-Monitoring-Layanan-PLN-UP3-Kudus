<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Monitoring Permohonan</h1>
        <p class="text-sm text-gray-500 mt-1">Pantau seluruh permohonan layanan pelanggan berdasarkan unit dan status.</p>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Dropdown Unit --}}
            <div>
                <label for="unit-select" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <select id="unit-select" wire:model.live="selectedUnit"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @foreach($units as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Dropdown Status --}}
            <div>
                <label for="status-select" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status-select" wire:model.live="selectedStatus"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="SEMUA">Semua Status</option>
                    @foreach($this->statusOptions as $status)
                        <option value="{{ $status }}">{{ $this->getStatusLabel($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Registrasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis Layanan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Detail Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($permohonan as $item)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-900">
                                {{ $item->nomor_permohonan ?? 'DRF-' . str_pad((string)$item->id, 6, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ $item->applicant?->nama_lengkap ?? $item->applicant_nik ?? '-' }}</div>
                                <div class="text-xs text-gray-400">{{ $item->applicant_nik ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $item->jenis_layanan ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $item->status->getLabel() }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @php
                                    $statusValue = $item->status instanceof \App\Enums\PermohonanStatus ? $item->status->value : $item->status;
                                    $statusColor = match($statusValue) {
                                        'VERIFIKASI_DATA' => 'yellow',
                                        'UNIT_SURVEY' => 'blue',
                                        'UNIT_PERENCANAAN' => 'indigo',
                                        'PEMBAYARAN' => 'purple',
                                        'UNIT_KONSTRUKSI' => 'orange',
                                        'UNIT_PENYALAAN' => 'teal',
                                        'SELESAI' => 'green',
                                        default => 'gray',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800">
                                    {{ $item->status->getLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $item->status_detail ? $this->getStatusLabel($item->status_detail->value) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $item->submitted_at ? $item->submitted_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('admin.monitoring.detail', $item->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400">
                                Tidak ada permohonan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Total --}}
    <div class="mt-4 text-sm text-gray-500">
        Total: <strong>{{ $permohonan->count() }}</strong> permohonan
        @if($selectedUnit !== 'SEMUA')
            di unit <strong>{{ $units[$selectedUnit] ?? $selectedUnit }}</strong>
        @endif
    </div>
</div>