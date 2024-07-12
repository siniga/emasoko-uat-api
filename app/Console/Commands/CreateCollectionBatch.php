<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Services\MnoBankServices;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateCollectionBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and Send payment bacthes';

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
        $debits = DebitOrder::whereHas("partner", function ($query) {
            return $query->where("business_name", "NHC");
        })->where(function ($query) {
            return $query->where(function ($query) {
                return $query->whereNull('next_debit_date')->whereBetween('start_date', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
            })->orWhere(function ($query) {
                return $query->whereBetween('next_debit_date', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
            });
        })->with('customerAccount')->get()->groupBy("partner_id");
        foreach ($debits as $pDebit => $partnerDebits) {
            $partnerDebits = $partnerDebits->groupBy("customerAccount.account_id");
            foreach ($partnerDebits as $debit => $dbts) {
                DB::beginTransaction();
                $info = $dbts->first();
                $partner = $info->partner;
                $batch = CollectionBatch::create([
                    "corporateReference" => $partner->reference,
                    "selectedPostingDate" => null,
                    "createdBy" => 1,
                    "verifiedBy" => 1,
                    "createdDate" => Carbon::now(),
                    "verifyDate" => Carbon::now(),
                ]);
                if ($batch->wasRecentlyCreated) {
                    foreach ($dbts as $dbt) {
                        $collectionDebitOrder = CollectionDebitOrder::create([
                            "collection_batch_id" => $batch->id,
                            "debit_order_id" => $dbt->id,
                            "status" => "pending",
                        ]);
                        if ($collectionDebitOrder->wasRecentlyCreated) {
                            $periodicity = str_replace('ly', '', strtolower($dbt->periodicity));
                            $dbt->next_debit_date = date('Y-m-d H:i:s', strtotime("+1 " . $periodicity, strtotime($dbt->next_debit_date ?? $dbt->start_date)));
                            $dbt->save();
                        }
                    }
                }
                $mnoBank = new MnoBankServices($info->customerAccount->account);
                $res = $mnoBank->collectionFileReceptionAndValidation(request(), $batch);
                echo "Batch Created\n";
                if ($res->status) {
                    sleep(20);
                    echo "Check Status\n";
                    $mnoBank->collectionStatus(request(), $batch);
                }
                DB::commit();
            }
        }
        return 0;
    }
}
