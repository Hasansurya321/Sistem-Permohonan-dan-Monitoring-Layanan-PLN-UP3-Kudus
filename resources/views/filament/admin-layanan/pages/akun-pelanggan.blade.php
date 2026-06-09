<x-filament-panels::page>
    <div class="akun-kpi-grid">
        <div class="akun-kpi-row-3">
            <a href="#" class="akun-kpi-card-link">
                <x-filament.admin-layanan.kpi-card-akun
                    label="Total Pelanggan:"
                    value="{{ $totalPelanggan }}"
                    subtitle="Akun Pelanggan"
                    icon="users"
                    color="#14b8d4"
                />
            </a>
            <a href="{{ \App\Filament\AdminLayanan\Pages\PermintaanAkun::getUrl() }}" class="akun-kpi-card-link">
                <x-filament.admin-layanan.kpi-card-akun
                    label="Permintaan Akun:"
                    value="{{ $permintaanAkun }}"
                    subtitle="Menunggu Persetujuan"
                    icon="user-check"
                    color="#7c3aed"
                />
            </a>
            <x-filament.admin-layanan.kpi-card-akun
                label="Lupa Password:"
                value="{{ $lupaPassword }}"
                subtitle="Permintaan Reset"
                icon="lock"
                color="#06b6d4"
            />
        </div>
    </div>
</x-filament-panels::page>