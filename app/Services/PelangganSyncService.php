<?php

namespace App\Services;

use App\Models\CustomerAccountRequest;
use App\Models\MasterPelanggan;
use App\Models\MasterSlo;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PelangganSyncService
 *
 * ONE SOURCE OF TRUTH — Sinkronisasi data pelanggan dari proses registrasi/aktivasi
 * ke tabel master_pelanggan. Service ini memastikan setiap pelanggan baru yang aktif
 * langsung terintegrasi ke master_pelanggan tanpa terkecuali.
 *
 * Dipanggil oleh:
 * - PelangganAuthController::activate() — setelah aktivasi akun
 * - Backfill migration — untuk data existing
 */
class PelangganSyncService
{
    /**
     * Sinkronisasi data pelanggan dari CustomerAccountRequest ke master_pelanggan.
     * Method ini WAJIB dipanggil setelah aktivasi akun berhasil.
     * Tidak ada kondisi — berlaku untuk SEMUA pelanggan baru.
     *
     * ONE SOURCE OF TRUTH: master_pelanggan.user_id menghubungkan ke users.id.
     *
     * @param CustomerAccountRequest $request Data permintaan akun yang sudah di-approve
     * @param int|null $userId ID user yang baru dibuat (wajib untuk aktivasi baru)
     * @return MasterPelanggan
     */
    public static function syncAfterActivation(CustomerAccountRequest $request, ?int $userId = null): MasterPelanggan
    {
        // Guard: NIK wajib ada untuk sinkronisasi
        if (empty($request->nik)) {
            Log::warning('PelangganSyncService: NIK kosong, sinkronasi dilewati.', [
                'email' => $request->email,
                'customer_account_request_id' => $request->id,
            ]);
            throw new \RuntimeException('Sinkronasi gagal: NIK pelanggan tidak ditemukan.');
        }

        try {
            $master = MasterPelanggan::updateOrCreate(
                ['nik' => $request->nik],  // Universal identifier
                [
                    'user_id'          => $userId,
                    'id_pelanggan_12' => $request->id_pelanggan,
                    'nama_lengkap'     => $request->full_name,
                    'no_meter'         => $request->nomor_meter,
                    'no_kk'            => $request->no_kk,
                    'no_hp'            => $request->phone,
                    'npwp'             => $request->nomor_npwp,
                    'provinsi'         => $request->province,
                    'kab_kota'         => $request->regency,
                    'kecamatan'        => $request->district,
                    'kelurahan'        => $request->village,
                    // CustomerAccountRequest tidak memiliki rt/rw, gunakan default
                    'rt'               => '-',
                    'rw'               => '-',
                    'alamat_detail'    => $request->address_text,
                ]
            );

            // SINKRONISASI SLO: Copy data SLO dari customer_account_requests ke master_slo.
            // Berlaku untuk SEMUA pelanggan baru tanpa terkecuali.
            // updateOrCreate menjamin idempoten — aman dijalankan berulang.
            if (!empty($request->slo_reg) && !empty($request->slo_cert)) {
                MasterSlo::updateOrCreate(
                    ['no_registrasi_slo' => $request->slo_reg],
                    [
                        'no_sertifikat_slo' => $request->slo_cert,
                        'nik_pemilik' => $request->nik,
                        'nama_pemilik' => $request->full_name,
                        'nama_lembaga' => 'Lembaga Inspeksi Teknik (Registrasi Online)',
                    ]
                );

                Log::info('PelangganSyncService: Sinkronasi SLO berhasil.', [
                    'nik' => $request->nik,
                    'slo_reg' => $request->slo_reg,
                    'slo_cert' => $request->slo_cert,
                ]);
            }

            Log::info('PelangganSyncService: Sinkronasi berhasil.', [
                'nik' => $request->nik,
                'id_pelanggan_12' => $request->id_pelanggan,
                'nama_lengkap' => $request->full_name,
                'master_pelanggan_id' => $master->id,
                'action' => $master->wasRecentlyCreated ? 'CREATE' : 'UPDATE',
            ]);

            return $master;
        } catch (\Throwable $e) {
            Log::error('PelangganSyncService: Sinkronasi gagal.', [
                'nik' => $request->nik,
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Backfill data user existing (sebelum solusi ini diterapkan) ke master_pelanggan.
     * Hanya mengisi data yang belum ada di master_pelanggan (firstOrCreate).
     * Aman dijalankan multiple times — tidak overwrite data existing.
     *
     * @return array{processed: int, created: int, skipped: int}
     */
    public static function backfillExistingUsers(): array
    {
        $users = User::where('role', 'pelanggan')
            ->where('status', 'active')
            ->whereNotNull('nik')
            ->get();

        $processed = 0;
        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $processed++;

            // Cek apakah sudah ada di master_pelanggan
            $exists = MasterPelanggan::where('nik', $user->nik)->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                MasterPelanggan::create([
                    'user_id'         => $user->id,  // FIX: Isi user_id untuk relasi ONE SOURCE OF TRUTH
                    'nik'             => $user->nik,
                    'nama_lengkap'    => $user->name,
                    'id_pelanggan_12' => $user->id_pelanggan,
                    'no_meter'        => $user->nomor_meter,
                    'no_kk'           => $user->no_kk,
                    'no_hp'           => $user->phone,
                    'npwp'            => $user->nomor_npwp,
                    'provinsi'        => '',  // Tidak ada di users table
                    'kab_kota'        => '',
                    'kecamatan'       => '',
                    'kelurahan'       => '',
                    'rt'              => '-',
                    'rw'              => '-',
                    'alamat_detail'   => $user->address_text ?? $user->address,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error('PelangganSyncService::backfill error', [
                    'user_id' => $user->id,
                    'nik' => $user->nik,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('PelangganSyncService: Backfill selesai.', [
            'total_users' => $users->count(),
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return [
            'processed' => $processed,
            'created' => $created,
            'skipped' => $skipped,
        ];
    }
}