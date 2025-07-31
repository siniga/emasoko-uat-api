<?php

namespace App\Http\Controllers;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;


class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    //index page
    function index()
    {
        $title = 'Dashboard';

        $approvedStatuses = [0, 1, 2]; // status

        //current year and month
        $current_year = date('Y');
        $current_month = date('m');

        //query
        $debit_orders = DebitOrder::mandateApprovalStats();

        // Map over all approved statuses to ensure complete results
        $mandates = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
            $order = $debit_orders->firstWhere('approved', $status);
            return [
                'approved' => $status,
                'number_of_mandate' => $order ? $order->number_of_mandate : 0,
                'total_amount' => $order ? $order->total_amount : 0,
            ];
        });

        //total number of mandates
        $total_number_of_mandate = $mandates[0]['number_of_mandate'] + $mandates[1]['number_of_mandate'] + $mandates[2]['number_of_mandate'];

        //summation of mandate values
        $total_mandate_value = $mandates[0]['total_amount'] + $mandates[1]['total_amount'] + $mandates[2]['total_amount'];

        // This Month’s Collection
        $this_month = Installment::thisMonthStats($current_month, $current_year);

        // Today’s Collection
        $today = Installment::todayStats();

        //stats
        $stats  = [
            //number of mandates
            'number_of_pending_mandate' =>  $mandates[0]['number_of_mandate'],
            'number_of_approved_mandate' =>  $mandates[1]['number_of_mandate'],
            'number_of_failed_mandate' =>  $mandates[2]['number_of_mandate'],
            'total_number_of_mandate' => $total_number_of_mandate,

            //mandates values
            'pending_mandate_value' => $mandates[0]['total_amount'],
            'approved_mandate_value' => $mandates[1]['total_amount'],
            'failed_mandate_value' => $mandates[2]['total_amount'],
            'total_mandate_value' => $total_mandate_value,

            //this month and today collection
            'month' => date('F'),
            'this_month_expected' => $this_month->total_amount,
            'this_month_paid' => $this_month->paid_amount,
            'today_expected' => $today->total_amount,
            'today_paid' => $today->paid_amount
        ];

        //partners
        $partners = Partner::filterCondition()->where('status', 202)->get();

        $arr_data = [];
        foreach ($partners as $partner) {
            //this month collection
            $this_month = Installment::monthlyCollectionStats($partner->id, $current_month, $current_year);

            //create dictionary
            if ($this_month->number_of_collection > 0) {
                $arr_data[] = [
                    'uuid' => $partner->uuid,
                    'partner_name' => $partner->business_name,
                    'number_of_collection' => $this_month->number_of_collection,
                    'total_amount' => $this_month->total_amount,
                    'outstanding_amount' => $this_month->outstanding_amount,
                    'paid_amount' => $this_month->paid_amount,
                    'percent' => $this_month->paid_amount > 0 ? round(($this_month->paid_amount / $this_month->total_amount) * 100, 2) : 0
                ];
            }
        }

        //sort array
        $arr_data = collect($arr_data)->sortBy('number_of_collection')->reverse()->toArray();

        //post data
        $post_data = ['year' => $current_year, 'month' => $current_month];

        //title
        $display_current_month = date('F', mktime(0, 0, 0, $current_month, 1));
        $collection_title = "Actual Monthly Collection - $display_current_month, $current_year";

        //render view
        return view('dashboard', compact('title', 'collection_title', 'arr_data', 'stats', 'post_data'));
    }
}
