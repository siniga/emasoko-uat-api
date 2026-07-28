<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use App\Services\CollectionBatchService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CreateTrackedFailedCollectionBatches extends Command
{
    private const TRACKED_REMARK = 'Item Can Be Collected And Has Been Tracked Successfully.';

    protected $signature = 'collections:create-tracked-failed-batches
                            {--month= : Collection month number, defaults to current month}
                            {--year= : Collection year, defaults to current year}
                            {--dry-run : Show eligible batches and installments without creating batches}';

    protected $description = 'Create new collection batches from current-month successful batches that contain tracked failed installments';

    public function __construct(protected CollectionBatchService $collectionBatchService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        [$month, $year] = $this->period();
        if (!$month || !$year) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $monthLabel = Carbon::create($year, $month, 1)->format('F Y');

        $this->info("== Creating tracked failed collection batches for {$monthLabel} ==");

        if ($dryRun) {
            $this->warn('Dry run mode: no collection batches will be created.');
        }

        $sourceBatches = $this->sourceBatches($month, $year);

        if ($sourceBatches->isEmpty()) {
            $this->warn('No successful collection batches with tracked failed installments were found.');

            return self::SUCCESS;
        }

        $created = 0;
        $createdReferences = [];
        $totalEligibleInstallments = 0;

        foreach ($sourceBatches as $sourceBatch) {
            $installments = $this->trackedFailedInstallments($sourceBatch);
            $totalEligibleInstallments += $installments->count();

            if ($installments->isEmpty()) {
                $this->line("Batch {$sourceBatch->collectionReference}: no eligible tracked failed installments after loading.");
                continue;
            }

            $this->line("Batch {$sourceBatch->collectionReference}: {$installments->count()} tracked failed installment(s) found.");

            if ($dryRun) {
                continue;
            }

            $collectionDate = Carbon::parse($sourceBatch->collectionDate)->toDateString();

            $batch = $this->collectionBatchService->createRepairBatchFromInstallments(
                $sourceBatch->partner,
                $installments,
                null,
                [
                    'forced_collection_date' => $collectionDate,
                    'batch_collection_date' => $collectionDate,
                    'source_batch_id' => $sourceBatch->id,
                ]
            );

            if (!$batch) {
                $this->warn("No new batch created from {$sourceBatch->collectionReference} after applying batch creation rules.");
                $this->reportSkippedCandidates($sourceBatch);
                continue;
            }

            $created++;
            $createdReferences[] = $batch->collectionReference;
            $this->info("Created batch {$batch->collectionReference} from {$sourceBatch->collectionReference} with {$batch->number_of_collections} collection(s).");

            Log::info('Created tracked failed collection batch.', [
                'source_batch_id' => $sourceBatch->id,
                'source_reference' => $sourceBatch->collectionReference,
                'new_batch_id' => $batch->id,
                'new_reference' => $batch->collectionReference,
                'number_of_collections' => $batch->number_of_collections,
            ]);
        }

        if ($dryRun) {
            $this->info("Dry run complete: {$sourceBatches->count()} source batch(es), {$totalEligibleInstallments} eligible installment(s).");

            return self::SUCCESS;
        }

        $this->info("== {$created} tracked failed collection batch(es) created ==");

        if ($createdReferences) {
            $this->line('Created references: ' . implode(', ', $createdReferences));
        }

        return self::SUCCESS;
    }

    protected function period(): array
    {
        $month = $this->option('month') ?: now()->month;
        $year = $this->option('year') ?: now()->year;

        if (!is_numeric($month) || (int) $month < 1 || (int) $month > 12) {
            $this->error('The --month option must be a number from 1 to 12.');

            return [null, null];
        }

        if (!is_numeric($year) || (int) $year < 2000) {
            $this->error('The --year option must be a valid year.');

            return [null, null];
        }

        return [(int) $month, (int) $year];
    }

    protected function sourceBatches(int $month, int $year): Collection
    {
        return CollectionBatch::query()
            ->with(['partner'])
            ->where('deleted', 0)
            ->where(function ($query) {
                $query->where('lifecycle_status', CollectionBatch::LIFECYCLE_PARTIALLY_PROCESSED);
            })
            ->whereMonth('collectionDate', $month)
            ->whereYear('collectionDate', $year)
            ->whereHas('debits.installment', function ($query) {
                $query->whereIn('status', ['FAILED', 'FUNDSCHECK'])
                    ->where('remarks', self::TRACKED_REMARK);
            })
            ->orderBy('collectionDate')
            ->orderBy('collectionReference')
            ->get();
    }

    protected function trackedFailedInstallments(CollectionBatch $sourceBatch): Collection
    {
        $installmentIds = collect($sourceBatch->installment_id_list)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($installmentIds->isEmpty()) {
            return collect();
        }

        return Installment::query()
            ->with(['debitOrder.customerAccount', 'debitOrder.partner'])
            ->whereIn('id', $installmentIds)
            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
            ->where('remarks', self::TRACKED_REMARK)
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();
    }

    protected function reportSkippedCandidates(CollectionBatch $sourceBatch): void
    {
        $reasons = collect($this->collectionBatchService->candidateSkipReasons());

        if ($reasons->isEmpty()) {
            Log::warning('Tracked failed collection batch creation was blocked without candidate skip details.', [
                'source_batch_id' => $sourceBatch->id,
                'source_reference' => $sourceBatch->collectionReference,
            ]);

            return;
        }

        $this->line('Skipped candidate reasons:');

        $reasons
            ->groupBy('reason')
            ->map(fn ($items) => $items->count())
            ->sortDesc()
            ->each(fn (int $count, string $reason) => $this->line(" - {$reason}: {$count}"));

        Log::warning('Tracked failed collection batch candidates were skipped.', [
            'source_batch_id' => $sourceBatch->id,
            'source_reference' => $sourceBatch->collectionReference,
            'reason_counts' => $reasons->groupBy('reason')->map(fn ($items) => $items->count())->all(),
            'reasons' => $reasons->values()->all(),
        ]);
    }
}
