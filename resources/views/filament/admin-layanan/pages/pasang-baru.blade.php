<x-filament-panels::page>
    <div class="tambah-daya-container">
        {{-- Segmented Navigation Tabs — 3 equal columns --}}
        <div class="td-status-tabs" style="grid-template-columns: 1fr 1fr 1fr;">
            <button
                type="button"
                class="td-tab {{ $activeTab === 'menunggu' ? 'td-tab-active' : '' }}"
                wire:click="setTab('menunggu')"
                wire:loading.attr="disabled"
                wire:target="setTab"
            >
                <span wire:loading.remove wire:target="setTab('menunggu')">Menunggu</span>
                <span wire:loading wire:target="setTab('menunggu')" class="td-tab-loading">Memuat...</span>
            </button>
            <button
                type="button"
                class="td-tab {{ $activeTab === 'pending' ? 'td-tab-active' : '' }}"
                wire:click="setTab('pending')"
                wire:loading.attr="disabled"
                wire:target="setTab"
            >
                <span wire:loading.remove wire:target="setTab('pending')">Pending</span>
                <span wire:loading wire:target="setTab('pending')" class="td-tab-loading">Memuat...</span>
            </button>
            <button
                type="button"
                class="td-tab {{ $activeTab === 'selesai' ? 'td-tab-active' : '' }}"
                wire:click="setTab('selesai')"
                wire:loading.attr="disabled"
                wire:target="setTab"
            >
                <span wire:loading.remove wire:target="setTab('selesai')">Selesai</span>
                <span wire:loading wire:target="setTab('selesai')" class="td-tab-loading">Memuat...</span>
            </button>
        </div>

        {{-- Loading overlay for table area --}}
        <div class="td-table-container" wire:loading.class="td-table-loading" wire:target="setTab">
            <div wire:loading wire:target="setTab" class="td-loading-overlay">
                <div class="td-loading-spinner"></div>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
