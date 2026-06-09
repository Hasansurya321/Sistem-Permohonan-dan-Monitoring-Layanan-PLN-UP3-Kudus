# Audit State Machine — Permintaan Akun Pelanggan

**Tanggal:** 5 Juni 2026  
**Obyek:** Model `App\Models\CustomerAccountRequest` — kolom `status`

---

## 1. File yang Mengubah Status CustomerAccountRequest

### A. Admin Controller (Intentional — Diizinkan)

| File                                                       | Method      | Baris  | Perubahan              | Trigger             |
| ---------------------------------------------------------- | ----------- | ------ | ---------------------- | ------------------- |
| `app/Http/Controllers/Admin/CustomerRequestController.php` | `approve()` | 48-79  | `pending` → `approved` | Admin klik "Terima" |
| `app/Http/Controllers/Admin/CustomerRequestController.php` | `reject()`  | 81-105 | `pending` → `rejected` | Admin klik "Tolak"  |

### B. Auth Controller (Redundant — Tidak Perlu)

| File                                                    | Method       | Baris   | Perubahan                        | Trigger                       |
| ------------------------------------------------------- | ------------ | ------- | -------------------------------- | ----------------------------- |
| `app/Http/Controllers/Auth/PelangganAuthController.php` | `activate()` | 222-224 | `status` → `approved` (redundan) | User klik link aktivasi email |

**Detail celah:**  
Pada method `activate()` line 222-224, status request di-set ulang menjadi `approved`. Namun kondisi di line 183 hanya mengizinkan request dengan status `pending` atau `approved`. Token aktivasi hanya dibuat saat admin melakukan `approve()`, yang juga mengubah status menjadi `approved` dalam satu transaksi. Sehingga ketika user klik aktivasi, status sudah `approved` dari admin — operasi ini **redundan** (write no-op) dan tidak memindahkan data antar filter.

**Rekomendasi:** Hapus update status di `activate()` karena tidak diperlukan. Status sudah di-set `approved` oleh admin.

---

## 2. File yang Tidak Mengubah Status CustomerAccountRequest

| File                                                    | Alasan                                                                          |
| ------------------------------------------------------- | ------------------------------------------------------------------------------- |
| `app/Models/CustomerAccountRequest.php`                 | Tidak ada `boot()`, observer, trait, atau accessor/mutator yang mengubah status |
| `app/Models/ActivationToken.php`                        | Hanya relasi, tidak mengubah status request                                     |
| `app/Events/ServiceRequestStatusChanged.php`            | Hanya untuk `ServiceRequest`, bukan `CustomerAccountRequest`                    |
| `app/Listeners/SendWorkflowNotifications.php`           | Hanya untuk `ServiceRequest`, tidak menyentuh `CustomerAccountRequest`          |
| `app/Console/Commands/BackfillServiceRequestEvents.php` | Hanya untuk `ServiceRequest`                                                    |
| `database/seeders/FinalDemoSeeder.php`                  | Hanya membuat data baru (insert), tidak mengupdate status existing              |
| `app/Filament/AdminLayanan/Pages/PermintaanAkun.php`    | Hanya membaca data untuk ditampilkan (read-only)                                |
| Observer                                                | **Tidak ada observer** untuk model apapun di project ini                        |
| Scheduler                                               | **Tidak ada scheduler** (`bootstrap/app.php` tidak mengandung `->schedule()`)   |

---

## 3. Route yang Mengubah Status CustomerAccountRequest

| Route                                                | Method                                 | Middleware           | Perubahan                                                          |
| ---------------------------------------------------- | -------------------------------------- | -------------------- | ------------------------------------------------------------------ |
| `POST /admin/permintaan-akun-pelanggan/{id}/approve` | `CustomerRequestController::approve()` | `auth:employee`      | `pending` → `approved` ✅                                          |
| `POST /admin/permintaan-akun-pelanggan/{id}/reject`  | `CustomerRequestController::reject()`  | `auth:employee`      | `pending` → `rejected` ✅                                          |
| `GET /aktivasi/{token}`                              | `PelangganAuthController::activate()`  | **TANPA MIDDLEWARE** | **Redundan** (set `approved` pada status yang sudah `approved`) ⚠️ |

