<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use App\Enums\PaymentFailureReason;
use App\Events\ServiceRequestStatusChanged;
use App\Services\DummyTambahDayaBillingService;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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
        'payment_attempt_count' => 'integer',
        'cancelled_by' => 'string',
        'failure_reason' => PaymentFailureReason::class,
        'revision_count' => 'integer',
        'last_revision_at' => 'datetime',
    ];

    public function canRetryPayment(): bool
    {
        if ($this->status !== PermohonanStatus::PEMBAYARAN) {
            return false;
        }

        // Masih dalam proses pembayaran, attempt < 3, dan belum dibatalkan
        return $this->payment_attempt_count < 3
            && $this->cancelled_at === null;
    }

    public function getRemainingPaymentAttempts(): int
    {
        return max(0, 3 - ($this->payment_attempt_count ?? 0));
    }

    public function incrementPaymentAttempt(?PaymentFailureReason $failureReason = null): void
    {
        $this->increment('payment_attempt_count');
        $this->refresh();

        // Catat setiap percobaan gagal sebagai event unik
        $attemptNum = $this->payment_attempt_count;
        $reasonLabel = $failureReason?->getLabel() ?? 'Pembayaran gagal';
        
        // 🔴 FIX: Update status_detail dari MENUNGGU_PEMBAYARAN → PEMBAYARAN_PENDING
        // Ini akan memindahkan request dari filter "Menunggu" ke "Pending"
        // sehingga tidak muncul di 2 filter sekaligus
        if ($this->status_detail === PermohonanDetailStatus::MENUNGGU_PEMBAYARAN) {
            $this->update(['status_detail' => PermohonanDetailStatus::PEMBAYARAN_PENDING]);
        }
        
        // Record event untuk setiap percobaan dengan note unik
        $this->recordEventIfMissing(
            PermohonanStatus::PEMBAYARAN,
            PermohonanDetailStatus::PEMBAYARAN_PENDING,
            now(),
            "Percobaan pembayaran ke-{$attemptNum} gagal. {$reasonLabel}.",
            'Sistem',
            'system'
        );

        // Jika sudah 3x gagal → jadi gagal final (SELESAI + PERMOHONAN_GAGAL)
        if ($attemptNum >= 3) {
            $this->applyTransition(
                PermohonanStatus::SELESAI,
                PermohonanDetailStatus::PERMOHONAN_GAGAL,
                now(),
                'Pembayaran gagal setelah 3 kali percobaan.'
            );
        }
    }

    public function cancelByCustomer(): void
    {
        $actor = $this->getCurrentActor();
        $userId = $actor['id']; // ID user yang login (web guard)

        $this->update([
            'cancelled_at' => now(),
            'cancelled_by' => $userId, // INT, bukan string 'customer'
            'failure_reason' => PaymentFailureReason::CUSTOMER_CANCELLED,
        ]);

        $this->applyTransition(
            PermohonanStatus::SELESAI,
            PermohonanDetailStatus::PERMOHONAN_GAGAL,
            null,
            'Permohonan dibatalkan oleh pelanggan.'
        );
    }

    public function cancelByAdmin(): void
    {
        $this->update([
            'cancelled_at' => now(),
            'cancelled_by' => 'admin',
            'failure_reason' => PaymentFailureReason::ADMIN_CANCELLED,
        ]);

        $this->applyTransition(
            PermohonanStatus::SELESAI,
            PermohonanDetailStatus::PERMOHONAN_GAGAL,
            null,
            'Permohonan dibatalkan oleh Admin Layanan.'
        );
    }

    public function isPaymentFailedFinal(): bool
    {
        return $this->status === PermohonanStatus::SELESAI
            && $this->status_detail === PermohonanDetailStatus::PEMBAYARAN_GAGAL;
    }

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
        return $query->where(function ($q) {
            $q->where('status', PermohonanStatus::SELESAI)
              ->orWhere(function ($q2) {
                  $q2->where('status', PermohonanStatus::UNIT_PENYALAAN)
                     ->where('status_detail', PermohonanDetailStatus::PENYALAAN_SELESAI)
                     ->whereNotNull('completed_at');
              });
        });
    }

    public function scopeWaiting($query)
    {
        // Menunggu: VERIFIKASI_DATA dengan detail MENUNGGU_VERIFIKASI_DATA
        return $query->where('status', PermohonanStatus::VERIFIKASI_DATA)
            ->where('status_detail', PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA);
    }

    public function scopeProcessing($query)
    {
        // Semua status global yang bukan SELESAI
        return $query->whereIn('status', [
            PermohonanStatus::VERIFIKASI_DATA->value,
            PermohonanStatus::UNIT_SURVEY->value,
            PermohonanStatus::UNIT_PERENCANAAN->value,
            PermohonanStatus::PEMBAYARAN->value,
            PermohonanStatus::UNIT_KONSTRUKSI->value,
            PermohonanStatus::UNIT_PENYALAAN->value,
        ]);
    }

    public function scopeDone($query)
    {
        return $query->where('status', PermohonanStatus::SELESAI);
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->is_draft === true;
    }

    public function isProcessing(): bool
    {
        return $this->status && $this->status->isProcessing();
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null || $this->status === PermohonanStatus::SELESAI;
    }

    protected static array $workflowTransitions = [
        // Admin Layanan
        PermohonanStatus::VERIFIKASI_DATA->value => [
            PermohonanStatus::UNIT_SURVEY,
            PermohonanStatus::SELESAI,
        ],
        PermohonanStatus::UNIT_SURVEY->value => [
            PermohonanStatus::UNIT_PERENCANAAN,
        ],
        PermohonanStatus::UNIT_PERENCANAAN->value => [
            PermohonanStatus::PEMBAYARAN,
        ],
        PermohonanStatus::PEMBAYARAN->value => [
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanStatus::SELESAI,
        ],
        PermohonanStatus::UNIT_KONSTRUKSI->value => [
            PermohonanStatus::UNIT_PENYALAAN,
        ],
        PermohonanStatus::UNIT_PENYALAAN->value => [
            PermohonanStatus::SELESAI,
        ],
        PermohonanStatus::SELESAI->value => [],
    ];

    protected static array $roleAllowedStatuses = [
        'admin_pelayanan' => [
            PermohonanStatus::VERIFIKASI_DATA,
            PermohonanStatus::UNIT_SURVEY,
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanStatus::PEMBAYARAN,
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanStatus::UNIT_PENYALAAN,
        ],
        'unit_survey' => [
            PermohonanStatus::UNIT_SURVEY,
        ],
        'unit_perencanaan' => [
            PermohonanStatus::UNIT_PERENCANAAN,
        ],
        'unit_konstruksi' => [
            PermohonanStatus::UNIT_KONSTRUKSI,
        ],
        'unit_te' => [
            PermohonanStatus::UNIT_PENYALAAN,
            PermohonanStatus::SELESAI,
        ],
        'supervisor' => [
            PermohonanStatus::VERIFIKASI_DATA,
            PermohonanStatus::UNIT_SURVEY,
            PermohonanStatus::UNIT_PERENCANAAN,
            PermohonanStatus::PEMBAYARAN,
            PermohonanStatus::UNIT_KONSTRUKSI,
            PermohonanStatus::UNIT_PENYALAAN,
            PermohonanStatus::SELESAI,
        ],
    ];

    protected function getCurrentActor(): array
    {
        // Employee guard (internal staff) is checked first, so actingAs('employee')
        // always takes priority even if web guard is also authenticated (Laravel's
        // actingAs does not clear other guards in tests).
        $employee = Auth::guard('employee')->user();
        if ($employee) {
            return [
                'guard' => 'employee',
                'id' => $employee->id,
                'role' => $employee->role,
                'name' => $employee->name,
            ];
        }

        $webUser = Auth::guard('web')->user();
        if ($webUser) {
            return [
                'guard' => 'web',
                'id' => $webUser->id,
                'role' => $webUser->role,
                'name' => $webUser->name,
            ];
        }

        return [
            'guard' => null,
            'id' => null,
            'role' => null,
            'name' => null,
        ];
    }

    protected function ensureActorCanTransition(
        PermohonanStatus $targetStatus,
        ?PermohonanDetailStatus $detail,
        bool $asSystem = false
    ): void {
        // SYSTEM TRANSITION — trusted, bypass authorization guard
        if ($asSystem) {
            return;
        }

        $actor = $this->getCurrentActor();

        // Single guard active: proceed normally
        if ($actor['guard'] === 'web') {
            $this->ensureWebGuardTransition($targetStatus, $detail);
            return;
        }

        if ($actor['guard'] === 'employee') {
            $this->ensureEmployeeGuardTransition($targetStatus, $detail);
            return;
        }

        // No guard authenticated
        throw new AuthorizationException('Aktor tidak ditemukan atau tidak diizinkan melakukan perubahan status.');
    }

    private function ensureWebGuardTransition(PermohonanStatus $targetStatus, ?PermohonanDetailStatus $detail): void
    {
        $currentStatus = $this->status;

        // Whitelist transisi yang diperbolehkan untuk pelanggan (web guard)
        $allowed = match(true) {
            // 1. Submit permohonan baru: DRAFT/null → VERIFIKASI_DATA + MENUNGGU_VERIFIKASI_DATA
            ($currentStatus === null || $this->isDraft())
                && $targetStatus === PermohonanStatus::VERIFIKASI_DATA
                && $detail === PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA
                => true,

            // 2. Generate QR / Bayar / Retry: PEMBAYARAN → PEMBAYARAN + PEMBAYARAN_PENDING
            $currentStatus === PermohonanStatus::PEMBAYARAN
                && $targetStatus === PermohonanStatus::PEMBAYARAN
                && $detail === PermohonanDetailStatus::PEMBAYARAN_PENDING
                => true,

            // 3. Batalkan permohonan: PEMBAYARAN → SELESAI + PERMOHONAN_GAGAL
            $currentStatus === PermohonanStatus::PEMBAYARAN
                && $targetStatus === PermohonanStatus::SELESAI
                && $detail === PermohonanDetailStatus::PERMOHONAN_GAGAL
                => true,

            default => false,
        };

        if (!$allowed) {
            throw new AuthorizationException('Anda tidak diizinkan mengubah status ini.');
        }
    }

    private function ensureEmployeeGuardTransition(PermohonanStatus $targetStatus, ?PermohonanDetailStatus $detail): void
    {
        // Dual-guard detection: both guards may be active in tests.
        // Check if web guard (pelanggan) is also authenticated.
        $webUser = Auth::guard('web')->user();

        $employeeUser = Auth::guard('employee')->user();
        $role = $employeeUser?->role;

        if ($role === 'supervisor') {
            return;
        }

        if ($targetStatus === PermohonanStatus::SELESAI && $this->status === PermohonanStatus::VERIFIKASI_DATA) {
            return;
        }

        if (!isset(self::$roleAllowedStatuses[$role])
            || !in_array($this->status, self::$roleAllowedStatuses[$role], true)
        ) {
            \Illuminate\Support\Facades\Log::error('Authorization denied in ensureActorCanTransition', [
                'role' => $role,
                'current_status' => $this->status?->value,
                'target_status' => $targetStatus?->value,
                'target_detail' => $detail?->value,
                'service_request_id' => $this->id,
            ]);
            throw new AuthorizationException('Peran Anda tidak diizinkan mengubah status ini.');
        }
    }

    protected function isTransitionAllowed(PermohonanStatus $targetStatus, ?PermohonanDetailStatus $detail): bool
    {
        $actor = $this->getCurrentActor();

        // Supervisor can bypass workflow graph
        if ($actor['guard'] === 'employee' && $actor['role'] === 'supervisor') {
            return true;
        }

        if ($this->status === $targetStatus) {
            return $detail === null || $detail->value !== $this->status_detail?->value;
        }

        return in_array($targetStatus, self::$workflowTransitions[$this->status->value] ?? [], true);
    }

    /**
     * Transisi sistem (gateway callback, QR simulator, scheduler, dll).
     * Tetap melalui state machine yang sama — hanya authorization guard yang dilewati.
     */
    public function transitionToSystem(
        PermohonanStatus|string $status,
        PermohonanDetailStatus|string|null $detail = null,
        ?string $note = null
    ): void {
        $this->transitionTo(
            $status,
            $detail,
            null,
            $note,
            true // asSystem = bypass authorization
        );
    }

    public function transitionTo(
        PermohonanStatus|string $status,
        PermohonanDetailStatus|string|null $detail = null,
        \Carbon\CarbonInterface|string|null $occurredAt = null,
        ?string $note = null,
        bool $asSystem = false
    ): void {
        $statusEnum = $status instanceof PermohonanStatus
            ? $status
            : PermohonanStatus::from($status);

        $detailEnum = is_null($detail)
            ? null
            : ($detail instanceof PermohonanDetailStatus
                ? $detail
                : PermohonanDetailStatus::tryFrom((string)$detail));

        if ($detailEnum && !in_array($detailEnum, $statusEnum->allowedDetails(), true)) {
            throw new \DomainException("Invalid status detail '{$detailEnum->value}' for status '{$statusEnum->value}'");
        }

        // Check workflow rules BEFORE authorization, so invalid transitions
        // throw DomainException (business rule) instead of AuthorizationException.
        if (!$this->isTransitionAllowed($statusEnum, $detailEnum)) {
            throw new \DomainException(sprintf(
                'Transition from %s to %s is not allowed.',
                $this->status->value,
                $statusEnum->value
            ));
        }

        $this->ensureActorCanTransition($statusEnum, $detailEnum, $asSystem);

        try {
            DB::transaction(function () use ($statusEnum, $detailEnum, $occurredAt, $note) {
                $this->applyTransition($statusEnum, $detailEnum, $occurredAt, $note);
            });
        } catch (\Throwable $e) {
            $actor = $this->getCurrentActor();
            Log::error('ServiceRequest transition failed', [
                'service_request_id' => $this->id,
                'current_status' => $this->status?->value,
                'current_status_detail' => $this->status_detail?->value,
                'target_status' => $statusEnum->value,
                'target_status_detail' => $detailEnum?->value,
                'actor' => $actor,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function applyTransition(
        PermohonanStatus $statusEnum,
        ?PermohonanDetailStatus $detailEnum = null,
        \Carbon\CarbonInterface|string|null $occurredAt = null,
        ?string $note = null
    ): void {
        if ($this->eventExists($statusEnum, $detailEnum)) {
            return;
        }

        $timestamp = $occurredAt
            ? Carbon::parse($occurredAt)
            : now();

        $actor = $this->getCurrentActor();

        $data = [
            'status' => $statusEnum,
            'status_detail' => $detailEnum,
            'status_changed_at' => $timestamp,
            'status_changed_by' => $actor['id'],
        ];

        if ($statusEnum === PermohonanStatus::SELESAI && !$this->completed_at) {
            $data['completed_at'] = $timestamp;
        }

        $this->update($data);

        $this->recordEventIfMissing($statusEnum, $detailEnum, $timestamp, $note, $actor['name'], $actor['role']);

        $originalStatus = $this->getOriginal('status');
        $originalDetail = $this->getOriginal('status_detail');

        event(new ServiceRequestStatusChanged(
            $this,
            $originalStatus ? PermohonanStatus::from($originalStatus instanceof PermohonanStatus ? $originalStatus->value : $originalStatus) : null,
            $originalDetail ? PermohonanDetailStatus::tryFrom($originalDetail instanceof PermohonanDetailStatus ? $originalDetail->value : $originalDetail) : null,
            $statusEnum,
            $detailEnum,
            $actor,
            $note
        ));

        Log::info('ServiceRequest status changed', [
            'service_request_id' => $this->id,
            'from_status' => $originalStatus instanceof PermohonanStatus ? $originalStatus->value : $originalStatus,
            'to_status' => $statusEnum->value,
            'actor' => $actor,
            'note' => $note,
        ]);
    }

    private function recordEventIfMissing(
        PermohonanStatus|string $status,
        PermohonanDetailStatus|string|null $detail = null,
        \Carbon\CarbonInterface|string|null $occurredAt = null,
        ?string $note = null,
        ?string $performedBy = null,
        ?string $performedByRole = null
    ): void {
        $statusValue = $status instanceof PermohonanStatus ? $status->value : (string) $status;
        $detailValue = $detail instanceof PermohonanDetailStatus ? $detail->value : $detail;

        if ($this->eventExists($status, $detail)) {
            return;
        }

        ServiceRequestEvent::create([
            'service_request_id' => $this->id,
            'status' => $statusValue,
            'status_detail' => $detailValue,
            'title' => $performedBy ? sprintf('%s (%s)', $performedBy, $performedByRole ?? '-') : null,
            'description' => $note,
            'updated_by_name' => $performedBy,
            'updated_by_role' => $performedByRole,
            'note' => $note,
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : now(),
        ]);
    }

    private function eventExists(
        PermohonanStatus|string $status,
        PermohonanDetailStatus|string|null $detail = null
    ): bool {
        $statusValue = $status instanceof PermohonanStatus ? $status->value : (string) $status;
        $detailValue = $detail instanceof PermohonanDetailStatus ? $detail->value : $detail;

        // ✅ VERIFIKASI DUPLIKASI — Gunakan fresh query dari DB, bukan dari relasi cache
        $exists = self::join('service_request_events as ev', 'ev.service_request_id', '=', 'service_requests.id')
            ->where('service_requests.id', $this->id)
            ->where('ev.status', $statusValue)
            ->when(
                $detailValue === null,
                fn ($query) => $query->whereNull('ev.status_detail'),
                fn ($query) => $query->where('ev.status_detail', $detailValue)
            )
            ->exists();

        return $exists;
    }

    /**
     * SINKRONISASI DATA PEMOHON KE applicant_identities
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
     * Auto-advance after SLO verification (simplified for new workflow).
     * Transition from VERIFIKASI_DATA -> UNIT_SURVEY with DITERIMA_UNIT_SURVEY.
     */
    public function finalizeSloVerificationAndAutoAdvance(): void
    {
        try {
            DB::transaction(function () {
                $draftNo = (string) $this->nomor_permohonan;

                preg_match_all('/\d+/', $draftNo, $matches);
                $digits = !empty($matches[0]) ? end($matches[0]) : null;

                if (!$digits) {
                    throw new \Exception('Nomor draft tidak valid (tidak ada angka).');
                }

                $officialNo = 'PLN-UP3KUDUS-' . $digits;

                $exists = self::where('nomor_permohonan', $officialNo)
                    ->where('id', '!=', $this->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception('Nomor resmi ' . $officialNo . ' sudah digunakan oleh permohonan lain.');
                }

                $this->update([
                    'nomor_permohonan' => $officialNo,
                    'is_draft' => false,
                ]);

                $baseTime = now()->subSeconds(8);

                // VERIFIKASI_DATA -> VERIFIKASI_DATA_SUKSES -> ADMINISTRASI_SELESAI -> UNIT_SURVEY
                $steps = [
                    [PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES],
                    [PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::ADMINISTRASI_SELESAI],
                    [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY],
                    [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_DIJADWALKAN],
                    [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_LAPANGAN],
                    [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_SUKSES],
                    [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_SELESAI],
                    [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::DITERIMA_UNIT_PERENCANAAN],
                    [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL],
                    [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::MATERIAL_TERSEDIA],
                    [PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::TAGIHAN_TERBIT],
                ];

                foreach ($steps as $index => [$status, $detail]) {
                    $this->applyTransition($status, $detail, $baseTime->copy()->addSeconds($index));
                }
            });
        } catch (\Throwable $e) {
            Log::error('ServiceRequest auto-advance failed', [
                'service_request_id' => $this->id,
                'current_status' => $this->status?->value,
                'current_status_detail' => $this->status_detail?->value,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ensure a ServiceRequest has at least one event record for timeline display.
     */
    public function ensureInitialEvent(): void
    {
        if ($this->events()->count() > 0) {
            return;
        }

        $status = $this->status ?? PermohonanStatus::VERIFIKASI_DATA;
        $detail = $this->status_detail;
        $occurredAt = $this->created_at ?? now();

        if (!$this->is_draft && $this->submitted_at) {
            $occurredAt = $this->submitted_at;
        }

        $this->recordEventIfMissing($status, $detail, $occurredAt);
    }

    // ──────────────────────────────────────────────
    //  ADMIN LAYANAN — SCOPES (Tambah Daya)
    // ──────────────────────────────────────────────

    /**
     * Tab "Menunggu" — antrian kerja Admin Layanan.
     * Permohonan baru + hasil revisi pelanggan yang belum diverifikasi.
     */
    public function scopeWaitingForAdmin($query)
    {
        return $query->where('status', PermohonanStatus::VERIFIKASI_DATA)
            ->where('status_detail', PermohonanDetailStatus::MENUNGGU_VERIFIKASI_DATA);
    }

    /**
     * Tab "Pending" — permohonan dikembalikan ke pelanggan.
     */
    public function scopePendingRevision($query)
    {
        return $query->where('status', PermohonanStatus::VERIFIKASI_DATA)
            ->where('status_detail', PermohonanDetailStatus::DIKEMBALIKAN_DENGAN_REVISI);
    }

    /**
     * Tab "Selesai — Sukses" — administrasi selesai, diterima PLN.
     * Setelah auto-advance, status berubah menjadi PEMBAYARAN + TAGIHAN_TERBIT
     * lalu PEMBAYARAN + MENUNGGU_PEMBAYARAN.
     * Untuk kompatibilitas, juga tetap cek VERIFIKASI_DATA + ADMINISTRASI_SELESAI (jika ada data lama).
     */
    public function scopeAdminSuccess($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($q2) {
                // Data baru step final: setelah auto-advance (PEMBAYARAN + MENUNGGU_PEMBAYARAN)
                $q2->where('status', PermohonanStatus::PEMBAYARAN)
                    ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN);
            })->orWhere(function ($q2) {
                // Data baru step sebelum final: PEMBAYARAN + TAGIHAN_TERBIT
                $q2->where('status', PermohonanStatus::PEMBAYARAN)
                    ->where('status_detail', PermohonanDetailStatus::TAGIHAN_TERBIT);
            })->orWhere(function ($q2) {
                // 🔴 FIX: Setelah pembayaran sukses (QRIS) - auto-forward ke UNIT_KONSTRUKSI
                $q2->where('status', PermohonanStatus::PEMBAYARAN)
                    ->where('status_detail', PermohonanDetailStatus::PEMBAYARAN_SUKSES);
            })->orWhere(function ($q2) {
                // 🔴 FIX: Hasil auto-forward dari pembayaran sukses - UNIT_KONSTRUKSI
                $q2->where('status', PermohonanStatus::UNIT_KONSTRUKSI)
                    ->whereIn('status_detail', [
                        PermohonanDetailStatus::DITERIMA_UNIT_KONSTRUKSI,
                        PermohonanDetailStatus::KONSTRUKSI_DIJADWALKAN,
                        PermohonanDetailStatus::PEMBANGUNAN_JARINGAN,
                        PermohonanDetailStatus::KONSTRUKSI_BERHASIL,
                        PermohonanDetailStatus::KONSTRUKSI_SELESAI,
                    ]);
            })->orWhere(function ($q2) {
                // 🔴 FIX: Hasil auto-forward dari pembayaran sukses - UNIT_PENYALAAN
                $q2->where('status', PermohonanStatus::UNIT_PENYALAAN)
                    ->whereIn('status_detail', [
                        PermohonanDetailStatus::DITERIMA_UNIT_PENYALAAN,
                        PermohonanDetailStatus::PENYALAAN_DIJADWALKAN,
                        PermohonanDetailStatus::PENYALAAN_BERHASIL,
                        PermohonanDetailStatus::PENYALAAN_SELESAI,
                    ]);
            })->orWhere(function ($q2) {
                // Data lama: masih VERIFIKASI_DATA + ADMINISTRASI_SELESAI (belum auto-advance)
                $q2->where('status', PermohonanStatus::VERIFIKASI_DATA)
                    ->where('status_detail', PermohonanDetailStatus::ADMINISTRASI_SELESAI);
            })->orWhere(function ($q2) {
                // ✅ Status akhir setelah pembayaran sukses: SELESAI + CLOSE
                $q2->where('status', PermohonanStatus::SELESAI)
                    ->where('status_detail', PermohonanDetailStatus::CLOSE);
            });
        });
    }

    /**
     * Tab "Selesai — Gagal" — ditolak setelah batas revisi.
     * Status: SELESAI + ADMINISTRASI_SELESAI
     */
    public function scopeAdminFailed($query)
    {
        return $query->where('status', PermohonanStatus::SELESAI)
            ->where('status_detail', PermohonanDetailStatus::ADMINISTRASI_SELESAI);
    }

    // ──────────────────────────────────────────────
    //  ADMIN LAYANAN — ACTIONS (Tambah Daya)
    // ──────────────────────────────────────────────

    /**
     * Konversi nomor draft ke nomor resmi.
     * - Tambah Daya: DRF-2026-001 → TD-2026-001
     * - Pasang Baru: DRF-2026-001 → PB-2026-001
     */
    public function convertDraftToOfficialNumber(): string
    {
        $draftNo = (string) $this->nomor_permohonan;

        preg_match_all('/\d+/', $draftNo, $matches);
        $digits = !empty($matches[0]) ? end($matches[0]) : null;

        $prefix = match($this->jenis_layanan) {
            'TAMBAH_DAYA' => 'TD',
            'PASANG_BARU' => 'PB',
            default => 'PLN',
        };

        $currentYear = now()->format('Y');
        $officialNo = $prefix . '-' . $currentYear . '-' . $digits;

        // Pastikan nomor resmi belum digunakan
        $exists = self::where('nomor_permohonan', $officialNo)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($exists) {
            // Jika sudah ada, tambahkan suffix unik
            $officialNo = $prefix . '-' . $currentYear . '-' . $digits . '-' . $this->id;
        }

        return $officialNo;
    }

    /**
     * Terima permohonan (Admin ACC) — Auto-advance penuh sampai Tagihan Terbit.
     *
     * Karena dashboard Unit Survey dan Unit Perencanaan tidak dibangun,
     * seluruh tahapan berikut dijalankan OTOMATIS oleh sistem saat Admin ACC:
     *
     * VERIFIKASI_DATA + VERIFIKASI_DATA_SUKSES
     * VERIFIKASI_DATA + ADMINISTRASI_SELESAI
     * UNIT_SURVEY + SURVEY_DIJADWALKAN ... SURVEY_SELESAI
     * UNIT_PERENCANAAN + DITERIMA_UNIT_PERENCANAAN ... PERENCANAAN_SELESAI
     * PEMBAYARAN + TAGIHAN_TERBIT (generate billing)
     */
    public function adminAccept(?string $note = null): void
    {
        DB::transaction(function () use ($note) {
            // 🔴 GUARD: Cegah duplikasi Payment aktif untuk permohonan yang sama.
            // Melindungi dari: double click, refresh, race condition, retry request,
            // trigger ulang method, human error admin, atau penyebab lainnya.
            // 1 Permohonan = 1 Tagihan Aktif (PENDING).
            if ($this->payments()->where('status', 'PENDING')->exists()) {
                throw new \DomainException(
                    'Tagihan untuk permohonan ini sudah aktif. Tidak dapat membuat tagihan ganda.'
                );
            }

            $now = now();

            // 1. Konversi nomor draft ke nomor resmi
            $officialNo = $this->convertDraftToOfficialNumber();
            $this->update(['nomor_permohonan' => $officialNo]);

            // 2. Auto-advance seluruh tahapan (sequential) — 15 step
            $steps = [
                // Admin Layanan
                [PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::VERIFIKASI_DATA_SUKSES, 'Verifikasi data berhasil, data sesuai.'],
                [PermohonanStatus::VERIFIKASI_DATA, PermohonanDetailStatus::ADMINISTRASI_SELESAI, 'Administrasi selesai, permohonan diterima PLN.'],
                // Unit Survey (otomatis) — dengan DITERIMA sebagai penanda awal
                [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::DITERIMA_UNIT_SURVEY, 'Permohonan diterima oleh Unit Survey.'],
                [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_DIJADWALKAN, 'Survey dijadwalkan oleh sistem.'],
                [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_LAPANGAN, 'Survey lapangan dilaksanakan.'],
                [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_SUKSES, 'Survey lapangan berhasil.'],
                [PermohonanStatus::UNIT_SURVEY, PermohonanDetailStatus::SURVEY_SELESAI, 'Survey selesai, data diteruskan ke perencanaan.'],
                // Unit Perencanaan & Material (otomatis) — lengkap 5 step
                [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::DITERIMA_UNIT_PERENCANAAN, 'Diterima Unit Perencanaan.'],
                [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::ANALISA_KEBUTUHAN_MATERIAL, 'Analisa kebutuhan material.'],
                [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::CEK_KETERSEDIAAN_MATERIAL, 'Mengecek ketersediaan material di gudang.'],
                [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::MATERIAL_TERSEDIA, 'Material tersedia.'],
                [PermohonanStatus::UNIT_PERENCANAAN, PermohonanDetailStatus::PERENCANAAN_SELESAI, 'Perencanaan selesai, menuju pembayaran.'],
                // Pembayaran — Tagihan Terbit + Menunggu Pembayaran
                [PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::TAGIHAN_TERBIT, 'Tagihan berhasil diterbitkan.'],
                [PermohonanStatus::PEMBAYARAN, PermohonanDetailStatus::MENUNGGU_PEMBAYARAN, 'Menunggu pembayaran dari pelanggan.'],
            ];

            foreach ($steps as $index => [$status, $detail, $stepNote]) {
                $this->applyTransition(
                    $status,
                    $detail,
                    $now->copy()->addSeconds($index),
                    $stepNote
                );
            }

            // 3. Generate dummy billing berdasarkan data permohonan
            $peruntukan = $this->peruntukan_koneksi;
            $daya = (int) ($this->daya_baru ?? 0);
            $billing = null;

            if ($peruntukan && $daya > 0) {
                $billing = DummyTambahDayaBillingService::generate($peruntukan, $daya);

                // Simpan billing ke payload_json
                $payload = $this->payload_json ?? [];
                $payload['dummy_billing'] = $billing;
                $this->update(['payload_json' => $payload]);
            }

            // 4. Buat record Payment agar tagihan muncul di menu pembayaran pelanggan
            $totalTagihan = $billing ? $billing['total'] : 0;
            \App\Models\Payment::create([
                'service_request_id' => $this->id,
                'amount'             => $totalTagihan,
                'status'             => 'PENDING',
                'transaction_id'     => 'INV-' . $officialNo,
                'expired_at'         => now()->addDays(7),
            ]);
        });
    }

    /**
     * Kirim ulang permohonan ke pelanggan untuk diperbaiki.
     * Workflow: MENUNGGU_VERIFIKASI_DATA → DIKEMBALIKAN_DENGAN_REVISI
     */
    public function adminSendBack(string $note): void
    {
        DB::transaction(function () use ($note) {
            $revisionKe = ($this->revision_count ?? 0) + 1;

            $this->applyTransition(
                PermohonanStatus::VERIFIKASI_DATA,
                PermohonanDetailStatus::DIKEMBALIKAN_DENGAN_REVISI,
                now(),
                $note
            );

            $this->increment('revision_count');
            $this->update([
                'last_revision_note' => $note,
                'last_revision_at'   => now(),
            ]);
            $this->refresh();
        });
    }

    /**
     * Gagalkan permohonan setelah batas revisi.
     * Workflow: MENUNGGU_VERIFIKASI_DATA → DITOLAK → ADMINISTRASI_SELESAI
     * Step 1: DITOLAK — konfirmasi bahwa data ditolak (masih VERIFIKASI_DATA)
     * Step 2: ADMINISTRASI_SELESAI — penutupan sesi admin (status global jadi SELESAI)
     */
    public function adminReject(?string $note = null): void
    {
        DB::transaction(function () use ($note) {
            $now = now();

            // Step 1: MENUNGGU_VERIFIKASI_DATA → DITOLAK (masih VERIFIKASI_DATA)
            $this->applyTransition(
                PermohonanStatus::VERIFIKASI_DATA,
                PermohonanDetailStatus::DITOLAK,
                $now,
                ($note ?? 'Gagal setelah revisi ke-' . ($this->revision_count ?: 0) . '.')
            );

            // Step 2: DITOLAK (VERIFIKASI_DATA) → ADMINISTRASI_SELESAI (SELESAI)
            $this->applyTransition(
                PermohonanStatus::SELESAI,
                PermohonanDetailStatus::ADMINISTRASI_SELESAI,
                $now->copy()->addSecond(),
                'Administrasi selesai, permohonan ditolak PLN.'
            );
        });
    }
}