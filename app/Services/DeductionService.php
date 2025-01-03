<?php

namespace App\Services;

use App\Jobs\SendSMSJob;
use App\Models\AppVariable;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeductionService
{
    public function __construct() {}

    //create debit order evidence of aggrement
    public function createEvidence(DebitOrder $debitOrder) {}

    //create debit order installments
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

        //3. Delete all installments
        $delete_installment = Installment::where('debit_order_id', $debitOrder->id)->delete();

        //4. Calculate installment dates
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
                        'installment_amount' => $paid_amount,
                        'current_balance' => 0,
                        'outstanding_amount' => $paid_amount,
                        'payment_date' => $installment_date
                    ]
                );
                $installment->save();
            }
        }

        //5. Ppdate debit order end_date
        $debitOrder->end_date = $last_installment_date;
        $debitOrder->save();

        return 200;
    }

    //process installment
    public function processInstallment(Installment $installment, $amount, $reference)
    {
        //0. Check if installment status is not CLOSED
        if ($installment->status == 'OPEN') {
            //1. Update installment => outstanding amount, current balance
            $outstanding_amount = $installment->outstanding_amount - $amount;
            $current_balance = $installment->current_balance + $amount;
            $installment->outstanding_amount = $outstanding_amount;
            $installment->current_balance = $current_balance;

            //2. update installment status {OPEN, CLOSED, PARTIAL_PAID}
            if ($outstanding_amount <= 0)
                $installment->status = "CLOSED";
            else if ($outstanding_amount > 0)
                $installment->status = "PARTIAL_PAID";

            //3. Save installment
            $installment->save();

            //4. Insert payment in installment amount
            $payment = new InstallmentPayment();
            $payment->installment_id = $installment->id;
            $payment->amount = $amount;
            $payment->reference = $reference;
            $payment->save();

            //5. Update debit order => outstanding amount, current balance
            $debit_order = $installment->debitOrder;
            $outstanding_amount = $debit_order->outstanding_amount - $amount;
            $current_balance = $debit_order->current_balance + $amount;
            $debit_order->outstanding_amount = $outstanding_amount;
            $debit_order->current_balance = $current_balance;

            //6. Update contract status = outstanding amount <= 0
            if ($outstanding_amount <= 0)
                $debit_order->contract_status = "CLOSED";

            //save debit order
            $debit_order->save();

            //installment amount
            $installment_amount = $installment->installment_amount;

            //payment date
            $payment_date = $installment->payment_date;

            //send message for outstanding amount and current balance from eMakato.
            $phone = $installment->debitOrder->customerAccount->phone;
            $message = "Makato ya tarehe {$payment_date} ya kiasi cha shilingi {$installment_amount} yamekamilishwa na kiasi kilichobaki ni Tsh {$outstanding_amount}. Asante kwa kutumia eMakato.";

            //send sms
            $data = ["phone" => $phone, "message" => $message];
            dispatch(new SendSMSJob($data));
            Log::debug("Installment => Processed");
        } else if ($installment->status == 'PARTIAL_PAID') {
            Log::debug("Installment => Partial Paid");
        } else if ($installment->status == 'CLOSED') {
            Log::debug("Installement => Closed");
        }

        return 200;
    }

    //update debit date
    public function updateNextDebitDate($debit_id, $payment_date)
    {
        //next installment
        $next_installment = Installment::where(['debit_order_id' => $debit_id, 'status' => 'OPEN'])
            ->whereDate('payment_date', '>', $payment_date)->orderBy("payment_date", "ASC");

        if ($next_installment->count() > 0) {
            $next_installment = $next_installment->first();

            //update next debit date
            $debit_order = DebitOrder::where("id", "=", $debit_id)->first();
            $debit_order->next_debit_date = $next_installment->payment_date;
            $debit_order->save();
        }
    }

    //get mandate reference
    function getMandateReference()
    {
        //app variable
        $app_variable = AppVariable::first();

        static $number = $app_variable->cur_mandate_reference;

        $number++;
        $cur_mandate_reference = $number;

        //update app variable
        $app_variable->cur_mandate_reference = $cur_mandate_reference;
        $app_variable->save();

        return $cur_mandate_reference;
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
