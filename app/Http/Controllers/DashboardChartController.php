<?php

namespace App\Http\Controllers;

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

class DashboardChartController extends Controller
{
    //Pending and Approved Mandates
    public function mandate_values(Request $request)
    {
        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        //current or requested year
        $year = date('Y');
        if ($request->has('year'))
            $year = $request->year;


        $approvedStatuses = [0, 1, 2];

        $grouped = DebitOrder::filterCondition()
            ->select([
                DB::raw('MONTH(debit_orders.created_at) as month_number'),
                'debit_orders.approved',
                DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'),
            ])
            ->whereIn('debit_orders.approved', $approvedStatuses)
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->whereYear('debit_orders.created_at', $year)
            ->whereHas('partner', function ($q) {
                $q->where('partners.status', 202);
            })
            ->groupBy(DB::raw('MONTH(debit_orders.created_at)'), 'debit_orders.approved')
            ->orderBy(DB::raw('MONTH(debit_orders.created_at)'))
            ->orderBy('debit_orders.approved')
            ->get()
            ->groupBy('month_number');

        $data_array = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthRows = collect($grouped->get($i, []));
            $results = collect($approvedStatuses)->map(function ($status) use ($monthRows) {
                $order = $monthRows->firstWhere('approved', $status);

                return [
                    'approved' => $status,
                    'total_amount' => $order ? (float) $order->total_amount : 0,
                ];
            });

            $pending_amount = $results[0]['total_amount'];
            $approved_amount = $results[1]['total_amount'];
            $rejected_amount = $results[2]['total_amount'];
            $total_amount = $pending_amount + $approved_amount + $rejected_amount;

            $data_array[] = [
                "month" => date("M", mktime(0, 0, 0, $i, 10)),
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
    public function mandate_percentages(Request $request)
    {
        //declare array
        $data_array = [];
        $approvedStatuses = [0, 1, 2];

        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        //query
        $debit_orders = DebitOrder::filterCondition()->select('debit_orders.approved', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
            ->where(function ($query) {
                $query->whereIn('debit_orders.approved', [0, 1, 2]);
            })
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->whereHas('partner', function ($q) {
                $q->where('partners.status', 202);
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
        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        //current or requested year
        $year = date('Y');
        if ($request->has('year'))
            $year = $request->year;

        $expectedByMonth = Installment::filterCondition()
            ->select([
                DB::raw('MONTH(installments.payment_date) as month_number'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
            ])
            ->whereYear('installments.payment_date', $year)
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where(['debit_orders.approved' => 1])
                    ->when(!is_null($channel), function ($query) use ($channel) {
                        $query->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            })
            ->groupBy(DB::raw('MONTH(installments.payment_date)'))
            ->pluck('total_amount', 'month_number');

        $paidByMonth = Installment::filterCondition()
            ->select([
                DB::raw('MONTH(installments.effective_payment_date) as month_number'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'),
            ])
            ->whereYear('installments.effective_payment_date', $year)
            ->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where(['debit_orders.approved' => 1])
                    ->when(!is_null($channel), function ($query) use ($channel) {
                        $query->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            })
            ->groupBy(DB::raw('MONTH(installments.effective_payment_date)'))
            ->pluck('paid_amount', 'month_number');

        $data_array = [];
        for ($i = 1; $i <= 12; $i++) {
            $data_array[] = [
                "month" => date("M", mktime(0, 0, 0, $i, 10)),
                "total" => (int) ($expectedByMonth[$i] ?? 0),
                "paid" => (int) ($paidByMonth[$i] ?? 0),
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array], 200);
    }

    //Weekly Collection
    public function weekly_collected_mandates(Request $request)
    {
        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        //current date
        $current_date = date('Y-m-d');
        if ($request->has('curr_date'))
            $current_date = date('Y-m-d', strtotime($request->input('curr_date')));
        else
            $current_date = $current_date;

        //get dates
        $dates = $this->get_dates($current_date);

        $startDate = min($dates);
        $endDate = max($dates);

        $expectedByDate = Installment::filterCondition()
            ->select([
                DB::raw('DATE(installments.payment_date) as day_date'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
            ])
            ->whereBetween('installments.payment_date', [$startDate, $endDate])
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where(['debit_orders.approved' => 1])
                    ->when(!is_null($channel), function ($query) use ($channel) {
                        $query->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            })
            ->groupBy(DB::raw('DATE(installments.payment_date)'))
            ->pluck('total_amount', 'day_date');

        $paidByDate = Installment::filterCondition()
            ->select([
                DB::raw('DATE(installments.effective_payment_date) as day_date'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'),
            ])
            ->whereBetween('installments.effective_payment_date', [$startDate, $endDate])
            ->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where(['debit_orders.approved' => 1])
                    ->when(!is_null($channel), function ($query) use ($channel) {
                        $query->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            })
            ->groupBy(DB::raw('DATE(installments.effective_payment_date)'))
            ->pluck('paid_amount', 'day_date');

        $data_array = [];
        foreach ($dates as $v) {
            $data_array[] = [
                "date" => date("d-m", strtotime($v)),
                "total" => (int) ($expectedByDate[$v] ?? 0),
                "paid" => (int) ($paidByDate[$v] ?? 0),
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

        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        foreach ($accounts as $key => $val) {
            //query per channel
            $debit_orders = DebitOrder::filterCondition()->select('debit_orders.approved', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
                ->whereHas('customerAccount', function ($q) use ($val) {
                    $q->where('account_id', $val->id);
                })->where(function ($query) {
                    $query->whereIn('debit_orders.approved', [0, 1, 2]);
                })
                ->when($channel != null, function ($q) use ($channel) {
                    $q->where('debit_orders.channel', $channel);
                })
                ->groupBy('debit_orders.approved')->orderBy('debit_orders.approved');

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
