<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_account_requests', function (Blueprint $table) {
            // Drop foreign key if not SQLite
            if (DB::getDriverName() !== 'sqlite') {
                try {
                    $table->dropForeign(['reviewed_by']);
                } catch (\Exception $e) {
                    // Ignore if not present
                }
                
                $table->foreign('reviewed_by')->references('id')->on('employees')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_account_requests', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                try {
                    $table->dropForeign(['reviewed_by']);
                } catch (\Exception $e) {
                    // Ignore
                }
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }
};
