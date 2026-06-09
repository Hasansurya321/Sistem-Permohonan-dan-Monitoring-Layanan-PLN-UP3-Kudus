<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Blueprint: Add payment_status and paid_at columns to service_requests
     * 
     * Kolom ini diperlukan untuk:
     * - payment_status: Track status pembayaran (pending/paid/failed)
     * - paid_at: Timestamp pembayaran sukses
     * 
     * Dengan kolom ini, idempotency check di successByGet() dapat bekerja
     * tanpa harus join ke tabel payments.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // Payment status di level ServiceRequest (sesuai blueprint)
            // Values: pending, paid, failed
            if (!Schema::hasColumn('service_requests', 'payment_status')) {
                $table->string('payment_status', 20)
                    ->nullable()
                    ->default('pending')
                    ->after('payment_attempt_count');
            }

            // Timestamp pembayaran sukses
            if (!Schema::hasColumn('service_requests', 'paid_at')) {
                $table->timestamp('paid_at')
                    ->nullable()
                    ->after('payment_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            
            if (Schema::hasColumn('service_requests', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
