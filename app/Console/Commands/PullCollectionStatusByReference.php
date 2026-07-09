<?php

namespace App\Console\Commands;

use App\Jobs\CollectionStatusJob;
use App\Models\CollectionBatch;
use Illuminate\Console\Command;

class PullCollectionStatusByReference extends Command
{
    protected $signature = 'collection:status-reference
        {collectionReference? : Collection batch reference to query}
        {--references= : Comma-separated collection batch references}
        {--sync : Run the status job immediately instead of dispatching it to the queue}';

    protected $description = 'Query collection status for one or more collection batch references';

    public function handle(): int
    {
        $references = $this->references();

        if ($references->isEmpty()) {
            $this->error('Pass a collection reference, e.g. php artisan collection:status-reference 17916, or use --references=17916,17832.');

            return self::FAILURE;
        }

        $batches = CollectionBatch::query()
            ->whereIn('collectionReference', $references)
            ->orderBy('collectionReference')
            ->get();

        $foundReferences = $batches->pluck('collectionReference')->map(fn ($reference) => (string) $reference);
        $references->diff($foundReferences)->each(
            fn ($reference) => $this->warn("Collection batch {$reference} was not found.")
        );

        if ($batches->isEmpty()) {
            return self::FAILURE;
        }

        $runSync = (bool) $this->option('sync');

        foreach ($batches as $batch) {
            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_STATUS_PENDING;
            $batch->save();

            $job = new CollectionStatusJob((string) $batch->id);

            if ($runSync) {
                dispatch_sync($job);
                $this->line("Pulled collection status for {$batch->collectionReference}.");
            } else {
                dispatch($job);
                $this->line("Queued collection status pull for {$batch->collectionReference}.");
            }
        }

        $this->info($runSync ? 'Collection status pull completed.' : 'Collection status pull queued.');

        return self::SUCCESS;
    }

    protected function references()
    {
        $argument = (string) ($this->argument('collectionReference') ?? '');
        $option = (string) ($this->option('references') ?? '');

        return collect(explode(',', trim($argument . ',' . $option, ',')))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();
    }
}
