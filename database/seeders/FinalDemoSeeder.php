<?php

namespace Database\Seeders;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Models\ApplicantIdentity;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * FinalDemoSeeder — Seeder produksi demo PLN UP3 Kudus
 *
 * ATURAN KERAS:
 * 1. JANGAN overwrite password employee existing.
 * 2. JANGAN duplicate employee records.
 * 3. JANGAN truncate tabel employees.
 * 4. Employee: detect → skip jika sudah ada.
 * 5. Pelanggan: firstOrCreate berdasarkan email.
 * 6. Seeder ini IDEMPOTENT — aman dijalankan berulang kali.
 *
 * SCOPE SEEDER INI:
 * - 5 pelanggan dummy (skip jika sudah exist)
 * - 7 service_requests dengan status bervariasi (workflow stages)
 * - payments untuk yang sudah bayar
 * - workflow history events realistis
 * - Menggunakan existing employee accounts sebagai actor
 */
class FinalDemoSeeder extends Seeder
{
    /** Existing employee accounts (only for referencing IDs — NOT created here) */
    private array $employees = [];

    // ─────────────────────────────────────────────────────────────────────────
    // MAIN RUN
    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        // ── Step 1: Load existing employees (JANGAN buat baru) ────────────────
        $this->loadExistingEmployees();

        // ── Step 2: Seed pelanggan (idempotent) ───────────────────────────────
        $users = $this->seedPelanggan();

        // ── Step 3: Clear old demo service_requests (keepsafe) ────────────────
        $this->clearDemoServiceRequests();

        // ── Step 4: Seed service_requests + events + payments ─────────────────
        $this->seedServiceRequests($users);

        $this->command->newLine();
        $this->command->info('✅ FinalDemoSeeder selesai!');
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Employee', Employee::count() . ' (existing, tidak disentuh)'],
                ['Pelanggan (User)', User::where('role', 'pelanggan')->count()],
                ['ServiceRequest', ServiceRequest::whereNotNull('submitted_at')->count()],
                ['Payment',        Payment::count()],
                ['Events',         ServiceRequestEvent::count()],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 1: Load existing employees — JANGAN buat ulang
    // ─────────────────────────────────────────────────────────────────────────

