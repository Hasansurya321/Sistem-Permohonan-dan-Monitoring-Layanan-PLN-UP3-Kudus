# LAPORAN FIX: jenis_layanan Selalu TAMBAH_DAYA

## Tanggal: 7 Juni 2026, 23:26 WIB

## Problem Statement

Ketika user memilih "Pasang Baru" dan submit permohonan, data tersimpan di database dengan `jenis_layanan = 'TAMBAH_DAYA'` padahal seharusnya `jenis_layanan = 'PASANG_BARU'`.

## Root Cause Analysis

### Penyebab

Field `jenis_layanan` ada di `$guarded` array di Model `ServiceRequest`:

```php
// app/Models/ServiceRequest.php - BEFORE
protected $guarded = ['id', 'jenis_layanan'];
```

### Mengapa Ini Menyebabkan Bug?

1. Migration mendefinisikan default value: `$table->enum('jenis_layanan', ['TAMBAH_DAYA', 'PASANG_BARU'])->default('TAMBAH_DAYA');`
2. Karena `jenis_layanan` ada di `$guarded`, Laravel **mengabaikan** semua nilai yang di-pass saat `ServiceRequest::create()`
3. Database menggunakan default value `TAMBAH_DAYA`

## Solution

Menghapus `jenis_layanan` dari `$guarded` array:

```php
// app/Models/ServiceRequest.php - AFTER
protected $guarded = ['id'];
```

## Verification

### Test Script: `test_e2e_pasang_baru.php`

```bash
php test_e2e_pasang_baru.php
```

### Hasil Test

**SEBELUM FIX:**

```
📋 Jenis:
❌ jenis_layanan = 'TAMBAH_DAYA' (SALAH!)
```

**SESUDAH FIX:**

```
📋 Jenis: PASANG_BARU
✅ jenis_layanan = 'PASANG_BARU' (BENAR)
📊 Admin Query (PASANG_BARU + VERIFIKASI_DATA): 2 records
```

## Files Modified

| File                            | Change                                |
| ------------------------------- | ------------------------------------- |
| `app/Models/ServiceRequest.php` | Hapus `jenis_layanan` dari `$guarded` |

## Files Created

| File                       | Purpose                                 |
| -------------------------- | --------------------------------------- |
| `test_e2e_pasang_baru.php` | Script test end-to-end untuk verifikasi |

## Kesimpulan

✅ **Bug Fixed!** Field `jenis_layanan` sekarang bisa di-set dengan benar saat create ServiceRequest untuk Pasang Baru.

## Recommendations

1. **Jangan pernah letakkan field yang perlu di-set oleh aplikasi di `$guarded`** - Ini adalah anti-pattern yang menyebabkan bug tersembunyi
2. Gunakan `$fillable` untuk field yang boleh di-set, atau `$guarded` hanya untuk field yang benar-benar tidak boleh diubah (seperti `id`, `created_at`, dll)
3. Selalu test dengan data berbeda (TAMBAH_DAYA vs PASANG_BARU) untuk memastikan tidak ada regression
