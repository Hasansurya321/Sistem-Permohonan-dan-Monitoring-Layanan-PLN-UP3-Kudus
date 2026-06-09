<?php

namespace Database\Factories;

use App\Enums\PermohonanDetailStatus;
use App\Enums\PermohonanStatus;
use App\Models\ApplicantIdentity;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    public function definition(): array
    {
        // Always create a user for submitter
        $user = User::factory()->create();

        // Create a minimal applicant identity
        $applicant = ApplicantIdentity::create([
            'nik'          => $this->faker->numerify('################'),
            'nama_lengkap' => $this->faker->name(),
            'no_hp'        => $this->faker->phoneNumber(),
            'user_id'      => $user->id,
        ]);

        return [
            'jenis_layanan'    => 'TAMBAH_DAYA',
            'nomor_permohonan' => 'PLN-UP3KUDUS-' . $this->faker->unique()->numerify('####'),
            'status'           => PermohonanStatus::VERIFIKASI_DATA,
            'status_detail'    => null,
            'is_draft'         => false,
            'submitted_at'     => now(),
            'submitter_user_id' => $user->id,
            'applicant_id'     => $applicant->id,
            'applicant_nik'    => $applicant->nik,
            'daya_baru'        => 2200,
            'slo_verification_status' => 'pending',
        ];
    }
}
