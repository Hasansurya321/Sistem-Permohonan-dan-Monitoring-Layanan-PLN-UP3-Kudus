<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if we're using SQLite (for testing compatibility).
     */
    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /**
     * Run the migrations.
     *
     * ONE SOURCE OF TRUTH — Relasi permanen users ↔ master_pelanggan.
     * Setiap user pelanggan aktif memiliki 1:1 data di master_pelanggan.
     * Seluruh modul bisnis (Tambah Daya, Pasang Baru, dll) membaca master_pelanggan.
     */
    public function up(): void
    {
        // Tambah kolom user_id di master_pelanggan
        Schema::table('master_pelanggan', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                  ->nullable()
                  ->unique()
                  ->after('id')
                  ->comment('FK ke users.id — relasi 1:1 dengan akun pelanggan');

            // Only add FK for MySQL (SQLite doesn't support FK in migrations well)
            if (!$this->isSqlite()) {
                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            }
        });

        // Backfill: tautkan master_pelanggan existing ke users berdasarkan NIK
        if ($this->isSqlite()) {
            // SQLite: Use subquery approach
            $affected = DB::update("
                UPDATE master_pelanggan 
                SET user_id = (
                    SELECT id FROM users 
                    WHERE users.nik = master_pelanggan.nik 
                    AND users.role = 'pelanggan'
                    LIMIT 1
                )
                WHERE user_id IS NULL
                AND EXISTS (
                    SELECT 1 FROM users 
                    WHERE users.nik = master_pelanggan.nik 
                    AND users.role = 'pelanggan'
                )
            ");
        } else {
            // MySQL: Use proper UPDATE JOIN
            $affected = DB::statement("
                UPDATE master_pelanggan m
                INNER JOIN users u ON u.nik = m.nik AND u.role = 'pelanggan'
                SET m.user_id = u.id
                WHERE m.user_id IS NULL
            ");
        }

        \Illuminate\Support\Facades\Log::info('Migration add_user_id_to_master_pelanggan: backfill selesai.', [
            'affected_rows' => $affected ?? 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_pelanggan', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};