> Semua route admin dilindungi middleware `auth:employee` — hanya admin yang bisa akses.

---

## 4. Event & Listener

Tidak ada event/listener yang mengubah status `CustomerAccountRequest`. Event `ServiceRequestStatusChanged` hanya untuk model `ServiceRequest`.

---

## 5. Hasil Pengujian Skenario

### Skenario 1 — Refresh Halaman

**Input:** Register → Pending → Refresh halaman  
**Expected:** Tetap di Menunggu  
**Hasil:** ✅ **Lolos** — Tidak ada kode yang mengubah status saat GET request ke halaman filter.

### Skenario 2 — Admin Buka Detail

**Input:** Register → Pending → Admin buka detail  
**Expected:** Tetap di Menunggu  
**Hasil:** ✅ **Lolos** — Method `show()` hanya read, tidak update.

### Skenario 3 — Admin Terima

**Input:** Register → Pending → Admin Terima  
**Expected:** Keluar dari Menunggu → Masuk Selesai → Badge "Sukses"  
**Hasil:** ✅ **Lolos** — `approve()` mengubah `pending` → `approved`. Filter Menunggu query `status = pending`, Selesai query `IN (approved, rejected)`. Badge mapping: `approved` → "Sukses" (color: success).

### Skenario 4 — Admin Tolak

**Input:** Register → Pending → Admin Tolak  
**Expected:** Keluar dari Menunggu → Masuk Selesai → Badge "Gagal"  
**Hasil:** ✅ **Lolos** — `reject()` mengubah `pending` → `rejected`. Badge mapping: `rejected` → "Gagal" (color: danger).

### Skenario 5 — Aktivasi Email

**Input:** Admin Terima (status → approved) → User klik aktivasi  
**Expected:** Tetap di Selesai, tidak mengubah filter  
**Hasil:** ✅ **Lolos** — Status sudah `approved` sebelum aktivasi. Update ke `approved` lagi di `activate()` adalah **no-op**. Tidak ada perubahan posisi filter.

---

## 6. Konfirmasi Lock State

| Jalur                           | Dapat memindahkan data antar filter?           | Aman?                        |
| ------------------------------- | ---------------------------------------------- | ---------------------------- |
| Admin Terima                    | ✅ Ya, intentional                             | ✅                           |
| Admin Tolak                     | ✅ Ya, intentional                             | ✅                           |
| Aktivasi email (user klik link) | ❌ Tidak (redundan write)                      | ⚠️ Sebaiknya dihapus kodenya |
| Refresh halaman                 | ❌ Tidak                                       | ✅                           |
| Logout/Login                    | ❌ Tidak                                       | ✅                           |
| Buka detail/email               | ❌ Tidak                                       | ✅                           |
| Scheduler/Cron                  | ❌ Tidak ada                                   | ✅                           |
| Observer                        | ❌ Tidak ada                                   | ✅                           |
| Console Command                 | ❌ Tidak untuk model ini                       | ✅                           |
| Event/Listener                  | ❌ Tidak                                       | ✅                           |
| Route publik tanpa auth         | ❌ Route aktivasi hanya read + redundant write | ⚠️                           |

---

## 7. Kesimpulan

**State machine sudah terkunci. Tidak ada jalur yang bisa memindahkan data antar filter selain aksi admin Terima atau Tolak.**

Namun ditemukan **1 kode redundant** yang sebaiknya diperbaiki:

### Perbaikan yang Direkomendasikan

**File:** `app/Http/Controllers/Auth/PelangganAuthController.php`  
**Method:** `activate()`  
**Baris:** 222-224

Hapus blok update status request karena:

1. Status sudah `approved` dari admin (token hanya dibuat saat approve)
2. Sesuai permintaan: "aktivasi email TIDAK BOLEH mengubah status permintaan akun"

```php
// Hapus ini:
$request->update([
    'status' => 'approved',
]);
```
