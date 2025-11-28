<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MandateUpdation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mandate:updation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update mandate details';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $current_date = Carbon::now();
        Log::debug("== Creating collection installments: '{$current_date}' ==");

        //debit orders
        $debit_orders = DebitOrder::whereIn('reference', [2711202518880, 2711202518867, 2711202518881, 2711202518842, 2711202518843, 2711202518805, 2611202518782, 2611202518781, 2611202518771, 2611202518766])->get();
        //$debit_orders = DebitOrder::where(['approved' => 1, 'status' => 240, 'deleted' => 0])->get();

        foreach ($debit_orders as $debit_order) {
            //calculate number of paid(status=CLOSED) and failed(status=FAILED) installments
            // $paid_installments = $debit_order->installments()->where(['status' => 'CLOSED'])->get();
            // $failed_installments = $debit_order->installments()->where(['status' => 'FAILED'])->get();
            // $debit_order->paid_installment = $paid_installments->count();
            // $debit_order->failed_installment = $failed_installments->count();
            // $debit_order->save();
            // create installments
            $this->deductionService->createInstallment($debit_order);
        }
        Log::debug("== Mandate Updation Completed: '{$current_date}' ==");
        return 0;
    }
}
