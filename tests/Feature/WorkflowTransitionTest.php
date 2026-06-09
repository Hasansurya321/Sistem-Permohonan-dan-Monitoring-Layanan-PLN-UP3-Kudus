<?php

namespace Tests\Feature;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Events\ServiceRequestStatusChanged;
use App\Listeners\SendWorkflowNotifications;
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

class WorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    private static int $seq = 0;

    private function makeEmployee(string $role = 'admin_pelayanan'): Employee
    {
        self::$seq++;
        return Employee::create([
            'name'      => 'Test Employee ' . self::$seq,
            'email'     => 'employee' . self::$seq . '@pln.test',
            'password'  => bcrypt('password'),
            'role'      => $role,
            'unit'      => $role,
            'jabatan'   => 'Staff',
            'is_active' => true,
        ]);
    }

    private function makeUser(): User
    {
        self::$seq++;
        return User::factory()->create([
            'role'      => 'pelanggan',
            'status'    => 'active',
            'is_active' => 1,
        ]);
    }

    private function makeServiceRequest(
        PermohonanStatus $status = PermohonanStatus::VERIFIKASI_DATA,
        ?PermohonanDetailStatus $detail = null,
        ?User $submitter = null
    ): ServiceRequest {
        if ($submitter) {
            $applicant = \App\Models\ApplicantIdentity::create([
                'nik'          => str_pad((string)(mt_rand(1, 9999999999999999)), 16, '0', STR_PAD_LEFT),
                'nama_lengkap' => $submitter->name,
                'no_hp'        => '0812345678',
                'user_id'      => $submitter->id,
            ]);

            return ServiceRequest::create([
                'jenis_layanan'           => 'TAMBAH_DAYA',
                'nomor_permohonan'        => 'PLN-UP3KUDUS-' . mt_rand(1000, 9999),
                'status'                  => $status,
                'status_detail'           => $detail,
                'is_draft'                => false,
                'submitted_at'            => now(),
                'submitter_user_id'       => $submitter->id,
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

    public function test_transition_dispatches_ServiceRequestStatusChanged_event(): void
    {
        Event::fake();

        $employee = $this->makeEmployee('admin_pelayanan');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(PermohonanStatus::VERIFIKASI_DATA);

        $sr->transitionTo(
            PermohonanStatus::VERIFIKASI_DATA,
            PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES
        );

        Event::assertDispatched(ServiceRequestStatusChanged::class, function ($event) use ($sr) {
            return $event->serviceRequest->id === $sr->id
                && $event->toStatus === PermohonanStatus::VERIFIKASI_DATA
                && $event->toDetail === PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES;
        });
    }

    public function test_transition_creates_ServiceRequestEvent_record(): void
    {
        $employee = $this->makeEmployee('admin_pelayanan');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(PermohonanStatus::VERIFIKASI_DATA);

        $sr->transitionTo(
            PermohonanStatus::VERIFIKASI_DATA,
            PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
            null,
            'Catatan verifikasi test'
        );

        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $sr->id,
            'status'             => PermohonanStatus::VERIFIKASI_DATA->value,
            'status_detail'      => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES->value,
            'note'               => 'Catatan verifikasi test',
        ]);
    }

    public function test_admin_pelayanan_can_transition_to_verifikasi_sukses(): void
    {
        $employee = $this->makeEmployee('admin_pelayanan');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(PermohonanStatus::VERIFIKASI_DATA);
        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::VERIFIKASI_DATA, $sr->status);
        $this->assertEquals(PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES, $sr->status_detail);
    }

    public function test_unit_survey_can_schedule_and_complete_survey(): void
    {
        $employee = $this->makeEmployee('unit_survey');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::UNIT_SURVEY,
            PermohonanDetailStatus::DITERIMA_UNIT_SURVEY
        );

        // Jadwalkan
        $sr->transitionTo(
            PermohonanStatus::UNIT_SURVEY,
            PermohonanDetailStatus::SURVEY_DIJADWALKAN,
            null,
            'Jadwal: Senin 09.00'
        );
        $sr->refresh();
        $this->assertEquals(PermohonanDetailStatus::SURVEY_DIJADWALKAN, $sr->status_detail);

        // Selesaikan survey → advance ke perencanaan
        $sr->transitionTo(PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_SELESAI);
        $sr->transitionTo(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL
        );

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_PERENCANAAN, $sr->status);
    }

    public function test_unit_perencanaan_can_advance_to_pembayaran(): void
    {
        $employee = $this->makeEmployee('unit_perencanaan');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL
        );

        $sr->transitionTo(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::MATERIAL_TERSEDIA
        );
        $sr->transitionTo(
            PermohonanStatus::PEMBAYARAN,
            PermohonanDetailStatus::TAGIHAN_TERBIT
        );

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::PEMBAYARAN, $sr->status);
        $this->assertEquals(PermohonanDetailStatus::TAGIHAN_TERBIT, $sr->status_detail);
    }

    public function test_pembayaran_advances_to_konstruksi_not_selesai(): void
    {
        $employee = $this->makeEmployee('admin_pelayanan');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::PEMBAYARAN,
            PermohonanDetailStatus::TAGIHAN_TERBIT
        );

        $sr->transitionTo(
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanDetailStatus::PEMBANGUNAN_JARINGAN
        );

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_KONSTRUKSI, $sr->status);
        $this->assertNull($sr->completed_at);
    }

    public function test_unit_konstruksi_can_advance_to_penyalaan_te(): void
    {
        $employee = $this->makeEmployee('unit_konstruksi');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanDetailStatus::PEMBANGUNAN_JARINGAN
        );

        $sr->transitionTo(
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanDetailStatus::KONSTRUKSI_BERHASIL
        );
        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::UNIT_PENYALAAN, $sr->status);
    }

    public function test_unit_te_can_finalize_to_selesai(): void
    {
        $employee = $this->makeEmployee('unit_te');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::UNIT_PENYALAAN,
            null
        );

        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_BERHASIL);
        $sr->transitionTo(PermohonanStatus::UNIT_PENYALAAN, PermohonanDetailStatus::PENYALAAN_DIJADWALKAN);
        $sr->transitionTo(PermohonanStatus::SELESAI, PermohonanDetailStatus::CLOSE);

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::SELESAI, $sr->status);
        $this->assertNotNull($sr->completed_at);
    }

    public function test_unit_survey_cannot_transition_to_konstruksi(): void
    {
        $this->expectException(AuthorizationException::class);

        $employee = $this->makeEmployee('unit_survey');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanDetailStatus::PEMBANGUNAN_JARINGAN
        );

        $sr->transitionTo(
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN
        );
    }

    public function test_unit_konstruksi_cannot_transition_to_verifikasi(): void
    {
        $this->expectException(AuthorizationException::class);

        $employee = $this->makeEmployee('unit_konstruksi');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(
            PermohonanStatus::VERIFIKASI_DATA,
            PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA
        );

        $sr->transitionTo(PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES);
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $this->expectException(\DomainException::class);

        $employee = $this->makeEmployee('unit_perencanaan');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(PermohonanStatus::UNIT_PERENCANAAN);

        // Cannot jump from UNIT_PERENCANAAN directly to SELESAI (must go through PEMBAYARAN, etc)
        $sr->transitionTo(PermohonanStatus::SELESAI, PermohonanDetailStatus::CLOSE);
    }

    public function test_customer_receives_email_on_pembayaran(): void
    {
        Notification::fake();

        $employee = $this->makeEmployee('unit_perencanaan');
        $this->actingAs($employee, 'employee');

        $user = $this->makeUser();
        $sr   = $this->makeServiceRequest(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::MATERIAL_TERSEDIA,
            $user
        );

        $sr->transitionTo(
            PermohonanStatus::PEMBAYARAN,
            PermohonanDetailStatus::TAGIHAN_TERBIT
        );

        Notification::assertSentTo(
            $user,
            CustomerWorkflowNotification::class,
            fn ($n) => $n->newStatus === PermohonanStatus::PEMBAYARAN
        );
    }

    public function test_customer_receives_email_on_selesai(): void
    {
        Notification::fake();

        $employee = $this->makeEmployee('unit_te');
        $this->actingAs($employee, 'employee');

        $user = $this->makeUser();
        $sr   = $this->makeServiceRequest(
            PermohonanStatus::UNIT_PENYALAAN,
            PermohonanDetailStatus::PENYALAAN_DIJADWALKAN,
            $user
        );

        $sr->transitionTo(PermohonanStatus::SELESAI, PermohonanDetailStatus::CLOSE);

        Notification::assertSentTo(
            $user,
            CustomerWorkflowNotification::class,
            fn ($n) => $n->newStatus === PermohonanStatus::SELESAI
        );
    }

    public function test_customer_does_not_receive_email_on_minor_detail_only_change(): void
    {
        Notification::fake();

        $user = $this->makeUser();

        $perenEmployee = $this->makeEmployee('unit_perencanaan');
        $this->actingAs($perenEmployee, 'employee');

        $sr2 = $this->makeServiceRequest(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
            $user
        );

        $sr2->transitionTo(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::MATERIAL_MENUNGGU
        );

        Notification::assertNotSentTo($user, CustomerWorkflowNotification::class);
    }

    public function test_supervisor_can_cancel_any_request(): void
    {
        $employee = $this->makeEmployee('supervisor');
        $this->actingAs($employee, 'employee');

        $sr = $this->makeServiceRequest(PermohonanStatus::UNIT_KONSTRUKSI);

        $sr->transitionTo(PermohonanStatus::SELESAI, null, null, 'Dibatalkan supervisor');

        $sr->refresh();
        $this->assertEquals(PermohonanStatus::SELESAI, $sr->status);
    }
}