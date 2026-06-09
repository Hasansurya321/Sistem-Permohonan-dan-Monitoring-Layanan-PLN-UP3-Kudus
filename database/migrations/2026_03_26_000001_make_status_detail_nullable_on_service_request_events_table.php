<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_request_events', function (Blueprint $table) {
            // status_detail can be null for statuses that do not have a detail, such as DRAFT.
            $table->string('status_detail')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_request_events', function (Blueprint $table) {
            $table->string('status_detail')->nullable(false)->change();
        });
    }
};
