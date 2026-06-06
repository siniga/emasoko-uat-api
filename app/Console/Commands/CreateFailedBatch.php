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

        $created = 0;
        $createdReferences = [];

        foreach ($batches as $sourceBatch) {
            $batch = DB::transaction(function () use ($sourceBatch) {
                $installmentIds = $sourceBatch->installment_id_list;

                if (empty($installmentIds)) {
                    return null;
                }

                $installments = $this->repairableInstallments($installmentIds);

                if ($installments->isEmpty()) {
                    return null;
                }

                $collectionDate = $sourceBatch->collectionDate
                    ? Carbon::parse($sourceBatch->collectionDate)
                    : now();

                $originalState = [
                    'deleted' => $sourceBatch->deleted,
                    'recreated' => $sourceBatch->recreated,
                    'lifecycle_status' => $sourceBatch->lifecycle_status,
                ];

                $sourceBatch->deleted = 1;
                $sourceBatch->save();

                $batch = $this->collectionBatchService->createBatchFromInstallments(
                    $sourceBatch->partner,
                    $installments,
                    null,
                    [
                        'forced_collection_date' => $collectionDate,
                        'batch_collection_date' => $collectionDate->toDateString(),
                    ]
                );

                if (!$batch) {
                    $sourceBatch->fill($originalState);
                    $sourceBatch->save();

                    return null;
                }

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

            //messageid
            $messageId = $this->messagingService->generateMessageId();

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

            //send sms
            $response = $this->messagingService->actionSendSMS($messageId);
            Log::debug("== Collection SMS sent ==");
        }

        Log::debug("== {$created} repaired failed collection batches created ==");

        return self::SUCCESS;
    }

    protected function repairableInstallments(array $installmentIds)
    {
        return Installment::whereIn('id', $installmentIds)
            ->where('status', 'FAILED')
            ->whereIn('remarks', [
                'Item Can Be Collected And Has Been Tracked Successfully.',
                'Insufficient funds Archive',
            ])
            ->get()
            ->each(function (Installment $installment) {
                $this->normalizeInstallmentAmount($installment);
            });
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
}
