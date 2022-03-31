<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DebitOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardChartController extends Controller
{
    //aggregate consent percentage
    function getPercentageConsent()
    {
        $data_array = [];
        $percent_data = '';



        //pending debit orders
        $pending_debit_orders = DebitOrder::where(['approved' => 0])->count();

        //approved debit orders
        $approved_debit_orders = DebitOrder::where(['approved' => 1])->count();

        //aggregate
        $aggregate = $pending_debit_orders + $approved_debit_orders;

        //percentage
        $percent_approved = ($approved_debit_orders / $aggregate) * 100;
        $percent_pending = ($pending_debit_orders / $aggregate) * 100;

        //response
        echo json_encode(['error' => FALSE, 'approved' => $percent_approved, 'pending' => $percent_pending], 200);
    }


    //consent per channel
    function getConsentPerChannel()
    {
        //acounts
        $accounts = Account::all();

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
        echo json_encode(['error' => FALSE, 'data_array' => $data_array], 200);
    }
}
