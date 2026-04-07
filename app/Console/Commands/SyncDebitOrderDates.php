<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDebitOrderDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debit-orders:sync-next-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync next debit dates for all active debit orders from unpaid installment schedule';

    public function __construct(protected DeductionService $deductionService)
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
        $referenceDate = Carbon::now();
        Log::debug("== Sync next debit dates: '{$referenceDate}' ==");

        $count = 0;

        DebitOrder::query()
            ->eligibleForCollection()
            ->chunkById(200, function ($debitOrders) use (&$count, $referenceDate) {
            foreach ($debitOrders as $debitOrder) {
                $this->deductionService->syncNextDebitDate($debitOrder, $referenceDate);
                $count++;
            }
        });

        Log::debug("== Synced next debit dates for {$count} active debit orders ==");
        $this->info("Synced next debit dates for {$count} active debit orders.");

        return self::SUCCESS;
    }
}
