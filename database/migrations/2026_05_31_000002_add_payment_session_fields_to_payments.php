<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah payment_token dan expired_at ke tabel payments.
     * Kolom status tetap string (varchar) karena SQLite tidak support ALTER ENUM.
     * Di MySQL/MariaDB, kolom status sudah didefinisikan sebagai enum di migration awal.
     * Nilai 'EXPIRED' dapat disimpan tanpa masalah karena SQLite tidak memvalidasi ENUM.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_token')) {
                $table->string('payment_token', 64)
                    ->nullable()
                    ->unique()
                    ->after('service_request_id')
                    ->comment('UUID token untuk QR payment session');
            }

            if (!Schema::hasColumn('payments', 'expired_at')) {
                $table->timestamp('expired_at')
                    ->nullable()
                    ->after('paid_at')
                    ->comment('Waktu kedaluwarsa payment session');
            }
        });

        // Untuk MySQL/MariaDB: tambah EXPIRED ke ENUM
        // Untuk SQLite: tidak perlu karena SQLite tidak memvalidasi ENUM constraint
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('PENDING','SUCCESS','FAILED','EXPIRED') NOT NULL DEFAULT 'PENDING'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_token', 'expired_at']);
        });

        // Kembalikan ENUM ke 3 nilai awal (MySQL only)
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('PENDING','SUCCESS','FAILED') NOT NULL DEFAULT 'PENDING'");
        }
    }
};
