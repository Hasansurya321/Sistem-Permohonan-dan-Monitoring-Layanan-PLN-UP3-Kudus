# LAPORAN FIX SISTEM PEMBAYARAN QRIS

## Tanggal: 7 Juni 2026

---

## 1. RINGKASAN MASALAH

**Masalah Awal:**
Kolom `payment_token` tidak ditemukan di tabel `service_requests`. Error:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'payment_token' in 'where clause'
```

**Penyebab:**
`payment_token` berada di tabel `payments`, bukan di `service_requests`. Query salah:

```php
// SALAH - mencari di tabel yang salah
$serviceRequest = ServiceRequest::where('payment_token', $token)->first();
```

**Solusi:**
Cari Payment terlebih dahulu, lalu ambil ServiceRequest dari relasi:

```php
// BENAR - cari Payment dulu
$payment = Payment::where('payment_token', $token)
    ->with('serviceRequest')
    ->firstOrFail();

$serviceRequest = $payment->serviceRequest;
```

---

## 2. FILE YANG DIPERBAIKI

### A. `app/Http/Controllers/QrisSimulatorController.php`

#### Method `successByGet(string $token)`:

```php
public function successByGet(string $token)
{
    try {
        // 1. Find Payment first (payment_token ada di payments, bukan service_requests)
        $payment = Payment::where('payment_token', $token)
            ->with('serviceRequest')
            ->firstOrFail();

        $serviceRequest = $payment->serviceRequest;

        // 2. Guard 1: IDEMPOTENCY - sudah pernah bayar
        if ($serviceRequest->payment_status === 'paid') {
            return view('pelanggan.payment-success', [
                'serviceRequest' => $serviceRequest
            ]);
        }

        // 3. Guard 2: BATAS PERCOBAAN - sudah 3x gagal
        if ($serviceRequest->payment_attempt_count >= 3) {
            return view('pelanggan.payment-failed', [
                'serviceRequest' => $serviceRequest,
                'message' => 'Batas percobaan pembayaran telah habis.'
            ]);
        }

        // 4. Guard 3: Cek Payment record PENDING
        $payment = Payment::where('payment_token', $token)
            ->where('status', 'PENDING')
            ->first();

        if (!$payment) {
            return view('pelanggan.payment-failed', [
                'serviceRequest' => $serviceRequest,
                'message' => 'Tagihan tidak ditemukan atau sudah tidak aktif.'
            ]);
        }

        // 5. PROSES PEMBAYARAN
        $payment->update([
            'status' => 'SUCCESS',
            'paid_at' => now(),
            'ref_no' => 'QR-' . strtoupper(substr(uniqid(), -8)),
        ]);

        $serviceRequest->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $serviceRequest->transitionToSystem(
            PermohonanStatus::PEMBAYARAN,
            PermohonanDetailStatus::PEMBAYARAN_SUKSES,
            'Pembayaran berhasil melalui QRIS.'
        );

        return view('pelanggan.payment-success', [
            'serviceRequest' => $serviceRequest->fresh()
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        \Log::warning('Payment callback: token tidak ditemukan - ' . $token);
        return response('Token pembayaran tidak valid.', 404);
    } catch (\Exception $e) {
        \Log::error('Payment callback error: ' . $e->getMessage());
        return response('Terjadi kesalahan sistem. Hubungi admin.', 500);
    }
}
```

#### Method `fail(string $token)`:

```php
public function fail(string $token)
{
    try {
        // Find Payment first
        $payment = Payment::where('payment_token', $token)
            ->with('serviceRequest')
            ->firstOrFail();

        $serviceRequest = $payment->serviceRequest;

        // Guard: Jika sudah paid
        if ($serviceRequest->payment_status === 'paid') {
            return view('pelanggan.payment-success', [
                'serviceRequest' => $serviceRequest
            ]);
        }

        // Guard: Sudah 3x gagal
        if ($serviceRequest->payment_attempt_count >= 3) {
            return view('pelanggan.payment-failed', [
                'serviceRequest' => $serviceRequest,
                'message' => 'Batas percobaan pembayaran telah habis.'
            ]);
        }

        // Increment attempt_count
        $serviceRequest->increment('payment_attempt_count');
        $serviceRequest->refresh();

        $attemptAfter = $serviceRequest->payment_attempt_count;

        // Jika sudah 3x gagal → PERMOHONAN_GAGAL
        if ($attemptAfter >= 3) {
            $serviceRequest->update([
                'status' => 'SELESAI',
                'status_detail' => 'PERMOHONAN_GAGAL',
                'payment_status' => 'failed',
            ]);

            return view('pelanggan.payment-failed', [
                'serviceRequest' => $serviceRequest->fresh(),
                'message' => 'Batas percobaan pembayaran telah habis. Permohonan dinyatakan gagal.',
            ]);
        }

        // Gagal tapi belum 3x → info sisa percobaan
        return response()->json([
            'success' => false,
            'attempts' => $attemptAfter,
            'remaining_attempts' => 3 - $attemptAfter,
            'is_final' => false,
            'message' => "Pembayaran gagal. Sisa percobaan: " . (3 - $attemptAfter),
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        \Log::warning('Payment fail: token tidak ditemukan - ' . $token);
        return response('Token pembayaran tidak valid.', 404);
    } catch (\Exception $e) {
        \Log::error('Payment fail error: ' . $e->getMessage());
        return response('Terjadi kesalahan sistem.', 500);
    }
}
```

---

## 3. VIEW YANG DIBUAT

### A. `resources/views/pelanggan/payment-success.blade.php`

Halaman sukses pembayaran dengan tampilan:

- Checkmark animasi
- Pesan "Pembayaran Berhasil"
- Detail nomor permohonan
- Tombol "Lihat Status Permohonan"

### B. `resources/views/pelanggan/payment-failed.blade.php`

Halaman gagal pembayaran dengan tampilan:

- Icon X merah
- Pesan "Pembayaran Gagal"
- Info batas percobaan
- Tombol "Hubungi Admin"

---

## 4. ATURAN BUSINESS LOGIC

Berdasarkan Blueprint Sistem Pembayaran:

| Aturan          | Deskripsi                                       |
| --------------- | ----------------------------------------------- |
| Batas Percobaan | Maksimal 3 kali percobaan pembayaran            |
| Percobaan       | Dihitung dari aksi GAGAL, bukan SUKSES          |
| Idempotency     | Sukses di attempt berapapun tidak mengubah data |
| Final           | Gagal ke-3 adalah final, tidak bisa coba lagi   |

### State Machine:

```
                    ┌─────────────┐
                    │   PENDING   │
                    └──────┬──────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
           ▼               ▼               ▼
     ┌──────────┐   ┌──────────┐    ┌──────────────┐
     │  FAIL #1 │   │  FAIL #2 │    │   FAIL #3    │
     │ attempts=1│   │ attempts=2│    │  attempts=3 │
     └─────┬────┘   └─────┬────┘    │  PERMOHONAN_ │
           │              │         │    GAGAL     │
           ▼              ▼         │   (FINAL)    │
     ┌──────────┐   ┌──────────┐    └──────────────┘
     │  SUCCESS │   │  SUCCESS │
     │  attempts│   │  attempts│
     │  = 1    │   │  = 2    │
     │  (paid) │   │  (paid)  │
     └──────────┘   └──────────┘
```

---

## 5. PENGUJIAN (TEST SUITE TC1-TC4)

File: `tests/TC_Payment_Blueprint.php`

### TC1: Sukses Langsung (attempts=0)

**Skenario:** Pembayaran berhasil pada percobaan pertama

**Langkah:**

1. Buat ServiceRequest dengan `payment_attempt_count = 0`
2. Buat Payment dengan `status = PENDING`
3. Panggil `successByGet(token)`

**Hasil Esperimen:**

```
payment_attempt_count: 0 → 0 (TIDAK BERUBAH)
payment_status: pending → paid
Payment.status: PENDING → SUCCESS
status_detail: MENUNGGU_PEMBAYARAN → PEMBAYARAN_SUKSES
```

**✅ PASS**

---

### TC2: Gagal 1x → Sukses

**Skenario:** Gagal 1 kali, lalu berhasil

**Langkah:**

1. Buat ServiceRequest dengan `payment_attempt_count = 0`
2. Panggil `fail(token)` → attempts menjadi 1
3. Panggil `successByGet(token)` → payment_status = paid

**Hasil Esperimen:**

```
After fail #1: attempts = 1
After success: attempts = 1 (TIDAK BERUBAH)
payment_status: pending → paid
```

**✅ PASS**

---

### TC3: Gagal 2x → Sukses

**Skenario:** Gagal 2 kali, lalu berhasil

**Langkah:**

1. Buat ServiceRequest dengan `payment_attempt_count = 0`
2. Panggil `fail()` → attempts = 1
3. Panggil `fail()` → attempts = 2
4. Panggil `successByGet()` → payment_status = paid

**Hasil Esperimen:**

```
After fail #1: attempts = 1
After fail #2: attempts = 2
After success: attempts = 2 (TIDAK BERUBAH)
payment_status: pending → paid
```

**✅ PASS**

---

### TC4: Gagal 3x → Final + Attempt 4 Ditolak

**Skenario:** Gagal 3 kali (final), attempt ke-4 ditolak

**Langkah:**

1. Buat ServiceRequest dengan `payment_attempt_count = 0`
2. Panggil `fail()` 3x → attempts = 3
3. Verifikasi status = SELESAI, status_detail = PERMOHONAN_GAGAL
4. Panggil `successByGet()` → harus DITOLAK

**Hasil Esperimen:**

```
After 3 fails:
  attempts = 3
  status = SELESAI
  status_detail = PERMOHONAN_GAGAL
  payment_status = failed

After attempt 4 (successByGet):
  attempts = 3 (TIDAK BERUBAH)
  status = SELESAI (TIDAK BERUBAH)
  payment_status = failed (TIDAK BERUBAH)
```

**✅ PASS**

---

## 6. HASIL TEST SUITE

```
╔════════════════════════════════════════════════════════════╗
║                    FINAL SUMMARY                         ║
╚════════════════════════════════════════════════════════════╝
  TC1: ✅ PASS
  TC2: ✅ PASS
  TC3: ✅ PASS
  TC4: ✅ PASS

🎉 ALL TEST CASES PASSED! Blueprint implementation is correct.
```

---

## 7. CARA MENJALANKAN TEST

```bash
# Jalankan test suite lengkap TC1-TC4
cd "d:\PKL PLN\PLN-Monitoring-Backup B"
php tests/TC_Payment_Blueprint.php

# Jalankan debug payment flow
php debug_payment.php
```

---

## 8. KESIMPULAN

1. **Masalah utama** - Query mencari `payment_token` di tabel yang salah telah diperbaiki
2. **Business logic** - Aturan 3 percobaan dan idempotency berfungsi dengan benar
3. **Test suite** - Semua 4 test case (TC1-TC4) PASS
4. **Implementasi** - Sesuai dengan Blueprint Sistem Pembayaran

---

## 9. FILE TERKAIT

| File                                                  | Deskripsi          |
| ----------------------------------------------------- | ------------------ |
| `app/Http/Controllers/QrisSimulatorController.php`    | Controller utama   |
| `tests/TC_Payment_Blueprint.php`                      | Test suite TC1-TC4 |
| `debug_payment.php`                                   | Debug script       |
| `resources/views/pelanggan/payment-success.blade.php` | View sukses        |
| `resources/views/pelanggan/payment-failed.blade.php`  | View gagal         |

---

_Dokumen ini dibuat pada 7 Juni 2026_
