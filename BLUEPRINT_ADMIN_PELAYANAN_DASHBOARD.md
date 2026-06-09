# Blueprint Dashboard Admin Pelayanan

Blueprint ini menyusun redesign dashboard Filament panel `admin-pelayanan` tanpa mengubah logic backend.

## Prinsip Implementasi

- Pertahankan page utama dengan `extends Filament\Pages\Dashboard` agar tetap memakai route root panel dan lifecycle dashboard Filament.
- Override view dashboard ke Blade custom.
- Pisahkan layout visual dashboard dari data/query yang sudah ada.
- Pakai widget Filament untuk area yang masih butuh lifecycle widget.
- Gunakan `getViewData()` hanya untuk data presentational dan pengelompokan section.

## File yang Diubah / Ditambahkan

### 1. Dashboard page class

Path:
`app/Filament/AdminPelayanan/Pages/AdminLayananDashboard.php`

Tanggung jawab:
- Menjadi entry point dashboard panel.
- Menetapkan custom Blade view.
- Mengelompokkan widget menjadi KPI, chart, dan table.
- Menyediakan data ringan untuk Blade melalui `getViewData()`.

Status implementasi:
- Sudah discaffold.

### 2. Dashboard Blade utama

Path:
`resources/views/filament/admin-pelayanan/pages/admin-layanan-dashboard.blade.php`

Tanggung jawab:
- Menjadi shell dashboard custom.
- Merender hero, quick links, KPI, chart, dan table.
- Memanggil widget Filament secara terstruktur dengan `<x-filament-widgets::widgets>`.

Status implementasi:
- Sudah discaffold.

### 3. Hero partial

Path:
`resources/views/filament/admin-pelayanan/partials/dashboard-hero.blade.php`

Tanggung jawab:
- Menampilkan header dashboard versi custom.
- Menjelaskan boundary arsitektur: UI berubah, backend tetap.

Status implementasi:
- Sudah discaffold.

### 4. Quick links partial

Path:
`resources/views/filament/admin-pelayanan/partials/dashboard-quick-links.blade.php`

Tanggung jawab:
- Menyediakan shortcut ke resource utama panel.

Status implementasi:
- Sudah discaffold.

## File yang Tetap Digunakan

- `app/Providers/Filament/AdminPelayananPanelProvider.php`
- `app/Filament/AdminPelayanan/Widgets/AdminLayananStatsOverview.php`
- `app/Filament/AdminPelayanan/Widgets/TrenPermohonanPerHariChart.php`
- `app/Filament/AdminPelayanan/Widgets/PaymentGatewayChart.php`
- `app/Filament/AdminPelayanan/Widgets/AntrianVerifikasiRegistrasiTable.php`
- `app/Filament/AdminPelayanan/Widgets/AntrianVerifikasiSloTable.php`
- `app/Filament/AdminPelayanan/Widgets/MenungguDistribusiSetelahPembayaranTable.php`
- seluruh Resource, Model, Enum, Controller, dan query backend

## Flow Data Teknis

1. Panel provider mendaftarkan page dashboard.
2. Route panel `/internal/admin-pelayanan/` tetap diarahkan ke `AdminLayananDashboard`.
3. `AdminLayananDashboard` merender custom Blade.
4. Blade memanggil widget per section:
   - KPI memakai `AdminLayananStatsOverview`
   - chart memakai `TrenPermohonanPerHariChart` dan `PaymentGatewayChart`
   - table memakai tiga widget table custom yang sudah ada
5. Masing-masing widget tetap menjalankan logic existing-nya sendiri:
   - `getStats()`
   - `getData()`
   - `getViewData()`

## Tahap Rollout yang Disarankan

1. Finalisasi visual shell dashboard di Blade utama.
2. Rapikan masing-masing widget agar visual card/table/chart konsisten dengan shell baru.
3. Jika KPI/chart/table perlu data real:
   - ganti isi method widget masing-masing
   - jangan pindahkan query ke Blade
4. Jika ingin custom sidebar/topbar global satu panel:
   - pakai render hook dan CSS terlebih dahulu
   - hindari publish vendor views kecuali benar-benar perlu

## Catatan Arsitektur Penting

- Jangan pindah ke `Filament\Pages\Page` biasa untuk menggantikan dashboard root kecuali Anda juga siap override routing page.
- Jangan render table resource sepenuhnya manual jika masih butuh sorting, filter, pagination, dan action modal bawaan Filament.
- Untuk redesign total, lebih aman memindahkan “shell” ke Blade baru sambil mempertahankan widget lifecycle daripada membongkar dashboard menjadi HTML statis penuh.
