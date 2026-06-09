<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\PelangganSyncService;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backfill data pelanggan existing (yang sudah aktif sebelum solusi ini diterapkan)
     * ke tabel master_pelanggan. Hanya mengisi data yang belum ada (firstOrCreate).
     * Aman dijalankan multiple times — tidak overwrite data existing.
     *
     * Latar belakang:
     * Sebelum solusi arsitektur ini, sinkronasi ke master_pelanggan hanya terjadi
     * secara kondisional (jika id_pelanggan diisi). Akibatnya ada user aktif yang
     * datanya tidak ada di master_pelanggan, sehingga tidak bisa mengakses modul
     * layanan seperti Tambah Daya.
     *
     * Solusi: Semua user aktif dengan NIK akan di-backfill ke master_pelanggan.
     */
    public function up(): void
    {
        $result = PelangganSyncService::backfillExistingUsers();

        // Log ke output migration
        echo PHP_EOL;
        echo "=== BACKFILL MASTER PELANGGAN ===" . PHP_EOL;
        echo "Total user diproses : {$result['processed']}" . PHP_EOL;
        echo "Data baru dibuat    : {$result['created']}" . PHP_EOL;
        echo "Data sudah ada      : {$result['skipped']}" . PHP_EOL;
        echo "================================" . PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * Reverse the migrations.
     *
     * Tidak ada rollback untuk backfill data. Data yang sudah masuk ke
     * master_pelanggan adalah data legitimate yang diperlukan untuk
     * kelancaran operasional pelanggan.
     */
    public function down(): void
    {
        // Tidak ada operasi rollback — data master_pelanggan bersifat kumulatif.
        // Hapus baris berikut jika ingin benar-benar menghapus hasil backfill:
        // (Tidak direkomendasikan karena akan mengganggu layanan pelanggan)
    }
};