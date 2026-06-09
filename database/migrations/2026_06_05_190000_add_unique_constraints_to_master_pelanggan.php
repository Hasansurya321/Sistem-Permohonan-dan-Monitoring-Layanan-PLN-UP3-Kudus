<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ONE SOURCE OF TRUTH — Proteksi database agar tidak ada duplikat 
     * id_pelanggan_12 dan no_meter di master_pelanggan.
     * 
     * Data duplikat sudah dibersihkan sebelum migration dijalankan.
     * Setiap pelanggan baru yang aktivasi akan mendapat id_pelanggan_12
     * dan no_meter yang unik dari CustomerAccountRequest.
     */
    public function up(): void
    {
        Schema::table('master_pelanggan', function (Blueprint $table) {
            // Unique index untuk id_pelanggan_12 (nullable — ada data null dari backfill lama)
            $table->unique('id_pelanggan_12', 'master_pelanggan_id_pelanggan_12_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_pelanggan', function (Blueprint $table) {
            $table->dropUnique('master_pelanggan_id_pelanggan_12_unique');
        });
    }
};