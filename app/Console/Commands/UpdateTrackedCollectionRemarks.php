<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTrackedCollectionRemarks extends Command
{
    private const OLD_REMARK = 'Item Has Previously Been Collected And Cannot Be Processed More Than Once.';

    private const NEW_REMARK = 'Item Can Be Collected And Has Been Tracked Successfully.';

    protected $signature = 'collections:update-tracked-remarks
        {--references= : Comma-separated collection batch references}
        {--dry-run : Report matching installments without updating them}';

    protected $description = 'Update the duplicate-collection remark for installments belonging to specified collection batches';

    public function handle(): int
    {
        $references = collect(explode(',', (string) $this->option('references')))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();

        if ($references->isEmpty()) {
            $this->error('Pass batch references using --references=17916,17832.');

            return self::FAILURE;
        }

        $batches = CollectionBatch::query()
            ->whereIn('collectionReference', $references)
            ->get();

        $foundReferences = $batches->pluck('collectionReference')->map(fn ($reference) => (string) $reference);
        $references->diff($foundReferences)->each(
            fn ($reference) => $this->warn("Collection batch {$reference} was not found.")
        );

        if ($batches->isEmpty()) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $totalMatched = 0;
        $totalUpdated = 0;
        $processedInstallmentIds = [];

        foreach ($batches as $batch) {
            $installmentIds = collect($batch->installment_id_list)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->reject(fn ($id) => isset($processedInstallmentIds[$id]))
                ->values();

            $installmentIds->each(function (int $id) use (&$processedInstallmentIds) {
                $processedInstallmentIds[$id] = true;
            });

            $matchingQuery = Installment::query()
                ->whereIn('id', $installmentIds)
                ->where('remarks', self::OLD_REMARK);

            $matched = $matchingQuery->count();
            $updated = 0;

            if (!$dryRun && $matched > 0) {
                $updated = DB::transaction(fn () => Installment::query()
                    ->whereIn('id', $installmentIds)
                    ->where('remarks', self::OLD_REMARK)
                    ->update([
                        'remarks' => self::NEW_REMARK,
                        'updated_at' => now(),
                    ]));
            }

            $totalMatched += $matched;
            $totalUpdated += $updated;

            $action = $dryRun ? 'matched' : 'updated';
            $count = $dryRun ? $matched : $updated;
            $this->line("Batch {$batch->collectionReference}: {$count} installment(s) {$action}.");
        }

        if ($dryRun) {
            $this->info("Dry run complete: {$totalMatched} installment(s) would be updated.");
        } else {
            $this->info("Completed: {$totalUpdated} installment(s) updated.");
        }

        return self::SUCCESS;
    }
}
