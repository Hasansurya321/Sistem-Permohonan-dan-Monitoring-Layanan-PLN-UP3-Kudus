# 🔐 FILTER ADMIN LAYANAN — DOKUMENTASI PENGUNCIAN LOGIC FINAL

**Tanggal:** 1 Juni 2026
**Unit:** Admin Layanan — Permohonan Layanan (Tambah Daya / Pasang Baru)
**Status:** ✅ TERKUNCI — Tidak ada perubahan tanpa dokumen ini direvisi

---

## 📋 DAFTAR FILTER & SCOPE DATABASE

| Filter (Tab)       | Scope Name          | `status` (Global) | `status_detail`              | Tampilan Badge                |
| ------------------ | ------------------- | ----------------- | ---------------------------- | ----------------------------- |
| **Menunggu**       | `waitingForAdmin()` | `VERIFIKASI_DATA` | `MENUNGGU_VERIFIKASI_DATA`   | 🔵 Menunggu Verifikasi Data   |
| **Pending**        | `pendingRevision()` | `VERIFIKASI_DATA` | `DIKEMBALIKAN_DENGAN_REVISI` | 🟡 Dikembalikan Dengan Revisi |
| **Selesai Sukses** | `adminSuccess()`    | `VERIFIKASI_DATA` | `ADMINISTRASI_SELESAI`       | 🟢 Diterima PLN               |
| **Selesai Gagal**  | `adminFailed()`     | `SELESAI`         | `ADMINISTRASI_SELESAI`       | 🔴 Ditolak PLN                |

> **KETENTUAN:** Setiap kombinasi `(status, status_detail)` hanya boleh masuk ke **SATU** filter. Tidak ada tumpang tindih. Verifikasi bahwa tidak ada scope yang menghasilkan query yang sama.

---

## 🎯 AKSI ADMIN — SATU-SATUNYA PENYEBAB PERPINDAHAN

### Aksi 1: Verifikasi Data ✅

| Atribut           | Nilai                                                                      |
| ----------------- | -------------------------------------------------------------------------- |
| **Tombol**        | `Verifikasi Data` (hijau, icon check-circle)                               |
| **Metode**        | `adminAccept()`                                                            |
| **Syarat Muncul** | `activeTab = 'menunggu'` AND `status_detail = MENUNGGU_VERIFIKASI_DATA`    |
| **Yang Terjadi**  | `MENUNGGU_VERIFIKASI_DATA → VERIFIKASI_DATA_SUKSES → ADMINISTRASI_SELESAI` |
| **Konversi No**   | Draft → Resmi (`DRF-2026-001` → `TD-2026-001` / `PB-2026-001`)             |
| **Perpindahan**   | **Menunggu → Selesai (Sukses)**                                            |
| **Status Final**  | `VERIFIKASI_DATA` + `ADMINISTRASI_SELESAI`                                 |

### Aksi 2: Kembalikan ke Pelanggan ⬅️

| Atribut           | Nilai                                                                                            |
| ----------------- | ------------------------------------------------------------------------------------------------ |
| **Tombol**        | `Kembalikan ke Pelanggan (Revisi x/2)` (kuning, icon arrow-uturn)                                |
| **Metode**        | `adminSendBack(string $note)`                                                                    |
| **Syarat Muncul** | `activeTab = 'menunggu'` AND `status_detail = MENUNGGU_VERIFIKASI_DATA` AND `revision_count < 2` |
| **Wajib Diisi**   | `Catatan Perbaikan` (textarea, required)                                                         |
| **Yang Terjadi**  | `MENUNGGU_VERIFIKASI_DATA → DIKEMBALIKAN_DENGAN_REVISI` + `revision_count++`                     |
| **Perpindahan**   | **Menunggu → Pending**                                                                           |
| **Status Final**  | `VERIFIKASI_DATA` + `DIKEMBALIKAN_DENGAN_REVISI`                                                 |

### Aksi 3: Tolak Permohonan ❌

| Atribut           | Nilai                                                                                             |
| ----------------- | ------------------------------------------------------------------------------------------------- |
| **Tombol**        | `Tolak Permohonan` (merah, icon x-circle)                                                         |
| **Metode**        | `adminReject(?string $note)`                                                                      |
| **Syarat Muncul** | `activeTab = 'menunggu'` AND `status_detail = MENUNGGU_VERIFIKASI_DATA` AND `revision_count >= 2` |
| **Yang Terjadi**  | `MENUNGGU_VERIFIKASI_DATA → DITOLAK → ADMINISTRASI_SELESAI`                                       |
| **Perpindahan**   | **Menunggu → Selesai (Gagal)**                                                                    |
| **Status Final**  | `SELESAI` + `ADMINISTRASI_SELESAI`                                                                |

---

## 🔄 STATE MACHINE — Diagram Perpindahan

```
                           ┌────────────────────────┐
                           │       MENUNGGU         │
                           │  MENUNGGU_VERIFIKASI   │
                           │      _DATA             │
                           └───────────┬────────────┘
                                       │
                ┌──────────────────────┼──────────────────────┐
                │                      │                      │
                ▼                      ▼                      ▼
     ┌──────────────────┐   ┌──────────────────┐   ┌──────────────────┐
     │     PENDING      │   │  SELESAI SUKSES   │   │  SELESAI GAGAL   │
     │DIKEMBALIKAN_DENGAN│   │ADMINISTRASI_SELESAI│   │ADMINISTRASI_SELESAI│
     │    _REVISI       │   │ (VERIFIKASI_DATA)  │   │   (SELESAI)       │
     └────────┬─────────┘   └──────────────────┘   └──────────────────┘
              │
              │ (Pelanggan kirim ulang via Monitoring → Perlu Revisi)
              │
              ▼
     ┌──────────────────┐
     │     MENUNGGU     │  ◀── Kembali ke awal siklus
     │ (lagi, di atas)  │
     └──────────────────┘
```

