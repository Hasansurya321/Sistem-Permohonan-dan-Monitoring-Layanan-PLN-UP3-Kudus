<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceRequest;
use App\Enums\PermohonanStatus;
use App\Enums\PermohonanDetailStatus;
use Illuminate\Support\Facades\Log;

class BackfillPaymentStatus extends Command
{
    protected $signature = 'payment:backfill-status {--dry-run : Preview changes without applying}';
    protected $description = 'Backfill status_detail for old payment attempts (MENUNGGU_PEMBAYARAN → PEMBAYARAN_PENDING)';

    public function handle(): int
    {
        $this->info('=== Backfill Payment Status Detail ===');
        
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be applied');
        }

        // Find all requests with payment_attempt_count > 0 but status_detail = MENUNGGU_PEMBAYARAN
        $query = ServiceRequest::where('status', PermohonanStatus::PEMBAYARAN)
            ->where('status_detail', PermohonanDetailStatus::MENUNGGU_PEMBAYARAN)
            ->where(function ($q) {
                $q->where('payment_attempt_count', '>', 0)
                  ->orWhereNotNull('cancelled_at');
            });

        $affectedRequests = $query->get();

        $this->info("Found {$affectedRequests->count()} request(s) to backfill");

        if ($affectedRequests->count() === 0) {
            $this->info('No records need backfill. All good!');
            return Command::SUCCESS;
        }

        // Show preview
        $this->table(
            ['ID', 'Nomor Permohonan', 'Attempt Count', 'Cancelled At', 'Status Detail (Before → After)'],
            $affectedRequests->map(fn ($sr) => [
                $sr->id,
                $sr->nomor_permohonan ?? '-',
                $sr->payment_attempt_count ?? 0,
                $sr->cancelled_at ? $sr->cancelled_at->format('Y-m-d H:i:s') : '-',
                $sr->status_detail->value . ' → PEMBAYARAN_PENDING',
            ])
        );

        if ($dryRun) {
            $this->info('Dry run complete. Run without --dry-run to apply changes.');
            return Command::SUCCESS;
        }

        // Confirm before applying
        if (!$this->confirm('Do you want to update ' . $affectedRequests->count() . ' record(s)?', true)) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        // Apply updates
        $updated = 0;
        $bar = $this->output->createProgressBar($affectedRequests->count());

        foreach ($affectedRequests as $sr) {
            $oldDetail = $sr->status_detail->value;
            
            $sr->update([
                'status_detail' => PermohonanDetailStatus::PEMBAYARAN_PENDING,
            ]);

            // Also record event for timeline
            if ($sr->payment_attempt_count > 0) {
                $sr->recordEventIfMissing(
                    PermohonanStatus::PEMBAYARAN,
                    PermohonanDetailStatus::PEMBAYARAN_PENDING,
                    now(),
                    "Status di-backfill dari {$oldDetail} ke PEMBAYARAN_PENDING (sync data lama).",
                    'System',
                    'system'
                );
            }

            Log::info('Payment status backfilled', [
                'service_request_id' => $sr->id,
                'old_status_detail' => $oldDetail,
                'new_status_detail' => 'PEMBAYARAN_PENDING',
            ]);

            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully updated {$updated} record(s).");
        $this->info('Done!');

        return Command::SUCCESS;
    }
}