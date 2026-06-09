<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\ApplicantIdentity;
use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Illuminate\Auth\Access\AuthorizationException;

class ServiceRequestWorkflowTest extends TestCase
{
    public function test_system_can_confirm_payment_detail()
    {
        $user = User::create([
            'name' => 'Budi Pelanggan',
            'email' => 'budi@example.com',
            'password' => 'password',
            'role' => 'pelanggan',
            'is_active' => true,
        ]);

        $applicant = ApplicantIdentity::create([
            'nik' => '1234567890123456',
            'nama_lengkap' => 'Budi Pelanggan',
            'user_id' => $user->id,
        ]);

        $request = ServiceRequest::create([
            'jenis_layanan' => 'TAMBAH_DAYA',
            'submitter_user_id' => $user->id,
            'applicant_id' => $applicant->id,
            'applicant_nik' => $applicant->nik,
            'daya_baru' => 2200,
            'jenis_produk' => 'PASCABAYAR',
            'peruntukan_koneksi' => 'RUMAH_TANGGA',
            'status' => PermohonanStatus::PEMBAYARAN,
            'status_detail' => PermohonanDetailStatus::TAGIHAN_TERBIT,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        // 🔴 FIX: Pembayaran sukses hanya bisa dilakukan oleh system (QRIS callback gateway),
        // bukan oleh pelanggan. Gunakan transitionToSystem().
        $request->transitionToSystem(
            PermohonanStatus::PEMBAYARAN,
            PermohonanDetailStatus::PEMBAYARAN_SUKSES,
            'Konfirmasi pembayaran dari gateway.'
        );

        $this->assertEquals(PermohonanStatus::PEMBAYARAN, $request->status);
        $this->assertEquals(PermohonanDetailStatus::PEMBAYARAN_SUKSES, $request->status_detail);
        $this->assertDatabaseHas('service_request_events', [
            'service_request_id' => $request->id,
            'status' => PermohonanStatus::PEMBAYARAN->value,
            'status_detail' => PermohonanDetailStatus::PEMBAYARAN_SUKSES->value,
        ]);
    }

    public function test_customer_cannot_update_non_payment_status()
    {
        $user = User::create([
            'name' => 'Ayu Pelanggan',
            'email' => 'ayu@example.com',
            'password' => 'password',
            'role' => 'pelanggan',
            'is_active' => true,
        ]);

        $applicant = ApplicantIdentity::create([
            'nik' => '2345678901234567',
            'nama_lengkap' => 'Ayu Pelanggan',
            'user_id' => $user->id,
        ]);

        $request = ServiceRequest::create([
            'jenis_layanan' => 'TAMBAH_DAYA',
            'submitter_user_id' => $user->id,
            'applicant_id' => $applicant->id,
            'applicant_nik' => $applicant->nik,
            'daya_baru' => 2200,
            'jenis_produk' => 'PASCABAYAR',
            'peruntukan_koneksi' => 'RUMAH_TANGGA',
            'status' => PermohonanStatus::VERIFIKASI_DATA,
            'status_detail' => null,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->expectException(AuthorizationException::class);

        $request->transitionTo(
            PermohonanStatus::VERIFIKASI_DATA,
            PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
            now(),
            'Pelanggan tidak diizinkan mengubah status ini.'
        );
    }

    public function test_status_transitions_cannot_skip_steps()
    {
        $employee = Employee::create([
            'name' => 'Unit Perencanaan Test',
            'email' => 'unit_perencanaan@example.com',
            'password' => 'password',
            'role' => 'unit_perencanaan',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Rudi Pelanggan',
            'email' => 'rudi@example.com',
            'password' => 'password',
            'role' => 'pelanggan',
            'is_active' => true,
        ]);

        $applicant = ApplicantIdentity::create([
            'nik' => '3456789012345678',
            'nama_lengkap' => 'Rudi Pelanggan',
            'user_id' => $user->id,
        ]);

        $request = ServiceRequest::create([
            'jenis_layanan' => 'TAMBAH_DAYA',
            'submitter_user_id' => $user->id,
            'applicant_id' => $applicant->id,
            'applicant_nik' => $applicant->nik,
            'daya_baru' => 2200,
            'jenis_produk' => 'PASCABAYAR',
            'peruntukan_koneksi' => 'RUMAH_TANGGA',
            'status' => PermohonanStatus::VERIFIKASI_DATA,
            'status_detail' => null,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->actingAs($employee, 'employee');

        $this->expectException(\DomainException::class);

        $request->transitionTo(
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL,
            now(),
            'Mencoba loncat dari VERIFIKASI_DATA ke UNIT_PERENCANAAN.'
        );
    }

    public function test_unit_survey_employee_can_move_to_survey_lapangan()
    {
        $employee = Employee::create([
            'name' => 'Anton Survey',
            'email' => 'anton.survey@example.com',
            'password' => 'password',
            'role' => 'unit_survey',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Sari Pelanggan',
            'email' => 'sari@example.com',
            'password' => 'password',
            'role' => 'pelanggan',
            'is_active' => true,
        ]);

        $applicant = ApplicantIdentity::create([
            'nik' => '4567890123456789',
            'nama_lengkap' => 'Sari Pelanggan',
            'user_id' => $user->id,
        ]);

        $request = ServiceRequest::create([
            'jenis_layanan' => 'TAMBAH_DAYA',
            'submitter_user_id' => $user->id,
            'applicant_id' => $applicant->id,
            'applicant_nik' => $applicant->nik,
            'daya_baru' => 2200,
            'jenis_produk' => 'PASCABAYAR',
            'peruntukan_koneksi' => 'RUMAH_TANGGA',
            'status' => PermohonanStatus::VERIFIKASI_DATA,
            'status_detail' => PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        // unit_survey hanya bisa menangani UNIT_SURVEY, jadi set status awal ke UNIT_SURVEY
        $request->status = PermohonanStatus::UNIT_SURVEY;
        $request->status_detail = PermohonanDetailStatus::DITERIMA_UNIT_SURVEY;
        $request->save();
        $request->refresh();

        $this->actingAs($employee, 'employee');

        $request->transitionTo(
            PermohonanStatus::UNIT_SURVEY,
            PermohonanDetailStatus::SURVEY_DIJADWALKAN,
            now(),
            'Survey lapangan dijadwalkan oleh unit survey.'
        );

        $this->assertEquals(PermohonanStatus::UNIT_SURVEY, $request->status);
        $this->assertEquals(PermohonanDetailStatus::SURVEY_DIJADWALKAN, $request->status_detail);
    }
}