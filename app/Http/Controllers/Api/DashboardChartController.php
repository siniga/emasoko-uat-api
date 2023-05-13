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
        $pending = DebitOrder::where_cond()->where(['approved' => 0])->count();

        //approved
        $approved = DebitOrder::where_cond()->where(['approved' => 1])->count();

        //aggregate
        $aggregate = $pending + $approved;

        //percentage
        $percent_approved = ($approved / $aggregate) * 100;
        $percent_pending = ($pending / $aggregate) * 100;

        //response
        echo json_encode(['error' => FALSE, 'approved' => $percent_approved, 'pending' => $percent_pending], 200);
    }

    //mandate values
    function mandate_values()
    {
        //declare variable
        $data_array = [];

        for ($i = 0; $i <= 11; $i++) {
            $year_month = date("Y-m", strtotime(date('Y-m-01') . " -$i months"));
            $month = date('m', strtotime($year_month));
            $year = date('Y', strtotime($year_month));

            //query
            $debit_orders = DebitOrder::select('debit_orders.approved', DB::raw('SUM(debit_orders.total_amount) as total_amount'))
                ->where(function ($query) {
                    $query->where('debit_orders.approved', '=', 1)
                        ->orWhere('debit_orders.approved', '=', 0)
                        ->orWhere('debit_orders.approved', '=', 99);
                })->groupBy('debit_orders.approved')
                ->orderBy('debit_orders.approved', 'DESC');

            $debit_orders = $debit_orders->whereMonth('created_at', $month)->whereYear('created_at', $year)->get();

            //approve 
            $approved = 0;
            if (isset($debit_orders[0]->total_amount) && $debit_orders[0]->total_amount != null)
                $approved = $debit_orders[0]->total_amount;

            //pending
            $pending = 0;
            if (isset($debit_orders[1]->total_amount) && $debit_orders[1]->total_amount != null)
                $pending = $debit_orders[1]->total_amount;

            //rejected
            $rejected = 0;
            if (isset($debit_orders[2]->total_amount) && $debit_orders[2]->total_amount != null)
                $rejected = $debit_orders[2]->total_amount;

            ///aggregate mandate
            $aggregate = $approved + $pending + $rejected;

            //create data array
            $data_array[] = [
                // "month" => date("F", mktime(0, 0, 0, $month, 10)) . ',' . $year,
                "month" => date("M", mktime(0, 0, 0, $month, 10)),
                "total" => (int) $aggregate,
                "pending" =>  (int) $pending,
                "approved" => (int) $approved
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
        $accounts = Account::all();

        //declare array
        $data_array = [];
        foreach ($accounts as $key => $val) {
            //query per channel
            $debit_orders = DebitOrder::select('debit_orders.approved', DB::raw('COUNT(debit_orders.id) as count_approved'))
                ->join('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
                ->where('customer_accounts.account_id', '=', $val->id)
                ->where(function ($query) {
                    $query->where('debit_orders.approved', '=', 1)
                        ->orWhere('debit_orders.approved', '=', 0);
                })
                ->groupBy('debit_orders.approved')
                ->orderBy('debit_orders.approved', 'DESC');

            if (isset($_GET['month']) && $_GET['month'] != null) {
                $debit_orders = $debit_orders->whereMonth('debit_orders.created_at', $_GET['month'])
                    ->whereYear('debit_orders.created_at', date('Y'));
            }

            $debit_orders = $debit_orders->get();

            //pending
            $pending = 0;
            if (isset($debit_orders[0]->count_approved) && $debit_orders[0]->count_approved != null)
                $pending = $debit_orders[0]->count_approved;

            //approve 
            $approved = 0;
            if (isset($debit_orders[1]->count_approved) && $debit_orders[1]->count_approved != null)
                $approved = $debit_orders[1]->count_approved;

            //create data array
            $data_array[] = [
                "name" => $val->name,
                "pending" =>  (int) $pending,
                "approved" => (int) $approved
            ];
        }

        //response
        echo json_encode(['error' => FALSE, 'chart' => $data_array], 200);
    }
}
