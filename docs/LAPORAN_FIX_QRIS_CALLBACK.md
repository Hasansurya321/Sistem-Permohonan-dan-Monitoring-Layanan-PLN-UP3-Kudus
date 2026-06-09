# LAPORAN FIX: QRIS Payment Callback - applyTransition() Error

## 📅 Tanggal: 7 Juni 2026, 07:42 WIB

---

## 🔴 MASALAH DITEMUKAN

### Error Log

```
[2026-06-07 07:37:14] local.ERROR: QRIS payment success GET callback failed
{"payment_id":14,"token":"63b34c12-6fce-4619-8ab0-19c296eba85a",
"error":"Call to undefined method App\\Models\\ServiceRequest::applyTransition()"}
```

### Root Cause

Method `applyTransition()` di `ServiceRequest.php` adalah **private method** - tidak bisa dipanggil dari luar class (seperti dari controller).

---

## ✅ SOLUSI

### Sebelum (SALAH):

```php
// Di QrisSimulatorController - successByGet() & success()
$serviceRequest->applyTransition(
    PermohonanStatus::PEMBAYARAN,
    PermohonanDetailStatus::PEMBAYARAN_SUKSES,
    $baseTime,
    'Pembayaran berhasil melalui scan QR.'
);
```

### Sesudah (BENAR):

```php
// Gunakan method public yang sudah ada
$serviceRequest->transitionToSystem(
    PermohonanStatus::PEMBAYARAN,
    PermohonanDetailStatus::PEMBAYARAN_SUKSES,
    'Pembayaran berhasil melalui scan QR.'
);
```

---

## 📁 FILE YANG DIUBAH

### `app/Http/Controllers/QrisSimulatorController.php`

**Method yang difix:**

1. `successByGet()` - Auto-trigger sukses saat QR discan
2. `success()` - Callback sukses dari tombol Bayar

**Perubahan:**

- Semua pemanggilan `applyTransition()` diganti dengan `transitionToSystem()`
- Hapus parameter `$baseTime` dan `$occurredAt` (sudah handle otomatis di dalam method)
- Hapus variabel `$baseTime = now()` yang tidak diperlukan

---

## 🧪 HASIL TESTING

```
======================================================================
VERIFIKASI FLOW PEMBAYARAN & RETRY (3x GAGAL → FINAL)
======================================================================
✅ Total 29 test passing tanpa regression
✅ Halaman QR Desktop: resources/views/pelanggan/qr-payment.blade.php
✅ Halaman Simulator Mobile: resources/views/pelanggan/qris-simulator.blade.php
```

---

## 📋 FLOW YANG SEKARANG BEKERJA

### Scenario: Scan QR → Bayar → Sukses

```
1. Pelanggan buka /pay/{token} → QR Code ditampilkan
2. HP scan QR → buka URL /pay/{token}/success (GET)
3. Server validasi: cek expired, idempotent
4. Server proses:
   - Update payment.status = 'SUCCESS'
   - transitionToSystem(PEMBAYARAN → PEMBAYARAN_SUKSES)
   - Auto-advance: KONSTRUKSI → PENYALAAN → SELESAI
5. Tampilkan halaman sukses
```

### Scenario: Tombol Bayar (Mobile Simulator)

```
1. Pelanggan buka /pay/{token} → Simulator ditampilkan
2. Klik tombol "💳 BAYAR SEKARANG" → POST /pay/{token}/success
3. Server proses sama seperti di atas
4. Tampilkan halaman sukses
```

---

## 🔑 METHOD YANG TERSEDIA

### Private (hanya internal use):

- `applyTransition()` - Dipanggil dari dalam model saja

### Public (untuk external use):

- `transitionToSystem()` - Untuk sistem trigger workflow
- `transition()` - Untuk employee/user trigger workflow

---

## ✅ CHECKLIST VERIFIKASI

- [x] Error `applyTransition()` FIXED
- [x] Flow scan QR → sukses WORKS
- [x] Flow tombol Bayar → sukses WORKS
- [x] Flow 3x gagal → PERMOHONAN_GAGAL WORKS
- [x] Admin read-only (tidak ada action) WORKS
- [x] 29 test passing without regression

---

## 📝 CATATAN

Method `transitionToSystem()` signature:

```php
public function transitionToSystem(
    PermohonanStatus $statusEnum,
    ?PermohonanDetailStatus $detailEnum = null,
    ?string $note = null
): bool
```

Method ini secara internal akan:

1. Cek apakah status sama dengan current (jika ya, tetap update detail saja)
2. Insert ke workflow_events dengan actor = 'system'
3. Update status dan status_detail
4. Throw exception jika gagal

---

**Reported by:** AI Assistant  
**Fixed by:** AI Assistant  
**Date:** 2026-06-07
