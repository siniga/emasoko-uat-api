<?php

namespace App\Console\Commands;

use App\Jobs\CollectionStatusJob;
use Illuminate\Console\Command;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;

use App\Services\BankPayloadService;
use App\Services\BankApiService;
use App\Services\DeductionService;
use Carbon\Carbon;
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
        $current_date = Carbon::now();
        Log::debug("== Creating collection status: '{$current_date}' ==");

        //initialize deduction service
        $bankPayload = new BankPayloadService();
        $bankApi =  new BankApiService();

        //get the start and end of the month
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        //1. Get all collection batches created in the current month
        $collection_batches = CollectionBatch::where(['pushed' => 1, 'deleted' => 0])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('collectionReference', 'ASC')->get();

        if ($collection_batches->isEmpty())
            return;

        //2. Loop through each collection batch and create a payload for each
        foreach ($collection_batches as $value) {
            //collection batch
            $batch = CollectionBatch::findOrFail($value->id);

            //create payload
            $payload = $bankPayload->collectionStatusPayload($batch);

            //3. BackgroundJob for creating collection batches
            dispatch(new CollectionStatusJob($payload));
            sleep(5); // sleep for 5 seconds
        }

        //logging successiong
        Log::debug("== Success on creating collection batch status ==");

        return 200;
    }
}
