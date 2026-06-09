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
        // 1. Update tabel users untuk kebutuhan pelanggan
        Schema::table('users', function (Blueprint $table) {
            // phone
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            
            // address
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }

            // status enum pending/approved/active/rejected default pending
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['pending', 'approved', 'active', 'rejected'])
                      ->default('pending')
                      ->after('address');
            }

            // approved_by (referensi id pegawai yang melakukan approval)
            if (!Schema::hasColumn('users', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            }

            // approved_at
            if (!Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            // activated_at
            if (!Schema::hasColumn('users', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('approved_at');
            }

            // email_verified_at jika belum ada
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
        });

        // Ubah default value untuk is_active menjadi 0
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(0)->change();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(0)->after('status');
            });
        }

        // 2. Buat tabel employees untuk pegawai internal
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('role')->nullable();
                $table->string('unit')->nullable();
                $table->string('jabatan')->nullable();
                $table->boolean('is_active')->default(1);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 3. Buat tabel activation_tokens dengan relasi ke customer_account_requests dan users
        if (!Schema::hasTable('activation_tokens')) {
            Schema::create('activation_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('customer_account_request_id')->nullable();
                $table->string('token')->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();

                // Relasi foreign key ke users dan customer_account_requests
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('customer_account_request_id')->references('id')->on('customer_account_requests')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus tabel activation_tokens
        Schema::dropIfExists('activation_tokens');

        // Hapus tabel employees
        Schema::dropIfExists('employees');

        // Rollback kolom pada tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'status',
                'approved_by',
                'approved_at',
                'activated_at'
            ]);
            
            // Kembalikan default is_active ke 1 jika ada
            if (Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(1)->change();
            }
        });
    }
};
