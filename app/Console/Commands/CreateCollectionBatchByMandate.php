<?php

namespace App\Console\Commands;

use App\Services\CollectionBatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CreateCollectionBatchByMandate extends Command
{
    protected $signature = 'create:batches-by-mandate
                            {references* : Mandate references, separated by spaces or commas}';

    protected $description = 'Create NMB collection batches for selected mandate references';

    public function __construct(protected CollectionBatchService $collectionBatchService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $references = $this->mandateReferences();

        if ($references->isEmpty()) {
            $this->error('Pass at least one mandate reference.');

            return self::FAILURE;
        }

        $this->info('== Creating NMB collection batches by mandate reference ==');

        $result = $this->collectionBatchService->createBatchesByMandateReferences($references);
        $createdBatches = $result['created_batches'];

        foreach ($result['messages'] as $message) {
            $this->warn($message);
        }

        foreach ($createdBatches as $batch) {
            $this->info("Created batch {$batch->collectionReference} with {$batch->number_of_collections} collection(s).");
        }

        if ($createdBatches->isEmpty()) {
            $this->warn('No collection batch was created.');
            $this->reportSkippedCandidates();

            return self::SUCCESS;
        }

        $this->info('Created collection batch' . ($createdBatches->count() > 1 ? 'es' : '') . ': ' . $createdBatches->pluck('collectionReference')->implode(', '));

        return self::SUCCESS;
    }

    private function mandateReferences(): Collection
    {
        return collect($this->argument('references'))
            ->flatMap(fn ($reference) => explode(',', (string) $reference))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();
    }

    private function reportSkippedCandidates(): void
    {
        $reasons = collect($this->collectionBatchService->candidateSkipReasons());

        if ($reasons->isEmpty()) {
            return;
        }

        $this->line('Skipped candidate reasons:');

        $reasons
            ->groupBy('reason')
            ->map(fn ($items) => $items->count())
            ->sortDesc()
            ->each(fn (int $count, string $reason) => $this->line(" - {$reason}: {$count}"));
    }
}
