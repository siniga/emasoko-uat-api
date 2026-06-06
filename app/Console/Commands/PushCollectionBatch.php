<?php

namespace App\Console\Commands;

use App\Jobs\PushCollectionJob;
use App\Models\CollectionBatch;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use Carbon\Carbon;
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
        //current month
        $current_month = now()->month;
        $current_year = now()->year;

        //get unprocessed collection batch
        $collection_batches = CollectionBatch::where(['deleted' => 0])
            ->where(function ($query) {
                $query->whereIn('lifecycle_status', [
                    CollectionBatch::LIFECYCLE_READY,
                    CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH,
                ])->orWhere(function ($legacy) {
                    $legacy->whereNull('lifecycle_status')->where('pushed', 0);
                });
            })
            ->whereMonth("created_at", "=", $current_month)
            ->whereYear("created_at", "=", $current_year)
            ->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //take the first 10 collection batches
            $collection_batches = $collection_batches->take(200)->get();

            foreach ($collection_batches as $value) {
                //batch information
                $batch = CollectionBatch::findOrFail($value->id);

                if ($batch->canBePushed()) {
                    $batch->lifecycle_status = CollectionBatch::LIFECYCLE_QUEUED_FOR_PUSH;
                    $batch->save();
                    dispatch(new PushCollectionJob($batch->id));
                }
                sleep(5); //sleep for 5 seconds
            }
        }

        // Log the collection batches
        Log::debug("== Success on pushing collection batch ==");
        return 200;
    }
}
