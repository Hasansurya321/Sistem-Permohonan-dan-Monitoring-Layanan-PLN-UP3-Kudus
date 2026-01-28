<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            // jangan taro apa-apa di sini dulu
        });

        // guard supaya aman kalau kolom udah ada
        if (! Schema::hasColumn('service_requests', 'status_detail')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->string('status_detail', 255)->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('service_requests', 'status_detail')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropColumn('status_detail');
            });
        }
    }
};
