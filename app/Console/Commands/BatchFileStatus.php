<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Services\MnoBankServices;
use Illuminate\Console\Command;

class BatchFileStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $batches = CollectionBatch::get();
        foreach ($batches as $batch) {
            $debit = $batch->debits()->first();
            $debitOrder = $debit->debitOrder;
            $account = $debitOrder->customerAccount->account;
            $mnoBank = new MnoBankServices($account);
            $mnoBank->collectionStatus(request(), $batch);
        }
        return 0;
    }
}
