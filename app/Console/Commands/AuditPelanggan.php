<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\MasterPelanggan;
use App\Models\CustomerAccountRequest;
use App\Models\ApplicantIdentity;
use App\Models\ServiceRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditPelanggan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pelanggan:audit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit konsistensi data pelanggan dengan klasifikasi ERROR/WARNING/INFO.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== AUDIT KUALITAS DATA PELANGGAN ===');
        $this->newLine();

        // ── TOTAL RECORD ──
        $this->info('--- TOTAL RECORD ---');
        $this->line(sprintf('  users:                     %d', User::count()));
        $this->line(sprintf('  master_pelanggan:          %d', MasterPelanggan::count()));
        $this->line(sprintf('  customer_account_requests: %d', CustomerAccountRequest::count()));
        $this->line(sprintf('  applicant_identities:      %d', ApplicantIdentity::count()));
        $this->line(sprintf('  service_requests:          %d', ServiceRequest::count()));
        $this->newLine();

        // ══════════════════════════════════════════════════════
        // KLASIFIKASI ERROR (Data Integrity Violation)
        // ══════════════════════════════════════════════════════
        $this->error('╔══════════════════════════════════════════════╗');
        $this->error('║           ERROR — INTEGRITAS DATA           ║');
        $this->error('╚══════════════════════════════════════════════╝');
        $this->newLine();

        // ── ERROR 1: DUPLICATE NIK ──
        $this->error('--- DUPLICATE NIK (ERROR) ---');
        $dupNik = MasterPelanggan::select('nik', DB::raw('COUNT(*) as total'))
            ->whereNotNull('nik')
            ->groupBy('nik')
            ->having('total', '>', 1)
            ->get();
        if ($dupNik->isEmpty()) {
            $this->line('  ✅ Tidak ada duplikasi NIK.');
        } else {
            foreach ($dupNik as $d) {
                $records = MasterPelanggan::where('nik', $d->nik)->get(['id', 'nama_lengkap', 'id_pelanggan_12']);
                $this->error(sprintf('  ❌ NIK: %s (%d record)', $d->nik, $d->total));
                foreach ($records as $r) {
                    $this->line(sprintf('      - ID: %d | Nama: %s | IDPEL: %s', $r->id, $r->nama_lengkap ?? '-', $r->id_pelanggan_12 ?? '-'));
                }
            }
        }
        $this->newLine();

        // ── ERROR 2: DUPLICATE IDPEL ──
        $this->error('--- DUPLICATE IDPEL (ERROR) ---');
        $dupIdpel = MasterPelanggan::select('id_pelanggan_12', DB::raw('COUNT(*) as total'))
            ->whereNotNull('id_pelanggan_12')
            ->groupBy('id_pelanggan_12')
            ->having('total', '>', 1)
            ->get();
        if ($dupIdpel->isEmpty()) {
            $this->line('  ✅ Tidak ada duplikasi IDPEL.');
        } else {
            foreach ($dupIdpel as $d) {
                $records = MasterPelanggan::where('id_pelanggan_12', $d->id_pelanggan_12)->get(['id', 'nik', 'nama_lengkap']);
                $this->error(sprintf('  ❌ IDPEL: %s (%d record)', $d->id_pelanggan_12, $d->total));
                foreach ($records as $r) {
                    $this->line(sprintf('      - ID: %d | NIK: %s | Nama: %s', $r->id, $r->nik ?? '-', $r->nama_lengkap ?? '-'));
                }
            }
        }
        $this->newLine();

        // ── ERROR 3: DUPLICATE NO METER ──
        $this->error('--- DUPLICATE NO METER (ERROR) ---');
        $dupMeter = MasterPelanggan::select('no_meter', DB::raw('COUNT(*) as total'))
            ->whereNotNull('no_meter')
            ->groupBy('no_meter')
            ->having('total', '>', 1)
            ->get();
        if ($dupMeter->isEmpty()) {
            $this->line('  ✅ Tidak ada duplikasi No Meter.');
        } else {
            foreach ($dupMeter as $d) {
                $records = MasterPelanggan::where('no_meter', $d->no_meter)->get(['id', 'nik', 'nama_lengkap']);
                $this->error(sprintf('  ❌ No Meter: %s (%d record)', $d->no_meter, $d->total));
                foreach ($records as $r) {
                    $this->line(sprintf('      - ID: %d | NIK: %s | Nama: %s', $r->id, $r->nik ?? '-', $r->nama_lengkap ?? '-'));
                }
            }
        }
        $this->newLine();

        // ── ERROR 4: USER TANPA MASTER_PELANGGAN ──
        $this->error('--- USER TANPA MASTER_PELANGGAN (ERROR) ---');
        $usersWithoutMaster = User::leftJoin('master_pelanggan', 'master_pelanggan.user_id', '=', 'users.id')
            ->where('users.role', 'pelanggan')
            ->whereNull('master_pelanggan.id')
            ->select('users.id', 'users.name', 'users.email', 'users.nik')
            ->get();
        if ($usersWithoutMaster->isEmpty()) {
            $this->line('  ✅ Semua user memiliki record di master_pelanggan.');
        } else {
            foreach ($usersWithoutMaster as $u) {
                $this->error(sprintf('  ❌ ID: %d | Nama: %s | Email: %s | NIK: %s', $u->id, $u->name, $u->email, $u->nik ?? '-'));
            }
        }
        $this->newLine();

        // ── ERROR 5: MASTER_PELANGGAN TANPA USER ──
        $this->error('--- MASTER_PELANGGAN TANPA USER (ERROR) ---');
        $masterWithoutUser = MasterPelanggan::leftJoin('users', 'users.id', '=', 'master_pelanggan.user_id')
            ->whereNull('users.id')
            ->select('master_pelanggan.id', 'master_pelanggan.nik', 'master_pelanggan.nama_lengkap', 'master_pelanggan.id_pelanggan_12')
            ->get();
        if ($masterWithoutUser->isEmpty()) {
            $this->line('  ✅ Semua master_pelanggan memiliki user.');
        } else {
            foreach ($masterWithoutUser as $m) {
                $this->error(sprintf('  ❌ ID: %d | NIK: %s | Nama: %s | IDPEL: %s', $m->id, $m->nik ?? '-', $m->nama_lengkap ?? '-', $m->id_pelanggan_12 ?? '-'));
            }
        }
        $this->newLine();

        // ══════════════════════════════════════════════════════
        // KLASIFIKASI WARNING (Perlu Verifikasi Manual)
        // ══════════════════════════════════════════════════════
        $this->warn('╔══════════════════════════════════════════════╗');
        $this->warn('║         WARNING — VERIFIKASI MANUAL         ║');
        $this->warn('╚══════════════════════════════════════════════╝');
        $this->newLine();

        // ── WARNING 1: DUPLICATE NO KK ──
        $this->warn('--- DUPLICATE NO KK (WARNING) ---');
        $dupKk = MasterPelanggan::select('no_kk', DB::raw('COUNT(*) as total'))
            ->whereNotNull('no_kk')
            ->groupBy('no_kk')
            ->having('total', '>', 1)
            ->get();
        if ($dupKk->isEmpty()) {
            $this->line('  ✅ Tidak ada duplikasi No KK.');
        } else {
            $this->warn('  ⚠️  CATATAN: Satu KK dapat memiliki banyak anggota keluarga.');
            $this->warn('  ⚠️  Duplicate KK bukan ERROR — tetapi perlu verifikasi manual.');
            foreach ($dupKk as $d) {
                $records = MasterPelanggan::where('no_kk', $d->no_kk)->get(['id', 'nik', 'nama_lengkap']);
                $this->warn(sprintf('  ⚠️  No KK: %s (%d record)', $d->no_kk, $d->total));
                foreach ($records as $r) {
                    $this->line(sprintf('      - ID: %d | NIK: %s | Nama: %s', $r->id, $r->nik ?? '-', $r->nama_lengkap ?? '-'));
                }
            }
        }
        $this->newLine();

        // ── WARNING 2: MISSING NPWP ──
        $this->warn('--- MISSING NPWP (WARNING) ---');
        $noNpwp = MasterPelanggan::whereNull('npwp')->orWhere('npwp', '')->count();
        $totalMp = MasterPelanggan::count();
        $this->line(sprintf('  %d dari %d record master_pelanggan tanpa NPWP.', $noNpwp, $totalMp));
        $this->newLine();

        // ── WARNING 3: MISSING NO HP ──
        $this->warn('--- MISSING NO HP (WARNING) ---');
        $noHp = MasterPelanggan::whereNull('no_hp')->orWhere('no_hp', '')->count();
        $this->line(sprintf('  %d dari %d record master_pelanggan tanpa No HP.', $noHp, $totalMp));
        $this->newLine();

        // ── WARNING 4: ORPHAN APPLICANT IDENTITY ──
        $this->warn('--- ORPHAN APPLICANT_IDENTITY (WARNING) ---');
        $orphanApplicants = ApplicantIdentity::leftJoin('service_requests', 'service_requests.applicant_id', '=', 'applicant_identities.id')
            ->whereNull('service_requests.id')
            ->select('applicant_identities.id', 'applicant_identities.nik', 'applicant_identities.nama_lengkap')
            ->get();
        if ($orphanApplicants->isEmpty()) {
            $this->line('  ✅ Tidak ada orphan applicant_identity.');
        } else {
            foreach ($orphanApplicants as $a) {
                $this->warn(sprintf('  ⚠️  ID: %d | NIK: %s | Nama: %s', $a->id, $a->nik ?? '-', $a->nama_lengkap ?? '-'));
            }
        }
        $this->newLine();

        // ── WARNING 5: ORPHAN SERVICE REQUEST ──
        $this->warn('--- ORPHAN SERVICE_REQUEST (WARNING) ---');
        $orphanSr = ServiceRequest::leftJoin('applicant_identities', 'applicant_identities.id', '=', 'service_requests.applicant_id')
            ->whereNotNull('service_requests.applicant_id')
            ->whereNull('applicant_identities.id')
            ->select('service_requests.id', 'service_requests.applicant_nik', 'service_requests.status', 'service_requests.jenis_layanan')
            ->get();
        if ($orphanSr->isEmpty()) {
            $this->line('  ✅ Tidak ada orphan service_request.');
        } else {
            foreach ($orphanSr as $sr) {
                $this->warn(sprintf('  ⚠️  ID: %d | NIK: %s | Status: %s | Jenis: %s', $sr->id, $sr->applicant_nik ?? '-', $sr->status ?? '-', $sr->jenis_layanan ?? '-'));
            }
        }
        $this->newLine();

        // ══════════════════════════════════════════════════════
        // KLASIFIKASI INFO (Data Non-Produksi)
        // ══════════════════════════════════════════════════════
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║        INFO — DATA NON-PRODUKSI             ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();

        // ── INFO 1: Data dari seeder ──
        $this->info('--- DATA SEEDER (INFO) ---');
        $seedUsers = User::where('role', 'pelanggan')
            ->whereIn('email', [
                'pelanggan1@kudus.id',
                'pelanggan2@kudus.id',
                'pelanggan3@kudus.id',
                'pelanggan4@kudus.id',
                'pelanggan5@kudus.id',
            ])->count();
        $this->line(sprintf('  %d user dari FinalDemoSeeder.', $seedUsers));
        $this->newLine();

        // ── INFO 2: Data testing ──
        $this->info('--- DATA TESTING (INFO) ---');
        $testingUsers = User::where('role', 'pelanggan')
            ->where(function($q) {
                $q->where('email', 'like', '%test%')
                  ->orWhere('email', 'like', '%testing%')
                  ->orWhere('email', 'like', 'e2e%');
            })
            ->get();
        foreach ($testingUsers as $u) {
            $mp = MasterPelanggan::where('user_id', $u->id)->first();
            $this->line(sprintf('  ID: %d | Nama: %s | Email: %s | MP: %s',
                $u->id, $u->name, $u->email, $mp ? 'ADA' : 'TIDAK ADA'));
        }
        $this->newLine();

        // ── INFO 3: Inkonsistensi User Aktif ──
        $this->info('--- USER AKTIF TANPA NIK (INFO) ---');
        $activeNoNik = User::where('role', 'pelanggan')
            ->where('is_active', 1)
            ->where(function($q) { $q->whereNull('nik')->orWhere('nik', ''); })
            ->get();
        foreach ($activeNoNik as $u) {
            $this->line(sprintf('  ID: %d | Nama: %s | Email: %s', $u->id, $u->name, $u->email));
        }
        $this->newLine();

        $this->info('--- USER AKTIF TANPA IDPEL (INFO) ---');
        $activeNoIdpel = User::where('role', 'pelanggan')
            ->where('is_active', 1)
            ->where(function($q) { $q->whereNull('id_pelanggan')->orWhere('id_pelanggan', ''); })
            ->get();
        foreach ($activeNoIdpel as $u) {
            $mp = MasterPelanggan::where('user_id', $u->id)->first();
            $this->line(sprintf('  ID: %d | Nama: %s | Email: %s | NIK: %s | MP IDPEL: %s',
                $u->id, $u->name, $u->email, $u->nik ?? '-', $mp->id_pelanggan_12 ?? '-'));
        }
        $this->newLine();

        // ══════════════════════════════════════════════════════
        // FINAL SUMMARY
        // ══════════════════════════════════════════════════════
        $this->info('=== SUMMARY ===');

        $totalErrors = $dupNik->count()
            + $dupIdpel->count()
            + $dupMeter->count()
            + $usersWithoutMaster->count()
            + $masterWithoutUser->count();

        $totalWarnings = $dupKk->count()
            + ($noNpwp > 0 ? 1 : 0)
            + ($noHp > 0 ? 1 : 0)
            + $orphanApplicants->count()
            + $orphanSr->count();

        $totalRecords = User::count() + MasterPelanggan::count() + CustomerAccountRequest::count()
            + ApplicantIdentity::count() + ServiceRequest::count();

        $this->line(sprintf('  Total Record:          %d', $totalRecords));
        $this->newLine();
        $this->error(sprintf('  ERROR: %d masalah integritas data', $totalErrors));
        $this->warn(sprintf('  WARNING: %d perlu verifikasi manual', $totalWarnings));
        $this->info(sprintf('  INFO: Data non-produksi untuk referensi'));

        $this->newLine();
        if ($totalErrors === 0) {
            $this->info('  STATUS: ✅ DATA KONSISTEN — Tidak ada pelanggaran integritas data.');
        } else {
            $this->error(sprintf('  STATUS: ❌ Ditemukan %d ERROR — lihat detail di atas.', $totalErrors));
        }

        return Command::SUCCESS;
    }
}