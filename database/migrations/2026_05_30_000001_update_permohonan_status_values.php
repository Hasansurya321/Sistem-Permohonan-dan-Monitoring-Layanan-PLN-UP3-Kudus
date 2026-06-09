<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mapping OLD status values to NEW enum values
     * for both service_requests and service_request_events tables.
     */
    public function up(): void
    {
        // === STEP 1: Update service_requests.status ===
        $statusMapping = [
            'DRAFT'              => 'VERIFIKASI_DATA',
            'DITERIMA_PLN'       => 'VERIFIKASI_DATA',
            'VERIFIKASI_SLO'     => 'VERIFIKASI_DATA',
            'SURVEY_LAPANGAN'    => 'UNIT_SURVEY',
            'PERENCANAAN_MATERIAL' => 'UNIT_PERENCANAAN',
            'MENUNGGU_PEMBAYARAN' => 'PEMBAYARAN',
            'KONSTRUKSI_INSTALASI' => 'UNIT_KONSTRUKSI',
            'PENYALAAN_TE'       => 'UNIT_PENYALAAN',
            'DIBATALKAN_ADMIN'   => 'SELESAI',
        ];

        foreach ($statusMapping as $old => $new) {
            DB::table('service_requests')
                ->where('status', $old)
                ->update(['status' => $new]);
        }

        // === STEP 2: Update service_requests.status_detail ===
        $detailMapping = [
            // Admin Layanan
            'MENUNGGU_VERIFIKASI'        => 'MENUNGGU_VERIFIKASI_DATA',
            'SLO_VALID'                   => 'VERIFIKASI_DATA_SUKSES',
            'DOKUMEN_TIDAK_VALID'         => 'DIKEMBALIKAN_DENGAN_REVISI',
            'VERIFIKASI_GAGAL'            => 'DITOLAK',
            // Unit Survey
            'DITERUSKAN_UNIT_SURVEY'      => 'DITERIMA_UNIT_SURVEY',
            'SURVEY_BARU'                 => 'DITERIMA_UNIT_SURVEY',
            'SURVEY_SELESAI'              => 'SURVEY_SUKSES',
            // Unit Perencanaan
            'ANALISA_KEBUTUHAN_MATERIAL'  => 'ANALISA_KEBUTUHAN_MATERIAL',
            // Pembayaran
            'PEMBAYARAN_SELESAI'          => 'PEMBAYARAN_SUKSES',
            // Unit Konstruksi
            'KONSTRUKSI_JARINGAN'         => 'PEMBANGUNAN_JARINGAN',
            'KONSTRUKSI_PROGRESS'         => 'KONSTRUKSI_DIJADWALKAN',
            'INSTALASI_PELANGGAN'         => 'KONSTRUKSI_BERHASIL',
            // Unit Penyalaan
            'KONFIRMASI_NYALA'            => 'PENYALAAN_DIJADWALKAN',
            // Final
            'FINISH'                      => 'CLOSE',
            'ADMINISTRASI_AKHIR'          => 'CLOSE',
        ];

        foreach ($detailMapping as $old => $new) {
            DB::table('service_requests')
                ->where('status_detail', $old)
                ->update(['status_detail' => $new]);
        }

        // === STEP 3: Update service_request_events.status ===
        foreach ($statusMapping as $old => $new) {
            DB::table('service_request_events')
                ->where('status', $old)
                ->update(['status' => $new]);
        }

        // === STEP 4: Update service_request_events.status_detail ===
        foreach ($detailMapping as $old => $new) {
            DB::table('service_request_events')
                ->where('status_detail', $old)
                ->update(['status_detail' => $new]);
        }

        // === STEP 5: Fix is_draft logic ===
        // For VERIFIKASI_DATA with detail MENUNGGU_VERIFIKASI_DATA -> draft = false (submitted)
        DB::table('service_requests')
            ->where('status', 'VERIFIKASI_DATA')
            ->where('status_detail', 'MENUNGGU_VERIFIKASI_DATA')
            ->where('is_draft', true)
            ->update(['is_draft' => false]);

        // For DIKEMBALIKAN_DENGAN_REVISI -> draft = false
        DB::table('service_requests')
            ->where('status_detail', 'DIKEMBALIKAN_DENGAN_REVISI')
            ->where('is_draft', true)
            ->update(['is_draft' => false]);

        Log::info('Permohonan status values migrated successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse status mapping for service_requests
        $reverseStatus = [
            'VERIFIKASI_DATA'    => 'DITERIMA_PLN',
            'UNIT_SURVEY'        => 'SURVEY_LAPANGAN',
            'UNIT_PERENCANAAN'   => 'PERENCANAAN_MATERIAL',
            'PEMBAYARAN'         => 'MENUNGGU_PEMBAYARAN',
            'UNIT_KONSTRUKSI'    => 'KONSTRUKSI_INSTALASI',
            'UNIT_PENYALAAN'     => 'PENYALAAN_TE',
        ];

        foreach ($reverseStatus as $new => $old) {
            DB::table('service_requests')
                ->where('status', $new)
                ->update(['status' => $old]);
        }

        // Reverse detail mapping
        $reverseDetail = [
            'MENUNGGU_VERIFIKASI_DATA'  => 'MENUNGGU_VERIFIKASI',
            'VERIFIKASI_DATA_SUKSES'    => 'SLO_VALID',
            'DIKEMBALIKAN_DENGAN_REVISI' => 'DOKUMEN_TIDAK_VALID',
            'DITOLAK'                   => 'VERIFIKASI_GAGAL',
            'DITERIMA_UNIT_SURVEY'      => 'SURVEY_BARU',
            'PEMBAYARAN_SUKSES'         => 'PEMBAYARAN_SELESAI',
            'PEMBANGUNAN_JARINGAN'      => 'KONSTRUKSI_JARINGAN',
            'KONSTRUKSI_BERHASIL'       => 'INSTALASI_PELANGGAN',
            'PENYALAAN_DIJADWALKAN'     => 'KONFIRMASI_NYALA',
            'SURVEY_DIJADWALKAN'        => 'SURVEY_DIJADWALKAN',
            'SURVEY_LAPANGAN'           => 'SURVEY_LAPANGAN',
            'SURVEY_SUKSES'             => 'SURVEY_SELESAI',
            'SURVEY_GAGAL'              => 'SURVEY_GAGAL',
            'SURVEY_SELESAI'            => 'SURVEY_SELESAI',
        ];

        foreach ($reverseDetail as $new => $old) {
            DB::table('service_requests')
                ->where('status_detail', $new)
                ->update(['status_detail' => $old]);
        }

        Log::info('Permohonan status values rolled back.');
    }
};