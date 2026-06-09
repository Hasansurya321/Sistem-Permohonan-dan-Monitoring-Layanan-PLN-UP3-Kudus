<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            // NIK sudah ada, tambahkan sisanya
            if (!Schema::hasColumn('users', 'nomor_npwp')) {
                $table->string('nomor_npwp', 15)->nullable()->unique()->after('nik');
            }
            if (!Schema::hasColumn('users', 'slo_reg')) {
                $table->string('slo_reg', 50)->nullable()->after('nomor_npwp');
            }
            if (!Schema::hasColumn('users', 'slo_cert')) {
                $table->string('slo_cert', 100)->nullable()->after('slo_reg');
            }
            if (!Schema::hasColumn('users', 'no_kk')) {
                $table->string('no_kk', 16)->nullable()->after('slo_cert');
            }
            if (!Schema::hasColumn('users', 'id_pelanggan')) {
                $table->string('id_pelanggan', 12)->nullable()->unique()->after('no_kk');
            }
            if (!Schema::hasColumn('users', 'nomor_meter')) {
                $table->string('nomor_meter', 11)->nullable()->after('id_pelanggan');
            }
        });

        // Add columns to customer_account_requests table
        Schema::table('customer_account_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_account_requests', 'nik')) {
                $table->string('nik', 16)->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('customer_account_requests', 'nomor_npwp')) {
                $table->string('nomor_npwp', 15)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('customer_account_requests', 'slo_reg')) {
                $table->string('slo_reg', 50)->nullable()->after('nomor_npwp');
            }
            if (!Schema::hasColumn('customer_account_requests', 'slo_cert')) {
                $table->string('slo_cert', 100)->nullable()->after('slo_reg');
            }
            if (!Schema::hasColumn('customer_account_requests', 'no_kk')) {
                $table->string('no_kk', 16)->nullable()->after('slo_cert');
            }
            if (!Schema::hasColumn('customer_account_requests', 'id_pelanggan')) {
                $table->string('id_pelanggan', 12)->nullable()->after('no_kk');
            }
            if (!Schema::hasColumn('customer_account_requests', 'nomor_meter')) {
                $table->string('nomor_meter', 11)->nullable()->after('id_pelanggan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_npwp',
                'slo_reg',
                'slo_cert',
                'no_kk',
                'id_pelanggan',
                'nomor_meter',
            ]);
        });

        Schema::table('customer_account_requests', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'nomor_npwp',
                'slo_reg',
                'slo_cert',
                'no_kk',
                'id_pelanggan',
                'nomor_meter',
            ]);
        });
    }
};