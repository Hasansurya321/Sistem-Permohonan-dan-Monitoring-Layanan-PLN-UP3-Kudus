<?php

namespace App\Console\Commands;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvent;
use Illuminate\Console\Command;

class BackfillServiceRequestEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-sr-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing events for all service requests to ensure timeline consistency.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requests = ServiceRequest::whereDoesntHave('events')->get();

        if ($requests->isEmpty()) {
            $this->info('No service requests found missing events.');
            return;
        }

        $this->info("Found {$requests->count()} requests missing events. Starting backfill...");

        $bar = $this->output->createProgressBar($requests->count());
        $bar->start();

        foreach ($requests as $req) {
            $req->ensureInitialEvent();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completed successfully.');
    }
}
