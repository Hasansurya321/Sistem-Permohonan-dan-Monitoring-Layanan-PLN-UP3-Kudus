<?php

namespace Tests\Feature;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Events\ServiceRequestStatusChanged;
use App\Models\ApplicantIdentity;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvent;
use App\Models\User;
use App\Notifications\CustomerWorkflowNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function emp(string $role): Employee
    {
        self::$seq++;
        return Employee::create([
            'name'      => 'Emp ' . self::$seq . ' ' . $role,
            'email'     => 'emp' . self::$seq . '@pln.test',
            'password'  => bcrypt('password'),
            'role'      => $role,
            'unit'      => $role,
            'jabatan'   => 'Staff',
            'is_active' => true,
        ]);
    }

    private function pelanggan(): User
    {
        self::$seq++;
        return User::factory()->create([
            'role'      => 'pelanggan',
            'status'    => 'active',
            'is_active' => 1,
        ]);
    }

    private function freshSR(
        PermohonanStatus $status,
        ?PermohonanDetailStatus $detail = null,
        ?User $owner = null
    ): ServiceRequest {
        if ($owner) {
            $applicant = ApplicantIdentity::create([
                'nik'          => str_pad((string)(mt_rand(1000000000000000, 9999999999999999)), 16, '0', STR_PAD_LEFT),
                'nama_lengkap' => $owner->name,
                'no_hp'        => '0812345678',
                'user_id'      => $owner->id,
            ]);

            return ServiceRequest::create([
                'jenis_layanan'           => 'TAMBAH_DAYA',
                'nomor_permohonan'        => 'PLN-' . mt_rand(10000, 99999),
                'status'                  => $status,
                'status_detail'           => $detail,
                'is_draft'                => false,
                'submitted_at'            => now(),
                'submitter_user_id'       => $owner->id,
                'applicant_id'            => $applicant->id,
                'applicant_nik'           => $applicant->nik,
                'daya_baru'               => 2200,
                'slo_verification_status' => 'pending',
            ]);
        }

        return ServiceRequest::factory()->create([
            'status'        => $status,
            'status_detail' => $detail,
        ]);
    }

    public function test_full_end_to_end_workflow_pipeline(): void
    {
        $user = $this->pelanggan();
        $sr   = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA, null, $user);

        // Step 1: Admin Layanan verifikasi data
        $adminPelayanan = $this->emp('admin_pelayanan');
        $this->actingAs($adminPelayanan, 'employee');
        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);

        // Admin completes administration
        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::ADMINISTRASI_SELESAI);
        $sr->refresh();
        $this->assertEquals(PermohonanStatus::VERIFIKASI_DATA, $sr->status);

        // Forward to Survey
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_SURVEY, $sr->status);

        // Step 2: Unit Survey
        $unitSurvey = $this->emp('unit_survey');
        $this->actingAs($unitSurvey, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_DIJADWALKAN, null, 'Jadwal Senin 09.00');
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_SELESAI);
        $sr->transitionTo(PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_PERENCANAAN, $sr->status);

        // Step 3: Unit Perencanaan
        $unitPerencanaan = $this->emp('unit_perencanaan');
        $this->actingAs($unitPerencanaan, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::MATERIAL_TERSEDIA);
        $sr->transitionTo(PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::TAGIHAN_TERBIT);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::PEMBAYARAN, $sr->status);

        // Step 4: Payment via system (QR simulator / gateway callback)
        $sr->transitionToSystem(PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::PEMBAYARAN_SUKSES);

        $sr->refresh();
        $this->assertEquals(PermohonanDetailStatus::PEMBAYARAN_SUKSES, $sr->status_detail);

        // Step 5: Admin Pelayanan advances to Konstruksi
        $this->actingAs($adminPelayanan, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::PEMBANGUNAN_JARINGAN);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_KONSTRUKSI, $sr->status);

        // Step 6: Unit Konstruksi
        $unitKonstruksi = $this->emp('unit_konstruksi');
        $this->actingAs($unitKonstruksi, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN);
        $sr->transitionTo(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_BERHASIL);
        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN, null, null, 'Diteruskan ke Unit TE');

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_PENYALAAN, $sr->status);

        // Step 7: Unit TE
        $unitTe = $this->emp('unit_te');
        $this->actingAs($unitTe, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_BERHASIL);
        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_DIJADWALKAN);
        $sr->transitionTo(PermohonanStatus::SELESAI, PermohonanDetailStatus::CLOSE);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::SELESAI, $sr->status);
        $this->assertEquals(PermohonanDetailStatus::CLOSE, $sr->status_detail);
        $this->assertNotNull($sr->completed_at);
    }

    public function test_customer_receives_email_on_survey_lapangan(): void
    {
        Notification::fake();

        $user = $this->pelanggan();
        $sr   = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES, $user);

        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY);

        Notification::assertSentTo($user, CustomerWorkflowNotification::class,
            fn ($n) => $n->newStatus === PermohonanStatus::UNIT_SURVEY
        );
    }

    public function test_customer_receives_email_on_konstruksi(): void
    {
        Notification::fake();

        $user = $this->pelanggan();
        $sr   = $this->freshSR(PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::PEMBAYARAN_SUKSES, $user);

        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::PEMBANGUNAN_JARINGAN);

        Notification::assertSentTo($user, CustomerWorkflowNotification::class,
            fn ($n) => $n->newStatus === PermohonanStatus::UNIT_KONSTRUKSI
        );
    }

    public function test_customer_receives_email_on_penyalaan_te(): void
    {
        Notification::fake();

        $user = $this->pelanggan();
        $sr   = $this->freshSR(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_BERHASIL, $user);

        $unitKonstruksi = $this->emp('unit_konstruksi');
        $this->actingAs($unitKonstruksi, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN);

        Notification::assertSentTo($user, CustomerWorkflowNotification::class,
            fn ($n) => $n->newStatus === PermohonanStatus::UNIT_PENYALAAN
        );
    }

    public function test_customer_email_not_sent_on_material_menunggu_detail_update(): void
    {
        Notification::fake();

        $user = $this->pelanggan();
        $sr   = $this->freshSR(PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL, $user);

        $unitPerencanaan = $this->emp('unit_perencanaan');
        $this->actingAs($unitPerencanaan, 'employee');

        $sr->transitionTo(PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::MATERIAL_MENUNGGU);

        Notification::assertNothingSent();
    }

    public function test_selesai_and_close_flow(): void
    {
        $unitTe = $this->emp('unit_te');
        $this->actingAs($unitTe, 'employee');

        $sr = $this->freshSR(PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_BERHASIL);

        // Advance to SELESAI with CLOSE
        $sr->transitionTo(PermohonanStatus::SELESAI, PermohonanDetailStatus::CLOSE, null, 'Berkas diarsipkan.');
        $sr->refresh();
        $this->assertEquals(PermohonanStatus::SELESAI, $sr->status);
        $this->assertEquals(PermohonanDetailStatus::CLOSE, $sr->status_detail);

        // Verify events were recorded
        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $sr->id,
            'status'             => PermohonanStatus::SELESAI->value,
            'status_detail'      => PermohonanDetailStatus::CLOSE->value,
        ]);
    }

    public function test_same_transition_event_is_not_duplicated(): void
    {
        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');

        $sr = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA);

        // First transition
        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);
        $countBefore = ServiceRequestEvent::where('service_request_id', $sr->id)->count();

        $this->assertDatabaseCount('service_request_events', $countBefore);
        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $sr->id,
            'status'             => PermohonanStatus::VERIFIKASI_DATA->value,
            'status_detail'      => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES->value,
        ]);

        $this->assertEquals(1, ServiceRequestEvent::where('service_request_id', $sr->id)
            ->where('status', PermohonanStatus::VERIFIKASI_DATA->value)
            ->where('status_detail', PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES->value)
            ->count()
        );
    }

    public function test_event_dispatched_on_every_stage_transition(): void
    {
        Event::fake();

        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');

        $sr = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA);

        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY);

        Event::assertDispatched(ServiceRequestStatusChanged::class, 2);
    }

    public function test_verifikasi_data_can_be_forwarded_to_survey(): void
    {
        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');

        $sr = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);

        // Admin completes administration then forwards to Survey
        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::ADMINISTRASI_SELESAI);
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_SURVEY, $sr->status);
        $this->assertEquals(PermohonanDetailStatus::DITERIMA_UNIT_SURVEY, $sr->status_detail);
    }

    public function test_payment_confirmed_then_admin_advances_to_konstruksi(): void
    {
        $user = $this->pelanggan();
        $sr   = $this->freshSR(PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::TAGIHAN_TERBIT, $user);

        // Payment confirmed via system (QR Simulator / gateway)
        $sr->transitionToSystem(PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::PEMBAYARAN_SUKSES);

        $sr->refresh();
        $this->assertEquals(PermohonanDetailStatus::PEMBAYARAN_SUKSES, $sr->status_detail);
        $this->assertNull($sr->completed_at);

        // Admin Pelayanan forwards to Konstruksi
        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');
        $sr->transitionTo(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::PEMBANGUNAN_JARINGAN);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_KONSTRUKSI, $sr->status);
        $this->assertNull($sr->completed_at);
    }

    public function test_unit_te_cannot_do_verifikasi_transition(): void
    {
        $this->expectException(AuthorizationException::class);

        $unitTe = $this->emp('unit_te');
        $this->actingAs($unitTe, 'employee');

        $sr = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA);

        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);
    }

    public function test_unit_survey_cannot_advance_to_penyalaan_te(): void
    {
        $this->expectException(AuthorizationException::class);

        $unitSurvey = $this->emp('unit_survey');
        $this->actingAs($unitSurvey, 'employee');

        $sr = $this->freshSR(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::PEMBANGUNAN_JARINGAN);

        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN);
    }

    public function test_all_transitions_create_events_in_database(): void
    {
        $admin = $this->emp('admin_pelayanan');
        $this->actingAs($admin, 'employee');

        $sr = $this->freshSR(PermohonanStatus::VERIFIKASI_DATA);

        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES, null, 'Test note verifikasi');
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY, null, 'Test note Survey');

        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $sr->id,
            'status'             => PermohonanStatus::VERIFIKASI_DATA->value,
            'status_detail'      => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES->value,
            'note'               => 'Test note verifikasi',
            'updated_by_name'    => $admin->name,
            'updated_by_role'    => 'admin_pelayanan',
        ]);

        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $sr->id,
            'status'             => PermohonanStatus::UNIT_SURVEY->value,
            'status_detail'      => PermohonanDetailStatus::DITERIMA_UNIT_SURVEY->value,
            'note'               => 'Test note Survey',
        ]);
    }

    public function test_supervisor_can_cancel_at_konstruksi_stage(): void
    {
        $supervisor = $this->emp('supervisor');
        $this->actingAs($supervisor, 'employee');

        $sr = $this->freshSR(PermohonanStatus::UNIT_KONSTRUKSI, PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN);

        $sr->transitionTo(PermohonanStatus::SELESAI, null, null, 'Dibatalkan: tidak memenuhi syarat teknis.');

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::SELESAI, $sr->status);

        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $sr->id,
            'status'             => PermohonanStatus::SELESAI->value,
            'note'               => 'Dibatalkan: tidak memenuhi syarat teknis.',
        ]);
    }
}