<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateInterval;
use DatePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function GuzzleHttp\Promise\rejection_for;

class DashboardChartController extends Controller
{
    //Pending and Approved Mandates
    public function mandate_values(Request $request)
    {
        //current or requested year
        $year = date('Y');
        if ($request->has('year'))
            $year = $request->year;

        //declare variable
        $data_array = [];
        $approvedStatuses = [0, 1, 2];

        for ($i = 1; $i <= 12; $i++) {
            $month = date('m', mktime(0, 0, 0, $i, 10));

            //query
            $debit_orders = DebitOrder::filterCondition()->select('debit_orders.approved', DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'))
                ->where(function ($query) {
                    $query->whereIn('debit_orders.approved', [0, 1, 2]);
                })->groupBy('debit_orders.approved')->orderBy('debit_orders.approved')->toBase();

            $debit_orders = $debit_orders->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();

            // Map over all approved statuses to ensure complete results
            $results = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
                $order = $debit_orders->firstWhere('approved', $status);
                return [
                    'approved' => $status,
                    'total_amount' => $order ? $order->total_amount : 0,
                ];
            });

            //pending
            $pending_amount = $results[0]['total_amount'];

            //approved 
            $approved_amount = $results[1]['total_amount'];

            //rejected
            $rejected_amount = $results[2]['total_amount'];

            ///aggregate mandate
            $total_amount = $pending_amount + $approved_amount + $rejected_amount;

            //create data array
            $data_array[] = [
                // "month" => date("F", mktime(0, 0, 0, $month, 10)) . ',' . $year,
                "month" => date("M", mktime(0, 0, 0, $month, 10)),
                "total" => (int) $total_amount,
                "approved" => (int) $approved_amount,
                "rejected" => (int) $rejected_amount,
                "pending" => (int) $pending_amount
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array]);
    }

    //aggregate consent percentage
    public function mandate_percentages()
    {
        //declare array
        $data_array = [];
        $approvedStatuses = [0, 1, 2];

        //query
        $debit_orders = DebitOrder::filterCondition()->select('debit_orders.approved', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
            ->where(function ($query) {
                $query->whereIn('debit_orders.approved', [0, 1, 2]);
            })->groupBy('debit_orders.approved')->orderBy('debit_orders.approved')->toBase();

        $debit_orders = $debit_orders->get();

        // Map over all approved statuses to ensure complete results
        $results = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
            $order = $debit_orders->firstWhere('approved', $status);
            return [
                'approved' => $status,
                'number_of_approved' => $order ? $order->number_of_approved : 0,
            ];
        });

        //pending
        $pending = $results[0]['number_of_approved'];

        //approve 
        $approved = $results[1]['number_of_approved'];

        //rejected
        $rejected = $results[2]['number_of_approved'];

        //aggregate
        $aggregate = $pending + $approved + $rejected;

        //percentage
        $aggregate = $aggregate ? $aggregate : 1;
        $percent_approved = round(($approved / $aggregate) * 100, 2);
        $percent_rejected = round(($rejected / $aggregate) * 100, 2);
        $percent_pending = round(($pending / $aggregate) * 100, 2);

        //response
        return response()->json(['error' => FALSE, 'approved' => $percent_approved, 'rejected' => $percent_rejected, 'pending' => $percent_pending], 200);
    }

    //Monthly Collected 
    public function collected_mandates(Request $request)
    {
        //current or requested year
        $year = date('Y');
        if ($request->has('year'))
            $year = $request->year;

        //declare variable
        $data_array = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = date('m', mktime(0, 0, 0, $i, 10));

            //installments
            $installments = Installment::filterCondition()->select(DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'), DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'))->toBase();
            $installments = $installments->whereMonth('payment_date', $month)->whereYear('payment_date', $year)->get();

            //total amount
            $total_amount = $installments[0]->total_amount;
            $paid_amount = $installments[0]->paid_amount;

            //create data array
            $data_array[] = [
                "month" => date("M", mktime(0, 0, 0, $month, 10)),
                "total" => (int) $total_amount,
                "paid" => (int) $paid_amount,
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array], 200);
    }

    //Weekly Collection
    public function weekly_collected_mandates(Request $request)
    {
        //current date
        $current_date = date('Y-m-d');
        if ($request->has('curr_date'))
            $current_date = date('Y-m-d', strtotime($request->input('curr_date')));
        else
            $current_date = $current_date;

        Log::debug("current date");
        Log::debug($current_date);


        //get dates
        $dates = $this->get_dates($current_date);

        //declare variable
        $data_array = [];

        foreach ($dates as $key => $v) {
            //installments
            $installments = Installment::filterCondition()->select(DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'), DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'))->toBase();
            $installments = $installments->whereDate('payment_date', $v)->get();

            //total amount
            $total_amount = $installments[0]->total_amount;
            $paid_amount = $installments[0]->paid_amount;

            //create data array
            $data_array[] = [
                "date" => date("d-m", strtotime($v)),
                "total" => (int) $total_amount,
                "paid" => (int) $paid_amount,
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array], 200);
    }

    //mandate per channel
    public function mandate_channel(Request $request)
    {
        //acounts
        $accounts = Account::toBase()->get();

        //declare array
        $data_array = [];
        $approvedStatuses = [0, 1, 2];

        foreach ($accounts as $key => $val) {
            //query per channel
            $debit_orders = DebitOrder::filterCondition()->select('debit_orders.approved', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
                ->whereHas('customerAccount', function ($q) use ($val) {
                    $q->where('account_id', $val->id);
                })->where(function ($query) {
                    $query->whereIn('debit_orders.approved', [0, 1, 2]);
                })->groupBy('debit_orders.approved')->orderBy('debit_orders.approved');

            if ($request->has('month')) {
                $debit_orders = $debit_orders->whereMonth('debit_orders.created_at', $request->month)
                    ->whereYear('debit_orders.created_at', date('Y'));
            }

            $debit_orders = $debit_orders->get();

            // Map over all approved statuses to ensure complete results
            $results = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
                $order = $debit_orders->firstWhere('approved', $status);
                return [
                    'approved' => $status,
                    'number_of_approved' => $order ? $order->number_of_approved : 0,
                ];
            });

            //pending
            $pending = $results[0]['number_of_approved'];

            //approve 
            $approved = $results[1]['number_of_approved'];

            //rejected
            $rejected = $results[2]['number_of_approved'];

            //create data array
            $data_array[] = [
                "name" => $val->name,
                "pending" =>  (int) $pending,
                "approved" => (int) $approved,
                "rejected" => (int) $rejected
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array], 200);
    }

    //collection success rate
    public function monthly_collection_rate($uuid)
    {
        //current date
        $current_year = date('Y');
        $current_month = date('m');

        //partner
        $partner = Partner::where(['uuid' => $uuid])->firstOrfail();

        


    }

    public function get_dates($currentDate)
    {
        $arr_dates = [];
        for ($i = 0; $i < 7; $i++) {
            $installment_date = date('Y-m-d', strtotime($currentDate . "-{$i} day"));
            array_push($arr_dates, $installment_date);
        }

        // Output the dates
        return array_reverse($arr_dates);
    }
}
