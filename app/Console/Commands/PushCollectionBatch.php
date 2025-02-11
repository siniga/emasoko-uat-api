<?php

namespace App\Console\Commands;

use App\Jobs\PushCollectionJob;
use App\Models\CollectionBatch;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Push collection batches';

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
        $bankPayload = new BankPayloadService();
        $bankApi =  new BankApiService();

        //get unprocessed collection batch
        $collection_batches = CollectionBatch::where(['pushed' => 0])->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //take the first 10 collection batches
            $collection_batches = $collection_batches->get();

            foreach ($collection_batches as $value) {
                //single batch
                $batch = CollectionBatch::findOrFail($value->id);

                if (!empty($batch->request_csv)) {
                    //1. Create parameters
                    $csv = $batch->request_csv;
                    $arr_installments = explode(",", $batch->installment_ids);

                    //2. Create payload for collection 
                    $payload = $bankPayload->collectionPayload($batch, $csv);

                    //3. BackgroundJob for creating collection batches
                    dispatch(new PushCollectionJob($payload, $arr_installments));
                }
                sleep(5); //sleep for 5 seconds
            }
        }

        return 0;
    }
}
