<div class="pembayaran-filter-container">
    {{-- Segmented Navigation Tabs — 3 equal columns --}}
    <div class="pembayaran-status-tabs">
        <button
            type="button"
            class="pembayaran-tab {{ $activeTab === 'menunggu' ? 'pembayaran-tab-active' : '' }}"
            wire:click="setTab('menunggu')"
            wire:loading.attr="disabled"
            wire:target="setTab"
        >
            <span wire:loading.remove wire:target="setTab('menunggu')">Menunggu</span>
            <span wire:loading wire:target="setTab('menunggu')" class="pembayaran-tab-loading">Memuat...</span>
        </button>
        <button
            type="button"
            class="pembayaran-tab {{ $activeTab === 'pending' ? 'pembayaran-tab-active' : '' }}"
            wire:click="setTab('pending')"
            wire:loading.attr="disabled"
            wire:target="setTab"
        >
            <span wire:loading.remove wire:target="setTab('pending')">Pending</span>
            <span wire:loading wire:target="setTab('pending')" class="pembayaran-tab-loading">Memuat...</span>
        </button>
        <button
            type="button"
            class="pembayaran-tab {{ $activeTab === 'selesai' ? 'pembayaran-tab-active' : '' }}"
            wire:click="setTab('selesai')"
            wire:loading.attr="disabled"
            wire:target="setTab"
        >
            <span wire:loading.remove wire:target="setTab('selesai')">Selesai</span>
            <span wire:loading wire:target="setTab('selesai')" class="pembayaran-tab-loading">Memuat...</span>
        </button>
    </div>

    {{-- Loading overlay for table area --}}
    <div class="pembayaran-table-container" wire:loading.class="pembayaran-table-loading" wire:target="setTab">
        <div wire:loading wire:target="setTab" class="pembayaran-loading-overlay">
            <div class="pembayaran-loading-spinner"></div>
        </div>

        {{-- Session Messages --}}
        @if (session('success'))
            <div class="pembayaran-alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="pembayaran-alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Service Request List --}}
        @forelse($serviceRequests as $sr)
            @php
                $detail = $sr->status_detail?->value ?? '';
                $attempt = $sr->payment_attempt_count ?? 0;
                $nominal = $sr->payments->first()?->amount ?? 0;

                // ==================== TAB MENUNGGU ====================
                if ($activeTab === 'menunggu') {
                    $label = $sr->status_detail?->getLabel() ?? '-';
                    $badgeClass = 'pembayaran-badge-warning';
                    $showBayarSekarang = true;
                    $showCobaLagi = false;
                    $showCancel = true;
                    $showSisaKesempatan = false;

                // ==================== TAB PENDING ====================
                } elseif ($activeTab === 'pending') {
                    // 🔴 FIX: Label mengikuti status_detail (sinkron dengan detail tagihan)
                    // Attempt counter = payment_attempt_count (0, 1, 2)
                    // Total kesempatan = 3, sisanya = 3 - attempt
                    $nextAttempt = $attempt + 1;
                    $remaining = 3 - $attempt;
                    
                    // Label berdasarkan status_detail
                    if ($sr->status_detail === \App\Enums\PermohonanDetailStatus::PEMBAYARAN_PENDING) {
                        $label = 'Pending Pembayaran (' . $nextAttempt . '/3)';
                    } else {
                        $label = $sr->status_detail?->getLabel() ?? 'Pending';
                    }
                    
                    $badgeClass = 'pembayaran-badge-warning';
                    $showBayarSekarang = false;

                    // Attempt ke-1 (0) atau ke-2 (1) → masih bisa retry
                    // Attempt ke-3 (2) → TIDAK boleh retry lagi
                    if ($attempt < 2) {
                        $showCobaLagi = true;
                        $showSisaKesempatan = true;
                    } else {
                        $showCobaLagi = false;
                        $showSisaKesempatan = false;
                    }
                    $showCancel = true;

                // ==================== TAB SELESAI ====================
                } else {
                    $showBayarSekarang = false;
                    $showCobaLagi = false;
                    $showCancel = false;
                    $showSisaKesempatan = false;

                    if (in_array($detail, ['PEMBAYARAN_SUKSES', 'PEMBAYARAN_SELESAI'])) {
                        $label = 'Pembayaran Sukses';
                        $badgeClass = 'pembayaran-badge-success';
                    } elseif (in_array($detail, ['PEMBAYARAN_GAGAL', 'PERMOHONAN_GAGAL'])) {
                        $label = 'Permohonan Selesai : Status Gagal';
                        $badgeClass = 'pembayaran-badge-danger';
                    } elseif ($detail === 'CLOSE') {
                        // ✅ Status akhir setelah pembayaran sukses: SELESAI + CLOSE
                        $label = 'Permohonan Selesai';
                        $badgeClass = 'pembayaran-badge-success';
                    } else {
                        // Fallback untuk status tak terduga
                        $label = 'Status Tidak Diketahui';
                        $badgeClass = 'pembayaran-badge-danger';
                    }
                }
            @endphp

            <div class="pembayaran-item">
                <div class="pembayaran-item-info">
                    <span class="pembayaran-item-label">No. Permohonan</span>
                    <span class="pembayaran-item-value">{{ $sr->nomor_permohonan ?? '-' }}</span>
                </div>
                <div class="pembayaran-item-info">
                    <span class="pembayaran-item-label">Jumlah</span>
                    <span class="pembayaran-item-value">
                        Rp {{ number_format($nominal, 0, ',', '.') }}
                    </span>
                </div>
                <div class="pembayaran-item-info">
                    <span class="pembayaran-item-label">Status</span>
                    <span class="pembayaran-item-badge {{ $badgeClass }}">
                        {{ $label }}
                    </span>
                </div>

                {{-- Alasan kegagalan (hanya untuk Selesai - Gagal) --}}
                @if ($activeTab === 'selesai' && $sr->failure_reason)
                    <div class="pembayaran-item-info">
                        <span class="pembayaran-item-label">Alasan</span>
                        <span class="pembayaran-item-value">
                            {{ $sr->failure_reason->getLabel() ?? '-' }}
                        </span>
                    </div>
                @endif

                {{-- Action buttons --}}
                <div class="pembayaran-item-actions">
                    {{-- Tombol Lihat Detail — tersedia di semua tab --}}
                    <a href="{{ route('pembayaran.detail', $sr->id) }}"
                       class="pembayaran-btn pembayaran-btn-outline">
                        Lihat Detail
                    </a>

                    @if ($showBayarSekarang)
                        <button
                            type="button"
                            class="pembayaran-btn pembayaran-btn-primary"
                            wire:click="generatePaymentSession({{ $sr->id }})"
                            wire:loading.attr="disabled"
                            wire:target="generatePaymentSession({{ $sr->id }})"
                        >
                            Bayar Sekarang
                        </button>
                    @endif

                    @if ($showCobaLagi)
                        <button
                            type="button"
                            class="pembayaran-btn pembayaran-btn-primary"
                            wire:click="generatePaymentSession({{ $sr->id }})"
                            wire:loading.attr="disabled"
                            wire:target="generatePaymentSession({{ $sr->id }})"
                        >
                            Coba Bayar Lagi
                        </button>
                    @endif

                    @if ($showSisaKesempatan)
                        <span class="pembayaran-sisa-kesempatan">
                            Sisa Kesempatan: {{ $sr->getRemainingPaymentAttempts() }}
                        </span>
                    @endif

                    @if ($showCancel)
                        <button
                            type="button"
                            class="pembayaran-btn pembayaran-btn-danger"
                            wire:click="cancelRequest({{ $sr->id }})"
                            wire:loading.attr="disabled"
                            wire:target="cancelRequest({{ $sr->id }})"
                            onclick="return confirm('Yakin ingin membatalkan permohonan ini?')"
                        >
                            Batalkan Permohonan
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="pembayaran-empty">
                <p class="pembayaran-empty-text">Tidak ada data pembayaran untuk status ini.</p>
            </div>
        @endforelse
    </div>
</div>