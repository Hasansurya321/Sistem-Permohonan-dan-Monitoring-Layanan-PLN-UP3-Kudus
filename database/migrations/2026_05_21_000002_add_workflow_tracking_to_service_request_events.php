<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_request_events', function (Blueprint $table) {
            if (!Schema::hasColumn('service_request_events', 'updated_by_name')) {
                $table->string('updated_by_name')->nullable()->after('description');
            }
            if (!Schema::hasColumn('service_request_events', 'updated_by_role')) {
                $table->string('updated_by_role')->nullable()->after('updated_by_name');
            }
            if (!Schema::hasColumn('service_request_events', 'note')) {
                $table->text('note')->nullable()->after('updated_by_role');
            }
        });

        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'status_changed_by')) {
                $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_detail');
            }
            if (!Schema::hasColumn('service_requests', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable()->after('status_changed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_request_events', function (Blueprint $table) {
            if (Schema::hasColumn('service_request_events', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('service_request_events', 'updated_by_role')) {
                $table->dropColumn('updated_by_role');
            }
            if (Schema::hasColumn('service_request_events', 'updated_by_name')) {
                $table->dropColumn('updated_by_name');
            }
        });

        Schema::table('service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('service_requests', 'status_changed_at')) {
                $table->dropColumn('status_changed_at');
            }
            if (Schema::hasColumn('service_requests', 'status_changed_by')) {
                $table->dropColumn('status_changed_by');
            }
        });
    }
};
