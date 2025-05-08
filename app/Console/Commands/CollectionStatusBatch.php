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
        //$arr_references = [10239, 10240, 10241, 10242, 10243, 10244, 10245, 10246, 10247, 10248, 10249, 10253, 10254, 10255, 10256];
        //$arr_references = [10260, 10261, 10262, 10263, 10264, 10265, 10266, 10269, 10270, 10271, 10272, 10273, 10274, 10275, 10276, 10277, 10278, 10279];
        //$arr_references = [10280, 10281, 10285, 10286, 10287, 10288, 10290, 10294, 10295, 10296, 10299, 10300, 10301, 10302, 10303, 10304, 10305];
        //$arr_references = [10306, 10307, 10308, 10309, 10310, 10311, 10312, 10313, 10314, 10315, 10316, 10317, 10335, 10336];
        //$arr_references = [10341, 10342, 10344, 10345, 10346, 10347, 10348, 10349, 10350, 10351, 10352, 10353, 10354, 10355, 10356, 10357, 10358, 10359];
        //$arr_references = [10360, 10361, 10362, 10363, 10364, 10365];

        // Split into chunks of 5
        //$arr_references = array_chunk($arr_references, 5);

        // $collection_batches = CollectionBatch::whereIn('collectionReference', $arr_references)->orderBy('collectionReference', 'ASC');

        // if ($collection_batches->count() > 0) {
        //     //take the first 10 collection batches
        //     $collection_batches = $collection_batches->get();

        //     foreach ($collection_batches as $value) {
        //         //collection batch
        //         $batch = CollectionBatch::findOrFail($value->id);

        //         //create payload
        //         $payload = $bankPayload->collectionStatusPayload($batch);

        //         //3. BackgroundJob for creating collection batches
        //         dispatch(new CollectionStatusJob($payload));
        //         sleep(5); // sleep for 5 seconds
        //     }
        // }
        //logging successiong
        Log::debug("== Success on creating collection batch status ==");

        return 200;
    }
}
