<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_account_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique(); // Unique in pending requests
            $table->string('phone');
            $table->enum('gender', ['L', 'P']);
            $table->text('address_text')->nullable();
            
            // Region fields (string or id)
            $table->string('province')->nullable(); 
            $table->string('regency')->nullable();
            $table->string('district')->nullable();
            $table->string('village')->nullable();
            $table->string('postal_code')->nullable();

            $table->string('password_hash');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->foreignId('reviewed_by')->nullable()->constrained('employees');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_account_requests');
    }
};
