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

        //collection references
        //$arr_references = [10134, 10135, 10136, 10138, 10139, 10140, 10141, 10142, 10143, 10144, 10145, 10146, 10147, 10148, 10149, 10150, 10151, 10152, 10154, 10155];
        $arr_references = [10154, 10155, 10156, 10157, 10158, 10168, 10179, 10185, 10186, 10187, 10188, 10189, 10193, 10194, 10199, 10200, 10201, 10203, 10209];
        //$arr_references = [10210, 10211, 10212, 10213, 10214, 10215, 10216, 10217, 10218, 10219, 10220, 10221, 10222, 10223, 10224, 10225, 10226, 10227, 10228, 10229, 10230, 10231, 10232, 10233];

        // Split into chunks of 5
        //$arr_references = array_chunk($arr_references, 5);

        $collection_batches = CollectionBatch::whereIn('collectionReference', $arr_references)->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //take the first 10 collection batches
            $collection_batches = $collection_batches->get();

            foreach ($collection_batches as $value) {
                //collection batch
                $batch = CollectionBatch::findOrFail($value->id);

                //create payload
                $payload = $bankPayload->collectionStatusPayload($batch);

                //3. BackgroundJob for creating collection batches
                dispatch(new CollectionStatusJob($payload));
                sleep(5); // sleep for 5 seconds
            }
        }
        //logging successiong
        Log::debug("== Success on creating collection batch status ==");

        return 200;
    }
}
