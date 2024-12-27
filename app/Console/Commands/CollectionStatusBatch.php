<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;

use App\Services\BankPayloadService;
use App\Services\BankApiService;
use App\Services\DeductionService;
use Illuminate\Support\Facades\Log;

class CollectionStatusBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query Collection Status Batch';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
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
        //initialize deduction service
        $deductionService = new DeductionService();
        $bankPayload = new BankPayloadService();
        $bankApi =  new BankApiService();

        $collection_batches = CollectionBatch::where(['pushed' => 1, 'processed' => 0])->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //take the first collection batch
            $collection_batch = $collection_batches->first();

            //create payload
            $payload = $bankPayload->collectionStatusPayload($collection_batch);

            //send request
            $response = $bankApi->collectionStatus($payload);
        }

        return 200;
    }
}
