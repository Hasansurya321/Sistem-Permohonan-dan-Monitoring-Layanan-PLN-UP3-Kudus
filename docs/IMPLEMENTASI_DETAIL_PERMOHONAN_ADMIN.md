# LAPORAN IMPLEMENTASI — DETAIL PERMOHONAN ADMIN MONITORING

**Tanggal:** 6 Juni 2026
**Status:** ✅ SELESAI — Tampilan identik dengan halaman user

---

## 1. ARSITEKTUR

### Alur Data

```
User klik "Detail" di halaman Monitoring Admin
        ↓
Link: /admin/monitoring-detail/{id}
        ↓
Admin\MonitoringController::showDetail($id)
        ↓
Query ServiceRequest dengan relasi (applicant, events, payments, submitter.masterPelanggan)
        ↓
View: admin/monitoring/detail.blade.php
        ↓
Layout: layouts/admin-monitoring.blade.php
        ↓
@include('components.monitoring.detail-content', [...])
        ↓
SAME SOURCE OF TRUTH — Sama persis dengan halaman user (/pelanggan/monitoring/show.blade.php)
```

---

## 2. FILE YANG DIBUAT (BARU)

### 2.1 `resources/views/layouts/admin-monitoring.blade.php`

**Fungsi:** Layout khusus admin yang strukturnya **identik** dengan layout pelanggan.

**Isi:**

- DOCTYPE HTML dengan meta tags
- Livewire Styles
- Vite app.css + app.js
- FontAwesome 6.4.0 CDN
- Google Fonts Inter
- Navbar PLN UP3 Kudus (logo, menu Dashboard Admin, Monitoring)
- Tombol Logout
- Main container `max-w-[1200px] mx-auto min-h-screen pt-24 pb-12`

**Perbedaan dengan layout pelanggan:**
| Aspek | Pelanggan | Admin |
|-------|-----------|-------|
| Navbar menu | Dashboard, Monitoring, Pembayaran | Dashboard Admin, Monitoring |
| User section | Dropdown user/profile | Text "Admin Layanan" + Logout |
| Footer | `x-footer` dengan info PLN | Tidak ada |

### 2.2 `app/Http/Controllers/Admin/MonitoringController.php`

```php
class MonitoringController extends Controller
{
    public function showDetail($id)
    {
        $sr = ServiceRequest::with([
            'applicant',
            'events' => fn($q) => $q->orderBy('occurred_at', 'desc'),
            'payments',
            'submitter.masterPelanggan'
        ])->findOrFail($id);

        $sr->ensureInitialEvent();

        return view('admin.monitoring.detail', [
            'sr' => $sr,
            'backUrl' => url('/internal/admin-layanan/monitoring'),
            'showPaymentCTA' => false,
            'events' => $sr->events,
            'lokasi' => data_get($sr->payload_json, 'lokasi', []),
        ]);
    }
}
```

**Variabel yang dikirim ke view:**

| Variable          | Value                                | Sumber                                      |
| ----------------- | ------------------------------------ | ------------------------------------------- |
| `$sr`             | ServiceRequest                       | Query with relations                        |
| `$backUrl`        | `/internal/admin-layanan/monitoring` | Hardcode                                    |
| `$showPaymentCTA` | `false`                              | Admin tidak perlu bayar                     |
| `$events`         | Collection of ServiceRequestEvent    | Sorted by occurred_at desc                  |
| `$lokasi`         | Array dari payload_json              | `data_get($sr->payload_json, 'lokasi', [])` |

### 2.3 `resources/views/admin/monitoring/detail.blade.php`

```blade
@extends('layouts.admin-monitoring')

@section('content')
    @include('components.monitoring.detail-content', [
        'sr' => $sr,
        'backUrl' => $backUrl,
        'showPaymentCTA' => $showPaymentCTA,
        'events' => $events,
        'lokasi' => $lokasi,
    ])
@endsection
```

Hanya 8 baris — seluruh konten berasal dari `detail-content.blade.php`.

---

## 3. FILE YANG DIMODIFIKASI

### 3.1 `routes/web.php`

**Route baru ditambahkan:**

```php
Route::middleware(['auth:employee'])
    ->get('/admin/monitoring-detail/{id}',
        [\App\Http\Controllers\Admin\MonitoringController::class, 'showDetail'])
    ->name('admin.monitoring.detail');
```

**Middleware:** `auth:employee` — hanya employee/admin yang bisa akses.
**URL:** `/admin/monitoring-detail/{id}` — parameter ID ServiceRequest.

### 3.2 `resources/views/livewire/admin/monitoring-permohonan.blade.php`

**Perubahan:** Link "Detail" di tabel monitoring admin diubah dari Filament URL ke route baru:

```blade
{{-- SEBELUM --}}
<a href="{{ \App\Filament\AdminLayanan\Pages\MonitoringDetail::getUrl() }}?record={{ $item->id }}">

{{-- SESUDAH --}}
<a href="{{ route('admin.monitoring.detail', $item->id) }}">
```

---

## 4. SINGLE SOURCE OF TRUTH

`resources/views/components/monitoring/detail-content.blade.php` adalah **inti dari seluruh implementasi**.

File ini (386 baris) berisi:

- Back button
- Hero banner (gradient + badge + progress)
- Payment CTA (conditional)
- Cancellation notice
- Grid 2 kolom (stepper kiri + konten kanan)
- Workflow history (expandable timeline)
- Data pemohon
- Data layanan
- Lokasi instalasi
- Data SLO

**Digunakan oleh 3 halaman:**

