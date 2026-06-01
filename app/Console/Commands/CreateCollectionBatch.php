<?php

namespace App\Console\Commands;

use App\Jobs\SendSMSJob;
use App\Services\CollectionBatchService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        $current_date = Carbon::now(); //today batch ;
        Log::debug("== Collection for Current Date: '{$current_date}' ==");

        $partners = $this->collectionBatchService->candidatePartnersForDate($current_date);

        $success = 0;
        foreach ($partners as $partner) {
            $installments = $this->deductionService->getInstallments($partner, $current_date, 250);
            $batch = $this->collectionBatchService->createBatchFromInstallments($partner, $installments);

            if ($batch) {
                $success += 1;
            }
        }

        //send Notification of batch creation
        if ($success > 0) {
            // create messages
            $message = "Collection batches created. Review and approve batches to the next stage.";
            $arr_phone = [255717705746, 255656314330];

            foreach ($arr_phone as $phone) {
                //send sms
                $data = ["phone" => $phone, "message" => $message];
                dispatch(new SendSMSJob($data));
            }
        }

        // Log the collection batches
        Log::debug("== {$current_date} collection batches created ==");
        return 200;
    }
}
