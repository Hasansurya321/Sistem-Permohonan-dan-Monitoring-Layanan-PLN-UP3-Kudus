<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembersihan Legacy — tabel pelanggan_profiles.
 *
 * LATAR BELAKANG:
 * Hasil audit menunjukkan tabel pelanggan_profiles tidak memiliki data
 * dan tidak digunakan dalam flow aktif (registrasi, aktivasi, tambah daya,
 * pembayaran, monitoring). Tabel ini adalah legacy dari versi awal aplikasi
 * sebelum migrasi ke master_pelanggan.
 *
 * REFERENSI TERSISA (sudah di-audit):
 * - app/Models/PelangganProfile.php (model — akan dihapus)
 * - User::pelangganProfile() (relasi — akan dihapus)
 * - TambahDayaController::step1() — $user->profile (BUKAN pelangganProfile)
 *
 * RISK: RENDAH — Tidak ada modul aktif yang membaca/menulis tabel ini.
 * Aman dihapus setelah migration dijalankan.
 *
 * CATATAN:
 * Migration ini HANYA dijalankan jika seluruh regression test lulus.
 * Lihat LAPORAN_EKSEKUSI_REFACTOR.md untuk detail lengkap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pelanggan_profiles');
    }

    public function down(): void
    {
        Schema::create('pelanggan_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nik', 16)->nullable();
            $table->string('no_kk', 16)->nullable();
            $table->string('id_pelanggan', 12)->nullable();
            $table->string('nomor_meter', 11)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }
};