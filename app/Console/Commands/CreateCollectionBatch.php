<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Models\SmsLog;
use App\Services\CollectionBatchService;
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
    protected $signature = 'create:batches
                            {--date= : Collection date in YYYY-MM-DD format (defaults to today)}';

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
        $current_date = $this->collectionDate();

        if (!$current_date) {
            return self::FAILURE;
        }

        $this->info("== Collection for Current Date: '{$current_date}' ==");

        $batches = $this->collectionBatchService->createScheduledBatchesForDate($current_date);
        $success = $batches->count();

        //send sms to the admin if any collection batch is created
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
        $this->info("== {$success} collection batches created on {$current_date} ==");
        return self::SUCCESS;
    }

    private function collectionDate(): ?string
    {
        $value = $this->option('date');

        if (!$value) {
            return now()->toDateString();
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', (string) $value);
        } catch (\Throwable) {
            $date = null;
        }

        if (!$date || $date->toDateString() !== $value) {
            $this->components->error('The --date option must use the YYYY-MM-DD format.');

            return null;
        }

        return $date->toDateString();
    }
}
