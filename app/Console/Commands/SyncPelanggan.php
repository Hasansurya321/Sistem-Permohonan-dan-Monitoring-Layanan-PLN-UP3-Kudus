<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\MasterPelanggan;
use App\Models\CustomerAccountRequest;
use App\Services\PelangganSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPelanggan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pelanggan:sync
                            {--dry-run : Simulasi tanpa perubahan data}
                            {--user= : Sinkronisasi spesifik user berdasarkan ID (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi massal data pelanggan ke master_pelanggan. Mendeteksi dan memperbaiki seluruh inkonsistensi data pelanggan.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificUserId = $this->option('user');

        $mode = $dryRun ? 'DRY RUN' : 'LIVE';
        $this->info("=== PELANGGAN SYNC ({$mode}) ===");
        $this->newLine();

        // ── Ambil user ──
        $usersQuery = User::where('role', 'pelanggan')->orderBy('id');
        if ($specificUserId) {
            $usersQuery->where('id', $specificUserId);
        }
        $users = $usersQuery->get();
        $totalUser = $users->count();

        if ($totalUser === 0) {
            $this->warn('Tidak ada user dengan role pelanggan.');
            return Command::SUCCESS;
        }

        // ── Statistik ──
        $stats = [
            'total_user'              => $totalUser,
            'valid'                   => 0,
            'missing_master'          => 0,
            'missing_nik'             => 0,
            'missing_idpel'           => 0,
            'created'                 => 0,
            'created_via_car'         => 0,
            'created_via_user'        => 0,
            'updated'                 => 0,
            'skipped_no_nik'          => 0,
            'skipped_no_car_data'     => 0,
            'failed'                  => 0,
        ];

        // ── Detail ──
        $details = [];

        foreach ($users as $user) {
            $detail = [
                'id'    => $user->id,
                'nama'  => $user->name,
                'email' => $user->email,
                'status' => '',
            ];

            // Cek master_pelanggan existing via user_id
            $existingMaster = MasterPelanggan::where('user_id', $user->id)->first();

            if ($existingMaster) {
                // Update data jika diperlukan
                $needsUpdate = false;
                $updateData = [];

                if ($user->nik && $user->nik !== $existingMaster->nik) {
                    $updateData['nik'] = $user->nik;
                    $needsUpdate = true;
                }
                if ($user->name !== $existingMaster->nama_lengkap) {
                    $updateData['nama_lengkap'] = $user->name;
                    $needsUpdate = true;
                }

                if ($needsUpdate && !$dryRun) {
                    $existingMaster->update($updateData);
                    $stats['updated']++;
                    $detail['status'] = 'Updated';
                } else {
                    $stats['valid']++;
                    $detail['status'] = 'Valid';
                }

                // Cek apakah user ini aktif dan punya NIK tapi NIK-nya di master berbeda
                if ($user->is_active && !$user->nik) {
                    $stats['missing_nik']++;
                }
                if ($user->is_active && !$user->id_pelanggan) {
                    $stats['missing_idpel']++;
                }

                $details[] = $detail;
                continue;
            }

            // User TIDAK memiliki master_pelanggan
            $stats['missing_master']++;

            // Cek NIK — jika kosong, tidak bisa disinkronisasi
            if (empty($user->nik)) {
                $stats['skipped_no_nik']++;
                $detail['status'] = 'Skipped (NIK kosong)';
                $details[] = $detail;
                continue;
            }

            // Cek apakah sudah ada master_pelanggan dengan NIK yang sama (tanpa user_id)
            $masterByNik = MasterPelanggan::where('nik', $user->nik)->first();

            if ($masterByNik) {
                // Update user_id pada record yang sudah ada
                if (!$dryRun) {
                    $masterByNik->update(['user_id' => $user->id]);
                    $stats['updated']++;
                    $detail['status'] = 'Updated (user_id linked)';
                } else {
                    $detail['status'] = 'Would update (link user_id)';
                }
                $details[] = $detail;
                continue;
            }

            // Cari CustomerAccountRequest
            $car = CustomerAccountRequest::where('email', $user->email)->first();

            if ($car && !empty($car->nik)) {
                // Sync via CAR (menggunakan data lengkap dari registrasi)
                // Gunakan updateOrCreate berbasis NIK untuk handle duplicate constraint
                if (!$dryRun) {
                    try {
                        MasterPelanggan::updateOrCreate(
                            ['nik' => $car->nik],
                            [
                                'user_id'         => $user->id,
                                'id_pelanggan_12' => $car->id_pelanggan,
                                'nama_lengkap'    => $car->full_name,
                                'no_meter'        => $car->nomor_meter,
                                'no_kk'           => $car->no_kk,
                                'no_hp'           => $car->phone,
                                'npwp'            => $car->nomor_npwp,
                                'provinsi'        => $car->province,
                                'kab_kota'        => $car->regency,
                                'kecamatan'       => $car->district,
                                'kelurahan'       => $car->village,
                                'rt'              => '-',
                                'rw'              => '-',
                                'alamat_detail'   => $car->address_text,
                            ]
                        );
                        $stats['created_via_car']++;
                        $stats['created']++;
                        $detail['status'] = 'Created (via CAR)';
                    } catch (\Throwable $e) {
                        // Fallback: jika gagal karena unique constraint, coba tanpa id_pelanggan_12
                        if (str_contains($e->getMessage(), 'id_pelanggan_12_unique')) {
                            try {
                                MasterPelanggan::updateOrCreate(
                                    ['nik' => $car->nik],
                                    [
                                        'user_id'         => $user->id,
                                        'id_pelanggan_12' => null,
                                        'nama_lengkap'    => $car->full_name,
                                        'no_meter'        => $car->nomor_meter,
                                        'no_kk'           => $car->no_kk,
                                        'no_hp'           => $car->phone,
                                        'npwp'            => $car->nomor_npwp,
                                        'provinsi'        => $car->province,
                                        'kab_kota'        => $car->regency,
                                        'kecamatan'       => $car->district,
                                        'kelurahan'       => $car->village,
                                        'rt'              => '-',
                                        'rw'              => '-',
                                        'alamat_detail'   => $car->address_text,
                                    ]
                                );
                                $stats['created_via_car']++;
                                $stats['created']++;
                                $detail['status'] = 'Created (via CAR, IDPEL skipped due to conflict)';
                            } catch (\Throwable $e2) {
                                $stats['failed']++;
                                $detail['status'] = 'Failed: ' . $e2->getMessage();
                                Log::error('SyncPelanggan: syncAfterActivation failed', [
                                    'user_id' => $user->id,
                                    'error' => $e2->getMessage(),
                                ]);
                            }
                        } else {
                            $stats['failed']++;
                            $detail['status'] = 'Failed: ' . $e->getMessage();
                            Log::error('SyncPelanggan: syncAfterActivation failed', [
                                'user_id' => $user->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } else {
                    $stats['created_via_car']++;
                    $stats['created']++;
                    $detail['status'] = 'Would create (via CAR)';
                }
            } elseif ($user->nik) {
                // Fallback: buat dari data user yang terbatas menggunakan updateOrCreate
                if (!$dryRun) {
                    try {
                        MasterPelanggan::updateOrCreate(
                            ['nik' => $user->nik],
                            [
                                'user_id'         => $user->id,
                                'id_pelanggan_12' => $user->id_pelanggan,
                                'nama_lengkap'    => $user->name,
                                'no_meter'        => $user->nomor_meter,
                                'no_kk'           => $user->no_kk,
                                'no_hp'           => $user->phone,
                                'npwp'            => $user->nomor_npwp,
                                'provinsi'        => '',
                                'kab_kota'        => '',
                                'kecamatan'       => '',
                                'kelurahan'       => '',
                                'rt'              => '-',
                                'rw'              => '-',
                                'alamat_detail'   => $user->address_text ?? $user->address ?? '',
                            ]
                        );
                        $stats['created_via_user']++;
                        $stats['created']++;
                        $detail['status'] = 'Created (via user data)';
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $detail['status'] = 'Failed: ' . $e->getMessage();
                        Log::error('SyncPelanggan: create from user failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    $stats['created_via_user']++;
                    $stats['created']++;
                    $detail['status'] = 'Would create (via user data)';
                }
            } else {
                $stats['skipped_no_car_data']++;
                $detail['status'] = 'Skipped (no CAR, incomplete data)';
            }

            $details[] = $detail;
        }

        // ── Tampilkan Summary ──
        $this->newLine();
        $this->info('=== SUMMARY ===');
        $this->line(sprintf('  Total User              : %d', $stats['total_user']));
        $this->line(sprintf('  User Valid              : %d', $stats['valid']));
        $this->line(sprintf('  User Missing Master     : %d', $stats['missing_master']));
        $this->line(sprintf('  User Missing NIK        : %d', $stats['missing_nik']));
        $this->line(sprintf('  User Missing IDPEL      : %d', $stats['missing_idpel']));
        $this->newLine();
        $this->line(sprintf('  Created                 : %d', $stats['created']));
        $this->line(sprintf('    └ via CAR             : %d', $stats['created_via_car']));
        $this->line(sprintf('    └ via user data       : %d', $stats['created_via_user']));
        $this->line(sprintf('  Updated                 : %d', $stats['updated']));
        $this->line(sprintf('  Skipped                 : %d', $stats['skipped_no_nik'] + $stats['skipped_no_car_data']));
        $this->line(sprintf('    └ No NIK              : %d', $stats['skipped_no_nik']));
        $this->line(sprintf('    └ No CAR/data         : %d', $stats['skipped_no_car_data']));
        $this->line(sprintf('  Failed                  : %d', $stats['failed']));
        $this->newLine();

        // ── Tampilkan Detail ──
        $this->info('=== DETAIL ===');
        foreach ($details as $d) {
            $this->line(sprintf('  - %s (ID:%d) => %s', $d['nama'], $d['id'], $d['status']));
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn('Mode DRY RUN — tidak ada perubahan data.');
        } else {
            $this->info('Sinkronisasi selesai.');
        }

        return Command::SUCCESS;
    }
}