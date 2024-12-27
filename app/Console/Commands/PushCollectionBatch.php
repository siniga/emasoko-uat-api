<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
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
        $batches = CollectionBatch::where(['pushed' => 0])->orderBy('collectionReference', 'ASC');

        if ($batches->count() > 0) {
            //take the first batch
            $batch = $batches->first();

            if (!empty($batch->request_csv)) {
                //1. Create payload for collection 
                $payload = $bankPayload->collectionPayload($batch, $batch->request_csv);

                //2. Send payload to a server
                $response = $bankApi->collectionCreate($payload);
            } else {
                //Delete the batch
                $batch->delete();
            }
        }

        return 0;
    }
}
