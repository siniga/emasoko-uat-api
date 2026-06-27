<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Models\SmsLog;
use App\Models\CollectionBatch;
use App\Models\Installment;
use App\Services\CollectionBatchService;
use App\Services\MessagingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateFailedBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:failed-batches {--references= : Comma-separated current-month collection references to repair}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Format and create repaired current-month batches from failed bank-side collection batches';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected CollectionBatchService $collectionBatchService,
        protected MessagingService $messagingService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $references = collect(explode(',', (string) $this->option('references')))
            ->map(fn($reference) => trim($reference))
            ->filter()
            ->values();

        if ($references->isEmpty()) {
            $this->error('Pass affected batch references with --references=17916,17832.');

            return self::FAILURE;
        }

        $batches = CollectionBatch::whereIn('collectionReference', $references)
            ->where('deleted', 0)
            ->get();

        if ($batches->isEmpty()) {
            $this->warn('No active collection batches found for the provided references.');

            return self::SUCCESS;
        }

        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $outOfMonthBatches = $batches->reject(function (CollectionBatch $batch) use ($currentMonthStart, $currentMonthEnd) {
            return $batch->collectionDate
                && Carbon::parse($batch->collectionDate)->betweenIncluded($currentMonthStart, $currentMonthEnd);
        });

        $outOfMonthBatches->each(function (CollectionBatch $batch) {
            $collectionDate = $batch->collectionDate
                ? Carbon::parse($batch->collectionDate)->toDateString()
                : 'missing collection date';

            $this->warn("Skipping batch {$batch->collectionReference}: collection date {$collectionDate} is not in the current month.");

            Log::warning('Skipped failed batch repair outside current month.', [
                'source_batch_id' => $batch->id,
                'source_reference' => $batch->collectionReference,
                'collection_date' => $collectionDate,
            ]);
        });

        $batches = $batches->filter(function (CollectionBatch $batch) use ($currentMonthStart, $currentMonthEnd) {
            return $batch->collectionDate
                && Carbon::parse($batch->collectionDate)->betweenIncluded($currentMonthStart, $currentMonthEnd);
        })->values();

        if ($batches->isEmpty()) {
            $this->warn('No current-month collection batches found for the provided references.');

            return self::SUCCESS;
        }

        $created = 0;
        $createdReferences = [];

        foreach ($batches as $sourceBatch) {
            $batch = DB::transaction(function () use ($sourceBatch) {
                $installmentIds = $this->sourceInstallmentIds($sourceBatch);

                if (empty($installmentIds)) {
                    $this->warn("Batch {$sourceBatch->collectionReference} has no installment ids.");
                    return null;
                }

                $installments = $this->repairableInstallments($installmentIds);

                if ($installments->isEmpty()) {
                    $this->warn("Batch {$sourceBatch->collectionReference} has no repairable FAILED/FUNDSCHECK installments.");
                    return null;
                }

                $collectionDate = $sourceBatch->collectionDate
                    ? Carbon::parse($sourceBatch->collectionDate)
                    : now();

                $batch = $this->collectionBatchService->createRepairBatchFromInstallments(
                    $sourceBatch->partner,
                    $installments,
                    null,
                    [
                        'forced_collection_date' => $collectionDate,
                        'batch_collection_date' => $collectionDate->toDateString(),
                        'source_batch_id' => $sourceBatch->id,
                    ]
                );

                if (!$batch) {
                    $this->warn("Batch {$sourceBatch->collectionReference} candidates were blocked by duplicate or mandate validity checks.");
                    $this->reportBlockedCandidates($sourceBatch);

                    return null;
                }

                $sourceBatch->deleted = 1;
                $sourceBatch->recreated = 1;
                $sourceBatch->lifecycle_status = CollectionBatch::LIFECYCLE_RECREATED;
                $sourceBatch->save();

                return $batch;
            });

            if ($batch) {
                $created++;
                $createdReferences[] = $batch->collectionReference;
                $this->info("Created repaired batch {$batch->collectionReference} from {$sourceBatch->collectionReference}.");
            } else {
                $this->warn("No repaired batch created from {$sourceBatch->collectionReference}.");
            }
        }

        if ($created > 0) {
            $message = "Repaired collection batch" . ($created > 1 ? 'es' : '') . " " . implode(', ', $createdReferences) . " created. Review and approve to the next stage.";
            $arr_phone = [255717705746, 255656314330];

            $messageId = $this->messagingService->generateMessageId(); //messageId

            foreach ($arr_phone as $phone) {
                $sms_log = SmsLog::firstOrNew([
                    'message_id' => $messageId,
                    'phone' => $this->messagingService->castPhone($phone),
                ]);
                $sms_log->message = $message;
                $sms_log->uuid = Str::uuid();
                $sms_log->number_of_sms = $this->messagingService->countNumberOfSms($message);
                $sms_log->status = 'PENDING';
                $sms_log->created_by = 1;
                $sms_log->save();
            }

            $response = $this->messagingService->actionSendSMS($messageId);
        }

        $this->info("== {$created} repaired failed collection batches created ==");
        return self::SUCCESS;
    }

    protected function repairableInstallments(array $installmentIds)
    {
        return Installment::whereIn('id', $installmentIds)
            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
            ->where(function ($query) {
                $query->whereNull('remarks')
                    ->orWhereIn('remarks', [
                        'Item Can Be Collected And Has Been Tracked Successfully.',
                        'Duplicate Account No Found In File',
                        'Insufficient funds Archive',
                        'Insufficient Funds',
                        'Insufficient funds',
                        'Insufficient funds.',
                    ]);
            })
            ->get()
            ->each(function (Installment $installment) {
                $this->normalizeInstallmentAmount($installment);
            });
    }

    protected function sourceInstallmentIds(CollectionBatch $batch): array
    {
        if (!empty($batch->installment_ids)) {
            return collect(explode(',', (string) $batch->installment_ids))
                ->filter()
                ->map(fn($id) => (int) trim($id))
                ->values()
                ->all();
        }

        return $batch->installment_id_list;
    }

    protected function normalizeInstallmentAmount(Installment $installment): void
    {
        $debitOrder = $installment->debitOrder;
        $debitType = strtoupper((string) $debitOrder->debit_type);
        $expectedAmount = $debitType === 'FIXED'
            ? $debitOrder->installment_amount
            : $debitOrder->max_installment_amount;

        if ((float) $installment->installment_amount === (float) $expectedAmount) {
            return;
        }

        $installment->installment_amount = $expectedAmount;
        $installment->min_installment_amount = $debitOrder->min_installment_amount;
        $installment->max_installment_amount = $debitOrder->max_installment_amount;
        $installment->current_balance = 0;
        $installment->outstanding_amount = $expectedAmount;
        $installment->save();
    }

    protected function reportBlockedCandidates(CollectionBatch $sourceBatch): void
    {
        $reasons = collect($this->collectionBatchService->candidateSkipReasons());

        if ($reasons->isEmpty()) {
            Log::warning('Failed batch repair was blocked without candidate skip details.', [
                'source_batch_id' => $sourceBatch->id,
                'source_reference' => $sourceBatch->collectionReference,
            ]);

            return;
        }

        $this->line('Blocked candidate reasons:');

        $reasons
            ->groupBy('reason')
            ->map(fn($items) => $items->count())
            ->sortDesc()
            ->each(function (int $count, string $reason) {
                $this->line(" - {$reason}: {$count}");
            });

        $reasons
            ->where('reason', 'existing_unique_line_in_other_batch')
            ->take(10)
            ->each(function (array $reason) {
                $mandateReference = $reason['mandate_reference'] ?? 'unknown mandate';
                $batchReference = $reason['duplicate_batch_reference'] ?? 'unknown batch';
                $installmentId = $reason['installment_id'] ?? 'unknown installment';
                $collectionDate = $reason['collection_date'] ?? 'unknown date';

                $this->line("   * {$mandateReference} / installment {$installmentId} already exists in batch {$batchReference} for {$collectionDate}");
            });

        Log::warning('Failed batch repair candidates were blocked.', [
            'source_batch_id' => $sourceBatch->id,
            'source_reference' => $sourceBatch->collectionReference,
            'reason_counts' => $reasons->groupBy('reason')->map(fn($items) => $items->count())->all(),
            'reasons' => $reasons->values()->all(),
        ]);
    }
}
