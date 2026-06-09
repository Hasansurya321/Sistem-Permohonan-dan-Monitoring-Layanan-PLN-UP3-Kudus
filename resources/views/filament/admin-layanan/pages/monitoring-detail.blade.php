<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            /* HAPUS SEMUA WRAPPER FILAMENT YANG MEMBATASI */
            .fi-page,
            .fi-page > *,
            .fi-page > section,
            .fi-page > section > *,
            .fi-page > section > div,
            .fi-page > section > div > *,
            .fi-main,
            .fi-main > *,
            .fi-main-ctn,
            .fi-sidebar,
            .fi-topbar {
                all: unset !important;
                display: revert !important;
                box-sizing: border-box !important;
            }
            /* Framework grid — override jadi block */
            [class*="grid"][class*="auto-cols-fr"],
            [class*="gap-y-8"] {
                display: block !important;
            }
            /* Container utama — full width tanpa padding */
            .fi-main-ctn,
            .fi-main > div {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Content wrapper */
            .max-w-5xl {
                max-width: 1200px !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }
        </style>
    @endpush

    @include('components.monitoring.detail-content', [
        'sr' => $this->record,
        'backUrl' => \App\Filament\AdminLayanan\Pages\Monitoring::getUrl(),
        'showPaymentCTA' => false,
        'events' => $this->record->events()->orderBy('occurred_at', 'desc')->get(),
        'lokasi' => data_get($this->record->payload_json, 'lokasi', []),
    ])
</x-filament-panels::page>