    private function loadExistingEmployees(): void
    {
        $required = [
            'supervisor'       => 'hasan@supervisor.com',
            'admin_pelayanan'  => 'affan@adminlayanan.com',
            'unit_survey'      => 'budi@unitsurvey.com',
            'unit_perencanaan' => 'citra@unitperencanaan.com',
            'unit_konstruksi'  => 'dedi@unitkonstruksi.com',
            'unit_te'          => 'eka@unitte.com',
        ];

        foreach ($required as $role => $email) {
            $emp = Employee::where('email', $email)->first();
            if (! $emp) {
                $this->command->error("⚠  Employee [{$email}] tidak ditemukan di database!");
                $this->command->warn("   Jalankan InternalUsersSeeder terlebih dahulu.");
                continue;
            }
            $this->employees[$role] = $emp;
            $this->command->line("  <info>✓</info> Found employee [{$emp->name}] <comment>({$emp->role})</comment>");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 2: Seed 5 pelanggan demo (idempotent — firstOrCreate)
    // ─────────────────────────────────────────────────────────────────────────

    private function seedPelanggan(): array
    {
        $this->command->line('');
        $this->command->line('<comment>Seeding pelanggan...</comment>');

        $data = [
            [
                'name'     => 'Budi Santoso',
                'email'    => 'pelanggan1@kudus.id',
                'nik'      => '3319010101010001',
                'no_hp'    => '081234567801',
                'alamat'   => 'Jl. Veteran No.12, Kudus',
            ],
            [
                'name'     => 'Siti Aminah',
                'email'    => 'pelanggan2@kudus.id',
                'nik'      => '3319010101010002',
                'no_hp'    => '081234567802',
                'alamat'   => 'Jl. Diponegoro No.5, Kudus',
            ],
            [
                'name'     => 'Rudi Hartono',
                'email'    => 'pelanggan3@kudus.id',
                'nik'      => '3319010101010003',
                'no_hp'    => '081234567803',
                'alamat'   => 'Jl. Sunan Kudus No.3, Jati, Kudus',
            ],
            [
                'name'     => 'Rina Wati',
                'email'    => 'pelanggan4@kudus.id',
                'nik'      => '3319010101010004',
                'no_hp'    => '081234567804',
                'alamat'   => 'Jl. Lingkar Selatan No.88, Kudus',
            ],
            [
                'name'     => 'Agus Salim',
                'email'    => 'pelanggan5@kudus.id',
                'nik'      => '3319010101010005',
                'no_hp'    => '081234567805',
                'alamat'   => 'Jl. Pemuda No.21, Demak Gate, Kudus',
            ],
        ];

        $users = [];
        foreach ($data as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name'      => $d['name'],
                    'nik'       => $d['nik'],
                    'role'      => 'pelanggan',
                    'status'    => 'active',
                    'is_active' => true,
                    'password'  => Hash::make('Password123!'),
                ]
            );

            $wasCreated = $user->wasRecentlyCreated;
            $this->command->line(
                '  ' . ($wasCreated ? '<info>✓ Created</info>' : '<comment>↩ Exists</comment>') .
                ' pelanggan [' . $d['email'] . ']'
            );

            $users[$d['email']] = $user;
        }

        return $users;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 3: Clear demo service_requests (only demo data, keepsafe)
    // ─────────────────────────────────────────────────────────────────────────

    private function clearDemoServiceRequests(): void
    {
        $this->command->line('');
        $this->command->line('<comment>Clearing demo service_requests (safe reset)...</comment>');

        // Only delete service_requests owned by our demo pelanggan emails
        $demoEmails = [
            'pelanggan1@kudus.id',
            'pelanggan2@kudus.id',
            'pelanggan3@kudus.id',
            'pelanggan4@kudus.id',
            'pelanggan5@kudus.id',
        ];

        $demoUserIds = User::whereIn('email', $demoEmails)->pluck('id');
        $srIds = ServiceRequest::whereIn('submitter_user_id', $demoUserIds)->pluck('id');

        if ($srIds->isEmpty()) {
            $this->command->line('  <comment>No demo service_requests to clear.</comment>');
            return;
        }

        ServiceRequestEvent::whereIn('service_request_id', $srIds)->delete();
        Payment::whereIn('service_request_id', $srIds)->delete();

        // Also clear related applicant identities
        $applicantIds = ServiceRequest::whereIn('id', $srIds)->pluck('applicant_id');
        ServiceRequest::whereIn('id', $srIds)->delete();
        ApplicantIdentity::whereIn('id', $applicantIds)->delete();

        $this->command->line('  <info>✓</info> Cleared ' . $srIds->count() . ' old demo service_requests.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STEP 4: Seed service_requests with realistic workflow histories
    // ─────────────────────────────────────────────────────────────────────────

    private function seedServiceRequests(array $users): void
    {
        $this->command->line('');
        $this->command->line('<comment>Seeding service_requests with workflow history...</comment>');

        $emp = $this->employees; // shorthand

        // Guard: if employees missing, skip SR seeding safely
        if (empty($emp)) {
            $this->command->error('Cannot seed service_requests: employee accounts not found.');
            return;
        }

        $now = Carbon::now();

        // ──────────────────────────────────────────────────────────────────────
        // SR 1 — VERIFIKASI_DATA (brand new, just submitted)
        // User: Budi Santoso | Actor: Affan (admin_pelayanan)
        // ──────────────────────────────────────────────────────────────────────
        $this->makeSR(
            user:        $users['pelanggan1@kudus.id'],
            submittedAt: $now->copy()->subDays(1),
            jenis:       'TAMBAH_DAYA',
            dayaBaru:    2200,
            status:      PermohonanStatus::VERIFIKASI_DATA,
            detail:      PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
            noPermohonan: 'PLN-UP3KDS-2026-001',
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan berhasil diterima dan terdaftar di sistem PLN UP3 Kudus.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(1),
                ],
            ],
        );

        // ──────────────────────────────────────────────────────────────────────
        // SR 2 — VERIFIKASI_DATA (SLO valid, sedang diverifikasi)
        // User: Siti Aminah | Actor: Affan (admin_pelayanan)
        // ──────────────────────────────────────────────────────────────────────
        $this->makeSR(
            user:        $users['pelanggan2@kudus.id'],
            submittedAt: $now->copy()->subDays(5),
            jenis:       'PASANG_BARU',
            dayaBaru:    1300,
            status:      PermohonanStatus::VERIFIKASI_DATA,
            detail:      PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
            noPermohonan: 'PLN-UP3KDS-2026-002',
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan terdaftar di sistem.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(5),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
                    'title'  => 'Data Diverifikasi',
                    'desc'   => 'Data dan dokumen telah diverifikasi dan dinyatakan valid.',
                    'note'   => 'Semua dokumen lengkap.',
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(4),
                ],
            ],
        );

