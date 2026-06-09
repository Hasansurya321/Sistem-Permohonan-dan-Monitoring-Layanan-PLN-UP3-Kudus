# INVESTIGASI: Menu Pembayaran di Sidebar Admin Layanan Tidak Berpindah Halaman

> **Status:** Root Cause Ditemukan
> **Tanggal:** 30 Mei 2026
> **File Investigasi:** `app/Providers/Filament/AdminlayananPanelProvider.php`

---

## 1. Root Cause

**Lokasi:** `app/Providers/Filament/AdminlayananPanelProvider.php` — **Baris 78-81**

```php
NavigationItem::make('Pembayaran')
    ->icon('heroicon-o-credit-card')
    ->url('#')                          // <── INI PENYEBABNYA
    ->sort(4),
```

Menu **Pembayaran** menggunakan **`url('#')`** — yaitu anchor ke halaman yang sama (current page). Ini menyebabkan:

- Browser tidak berpindah kemana-mana
- URL tetap di `internal/admin-layanan/permohonan-layanan#`
- Tidak ada navigasi ke route `/pembayaran`

---

## 2. Definisi Menu Lengkap

Sidebar Admin Layanan didefinisikan secara eksplisit menggunakan `NavigationBuilder` di PanelProvider.

### Semua Item Navigasi (Baris 62-90):

```php
->navigation(function (NavigationBuilder $builder): NavigationBuilder {
    return $builder->items([
        NavigationItem::make('Dashboard')
            ->icon('heroicon-o-home')
            ->url(fn (): string => \App\Filament\AdminLayanan\Pages\Dashboard::getUrl())
            ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.dashboard'))
            ->sort(1),

        NavigationItem::make('Akun Pelanggan')
            ->icon('heroicon-o-users')
            ->url(fn (): string => \App\Filament\AdminLayanan\Pages\AkunPelanggan::getUrl())
            ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.akun-pelanggan'))
            ->sort(2),

        NavigationItem::make('Permohonan Layanan')
            ->icon('heroicon-o-document-text')
            ->url(fn (): string => \App\Filament\AdminLayanan\Pages\PermohonanLayanan::getUrl())
            ->isActiveWhen(fn () => request()->routeIs('filament.admin-layanan.pages.permohonan-layanan*'))
            ->sort(3),

        NavigationItem::make('Pembayaran')          // ← MASALAH DI SINI
            ->icon('heroicon-o-credit-card')
            ->url('#')                               // ← HARUSNYA route('pembayaran')
            ->sort(4),

        NavigationItem::make('Distribusi Unit')     // ← JUGA BERMASALAH
            ->icon('heroicon-o-map')
            ->url('#')                               // ← JUGA '#'
            ->sort(5),

        NavigationItem::make('Laporan Unit')        // ← JUGA BERMASALAH
            ->icon('heroicon-o-chart-bar')
            ->url('#')                               // ← JUGA '#'
            ->sort(6),
    ]);
})
```

### Ringkasan URL per Menu:

| Menu               | URL yang Digunakan            | Status                                     |
| ------------------ | ----------------------------- | ------------------------------------------ |
| Dashboard          | `Dashboard::getUrl()`         | ✅ Benar (mengarah ke halaman Filament)    |
| Akun Pelanggan     | `AkunPelanggan::getUrl()`     | ✅ Benar                                   |
| Permohonan Layanan | `PermohonanLayanan::getUrl()` | ✅ Benar                                   |
| **Pembayaran**     | **`#`**                       | ❌ **SALAH — Tidak mengarah ke mana-mana** |
| Distribusi Unit    | `#`                           | ❌ Placeholder                             |
| Laporan Unit       | `#`                           | ❌ Placeholder                             |

---

## 3. URL yang Dihasilkan Browser

Karena menggunakan `url('#')`, Filament akan me-render tag HTML sebagai berikut:

```html
<a href="#" class="fi-sidebar-item-button ..." wire:navigate>
    <!-- icon -->
    <svg class="heroicon-o-credit-card ...">...</svg>
    <!-- label -->
    <span class="...">Pembayaran</span>
</a>
```

**Akibat:** Ketika diklik, browser hanya melakukan scroll ke anchor `#` pada halaman yang sama, bukan navigasi ke `/pembayaran`.

---

## 4. Route Pembayaran yang Sudah Terdaftar

Route pembayaran sudah benar dan berfungsi di level Blade (non-Filament):

**File:** `routes/web.php` — Baris 107

```php
Route::middleware(['customer.only'])->group(function () {
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran');
    // ...
});
```

Route ini berada di **grup middleware `customer.only`** yang menggunakan guard `web` dengan role `pelanggan`.

---

## 5. Masalah Tambahan: Guard/Auth Conflict

Panel `admin-layanan` menggunakan **guard `employee`** untuk pegawai internal.

Route `/pembayaran` menggunakan **guard `web`** khusus role **`pelanggan`**.

**Implikasi:**

- Karyawan yang login ke panel admin-layanan TIDAK bisa mengakses route `/pembayaran` karena guard tidak cocok.
- Route `/pembayaran` hanya untuk user pelanggan (guard `web` + role `pelanggan`).

---

## 6. Kesimpulan Root Cause

| Aspek                 | Detail                                                                                                                   |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **File**              | `app/Providers/Filament/AdminlayananPanelProvider.php`                                                                   |
| **Baris**             | 80                                                                                                                       |
| **Kode Masalah**      | `->url('#')`                                                                                                             |
| **Seharusnya**        | Harusnya mengarah ke URL eksternal atau Filament page internal                                                           |
| **Penyebab langsung** | `#` tidak melakukan navigasi ke halaman manapun                                                                          |
| **Penyebab akar**     | Menu Pembayaran belum diimplementasikan di Filament Admin Layanan, jadi ditaruh `#` sebagai placeholder yang belum diisi |

**Daftar menu yang menggunakan `url('#')` (placeholder):**

1. **Pembayaran** (baris 78-81)
2. **Distribusi Unit** (baris 82-85)
3. **Laporan Unit** (baris 86-89)

---

## 7. Rekomendasi Perbaikan

Ada 2 opsi:

### Opsi A: Jika Pembayaran adalah halaman Filament (panel admin-layanan)

Buat Page Class baru:

```php
app/Filament/AdminLayanan/Pages/Pembayaran.php
```

Lalu di PanelProvider:

```php
->url(fn (): string => \App\Filament\AdminLayanan\Pages\Pembayaran::getUrl())
```

### Opsi B: Jika Pembayaran adalah halaman Blade eksternal (pelanggan)

Gunakan URL absolut:

```php
->url('/pembayaran', shouldOpenInNewTab: false)
```

**Catatan:** Menu ini akan keluar dari panel Filament dan masuk ke halaman Blade pelanggan. Pastikan guard auth cocok.
