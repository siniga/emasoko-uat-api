<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Services\CollectionBatchPushService;
use Illuminate\Console\Command;

class PushCollectionBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push collection batches directly without dispatching background jobs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(CollectionBatchPushService $pushService): int
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $collectionBatches = CollectionBatch::where('deleted', 0)
            ->where(function ($query) {
                $query->whereIn('lifecycle_status', [
                    CollectionBatch::LIFECYCLE_READY,
                    CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH,
                ])->orWhere(function ($legacy) {
                    $legacy->whereNull('lifecycle_status')->where('pushed', 0);
                });
            })
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->orderBy('collectionReference')
            ->get();

        if ($collectionBatches->isEmpty()) {
            $this->info('No collection batches are ready to push for the current month.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($collectionBatches as $batch) {
            $this->line("Pushing collection batch {$batch->collectionReference}...");

            $result = $pushService->push($batch);
            $message = $result['message'] ?? 'No response message.';
            $responseCode = $result['response_code'] ?? 'N/A';
            $correlationId = $result['correlation_id'] ?? 'N/A';

            if ($result['success'] ?? false) {
                $this->info("Pushed {$batch->collectionReference}. Response: {$responseCode}. Correlation: {$correlationId}. {$message}");
            } else {
                $failed++;
                $this->warn("Failed {$batch->collectionReference}. Response: {$responseCode}. Correlation: {$correlationId}. {$message}");
            }

            sleep(5);
        }

        if ($failed > 0) {
            $this->warn("Collection batch push completed with {$failed} failure(s).");

            return self::FAILURE;
        }

        $this->info('Collection batch push completed successfully.');

        return self::SUCCESS;
    }
}
