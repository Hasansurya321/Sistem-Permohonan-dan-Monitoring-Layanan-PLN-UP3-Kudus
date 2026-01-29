<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'jenis_layanan'];

    protected $casts = [
        'payload_json' => 'array',
        'last_saved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'is_draft' => 'boolean',
        'status' => PermohonanStatus::class,
        'status_detail' => PermohonanDetailStatus::class,
    ];

    public function applicant()
    {
        return $this->belongsTo(ApplicantIdentity::class, 'applicant_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeWaiting($query)
    {
        // Menunggu Eksekusi: Draft yang belum disubmit ATAU yang dikembalikan (Gagal Verifikasi)
        return $query->where(function($q) {
            $q->where('is_draft', true)
              ->orWhere('status_detail', PermohonanDetailStatus::VERIFIKASI_GAGAL->value);
        });
    }

    public function scopeProcessing($query)
    {
        // Sedang Berjalan: Sudah disubmit, tidak sedang dalam status "Gagal Verifikasi", 
        // belum selesai, dan belum dibatalkan.
        return $query->where('is_draft', false)
            ->where(function($q) {
                $q->whereNull('status_detail')
                  ->orWhere('status_detail', '!=', PermohonanDetailStatus::VERIFIKASI_GAGAL->value);
            })
            ->whereNotIn('status', [
                PermohonanStatus::SELESAI->value,
                PermohonanStatus::DIBATALKAN_ADMIN->value
            ])
            ->whereNull('cancelled_at')
            ->whereNull('completed_at');
    }

    public function scopeDone($query)
    {
        // Prioritize status enum for consistency
        // Note: Ideally, when completed_at is set, status should be SELESAI
        //       when cancelled_at is set, status should be DIBATALKAN_ADMIN
        return $query->where(function($q) {
            $q->whereIn('status', [
                PermohonanStatus::SELESAI->value,
                PermohonanStatus::DIBATALKAN_ADMIN->value
            ])
            // Fallback for legacy data during transition
            ->orWhereNotNull('completed_at')
            ->orWhereNotNull('cancelled_at');
        });
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->is_draft === true;
    }

    public function isProcessing(): bool
    {
        return !$this->is_draft && $this->status->isProcessing();
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null || $this->cancelled_at !== null;
    }

    /**
     * Transition status safely with validation and audit
     */
    public function transitionTo(
        PermohonanStatus|string $status,
        PermohonanDetailStatus|string|null $detail = null
    ): void {
        $statusEnum = $status instanceof PermohonanStatus
            ? $status
            : PermohonanStatus::from($status);

        $detailEnum = is_null($detail)
            ? null
            : ($detail instanceof PermohonanDetailStatus
                ? $detail
                : PermohonanDetailStatus::tryFrom((string)$detail));

        // Validate detail against status
        if ($detailEnum && !in_array($detailEnum, $statusEnum->allowedDetails())) {
            throw new \DomainException("Invalid status detail '{$detailEnum->value}' for status '{$statusEnum->value}'");
        }

        DB::transaction(function () use ($statusEnum, $detailEnum) {
            $data = [
                'status' => $statusEnum,
                'status_detail' => $detailEnum,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ];

            // If status is SELESAI, set completed_at
            if ($statusEnum === PermohonanStatus::SELESAI && !$this->completed_at) {
                $data['completed_at'] = now();
            }

            $this->update($data);
        });
    }

    /**
     * SINKRONISASI DATA PEMOHON KE applicant_identities
     * Hanya mengisi kolom yang masih kosong/blank (Tanpa Overwrite)
     */
    public function syncApplicantFromPayloadSafely(): void
    {
        $this->loadMissing('applicant');

        if (!$this->applicant) {
            return;
        }

        $payload = $this->payload_json ?? [];
        $lokasi  = data_get($payload, 'lokasi', []);

        if (!is_array($lokasi) || empty($lokasi)) {
            return;
        }

        $mapping = [
            'default_alamat_detail' => data_get($lokasi, 'alamat_detail'),
            'default_rt'            => data_get($lokasi, 'rt'),
            'default_rw'            => data_get($lokasi, 'rw'),
            'default_kelurahan'     => data_get($lokasi, 'kelurahan'),
            'default_kecamatan'     => data_get($lokasi, 'kecamatan'),
            'default_kab_kota'      => data_get($lokasi, 'kab_kota'),
            'default_provinsi'      => data_get($lokasi, 'provinsi'),
        ];

        foreach ($mapping as $column => $value) {
            if (blank($this->applicant->{$column}) && filled($value)) {
                $this->applicant->{$column} = $value;
            }
        }

        $this->applicant->save();
    }

    /**
     * SINKRONISASI LOKASI FISIK KE service_requests (Self)
     */
    public function syncLocationFromPayload(): void
    {
        $payload = $this->payload_json ?? [];
        $lokasi  = data_get($payload, 'lokasi', []);

        if (!is_array($lokasi) || empty($lokasi)) {
            return;
        }

        $coords = data_get($lokasi, 'koordinat');
        $lat = null;
        $lng = null;

        if ($coords && str_contains($coords, ',')) {
            $parts = explode(',', $coords);
            $lat = trim($parts[0]);
            $lng = trim($parts[1] ?? null);
        }

        $this->update([
            'lokasi_provinsi'        => data_get($lokasi, 'provinsi'),
            'lokasi_kab_kota'        => data_get($lokasi, 'kab_kota'),
            'lokasi_kecamatan'       => data_get($lokasi, 'kecamatan'),
            'lokasi_kelurahan'       => data_get($lokasi, 'kelurahan'),
            'lokasi_rt'              => data_get($lokasi, 'rt'),
            'lokasi_rw'              => data_get($lokasi, 'rw'),
            'lokasi_detail_tambahan' => data_get($lokasi, 'alamat_detail'),
            'koordinat_lat'          => $lat,
            'koordinat_lng'          => $lng,
        ]);
    }
}
