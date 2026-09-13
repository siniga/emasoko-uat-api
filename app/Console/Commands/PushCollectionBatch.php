<?php

namespace App\Console\Commands;

use App\Jobs\PushCollectionJob;
use App\Models\CollectionBatch;
use Illuminate\Console\Command;

class PushCollectionBatch extends Command
{
    protected $signature = 'push:batches';

    protected $description = 'Queue ready collection batches for background bank submission';

    public function handle(): int
    {
        $batches = CollectionBatch::query()
            ->where('deleted', 0)
            ->where(function ($query) {
                $query->where('lifecycle_status', CollectionBatch::LIFECYCLE_READY)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('lifecycle_status')->where('pushed', 0);
                    });
            })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->orderBy('collectionReference')
            ->get();

        if ($batches->isEmpty()) {
            $this->info('No collection batches are ready to queue for the current month.');

            return self::SUCCESS;
        }

        foreach ($batches as $batch) {
            PushCollectionJob::dispatch((string) $batch->id);
            $this->line("Queued collection batch {$batch->collectionReference}.");
        }

        $this->info("Queued {$batches->count()} collection batch(es) for background bank submission.");

        return self::SUCCESS;
    }
}
