<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\SmsLog;
use App\Services\CollectionBatchService;
use App\Services\DeductionService;
use App\Services\MessagingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateFailedCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:failed-collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create failed collection';

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
        $previousMonth = now()->subMonth();
        $previous_month = $previousMonth->month;
        $previous_year = $previousMonth->year;
        $this->info("== Previous Month: '{$previous_month}' ==");
        $this->info("== Previous Year: '{$previous_year}' ==");

        $created = $this->collectionBatchService->recreateFailedBatchesForMonth($previous_month, $previous_year);

        //Messaging Module
        if ($created > 0) {
            $message = "Collection batch" . ($created > 1 ? 'es' : '') . " created. Review and approve to the next stage.";
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

        $this->info("== {$previous_month} - {$previous_year} failed collection batches created ==");
        return self::SUCCESS;
    }
}
