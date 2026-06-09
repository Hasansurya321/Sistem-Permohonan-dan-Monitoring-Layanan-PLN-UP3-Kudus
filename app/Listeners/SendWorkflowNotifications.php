<?php

namespace App\Listeners;

use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use App\Events\ServiceRequestStatusChanged;
use App\Models\Employee;
use App\Notifications\CustomerWorkflowNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendWorkflowNotifications
{
    /**
     * Handle the ServiceRequestStatusChanged event.
     *
     * Responsibilities:
     * 1. Send customer email notification for key status transitions.
     * 2. (Extensible) Log internal notification intent for audit trail.
     *
     * Note: Filament flash notifications (->send()) are session-based and must
     * be triggered from within a Filament page/livewire context. This listener
     * handles background concerns: email delivery and logging.
     * Filament flash notifs are kept inside the Resource action closures where
     * they are already implemented (e.g. PembayaranResource, ViewVerifikasiSlo).
     */
    public function handle(ServiceRequestStatusChanged $event): void
    {
        $sr        = $event->serviceRequest;
        $newStatus = $event->toStatus;
        $newDetail = $event->toDetail;
        $actor     = $event->actor;
        $note      = $event->note;

        // ─── 1. Customer Email Notification ─────────────────────────────────
        $this->notifyCustomer($sr, $newStatus, $newDetail, $note);

        // ─── 2. Internal Log for Audit Trail ────────────────────────────────
        $this->logInternalTransition($sr, $newStatus, $newDetail, $actor, $note);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function notifyCustomer(
        $sr,
        PermohonanStatus $newStatus,
        ?PermohonanDetailStatus $newDetail,
        ?string $note
    ): void {
        // Only send for statuses that warrant a customer email
        if (! CustomerWorkflowNotification::shouldNotify($newStatus, $newDetail)) {
            return;
        }

        // Only if the service request has an active submitter (pelanggan guard)
        $sr->load('submitter');
        $submitter = $sr->submitter;
        if (! $submitter || ! $submitter->email) {
            return;
        }

        try {
            Notification::send(
                $submitter,
                new CustomerWorkflowNotification($sr, $newStatus, $newDetail, $note)
            );
        } catch (\Throwable $e) {
            // Never let a notification failure break the workflow transaction
            Log::warning('CustomerWorkflowNotification failed to send', [
                'service_request_id' => $sr->id,
                'nomor_permohonan'   => $sr->nomor_permohonan,
                'new_status'         => $newStatus->value,
                'submitter_email'    => $submitter->email,
                'error'              => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log internal transitions for audit / future dashboard notification support.
     * When a proper notifications table is added, this is where sendToDatabase() calls go.
     */
    private function logInternalTransition(
        $sr,
        PermohonanStatus $newStatus,
        ?PermohonanDetailStatus $newDetail,
        array $actor,
        ?string $note
    ): void {
        $targetUnit = $this->resolveTargetUnit($newStatus);

        Log::info('WorkflowNotification: internal transition', [
            'service_request_id' => $sr->id,
            'nomor_permohonan'   => $sr->nomor_permohonan,
            'new_status'         => $newStatus->value,
            'new_detail'         => $newDetail?->value,
            'target_unit'        => $targetUnit,
            'triggered_by_role'  => $actor['role'] ?? 'unknown',
            'triggered_by_name'  => $actor['name'] ?? 'unknown',
            'note'               => $note,
        ]);
    }

    /**
     * Map status → which internal unit should be notified next.
     */
    private function resolveTargetUnit(PermohonanStatus $status): ?string
    {
        return match ($status) {
            PermohonanStatus::VERIFIKASI_DATA        => 'admin_pelayanan',
            PermohonanStatus::UNIT_SURVEY            => 'unit_survey',
            PermohonanStatus::UNIT_PERENCANAAN       => 'unit_perencanaan',
            PermohonanStatus::PEMBAYARAN             => 'admin_pelayanan',
            PermohonanStatus::UNIT_KONSTRUKSI        => 'unit_konstruksi',
            PermohonanStatus::UNIT_PENYALAAN         => 'unit_te',
            PermohonanStatus::SELESAI                => 'admin_pelayanan',
            default                                  => null,
        };
    }
}
