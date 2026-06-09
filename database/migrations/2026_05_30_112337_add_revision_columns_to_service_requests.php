<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('service_requests', 'revision_count')) {
                $table->unsignedTinyInteger('revision_count')->default(0)->after('status_detail');
            }
            if (!Schema::hasColumn('service_requests', 'last_revision_at')) {
                $table->timestamp('last_revision_at')->nullable()->after('revision_count');
            }
            if (!Schema::hasColumn('service_requests', 'last_revision_note')) {
                $table->text('last_revision_note')->nullable()->after('last_revision_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['revision_count', 'last_revision_at', 'last_revision_note']);
        });
    }
};