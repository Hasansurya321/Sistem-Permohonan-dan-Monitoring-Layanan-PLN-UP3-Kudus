<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_identities', function (Blueprint $table) {
            if (!Schema::hasColumn('applicant_identities', 'no_kk')) {
                $table->string('no_kk', 16)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('applicant_identities', 'npwp')) {
                $table->string('npwp', 20)->nullable()->after('no_kk');
            }
            if (!Schema::hasColumn('applicant_identities', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('no_kk');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicant_identities', function (Blueprint $table) {
            $table->dropColumn(['no_kk', 'npwp', 'no_hp']);
        });
    }
};
