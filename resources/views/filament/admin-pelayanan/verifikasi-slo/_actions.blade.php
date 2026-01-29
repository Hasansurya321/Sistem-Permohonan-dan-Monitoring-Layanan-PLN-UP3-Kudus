<div class="flex items-center gap-4 mt-4 pt-4 border-t border-slate-100">
    <x-filament::button
        wire:click="verifikasiSukses"
        wire:loading.attr="disabled"
        color="success"
        icon="heroicon-o-check-circle"
    >
        Verifikasi Sukses
    </x-filament::button>

    <x-filament::button
        disabled
        color="gray"
        icon="heroicon-o-x-circle"
        x-tooltip="'Fitur belum aktif'"
    >
        Verifikasi Gagal
    </x-filament::button>
</div>
