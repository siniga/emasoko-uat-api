<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DailyCollectionReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily Collection Report';

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
        //current month
        $current_month = now()->month;
        $current_year = now()->year;

        //get unprocessed collection batch
        $collection_batches = CollectionBatch::where(['pushed' => 1, 'deleted' => 0])
            ->whereMonth("created_at", "=", $current_month)
            ->whereYear("created_at", "=", $current_year)
            ->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //take the first 10 collection batches
            $collection_batches = $collection_batches->get();

            $total = 0;
            $paid = 0;
            $insufficient_funds = 0;
            $failed = 0;
            foreach ($collection_batches as $value) {
                //batch information
                $batch = CollectionBatch::findOrFail($value->id);

                //arr installments
                $arr_installments = explode(",", $batch->installment_ids);
                Log::debug("== Array Installments ==");
                Log::debug($arr_installments);

                foreach ($arr_installments as $installment_id) {
                    $installment = Installment::where(['id' => $installment_id])->firstOrFail();
                    Log::debug("== Installment status: '{$installment->status}' ==");
                    $status = $installment->status;
                    
                    if ($status == "CLOSED") {
                        $paid++;
                    } else if ($status == "FAILED") {
                        $failed++;
                    } else if ($status == "FUNDSCHECK") {
                        $insufficient_funds++;
                    }

                    $total++;
                }
            }

            //Log the collection batches
            echo "== Total: '{$total}' ==";
            echo "<br />";
            echo "== Paid: '{$paid}' ==";
            echo "<br />";
            echo "== Insufficient funds: '{$insufficient_funds}' ==";
            echo "<br />";
            echo "== Failed: '{$failed}' ==";
        }
    }
}
