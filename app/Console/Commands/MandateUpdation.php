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
        //$debit_orders = DebitOrder::whereIn('reference', [2611202518752, 2611202518748, 2611202518741, 2611202518733, 2611202518710, 2611202518706, 2611202518685, 2511202518673, 2511202518654, 2511202518628, 2511202518565, 2411202518422, 2411202518412, 2411202518393, 2411202518371, 2411202518350, 2211202518205, 2211202518203, 1711202517936, 1311202517818, 1311202517815, 1311202517811])->get();
        $debit_orders = DebitOrder::where(['approved' => 1, 'status' => 240, 'deleted' => 0])->get();

        foreach ($debit_orders as $debit_order) {
            //calculate number of paid(status=CLOSED) and failed(status=FAILED) installments
            $paid_installments = $debit_order->installments()->where(['status' => 'CLOSED'])->get();
            $failed_installments = $debit_order->installments()->where(['status' => 'FAILED'])->get();
            $debit_order->paid_installment = $paid_installments->count();
            $debit_order->failed_installment = $failed_installments->count();
            $debit_order->save();
            // create installments
            // $this->deductionService->createInstallment($debit_order);
        }
        Log::debug("== Mandate Updation Completed: '{$current_date}' ==");
        return 0;
    }
}
