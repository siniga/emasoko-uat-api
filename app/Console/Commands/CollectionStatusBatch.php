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
    public function __construct(protected BankPayloadService $bankPayload)
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

        //get the start and end of the month
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        // get the start and end of the last month
        // $start = now()->subMonth()->startOfMonth();
        // $end   = now()->subMonth()->endOfMonth();
        //->whereDate('created_at', $current_date)

        //1. Get all collection batches created in the current month
        $collection_batches = CollectionBatch::where(['deleted' => 0])
            ->where(function ($query) {
                $query->whereIn('lifecycle_status', [
                    CollectionBatch::LIFECYCLE_PUSHED,
                    CollectionBatch::LIFECYCLE_STATUS_PENDING,
                    CollectionBatch::LIFECYCLE_PARTIALLY_PROCESSED,
                ])->orWhere(function ($legacy) {
                    $legacy->whereNull('lifecycle_status')->where('pushed', 1)->where('processed', 0);
                });
            })
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('collectionReference', 'ASC')->get();

        if ($collection_batches->isEmpty())
            return;

        //2. Loop through each collection batch and create a payload for each
        foreach ($collection_batches as $value) {
            //collection batch
            $batch = CollectionBatch::findOrFail($value->id);

            $batch->lifecycle_status = CollectionBatch::LIFECYCLE_STATUS_PENDING;
            $batch->save();
            dispatch(new CollectionStatusJob($batch->id));
            sleep(5); // sleep for 5 seconds
        }

        //logging successiong
        Log::debug("== Success on creating collection batch status ==");
        return 200;
    }
}
