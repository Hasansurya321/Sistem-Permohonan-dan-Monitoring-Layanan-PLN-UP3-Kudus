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

    public function events()
    {
        return $this->hasMany(ServiceRequestEvent::class, 'service_request_id')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    // Scopes
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at')->where('is_draft', false);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', '!=', PermohonanStatus::SELESAI);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PermohonanStatus::SELESAI);
    }

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

            // Log event for timeline
            ServiceRequestEvent::create([
                'service_request_id' => $this->id,
                'status' => $statusEnum,
                'status_detail' => $detailEnum,
                'occurred_at' => now(),
            ]);
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
    /**
     * Finalize SLO verification, generate official number, and auto-advance to payment.
     * Includes comprehensive event logging to skip intermediate steps for testing.
     */
    public function finalizeSloVerificationAndAutoAdvance(): void
    {
        // Idempotency check: Already at or past TAGIHAN_TERBIT
        if (!$this->is_draft && 
            $this->status === PermohonanStatus::MENUNGGU_PEMBAYARAN && 
            $this->status_detail === PermohonanDetailStatus::TAGIHAN_TERBIT) {
            
            // Further check: Do events already exist for this state?
            if ($this->events()->where('status', PermohonanStatus::MENUNGGU_PEMBAYARAN)->exists()) {
                return;
            }
        }

        DB::transaction(function () {
            $draftNo = (string) $this->nomor_permohonan;
            
            // Robust digit extraction: take the last sequence of digits
            preg_match_all('/\d+/', $draftNo, $matches);
            $digits = !empty($matches[0]) ? end($matches[0]) : null;

            if (!$digits) {
                throw new \Exception('Nomor draft tidak valid (tidak ada angka).');
            }

            // Official number: PLN-UP3KUDUS- digits
            $officialNo = 'PLN-UP3KUDUS-' . $digits;

            // Uniqueness check for official number
            $exists = self::where('nomor_permohonan', $officialNo)
                ->where('id', '!=', $this->id)
                ->exists();

            if ($exists) {
                throw new \Exception('Nomor resmi ' . $officialNo . ' sudah digunakan oleh permohonan lain.');
            }

            // Update main record
            $this->update([
                'nomor_permohonan' => $officialNo,
                'is_draft' => false,
                'status' => PermohonanStatus::MENUNGGU_PEMBAYARAN,
                'status_detail' => PermohonanDetailStatus::TAGIHAN_TERBIT,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);

            // --- Log Audit Trail (Comprehensive Events) ---
            $now = now();
            $events = [
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::DITERIMA_PLN->value,
                    'status_detail' => PermohonanDetailStatus::MENUNGGU_VERIFIKASI->value, // Fix null error
                    'occurred_at' => $now->copy()->subSeconds(9),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::VERIFIKASI_SLO->value,
                    'status_detail' => PermohonanDetailStatus::SLO_VALID->value,
                    'occurred_at' => $now->copy()->subSeconds(8),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::VERIFIKASI_SLO->value,
                    'status_detail' => PermohonanDetailStatus::DITERUSKAN_UNIT_SURVEY->value,
                    'occurred_at' => $now->copy()->subSeconds(7),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::SURVEY_LAPANGAN->value,
                    'status_detail' => PermohonanDetailStatus::SURVEY_BARU->value,
                    'occurred_at' => $now->copy()->subSeconds(6),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::SURVEY_LAPANGAN->value,
                    'status_detail' => PermohonanDetailStatus::SURVEY_DIJADWALKAN->value,
                    'occurred_at' => $now->copy()->subSeconds(5),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::SURVEY_LAPANGAN->value,
                    'status_detail' => PermohonanDetailStatus::SURVEY_SELESAI->value,
                    'occurred_at' => $now->copy()->subSeconds(4),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::PERENCANAAN_MATERIAL->value,
                    'status_detail' => PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL->value,
                    'occurred_at' => $now->copy()->subSeconds(3),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::PERENCANAAN_MATERIAL->value,
                    'status_detail' => PermohonanDetailStatus::MATERIAL_TERSEDIA->value,
                    'occurred_at' => $now->copy()->subSeconds(2),
                    'created_at' => $now, 'updated_at' => $now,
                ],
                [
                    'service_request_id' => $this->id,
                    'status' => PermohonanStatus::MENUNGGU_PEMBAYARAN->value,
                    'status_detail' => PermohonanDetailStatus::TAGIHAN_TERBIT->value,
                    'occurred_at' => $now,
                    'created_at' => $now, 'updated_at' => $now,
                ],
            ];

            ServiceRequestEvent::insert($events);
        });
    }

    /**
     * Ensure a ServiceRequest has at least one event record for timeline display.
     */
    public function ensureInitialEvent(): void
    {
        if ($this->events()->count() > 0) {
            return;
        }

        $status = $this->status ?? PermohonanStatus::DRAFT;
        $detail = $this->status_detail;
        $occurredAt = $this->created_at ?? now();

        // If it was submitted, occurred_at should ideally be submitted_at
        if (!$this->is_draft && $this->submitted_at) {
            $occurredAt = $this->submitted_at;
        }

        ServiceRequestEvent::create([
            'service_request_id' => $this->id,
            'status' => $status,
            'status_detail' => $detail,
            'occurred_at' => $occurredAt,
        ]);
    }
}

