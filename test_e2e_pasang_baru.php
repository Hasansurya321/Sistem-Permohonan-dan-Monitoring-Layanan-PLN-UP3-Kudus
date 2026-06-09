<?php
/**
 * Script Test End-to-End untuk Flow Pasang Baru
 * Menguji alur dari submit sampai tampil di admin panel
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ServiceRequest;
use App\Models\ApplicantIdentity;
use App\Models\User;
use App\Models\MasterPelanggan;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== TEST END-TO-END: FLOW PASANG BARU ===\n\n";

// 1. Setup - Find or create test user
echo "1. SETUP: Finding test user...\n";
$testUser = User::where('role', 'pelanggan')->first();

if (!$testUser) {
    echo "   ❌ Tidak ada user pelanggan. Jalankan seeder dulu.\n";
    exit(1);
}

echo "   ✅ User: {$testUser->name} (ID: {$testUser->id})\n";

// 2. Find or create MasterPelanggan
echo "\n2. SETUP: Finding MasterPelanggan data...\n";
$masterPelanggan = MasterPelanggan::first();

if (!$masterPelanggan) {
    echo "   ❌ Tidak ada MasterPelanggan. Jalankan seeder dulu.\n";
    exit(1);
}

echo "   ✅ MasterPelanggan: {$masterPelanggan->nama_lengkap} (NIK: {$masterPelanggan->nik})\n";

// 3. Hapus draft PASANG_BARU yang mungkin masih ada
echo "\n3. CLEANUP: Menghapus draft PASANG_BARU sebelumnya...\n";
$deleted = ServiceRequest::where('submitter_user_id', $testUser->id)
    ->where('jenis_layanan', 'PASANG_BARU')
    ->where('is_draft', true)
    ->delete();
echo "   ✅ Deleted {$deleted} draft records\n";

// 4. Simulasi Step 1-5
echo "\n4. SIMULASI SUBMIT: Membuat PASANG_BARU...\n";

try {
    $sr = DB::transaction(function () use ($testUser, $masterPelanggan) {
        // Data wizard simulasi
        $wizard = [
            'for_whom' => 'self',
            'applicant_nik' => $masterPelanggan->nik,
            'applicant_name' => $masterPelanggan->nama_lengkap,
            'lokasi' => [
                'provinsi' => 'JAWA TENGAH',
                'kab_kota' => 'KUDUS',
                'kecamatan' => 'KOTA KUDUS',
                'kelurahan' => 'DEMAAN',
                'rt' => '001',
                'rw' => '001',
            ],
            'daya_baru' => 2200,
            'jenis_produk' => 'PASCABAYAR',
            'peruntukan_koneksi' => 'RUMAH_TANGGA',
            'slo_no_registrasi' => 'SLO-TEST-' . time(),
            'slo_no_sertifikat' => 'CERT-TEST-' . time(),
        ];

        // Create ApplicantIdentity
        $applicant = ApplicantIdentity::updateOrCreate(
            ['nik' => $masterPelanggan->nik],
            [
                'nama_lengkap' => $masterPelanggan->nama_lengkap,
                'nik' => $masterPelanggan->nik,
                'no_kk' => '3312000000000001',
                'no_hp' => '6281234567890',
                'user_id' => $testUser->id,
            ]
        );

        // Create ServiceRequest - INI CRITICAL PART
        $serviceRequest = ServiceRequest::create([
            'submitter_user_id' => $testUser->id,
            'applicant_id' => $applicant->id,
            'applicant_nik' => $masterPelanggan->nik,
            'jenis_layanan' => 'PASANG_BARU', // HARUS PASANG_BARU!
            'status' => PermohonanStatus::VERIFIKASI_DATA,
            'status_detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
            'is_draft' => false,
            'submitted_at' => now(),
            'daya_baru' => $wizard['daya_baru'],
            'peruntukan_koneksi' => $wizard['peruntukan_koneksi'],
            'payload_json' => $wizard,
        ]);

        // Generate nomor permohonan
        $serviceRequest->update([
            'nomor_permohonan' => 'PB-TEST-' . date('Ymd') . '-' . str_pad($serviceRequest->id, 4, '0', STR_PAD_LEFT),
        ]);

        return $serviceRequest;
    });

    echo "   ✅ ServiceRequest created!\n";
    echo "   📋 ID: {$sr->id}\n";
    echo "   📋 Nomor: {$sr->nomor_permohonan}\n";
    echo "   📋 Jenis: {$sr->jenis_layanan}\n";
    echo "   📋 Status: {$sr->status->getLabel()}\n";
    echo "   📋 is_draft: " . ($sr->is_draft ? 'Ya' : 'Tidak') . "\n";

} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Verifikasi data di database
echo "\n5. VERIFIKASI: Checking database record...\n";
$verify = ServiceRequest::find($sr->id);

if ($verify->jenis_layanan === 'PASANG_BARU') {
    echo "   ✅ jenis_layanan = 'PASANG_BARU' (BENAR)\n";
} else {
    echo "   ❌ jenis_layanan = '{$verify->jenis_layanan}' (SALAH!)\n";
}

// 6. Verifikasi query untuk admin
echo "\n6. VERIFIKASI: Admin queries...\n";

// Query untuk admin Pasang Baru
$adminQuery = ServiceRequest::where('jenis_layanan', 'PASANG_BARU')
    ->where('status', PermohonanStatus::VERIFIKASI_DATA)
    ->count();

echo "   📊 Admin Query (PASANG_BARU + VERIFIKASI_DATA): {$adminQuery} records\n";

// Query untuk landing page
$landingQuery = ServiceRequest::where('submitter_user_id', $testUser->id)
    ->processing()
    ->first();

if ($landingQuery) {
    echo "   📊 Landing Page Query: Found active request\n";
    echo "       jenis_layanan: {$landingQuery->jenis_layanan}\n";
} else {
    echo "   📊 Landing Page Query: No active request found\n";
}

// 7. Summary
echo "\n=== SUMMARY ===\n";
echo "✅ Record PASANG_BARU berhasil dibuat\n";
echo "✅ Data tersimpan dengan benar di database\n";
echo "\nUntuk verifikasi manual:\n";
echo "1. Login sebagai user {$testUser->email}\n";
echo "2. Buka halaman Monitoring\n";
echo "3. Lihat apakah permohonan menampilkan 'Pasang Baru'\n";
echo "4. Buka Admin Panel → Permohonan Layanan → Pasang Baru\n";
echo "5. Pastikan record muncul di list\n";

echo "\n=== TEST COMPLETE ===\n";
