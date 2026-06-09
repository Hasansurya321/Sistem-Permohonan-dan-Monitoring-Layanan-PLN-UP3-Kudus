# 📋 LAPORAN AUDIT SISTEM PEMBAYARAN QRIS

## 🔍 HASIL AUDIT

### 1. Frontend (qris-simulator.blade.php)

- ✅ Tombol "Bayar Sekarang" → POST `/pay/{token}/success`
- ❌ **TIDAK ADA TOMBOL "GAGAL"** ← MASALAH UTAMA

### 2. Backend (QrisSimulatorController)

- ✅ `success()` - trigger payment SUCCESS
- ✅ `fail()` - increment attempt_count
- ✅ `successByGet()` - auto-trigger from QR scan

### 3. Model (ServiceRequest)

- ✅ `incrementPaymentAttempt()` - handle retry logic
- ✅ Jika attempt >= 3 → SELESAI + PERMOHONAN_GAGAL

### 4. Model (Payment)

- ✅ `isExpired()` - cek expired
- ✅ `isSessionActive()` - cek session aktif

---

## 🎯 TEST CASE YANG PERLU DICAKUP

| #   | Test Case         | Expected                  |
| --- | ----------------- | ------------------------- |
| TC1 | Sukses Langsung   | Payment SUCCESS           |
| TC2 | Gagal 1x → Sukses | attempt=1 → SUCCESS       |
| TC3 | Gagal 2x → Sukses | attempt=2 → SUCCESS       |
| TC4 | Gagal 3x → Final  | attempt=3 → SELESAI_GAGAL |

---

## 🔧 RENCANA IMPLEMENTASI

### Step 1: Fix QR Generator → Kembalikan ke Ngrok

Edit `QrisSimulatorController.php`:

```php
// Selalu gunakan ngrok URL untuk QR
$baseUrl = config('app.ngrok_url', 'https://immovably-legroom-jolly.ngrok-free.dev');
```

### Step 2: Tambah Tombol "Gagal" di qris-simulator.blade.php

Tambahkan form POST ke `/pay/{token}/fail`

### Step 3: Verifikasi Route di routes/web.php

```php
Route::post('/pay/{token}/success', ...) // SUDAH ADA
Route::post('/pay/{token}/fail', ...)    // SUDAH ADA
```

### Step 4: Test Semua 4 Scenario

1. TC1: Klik "Bayar Sekarang" → Berhasil
2. TC2: Klik "Gagal" → Klik "Bayar Sekarang" → Berhasil
3. TC3: Klik "Gagal" → Klik "Gagal" → Klik "Bayar Sekarang" → Berhasil
4. TC4: Klik "Gagal" → Klik "Gagal" → Klik "Gagal" → Final Gagal

---

## 📊 STATUS SAAT INI

| Komponen      | Status                   |
| ------------- | ------------------------ |
| QR Generator  | ❌ Perlu fix (Local URL) |
| Tombol Sukses | ✅ Sudah ada             |
| Tombol Gagal  | ❌ Belum ada             |
| Route Success | ✅ Sudah ada             |
| Route Fail    | ✅ Sudah ada             |
| Logic Attempt | ✅ Sudah ada             |
| Logic Expired | ✅ Sudah ada             |
