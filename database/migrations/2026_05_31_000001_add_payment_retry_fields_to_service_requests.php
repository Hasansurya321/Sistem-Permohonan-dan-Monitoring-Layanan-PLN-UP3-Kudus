<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambah kolom untuk payment retry mechanism:
     * - payment_attempt_count : counter percobaan pembayaran gagal
     * - cancelled_at          : waktu pembatalan
     * - cancelled_by          : pihak yang membatalkan (customer/admin/system)
     * - failure_reason        : alasan kegagalan (enum PaymentFailureReason)
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'payment_attempt_count')) {
                $table->unsignedTinyInteger('payment_attempt_count')
                    ->default(0)
                    ->after('status_detail')
                    ->comment('Jumlah percobaan pembayaran gagal');
            }

            if (!Schema::hasColumn('service_requests', 'cancelled_at')) {
                $table->timestamp('cancelled_at')
                    ->nullable()
                    ->after('payment_attempt_count')
                    ->comment('Waktu pembatalan pembayaran');
            }

            if (!Schema::hasColumn('service_requests', 'cancelled_by')) {
                $table->string('cancelled_by', 20)
                    ->nullable()
                    ->after('cancelled_at')
                    ->comment('Pihak pembatal: customer, admin, system');
            }

            if (!Schema::hasColumn('service_requests', 'failure_reason')) {
                $table->string('failure_reason', 50)
                    ->nullable()
                    ->after('cancelled_by')
                    ->comment('Alasan kegagalan final, lihat enum PaymentFailureReason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn([
                'payment_attempt_count',
                'cancelled_at',
                'cancelled_by',
                'failure_reason',
            ]);
        });
    }
};