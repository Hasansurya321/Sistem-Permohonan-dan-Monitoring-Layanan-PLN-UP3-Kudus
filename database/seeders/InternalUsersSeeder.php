<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * InternalUsersSeeder — Safe idempotent seeder for internal employee accounts.
 *
 * ATURAN:
 * - JANGAN truncate tabel employees.
 * - JANGAN overwrite password jika employee sudah ada.
 * - Gunakan firstOrCreate: skip jika sudah exist.
 */
class InternalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $internalUsers = [
            [
                'name'    => 'Hasan',
                'email'   => 'hasan@supervisor.com',
                'role'    => 'supervisor',
                'unit'    => 'UP3 Kudus',
                'jabatan' => 'Supervisor',
            ],
            [
                'name'    => 'Affan',
                'email'   => 'affan@adminlayanan.com',
                'role'    => 'admin_pelayanan',
                'unit'    => 'UP3 Kudus',
                'jabatan' => 'Admin Pelayanan',
            ],
            [
                'name'    => 'Budi Surveyor',
                'email'   => 'budi@unitsurvey.com',
                'role'    => 'unit_survey',
                'unit'    => 'UP3 Kudus',
                'jabatan' => 'Unit Survey',
            ],
            [
                'name'    => 'Citra Planner',
                'email'   => 'citra@unitperencanaan.com',
                'role'    => 'unit_perencanaan',
                'unit'    => 'UP3 Kudus',
                'jabatan' => 'Unit Perencanaan',
            ],
            [
                'name'    => 'Dedi Konstruktor',
                'email'   => 'dedi@unitkonstruksi.com',
                'role'    => 'unit_konstruksi',
                'unit'    => 'UP3 Kudus',
                'jabatan' => 'Unit Konstruksi',
            ],
            [
                'name'    => 'Eka Teknisi',
                'email'   => 'eka@unitte.com',
                'role'    => 'unit_te',
                'unit'    => 'UP3 Kudus',
                'jabatan' => 'Unit TE',
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($internalUsers as $data) {
            $existing = Employee::where('email', $data['email'])->first();

            if ($existing) {
                // JANGAN overwrite password — hanya update non-sensitive fields
                $existing->update([
                    'name'    => $data['name'],
                    'role'    => $data['role'],
                    'unit'    => $data['unit'],
                    'jabatan' => $data['jabatan'],
                    'is_active' => true,
                ]);
                $this->command->line("  <comment>↩ Exists</comment> [{$data['email']}] — password unchanged.");
                $skipped++;
            } else {
                Employee::create([
                    'name'      => $data['name'],
                    'email'     => $data['email'],
                    'password'  => Hash::make('Password123!'),
                    'role'      => $data['role'],
                    'unit'      => $data['unit'],
                    'jabatan'   => $data['jabatan'],
                    'is_active' => true,
                ]);
                $this->command->line("  <info>✓ Created</info> [{$data['email']}]");
                $created++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ InternalUsersSeeder: {$created} created, {$skipped} skipped (existing).");
    }
}
