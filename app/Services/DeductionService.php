<?php

namespace App\Services;

use App\Models\AppVariable;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeductionService
{
    public function __construct() {}

    public function createInstallment(DebitOrder $debitOrder)
    {
        //1. Get debit order details
        $debit_type = $debitOrder->debit_type;
        $periodicity = $debitOrder->periodicity;
        $start_date = $debitOrder->start_date;
        $end_date = $debitOrder->end_date;
        $last_installment_date = null;
        $number_of_installment = $debitOrder->installment_number;
        $total_amount = $debitOrder->total_amount;

        // 2. Set addition number of days from periodicity
        $number_of_days = "+0 day";
        if ($periodicity == "ANNUALLY")
            $number_of_days = "+1 year";
        if ($periodicity == "MONTHLY")
            $number_of_days = '+30 day';
        else if ($periodicity == "WEEKLY")
            $number_of_days = '+1 week';
        else if ($periodicity == "DAILY")
            $number_of_days = "+1 day";

        //2. Calculate installment dates
        if ($debit_type == "FIXED") {
            $sum_installment_amount = 0;
            for ($i = 1; $i <= $number_of_installment; $i++) {
                if ($last_installment_date == null) {
                    $installment_date = date('Y-m-d', strtotime($start_date));

                    //SUMMATION installment Amount
                    $sum_installment_amount += $debitOrder->installment_amount;

                    //PAID Amount
                    $paid_amount = $debitOrder->installment_amount;

                    //assign last installment date
                    $last_installment_date =  $installment_date;
                } else {
                    $installment_date = date('Y-m-d', strtotime($last_installment_date . $number_of_days));

                    //SUMMATION installment Amount
                    $sum_installment_amount += $debitOrder->installment_amount;

                    //PAID Amount
                    if ($sum_installment_amount > $total_amount)
                        $paid_amount = $sum_installment_amount - $total_amount;
                    else
                        $paid_amount = $debitOrder->installment_amount;

                    //assign last installment date
                    $last_installment_date =  $installment_date;
                }

                //3. Create Installment deduction
                $installment = Installment::firstOrNew(
                    ['debit_order_id' => $debitOrder->id, 'installment_order' => $i],
                    [
                        'installment_amount' => $paid_amount,
                        'current_balance' => 0,
                        'outstanding_amount' => $paid_amount,
                        'payment_date' => $installment_date
                    ]
                );
                $installment->save();
                
                Log::debug("== Created Installment ==");
                Log::debug($installment);
            }
        } else if ($debit_type == "DYNAMIC") {
            $sum_installment_amount = 0;
            for ($i = 1; $i <= $number_of_installment; $i++) {
                if ($last_installment_date == null) {
                    $installment_date = date('Y-m-d', strtotime($start_date));

                    //SUMMATION installment Amount
                    $sum_installment_amount += $debitOrder->max_installment_amount;

                    //PAID Amount
                    $paid_amount = $debitOrder->max_installment_amount;

                    //assign last installment date
                    $last_installment_date =  $installment_date;
                } else {
                    $installment_date = date('Y-m-d', strtotime($last_installment_date . $number_of_days));

                    //SUMMATION installment Amount
                    $sum_installment_amount += $debitOrder->max_installment_amount;

                    //PAID Amount
                    if ($sum_installment_amount > $total_amount)
                        $paid_amount = $sum_installment_amount - $total_amount;
                    else
                        $paid_amount = $debitOrder->max_installment_amount;

                    //assign last installment date
                    $last_installment_date =  $installment_date;
                }

                //check for paid amount and min 
                $min_paid_amount = $debitOrder->min_installment_amount;
                if ($min_paid_amount > $paid_amount)
                    $min_paid_amount = $paid_amount;

                //3. Create Installment deduction
                $installment = Installment::firstOrNew(
                    ['debit_order_id' => $debitOrder->id, 'installment_order' => $i],
                    [
                        'min_installment_amount' => $min_paid_amount,
                        'max_installment_amount' => $paid_amount,
                        'current_balance' => 0,
                        'outstanding_amount' => $paid_amount,
                        'payment_date' => $installment_date
                    ]
                );
                $installment->save();

                Log::debug("== Created Installment ==");
                Log::debug($installment);
            }
        }

        return 200;
    }

    //get collection reference
    function getCollectionReference()
    {
        //app variable
        $app_variable = AppVariable::first();

        static $number = $app_variable->cur_collection_reference;

        $number++;
        $cur_collection_reference = $number;

        //update app variable
        $app_variable->cur_collection_reference = $cur_collection_reference;
        $app_variable->save();

        return $cur_collection_reference;
    }
}
