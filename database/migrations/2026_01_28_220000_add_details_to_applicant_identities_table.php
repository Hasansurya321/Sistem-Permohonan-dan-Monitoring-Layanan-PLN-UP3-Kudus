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
        Schema::table('applicant_identities', function (Blueprint $table) {
            $table->string('no_kk', 16)->nullable()->after('nama_lengkap');
            $table->string('no_hp', 20)->nullable()->after('no_kk');
            $table->string('npwp', 20)->nullable()->after('no_hp');
            $table->string('foto_bangunan')->nullable()->after('default_alamat_detail');
            $table->string('foto_ktp_selfie')->nullable()->after('foto_bangunan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_identities', function (Blueprint $table) {
            $table->dropColumn(['no_kk', 'no_hp', 'npwp', 'foto_bangunan', 'foto_ktp_selfie']);
        });
    }
};
