<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DebitOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function GuzzleHttp\Promise\rejection_for;

class DashboardChartController extends Controller
{
    //aggregate consent percentage
    function mandate_percentages()
    {
        //pending
        $pending = DebitOrder::where_cond()->toBase()->where(['approved' => 0])->count();

        //approved
        $approved = DebitOrder::where_cond()->toBase()->where(['approved' => 1])->count();

        //rejected
        $rejected = DebitOrder::where_cond()->toBase()->where(['approved' => 2])->count();

        //aggregate
        $aggregate = $pending + $approved + $rejected;

        //percentage
        $aggregate = $aggregate ? $aggregate : 1;
        $percent_approved = ($approved / $aggregate) * 100;
        $percent_rejected = ($rejected / $aggregate) * 100;
        $percent_pending = ($pending / $aggregate) * 100;

        //response
        return response()->json(['error' => FALSE, 'approved' => $percent_approved, 'rejected' => $percent_rejected, 'pending' => $percent_pending], 200);
    }

    //Pending and Approved Mandates
    function mandate_values()
    {
        //declare variable
        $data_array = [];
        $approvedStatuses = [0, 1, 2];

        for ($i = 0; $i <= 11; $i++) {
            $year_month = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
            $month = date('m', strtotime($year_month));
            $year = date('Y', strtotime($year_month));

            //query
            $debit_orders = DebitOrder::select('debit_orders.approved', DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'))
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
                "pending" => (int) $pending_amount
            ];
        }

        Log::debug($data_array);

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array]);
    }

    //Collected 
    function collected_mandates()
    {
        //declare variable
        $data_array = [];

        for ($i = 0; $i <= 11; $i++) {
            $year_month = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
            $month = date('m', strtotime($year_month));
            $year = date('Y', strtotime($year_month));

            //query
            $debit_orders = DebitOrder::select(DB::raw('SUM(debit_orders.total_amount) as total_amount'), DB::raw('SUM(debit_orders.paid_amount) as paid_amount'))
                ->where(function ($query) {
                    $query->where('debit_orders.approved', '=', 1);
                })->toBase();

            $debit_orders = $debit_orders->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();

            //total amount
            $total_amount = $debit_orders[0]->total_amount;
            $paid_amount = $debit_orders[0]->paid_amount;

            //create data array
            $data_array[] = [
                // "month" => date("F", mktime(0, 0, 0, $month, 10)) . ',' . $year,
                "month" => date("M", mktime(0, 0, 0, $month, 10)),
                "total" => (int) $total_amount,
                "paid" => (int) $paid_amount,
            ];
        }

        //response
        echo json_encode(['error' => FALSE, 'chart' => $data_array], 200);
    }


    //consent per channel
    function getConsentPerChannel()
    {
        //debit order
        $debit_orders = DebitOrder::where_cond();

        //acounts
        $accounts = Account::toBase()->get();

        //declare array
        $data_array = [];
        $approvedStatuses = [0, 1, 2];

        foreach ($accounts as $key => $val) {
            //query per channel
            $debit_orders = DebitOrder::select('debit_orders.approved', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
                ->whereHas('customerAccount', function ($q) use ($val) {
                    $q->where('account_id', $val->id);
                })->where(function ($query) {
                    $query->whereIn('debit_orders.approved', [0, 1, 2]);
                })->groupBy('debit_orders.approved')->orderBy('debit_orders.approved');

            if (isset($_GET['month']) && $_GET['month'] != null) {
                $debit_orders = $debit_orders->whereMonth('debit_orders.created_at', $_GET['month'])
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
}
