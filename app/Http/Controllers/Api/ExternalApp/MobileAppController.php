<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Validator;

class MobileAppController extends Controller
{
    public function summaries(Request $request)
    {
        $approvedStatuses = [0, 1, 2];

        //query
        $debit_orders = DebitOrder::filterCondition()->select('debit_orders.approved', DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'), DB::raw('COUNT(id) as number_of_mandate'))
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

        //recent debit orders
        $recent_debit_orders = DebitOrder::filterCondition()->take(10)->get();

        // graph stats
        $stats = [13545454, 23545454, 12545454, 23545454, 13545454, 23545454, 23545454, 23545454, 23545454, 23545454, 23545454, 23545454];

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'data' => [
                //number of mandates
                'pending_debit_orders' => $mandates[0]['number_of_mandate'],
                'approved_debit_orders' => $mandates[1]['number_of_mandate'],
                'failed_debit_orders' => $mandates[2]['number_of_mandate'],
                'total_debit_orders' => $total_number_of_mandate,

                //mandates values
                'pending_debit_orders_val' => $mandates[0]['total_amount'],
                'approved_debit_orders_val' => $mandates[1]['total_amount'],
                'failed_debit_orders_val' => $mandates[2]['total_amount'],
                'total_debit_orders_val' => $total_mandate_value,

                //stats
                'stats' => $stats,
                'recent_mandates' => $recent_debit_orders
            ]
        ]);
    }

    public function debit_orders(Request $request)
    {
        $mandates = DebitOrder::filterCondition()->paginate(50);

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'mandates' => $mandates,
        ]);
    }

    public function debit_orders_summaries(Request $request)
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
                "month" => date("M", mktime(0, 0, 0, $month, 10)),
                "total" => (int) $total_amount,
                "approved" => (int) $approved_amount,
                "rejected" => (int) $rejected_amount,
                "pending" => (int) $pending_amount
            ];
        }

        //response
        return response()->json([
            'success' => true,
            'response_code' => 200,
            'chart' => $data_array
        ]);
    }

    public function deduction_histories(Request $request, $dd_uuid)
    {
        //debit order
        $debit_order = DebitOrder::where(["uuid" => $dd_uuid])->first();

        $arr_data = [];
        foreach ($debit_order->installments as $installment) {
            $arr_data[] = [
                'date' => $installment->payment_date,
                'amount' => $installment->installment_amount,
                'status' => $installment->status
            ];
        }

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'installments' => $arr_data,
        ]);
    }

    public function actual_collections(Request $request)
    {
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
                    ->where('debit_orders.approved', '=', 1)
                    ->where('debit_orders.deleted', '=', 0)
                    ->where('debit_orders.status', '=', 240);
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

        return response()->json([
            'success' => true,
            'response_code' => 200,
            'collections' => $arr_data,
        ]);
    }
}
