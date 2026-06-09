<x-filament-panels::page>
    <div class="akun-kpi-grid">
        <div class="akun-kpi-row-2">
            <a href="{{ \App\Filament\AdminLayanan\Pages\TambahDaya::getUrl() }}" class="akun-kpi-card-link">
                <x-filament.admin-layanan.kpi-card-akun
                    label="Jumlah:"
                    value="1239"
                    subtitle="Tambah Daya"
                    icon="users"
                    color="#14b8d4"
                />
            </a>
            <a href="{{ \App\Filament\AdminLayanan\Pages\PasangBaru::getUrl() }}" class="akun-kpi-card-link">
                <x-filament.admin-layanan.kpi-card-akun
                    label="Menunggu:"
                    value="9"
                    subtitle="Pasang Baru"
                    icon="user-plus"
                    color="#7c3aed"
                />
            </a>
        </div>
    </div>
</x-filament-panels::page>