1. `pelanggan/monitoring/show.blade.php` → untuk user
2. `filament/admin-layanan/pages/monitoring-detail.blade.php` → untuk admin (Filament)
3. `admin/monitoring/detail.blade.php` → untuk admin (layout independent) ✅ **YANG AKTIF**

---

## 5. DEPENDENSI

| Dependensi                                                | Untuk                                   |
| --------------------------------------------------------- | --------------------------------------- |
| `@vite(['resources/css/app.css', 'resources/js/app.js'])` | Tailwind CSS                            |
| `@livewireStyles` + `@livewireScripts`                    | Livewire (untuk komponen interaktif)    |
| FontAwesome 6.4.0 CDN                                     | Icons (fa-check, fa-star, fa-user, dll) |
| Google Fonts Inter                                        | Font                                    |
| `App\Support\WorkflowStatusHelper`                        | Progress percent, icon, badge           |
| `App\Helpers\WaktuHelper`                                 | Format tanggal                          |
| `App\Enums\PermohonanStatus`                              | Status enum + labels                    |
| `App\Enums\PermohonanDetailStatus`                        | Detail status enum                      |
| `config('internal_roles.*.label')`                        | Label role employee                     |

---

## 6. CARA ROLLBACK

Jika terjadi masalah, rollback dengan menghapus/mengembalikan:

### Hapus file baru:

```bash
rm resources/views/layouts/admin-monitoring.blade.php
rm app/Http/Controllers/Admin/MonitoringController.php
rm resources/views/admin/monitoring/detail.blade.php
```

### Kembalikan routes/web.php:

Hapus baris:

```php
Route::middleware(['auth:employee'])->get('/admin/monitoring-detail/{id}', ...);
```

### Kembalikan livewire view:

Ubah link detail kembali ke:

```blade
<a href="{{ \App\Filament\AdminLayanan\Pages\MonitoringDetail::getUrl() }}?record={{ $item->id }}">
```

### Reset layout:

Ubah `resources/views/filament/admin-layanan/pages/monitoring-detail.blade.php` menjadi hanya include detail-content (tanpa CSS override yang agresif).

---

## 7. FILE LAMA YANG TIDAK DIGUNAKAN (AMAN DIHAPUS)

| File                                                                             | Status                                       |
| -------------------------------------------------------------------------------- | -------------------------------------------- |
| `app/Http/Controllers/Admin/MonitoringController.php` (lama — method index/show) | ❌ Tidak ada (sudah dihapus sebelumnya)      |
| `resources/views/admin/monitoring/index.blade.php`                               | ❌ Tidak ada (sudah dihapus sebelumnya)      |
| `resources/views/admin/monitoring/detail.blade.php` (lama — custom card)         | ❌ Tidak ada (sudah dihapus sebelumnya)      |
| `resources/views/components/admin-layout.blade.php`                              | ❌ Tidak ada (sudah dihapus sebelumnya)      |
| `resources/views/components/monitoring/hero-banner.blade.php`                    | ✅ Masih ada (tidak dipakai)                 |
| `resources/views/components/monitoring/workflow-history.blade.php`               | ✅ Masih ada (tidak dipakai)                 |
| `resources/views/components/monitoring/data-pemohon.blade.php`                   | ✅ Masih ada (tidak dipakai)                 |
| `resources/views/components/monitoring/data-layanan.blade.php`                   | ✅ Masih ada (tidak dipakai)                 |
| `resources/views/components/monitoring/lokasi-instalasi.blade.php`               | ✅ Masih ada (tidak dipakai)                 |
| `resources/views/components/monitoring/data-slo.blade.php`                       | ✅ Masih ada (tidak dipakai)                 |
| `app/Filament/AdminLayanan/Pages/MonitoringDetail.php`                           | ✅ Masih ada (tidak dipakai — Filament page) |

---

## 8. DOKUMENTASI FILE LENGKAP SAAT INI

### File Aktif untuk Detail Permohonan Admin:

| #   | File                                                             | Peran                                  |
| --- | ---------------------------------------------------------------- | -------------------------------------- |
| 1   | `routes/web.php`                                                 | Route `/admin/monitoring-detail/{id}`  |
| 2   | `app/Http/Controllers/Admin/MonitoringController.php`            | Controller                             |
| 3   | `resources/views/admin/monitoring/detail.blade.php`              | View (hanya include)                   |
| 4   | `resources/views/layouts/admin-monitoring.blade.php`             | Layout (navbar + container)            |
| 5   | `resources/views/components/monitoring/detail-content.blade.php` | **Single Source of Truth** (386 baris) |
| 6   | `resources/views/components/monitoring/stepper.blade.php`        | Komponen stepper                       |
| 7   | `resources/views/livewire/admin/monitoring-permohonan.blade.php` | Tabel monitoring (link detail)         |

### File Aktif untuk Halaman User:

| #   | File                                                  | Peran                |
| --- | ----------------------------------------------------- | -------------------- |
| 1   | `app/Http/Controllers/MonitoringController.php`       | Controller `show()`  |
| 2   | `resources/views/pelanggan/monitoring/show.blade.php` | View (hanya include) |
| 3   | `resources/views/layouts/pelanggan.blade.php`         | Layout user          |

---

## 9. AKSES URL

| Halaman                  | URL                                  | Auth                |
| ------------------------ | ------------------------------------ | ------------------- |
| Monitoring Admin (tabel) | `/internal/admin-layanan/monitoring` | Filament (employee) |
| Detail Permohonan Admin  | `/admin/monitoring-detail/{id}`      | Employee            |
| Monitoring User (tabel)  | `/monitoring`                        | Customer            |
| Detail Permohonan User   | `/monitoring/{id}`                   | Customer            |
