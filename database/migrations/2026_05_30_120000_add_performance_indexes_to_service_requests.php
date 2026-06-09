<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan composite index untuk mempercepat query filter
     * pada halaman Tambah Daya dan Pasang Baru.
     *
     * Query yang umum:
     *   WHERE jenis_layanan = 'TAMBAH_DAYA' AND status = 'VERIFIKASI_DATA' AND status_detail = 'MENUNGGU_VERIFIKASI_DATA'
     *   WHERE jenis_layanan = 'PASANG_BARU'  AND status = 'VERIFIKASI_DATA' AND status_detail = 'DIKEMBALIKAN_DENGAN_REVISI'
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Composite index untuk filter Admin Layanan (Tambah Daya & Pasang Baru)
            $table->index(['jenis_layanan', 'status', 'status_detail'], 'idx_jenis_status_detail');

            // Index tambahan untuk filter status saja (tanpa jenis_layanan)
            $table->index(['status', 'status_detail'], 'idx_status_detail');

            // Index untuk submitted_at + is_draft (scope submitted)
            $table->index(['is_draft', 'submitted_at'], 'idx_draft_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('idx_jenis_status_detail');
            $table->dropIndex('idx_status_detail');
            $table->dropIndex('idx_draft_submitted');
        });
    }
};