---

## 📌 ATURAN KETAT (WAJIB)

### 1. ❌ DILARANG — Perpindahan Tanpa Aksi

Tidak ada mekanisme apapun yang memindahkan permohonan antar filter selain **3 aksi admin di atas**. Berikut yang TIDAK menyebabkan perpindahan:

- ✅ Switch tab (klik Menunggu/Pending/Selesai) — hanya ganti query, tidak ubah data
- ✅ Refresh halaman — read-only
- ✅ Buka/tutup halaman Detail — read-only
- ✅ Klik tombol lalu batal — requiresConfirmation() mencegah jika tidak jadi

### 2. ❌ DILARANG — Tumpang Tindih Scope

Setiap scope harus ekslusif:

```php
// ✅ BENAR — Tidak ada overlap
scopeWaitingForAdmin: status=VERIFIKASI_DATA, status_detail=MENUNGGU_VERIFIKASI_DATA
scopePendingRevision:  status=VERIFIKASI_DATA, status_detail=DIKEMBALIKAN_DENGAN_REVISI
scopeAdminSuccess:     status=VERIFIKASI_DATA, status_detail=ADMINISTRASI_SELESAI
scopeAdminFailed:      status=SELESAI,         status_detail=ADMINISTRASI_SELESAI
```

### 3. ❌ DILARANG — Mengubah Scope Tanpa Persetujuan

Jika suatu saat ada perubahan scope, maka **WAJIB** memastikan:

- Tidak ada overlap dengan scope lain
- Semua kombinasi `(status, status_detail)` tetap UNIK milik satu filter
- Dokumentasi ini diupdate

### 4. ✅ Batas Revisi Maksimal: 2 Kali

- `revision_count >= 2` → tombol `Kembalikan ke Pelanggan` HILANG
- `revision_count >= 2` → tombol `Tolak Permohonan` MUNCUL
- Pelanggan maksimal 2x kirim ulang (total 3x pengecekan admin)

---

## 🗺️ END-TO-END FLOW LENGKAP

### Flow Sukses (1x Verifikasi)

```
Pelanggan Kirim → Menunggu → [Verifikasi Data] → Selesai Sukses
                                                        ↓
                                              Diterima PLN
```

### Flow Revisi (2x Kirim Balik → Sukses)

```
Pelanggan Kirim → Menunggu → [Kembalikan] → Pending
                        ↑                         ↓
                  Pelanggan Kirim Ulang     (Perbaiki Data)
                        ↑                         ↓
                        └─────────── Menunggu ←────┘
                                           ↓
                                     [Verifikasi Data]
                                           ↓
                                    Selesai Sukses
                                    Diterima PLN
```

### Flow Gagal (2x Revisi → Ditolak)

```
Pelanggan Kirim → Menunggu → [Kembalikan] → Pending → (Kirim Ulang)
                                                          ↓
                                                     Menunggu
                                                          ↓
                                                   [Kembalikan]
                                                          ↓
                                            (revision_count = 2)
                                                          ↓
                                                     Menunggu
                                                          ↓
                                                   [Tolak]
                                                          ↓
                                              Selesai Gagal
                                              Ditolak PLN
```

---

## 📁 FILE YANG MENGAWAL LOGIC INI

| File                                                   | Fungsi                                         |
| ------------------------------------------------------ | ---------------------------------------------- |
| `app/Models/ServiceRequest.php`                        | Scopes (line 720-752) + Actions (line 758-882) |
| `app/Filament/AdminLayanan/Pages/TambahDaya.php`       | Filter UI + tombol aksi untuk Tambah Daya      |
| `app/Filament/AdminLayanan/Pages/PasangBaru.php`       | Filter UI + tombol aksi untuk Pasang Baru      |
| `app/Filament/AdminLayanan/Pages/DetailPermohonan.php` | Halaman detail + aksi dari halaman detail      |
| `app/Enums/PermohonanDetailStatus.php`                 | Definisi enum status_detail                    |
| `app/Enums/PermohonanStatus.php`                       | Definisi enum status + allowedDetails          |

---

## 🛡️ VERIFIKASI TERAKHIR

Sebelum menutup dokumen ini, lakukan verifikasi berikut:

- [x] Semua scope query tidak tumpang tindih
- [x] Setiap tombol aksi hanya muncul di kondisi yang tepat
- [x] Admin tidak bisa mengklik tombol yang tidak semestinya (syarat ketat)
- [x] Perpindahan hanya terjadi via method `adminAccept()`, `adminSendBack()`, `adminReject()`
- [x] Tidak ada auto-transition atau job scheduler yang memindahkan status
- [x] Sorting `status_changed_at DESC` memastikan data revisi muncul di atas

---

**Dokumen ini ditutup dan dikunci pada 1 Juni 2026.**
**Setiap perubahan harus melalui review dan update dokumen ini.**
