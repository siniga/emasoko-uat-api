<?php

namespace App\Http\Controllers;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

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

        $approvedStatuses = [0, 1, 2];

        //query
        $debit_orders = DebitOrder::filterCondition()->select(
            'debit_orders.approved',
            DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'),
            DB::raw('COUNT(id) as number_of_mandate')
        )
            ->where(function ($query) {
                $query->whereIn('debit_orders.approved', [0, 1, 2]);
            })->groupBy('debit_orders.approved')->orderBy('debit_orders.approved')->toBase();

        $debit_orders = $debit_orders->get();

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

        //current date
        $current_year = date('Y');
        $current_month = date('m');

        //collection stats
        $installments = Installment::filterCondition()->select(
            DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
            DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount')
        )->toBase();

        //this month collection
        $this_month = $installments->whereMonth('payment_date', $current_month)->whereYear('payment_date', $current_year)->get();

        //today collection
        $today = $installments->whereDate('payment_date', Carbon::now())->get();

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
            'this_month_expected' => $this_month[0]->total_amount,
            'this_month_paid' => $this_month[0]->paid_amount,
            'today_expected' => $today[0]->total_amount,
            'today_paid' => $today[0]->paid_amount
        ];

        //current date
        $current_year = date('Y');
        $current_month = date('m');

        //partners
        $partners = Partner::filterCondition()->where('status', 202)->get();

        $arr_data = [];
        foreach ($partners as $partner) {
            //collection stats
            $installments = Installment::select(
                DB::raw('COALESCE(COUNT(installments.id), 0) as number_of_collection'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
                DB::raw('COALESCE(SUM(installments.outstanding_amount), 0) as outstanding_amount'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount')
            )->whereHas('debitOrder', function ($query) use ($partner) {
                $query->where('debit_orders.partner_id', '=', $partner->id)
                    ->where('debit_orders.approved', '=', 1);
            })->toBase();

            //this month collection
            $this_month = $installments
                ->whereMonth('payment_date', $current_month)
                ->whereYear('payment_date', $current_year)->get();

            //create dictionary
            if ($this_month[0]->paid_amount > 0) {
                $arr_data[] = [
                    'uuid' => $partner->uuid,
                    'partner_name' => $partner->business_name,
                    'number_of_collection' => $this_month[0]->number_of_collection,
                    'total_amount' => $this_month[0]->total_amount,
                    'outstanding_amount' => $this_month[0]->outstanding_amount,
                    'paid_amount' => $this_month[0]->paid_amount,
                    'percent' => round(($this_month[0]->paid_amount / $this_month[0]->total_amount) * 100, 2)
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
