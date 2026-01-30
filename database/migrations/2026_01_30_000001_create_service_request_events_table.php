<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')
                ->constrained('service_requests')
                ->cascadeOnDelete();

            $table->string('status');
            $table->string('status_detail');

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['service_request_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_events');
    }
};
