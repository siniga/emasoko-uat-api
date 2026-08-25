<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Services\CollectionBatchPushService;
use Illuminate\Console\Command;

class PushCollectionBatchByReference extends Command
{
    protected $signature = 'push:batches-reference
        {collectionReference? : Collection batch reference to push immediately}
        {--references= : Comma-separated collection batch references to push immediately}';

    protected $description = 'Push collection batches by collection reference without dispatching background jobs';

    public function handle(CollectionBatchPushService $pushService): int
    {
        $references = $this->references();

        if ($references->isEmpty()) {
            $this->error('Pass a collection reference, e.g. php artisan push:batches-reference 17916, or use --references=17916,17832.');

            return self::FAILURE;
        }

        $batches = CollectionBatch::query()
            ->where('deleted', 0)
            ->whereIn('collectionReference', $references)
            ->orderBy('collectionReference')
            ->get();

        $foundReferences = $batches->pluck('collectionReference')->map(fn ($reference) => (string) $reference);
        $references->diff($foundReferences)->each(
            fn ($reference) => $this->warn("Collection batch {$reference} was not found or is archived.")
        );

        if ($batches->isEmpty()) {
            return self::FAILURE;
        }

        $failed = 0;

        foreach ($batches as $batch) {
            $this->line("Pushing collection batch {$batch->collectionReference}...");

            $result = $pushService->push($batch);
            $message = $result['message'] ?? 'No response message.';
            $responseCode = $result['response_code'] ?? 'N/A';
            $correlationId = $result['correlation_id'] ?? 'N/A';

            if ($result['success'] ?? false) {
                $this->info("Pushed {$batch->collectionReference}. Response: {$responseCode}. Correlation: {$correlationId}. {$message}");

                continue;
            }

            $failed++;
            $this->warn("Failed {$batch->collectionReference}. Response: {$responseCode}. Correlation: {$correlationId}. {$message}");
        }

        if ($failed > 0) {
            $this->warn("Collection batch push completed with {$failed} failure(s).");

            return self::FAILURE;
        }

        $this->info('Collection batch push completed successfully.');

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
