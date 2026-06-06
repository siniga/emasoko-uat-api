<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Services\CollectionBatchService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        protected CollectionBatchService $collectionBatchService
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //current month
        $previous_month = now()->subMonth()->month;
        $current_year = now()->year;
        Log::debug("== Previous Month: '{$previous_month}' ==");
        Log::debug("== Current Year: '{$current_year}' ==");

        $count = $this->collectionBatchService->recreateFailedBatchesForMonth($previous_month, $current_year);

        //TODO: Messaging module

        //send Notification of batch creation
        if ($count > 0) {
            $message = "Collection batches created. Review and approve batches to the next stage.";
            $arr_phone = [255717705746, 255656314330];

            foreach ($arr_phone as $phone) {
                //send sms
                $data = ["phone" => $phone, "message" => $message];
                dispatch(new SendSMSJob($data));
            }
        }

        // Log the collection batches
        Log::debug("== {$previous_month} - {$current_year} failed collection batches created ==");
        return 200;
    }
}