        // ──────────────────────────────────────────────────────────────────────
        // SR 3 — UNIT_SURVEY (survey dijadwalkan)
        // User: Rudi Hartono | Actor: Budi (unit_survey)
        // ──────────────────────────────────────────────────────────────────────
        $this->makeSR(
            user:        $users['pelanggan3@kudus.id'],
            submittedAt: $now->copy()->subDays(10),
            jenis:       'TAMBAH_DAYA',
            dayaBaru:    4400,
            status:      PermohonanStatus::UNIT_SURVEY,
            detail:      PermohonanDetailStatus::SURVEY_DIJADWALKAN,
            noPermohonan: 'PLN-UP3KDS-2026-003',
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan terdaftar di sistem.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(10),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
                    'title'  => 'Data Valid',
                    'desc'   => 'Data dinyatakan valid, diteruskan ke Unit Survey.',
                    'note'   => 'Semua dokumen lengkap dan terverifikasi.',
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(9),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::ADMINISTRASI_SELESAI,
                    'title'  => 'Administrasi Selesai',
                    'desc'   => 'Proses administrasi selesai. Diteruskan ke Unit Survey.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(9)->addHours(2),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::DITERIMA_UNIT_SURVEY,
                    'title'  => 'Diterima Unit Survey',
                    'desc'   => 'Permohonan survey masuk ke antrian Unit Survey.',
                    'note'   => null,
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(8),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::SURVEY_DIJADWALKAN,
                    'title'  => 'Survey Dijadwalkan',
                    'desc'   => 'Survey lapangan telah dijadwalkan. Petugas akan hadir ke lokasi.',
                    'note'   => 'Jadwal: Rabu, 22 Mei 2026 — Pukul 09.00 WIB. Pastikan ada di lokasi.',
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(7),
                ],
            ],
        );

        // ──────────────────────────────────────────────────────────────────────
        // SR 4 — PEMBAYARAN (tagihan sudah terbit)
        // User: Rina Wati | Actor: Citra (unit_perencanaan) + lainnya
        // ──────────────────────────────────────────────────────────────────────
        $sr4 = $this->makeSR(
            user:        $users['pelanggan4@kudus.id'],
            submittedAt: $now->copy()->subDays(20),
            jenis:       'TAMBAH_DAYA',
            dayaBaru:    5500,
            status:      PermohonanStatus::PEMBAYARAN,
            detail:      PermohonanDetailStatus::TAGIHAN_TERBIT,
            noPermohonan: 'PLN-UP3KDS-2026-004',
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan terdaftar.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(20),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
                    'title'  => 'Data Valid',
                    'desc'   => 'Data valid.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(19),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::ADMINISTRASI_SELESAI,
                    'title'  => 'Administrasi Selesai',
                    'desc'   => 'Proses administrasi selesai.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(19)->addHours(1),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::DITERIMA_UNIT_SURVEY,
                    'title'  => 'Diterima Unit Survey',
                    'desc'   => 'Permohonan survey baru masuk.',
                    'note'   => null,
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(17),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::SURVEY_DIJADWALKAN,
                    'title'  => 'Survey Dijadwalkan',
                    'desc'   => 'Jadwal survey telah ditetapkan.',
                    'note'   => 'Jadwal: Senin, 12 Mei 2026 — 10.00 WIB.',
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(16),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::SURVEY_SELESAI,
                    'title'  => 'Survey Lapangan Selesai',
                    'desc'   => 'Survey telah selesai dilakukan di lokasi pelanggan.',
                    'note'   => 'Lokasi layak. Daya 5500VA tersedia dari jaringan terdekat.',
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(15),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PERENCANAAN,
                    'detail' => PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
                    'title'  => 'Analisa Kebutuhan Material',
                    'desc'   => 'Tim perencanaan melakukan analisa kebutuhan material.',
                    'note'   => null,
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(13),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PERENCANAAN,
                    'detail' => PermohonanDetailStatus::MATERIAL_TERSEDIA,
                    'title'  => 'Material Tersedia',
                    'desc'   => 'Material yang dibutuhkan telah tersedia di gudang.',
                    'note'   => 'Kabel NYY 4x10mm² tersedia 50m. MCB 32A tersedia.',
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(11),
                ],
                [
                    'status' => PermohonanStatus::PEMBAYARAN,
                    'detail' => PermohonanDetailStatus::TAGIHAN_TERBIT,
                    'title'  => 'Tagihan Diterbitkan',
                    'desc'   => 'Tagihan biaya pemasangan telah diterbitkan. Pelanggan diminta melakukan pembayaran.',
                    'note'   => 'Nominal: Rp 2.750.000 — Batas bayar 7 hari.',
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(8),
                ],
            ],
        );

        // Add pending payment record
        if ($sr4) {
            Payment::firstOrCreate(
                ['service_request_id' => $sr4->id, 'status' => 'PENDING'],
                [
                    'amount' => 2750000,
                    'ref_no' => 'INV-' . $sr4->nomor_permohonan . '-001',
                ]
            );
        }

        // ──────────────────────────────────────────────────────────────────────
        // SR 5 — UNIT_KONSTRUKSI (sedang dikerjakan)
        // User: Agus Salim | Dedi (unit_konstruksi)
        // ──────────────────────────────────────────────────────────────────────
        $sr5 = $this->makeSR(
            user:        $users['pelanggan5@kudus.id'],
            submittedAt: $now->copy()->subDays(35),
            jenis:       'PASANG_BARU',
            dayaBaru:    2200,
            status:      PermohonanStatus::UNIT_KONSTRUKSI,
            detail:      PermohonanDetailStatus::PEMBANGUNAN_JARINGAN,
            noPermohonan: 'PLN-UP3KDS-2026-005',
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan terdaftar.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(35),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
                    'title'  => 'Data Valid',
                    'desc'   => 'Data valid.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(34),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::ADMINISTRASI_SELESAI,
                    'title'  => 'Administrasi Selesai',
                    'desc'   => 'Proses administrasi selesai.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(34)->addHours(1),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::SURVEY_SELESAI,
                    'title'  => 'Survey Selesai',
                    'desc'   => 'Survey lapangan selesai. Lokasi siap.',
                    'note'   => null,
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(30),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PERENCANAAN,
                    'detail' => PermohonanDetailStatus::MATERIAL_TERSEDIA,
                    'title'  => 'Material Siap',
                    'desc'   => 'Material tersedia. Tagihan diterbitkan.',
                    'note'   => null,
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(25),
                ],
                [
                    'status' => PermohonanStatus::PEMBAYARAN,
                    'detail' => PermohonanDetailStatus::TAGIHAN_TERBIT,
                    'title'  => 'Tagihan Terbit',
                    'desc'   => 'Tagihan biaya sambungan diterbitkan.',
                    'note'   => 'Nominal: Rp 1.875.000.',
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(22),
                ],
                [
                    'status' => PermohonanStatus::PEMBAYARAN,
                    'detail' => PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                    'title'  => 'Pembayaran Dikonfirmasi',
                    'desc'   => 'Pembayaran telah dikonfirmasi. Proses lanjut ke konstruksi.',
                    'note'   => 'Ref: PAY-2026-00915. Dibayar via PLN Mobile.',
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(20),
                ],
                [
                    'status' => PermohonanStatus::UNIT_KONSTRUKSI,
                    'detail' => PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI,
                    'title'  => 'Konstruksi Dimulai',
                    'desc'   => 'Tim konstruksi mulai mengerjakan jaringan distribusi ke lokasi pelanggan.',
                    'note'   => null,
                    'actor'  => $emp['unit_konstruksi'] ?? null,
                    'at'     => $now->copy()->subDays(18),
                ],
                [
                    'status' => PermohonanStatus::UNIT_KONSTRUKSI,
                    'detail' => PermohonanDetailStatus::PEMBANGUNAN_JARINGAN,
                    'title'  => 'Pembangunan Jaringan',
                    'desc'   => 'Pengerjaan konstruksi jaringan berjalan. Estimasi selesai 2 hari.',
                    'note'   => 'Tiang sudah terpasang. Kabel udara 70% selesai.',
                    'actor'  => $emp['unit_konstruksi'] ?? null,
                    'at'     => $now->copy()->subDays(15),
                ],
            ],
        );

        // Payment SUKSES
        if ($sr5) {
            Payment::firstOrCreate(
                ['service_request_id' => $sr5->id, 'status' => 'SUCCESS'],
                [
                    'amount' => 1875000,
                    'ref_no' => 'PAY-2026-00915',
                    'paid_at' => $now->copy()->subDays(20),
                ]
            );
        }

        // ──────────────────────────────────────────────────────────────────────
        // SR 6 — UNIT_PENYALAAN (menunggu penyalaan)
        // User: Budi Santoso (SR ke-2 milik Budi)
        // ──────────────────────────────────────────────────────────────────────
        $sr6 = $this->makeSR(
            user:        $users['pelanggan1@kudus.id'],
            submittedAt: $now->copy()->subDays(45),
            jenis:       'TAMBAH_DAYA',
            dayaBaru:    7700,
            status:      PermohonanStatus::UNIT_PENYALAAN,
            detail:      PermohonanDetailStatus::PENYALAAN_BERHASIL,
            noPermohonan: 'PLN-UP3KDS-2026-006',
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan terdaftar.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(45),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
                    'title'  => 'Data Valid',
                    'desc'   => 'Data valid.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(44),
                ],
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::ADMINISTRASI_SELESAI,
                    'title'  => 'Administrasi Selesai',
                    'desc'   => 'Proses administrasi selesai.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(44)->addHours(1),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::SURVEY_SELESAI,
                    'title'  => 'Survey Selesai',
                    'desc'   => 'Survey lapangan selesai.',
                    'note'   => null,
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(40),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PERENCANAAN,
                    'detail' => PermohonanDetailStatus::MATERIAL_TERSEDIA,
                    'title'  => 'Material Siap',
                    'desc'   => 'Material 7700VA tersedia.',
                    'note'   => null,
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(35),
                ],
                [
                    'status' => PermohonanStatus::PEMBAYARAN,
                    'detail' => PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                    'title'  => 'Pembayaran Dikonfirmasi',
                    'desc'   => 'Pembayaran Rp 3.450.000 dikonfirmasi.',
                    'note'   => 'Ref: PAY-2026-00823.',
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(30),
                ],
                [
                    'status' => PermohonanStatus::UNIT_KONSTRUKSI,
                    'detail' => PermohonanDetailStatus::KONSTRUKSI_BERHASIL,
                    'title'  => 'Instalasi Selesai',
                    'desc'   => 'Instalasi di sisi pelanggan selesai. Siap penyalaan.',
                    'note'   => 'MCB terpasang, kabel instalasi siap.',
                    'actor'  => $emp['unit_konstruksi'] ?? null,
                    'at'     => $now->copy()->subDays(15),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PENYALAAN,
                    'detail' => null,
                    'title'  => 'Diteruskan ke Unit TE',
                    'desc'   => 'Permohonan diteruskan ke Unit Teknik Elektrik untuk penyalaan.',
                    'note'   => null,
                    'actor'  => $emp['unit_konstruksi'] ?? null,
                    'at'     => $now->copy()->subDays(10),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PENYALAAN,
                    'detail' => PermohonanDetailStatus::PENYALAAN_BERHASIL,
                    'title'  => 'Penyalaan Berhasil',
                    'desc'   => 'Penyalaan listrik berhasil dilakukan. Tegangan terukur normal.',
                    'note'   => 'Tegangan: 220V ±5%. Arus nominal: 35A. Semua fase OK.',
                    'actor'  => $emp['unit_te'] ?? null,
                    'at'     => $now->copy()->subDays(5),
                ],
            ],
        );

        if ($sr6) {
            Payment::firstOrCreate(
                ['service_request_id' => $sr6->id, 'status' => 'SUCCESS'],
                [
                    'amount' => 3450000,
                    'ref_no' => 'PAY-2026-00823',
                    'paid_at' => $now->copy()->subDays(30),
                ]
            );
        }

        // ──────────────────────────────────────────────────────────────────────
        // SR 7 — SELESAI (completed, berkas CLOSE)
        // User: Siti Aminah (second SR)
        // ──────────────────────────────────────────────────────────────────────
        $this->makeSR(
            user:        $users['pelanggan2@kudus.id'],
            submittedAt: $now->copy()->subDays(60),
            jenis:       'PASANG_BARU',
            dayaBaru:    1300,
            status:      PermohonanStatus::SELESAI,
            detail:      PermohonanDetailStatus::CLOSE,
            noPermohonan: 'PLN-UP3KDS-2026-007',
            completedAt: $now->copy()->subDays(5),
            events: [
                [
                    'status' => PermohonanStatus::VERIFIKASI_DATA,
                    'detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA,
                    'title'  => 'Permohonan Diterima',
                    'desc'   => 'Permohonan terdaftar.',
                    'note'   => null,
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(60),
                ],
                [
                    'status' => PermohonanStatus::UNIT_SURVEY,
                    'detail' => PermohonanDetailStatus::SURVEY_SELESAI,
                    'title'  => 'Survey Selesai',
                    'desc'   => 'Survey lapangan selesai.',
                    'note'   => 'Lokasi: Jl. Diponegoro No.5, Kudus. Layak pasang.',
                    'actor'  => $emp['unit_survey'] ?? null,
                    'at'     => $now->copy()->subDays(54),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PERENCANAAN,
                    'detail' => PermohonanDetailStatus::MATERIAL_TERSEDIA,
                    'title'  => 'Material Siap',
                    'desc'   => 'Material tersedia.',
                    'note'   => null,
                    'actor'  => $emp['unit_perencanaan'] ?? null,
                    'at'     => $now->copy()->subDays(48),
                ],
                [
                    'status' => PermohonanStatus::PEMBAYARAN,
                    'detail' => PermohonanDetailStatus::PEMBAYARAN_SUKSES,
                    'title'  => 'Pembayaran Dikonfirmasi',
                    'desc'   => 'Pembayaran Rp 1.350.000 dikonfirmasi.',
                    'note'   => 'Ref: PAY-2026-00701.',
                    'actor'  => $emp['admin_pelayanan'] ?? null,
                    'at'     => $now->copy()->subDays(42),
                ],
                [
                    'status' => PermohonanStatus::UNIT_KONSTRUKSI,
                    'detail' => PermohonanDetailStatus::KONSTRUKSI_BERHASIL,
                    'title'  => 'Instalasi Selesai',
                    'desc'   => 'Instalasi di sisi pelanggan selesai.',
                    'note'   => null,
                    'actor'  => $emp['unit_konstruksi'] ?? null,
                    'at'     => $now->copy()->subDays(28),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PENYALAAN,
                    'detail' => PermohonanDetailStatus::PENYALAAN_BERHASIL,
                    'title'  => 'Penyalaan Berhasil',
                    'desc'   => 'Listrik telah menyala normal.',
                    'note'   => 'Pelanggan hadir dan menandatangani berita acara.',
                    'actor'  => $emp['unit_te'] ?? null,
                    'at'     => $now->copy()->subDays(12),
                ],
                [
                    'status' => PermohonanStatus::UNIT_PENYALAAN,
                    'detail' => PermohonanDetailStatus::PENYALAAN_SELESAI,
                    'title'  => 'Penyalaan Selesai',
                    'desc'   => 'Proses penyalaan selesai. Sambungan daya aktif.',
                    'note'   => 'Selesai tepat waktu. Pelanggan puas.',
                    'actor'  => $emp['unit_te'] ?? null,
                    'at'     => $now->copy()->subDays(8),
                ],
                [
                    'status' => PermohonanStatus::SELESAI,
                    'detail' => PermohonanDetailStatus::CLOSE,
                    'title'  => 'Berkas Ditutup',
                    'desc'   => 'Berkas permohonan ditutup secara resmi.',
                    'note'   => 'Berkas diarsipkan di lemari dokumen PLN UP3 Kudus.',
                    'actor'  => $emp['unit_te'] ?? null,
                    'at'     => $now->copy()->subDays(5),
                ],
            ],
        );

        // Payment for SR7
        $sr7 = ServiceRequest::where('nomor_permohonan', 'PLN-UP3KDS-2026-007')->first();
        if ($sr7) {
            Payment::firstOrCreate(
                ['service_request_id' => $sr7->id, 'status' => 'SUCCESS'],
                [
                    'amount' => 1350000,
                    'ref_no' => 'PAY-2026-00701',
                    'paid_at' => $now->copy()->subDays(42),
                ]
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER: Create a ServiceRequest with its workflow events
    // ─────────────────────────────────────────────────────────────────────────

    private function makeSR(
        User $user,
        Carbon $submittedAt,
        string $jenis,
        int $dayaBaru,
        PermohonanStatus $status,
        ?PermohonanDetailStatus $detail,
        string $noPermohonan,
        array $events,
        ?Carbon $completedAt = null,
    ): ?ServiceRequest {
        // Create or update applicant identity
        $applicant = ApplicantIdentity::firstOrCreate(
            ['user_id' => $user->id, 'nik' => $user->nik ?? ('33190101010100' . $user->id)],
            [
                'nama_lengkap' => $user->name,
                'no_hp'        => '081234567800',
            ]
        );

        // Create SR
        $sr = ServiceRequest::create([
            'jenis_layanan'           => $jenis,
            'nomor_permohonan'        => $noPermohonan,
            'status'                  => $status,
            'status_detail'           => $detail,
            'is_draft'                => false,
            'submitted_at'            => $submittedAt,
            'submitter_user_id'       => $user->id,
            'applicant_id'            => $applicant->id,
            'applicant_nik'           => $applicant->nik,
            'daya_baru'               => $dayaBaru,
            'jenis_produk'            => 'PASCABAYAR',
            'peruntukan_koneksi'      => 'RUMAH_TANGGA',
            'slo_verification_status' => 'verified',
            'lokasi_provinsi'         => 'Jawa Tengah',
            'lokasi_kab_kota'         => 'Kabupaten Kudus',
            'lokasi_kecamatan'        => 'Kota',
            'lokasi_kelurahan'        => 'Panjunan',
            'lokasi_detail_tambahan'  => 'Dekat Pasar Kliwon',
            'status_changed_at'       => $submittedAt,
            'completed_at'            => $completedAt,
        ]);

        // Create workflow events
        foreach ($events as $ev) {
            /** @var ?Employee $actor */
            $actor = $ev['actor'];

            ServiceRequestEvent::create([
                'service_request_id' => $sr->id,
                'status'             => $ev['status'],
                'status_detail'      => $ev['detail'],
                'title'              => $ev['title'],
                'description'        => $ev['desc'],
                'note'               => $ev['note'],
                'updated_by_name'    => $actor?->name,
                'updated_by_role'    => $actor?->role,
                'occurred_at'        => $ev['at'],
            ]);
        }

        $this->command->line(
            "  <info>✓</info> SR [{$noPermohonan}] — {$status->getLabel()}" .
            ($detail ? " / {$detail->getLabel()}" : '') .
            " <comment>({$user->name})</comment>"
        );

        return $sr;
    }
}