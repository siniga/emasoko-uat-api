<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Models\SmsLog;
use App\Services\CollectionBatchService;
use App\Services\DeductionService;
use App\Services\MessagingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateCollectionBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create collection bacthes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected DeductionService $deductionService,
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
        $current_date = Carbon::now();
        $this->info("== Collection for Current Date: '{$current_date}' ==");

        $partners = $this->collectionBatchService->candidatePartnersForDate($current_date);

        $success = 0;
        foreach ($partners as $partner) {
            $installments = $this->deductionService->getInstallments($partner, $current_date);

            foreach ($installments->chunk(250) as $chunk) {
                $batch = $this->collectionBatchService->createBatchFromInstallments($partner, $chunk->values());

                if ($batch) {
                    $success += 1;
                }
            }
        }

        //Messaging Module
        if ($success > 0) {
            $message = "Collection batch" . ($success > 1 ? 'es' : '') . " created. Review and approve to the next stage.";
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

        // Log the collection batches
        $this->info("== {$current_date} collection batches created ==");
        return self::SUCCESS;
    }
}
