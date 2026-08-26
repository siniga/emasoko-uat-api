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


        $summaryStatuses = [
            DebitOrder::SUMMARY_PENDING,
            DebitOrder::SUMMARY_APPROVED,
            DebitOrder::SUMMARY_FAILED,
        ];

        $grouped = DebitOrder::filterCondition()
            ->select([
                DB::raw('MONTH(debit_orders.created_at) as month_number'),
                'debit_orders.lifecycle_status',
                DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'),
            ])
            ->whereIn('debit_orders.lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->whereYear('debit_orders.created_at', $year)
            ->whereHas('partner', function ($q) {
                $q->where('partners.status', 202);
            })
            ->groupBy(DB::raw('MONTH(debit_orders.created_at)'), 'debit_orders.lifecycle_status')
            ->orderBy(DB::raw('MONTH(debit_orders.created_at)'))
            ->orderBy('debit_orders.lifecycle_status')
            ->get()
            ->groupBy('month_number');

        $data_array = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthRows = collect($grouped->get($i, []));
            $results = collect($summaryStatuses)->mapWithKeys(function ($summary) use ($monthRows) {
                $amount = $monthRows
                    ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary($summary), true))
                    ->sum('total_amount');

                return [$summary => (float) $amount];
            });

            $pending_amount = $results[DebitOrder::SUMMARY_PENDING] ?? 0;
            $approved_amount = $results[DebitOrder::SUMMARY_APPROVED] ?? 0;
            $rejected_amount = $results[DebitOrder::SUMMARY_FAILED] ?? 0;
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
        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        //query
        $debit_orders = DebitOrder::filterCondition()->select('debit_orders.lifecycle_status', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
            ->whereIn('debit_orders.lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->whereHas('partner', function ($q) {
                $q->where('partners.status', 202);
            })->groupBy('debit_orders.lifecycle_status')->orderBy('debit_orders.lifecycle_status')->toBase();

        $debit_orders = $debit_orders->get();

        $pending = collect($debit_orders)
            ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_PENDING), true))
            ->sum('number_of_approved');
        $approved = collect($debit_orders)
            ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_APPROVED), true))
            ->sum('number_of_approved');
        $rejected = collect($debit_orders)
            ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_FAILED), true))
            ->sum('number_of_approved');

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
        $channel = $request->filled('channel') ? $request->channel : null;
        $year = $request->filled('year') ? (int) $request->year : (int) date('Y');

        $expectedByMonth = $this->dashboardCollectionQuery($channel)
            ->select([
                DB::raw('MONTH(installments.payment_date) as month_number'),
                DB::raw('COALESCE(COUNT(installments.id), 0) as all_mandates'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as expected_amount'),
            ])
            ->whereYear('installments.payment_date', $year)
            ->groupBy(DB::raw('MONTH(installments.payment_date)'))
            ->get()
            ->keyBy('month_number');

        $paidByMonth = $this->dashboardCollectionQuery($channel)
            ->select([
                DB::raw('MONTH(installments.effective_payment_date) as month_number'),
                DB::raw('COALESCE(COUNT(installments.id), 0) as paid_mandates'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'),
            ])
            ->whereYear('installments.effective_payment_date', $year)
            ->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->groupBy(DB::raw('MONTH(installments.effective_payment_date)'))
            ->get()
            ->keyBy('month_number');

        $data_array = [];
        for ($i = 1; $i <= 12; $i++) {
            $expected = $expectedByMonth->get($i);
            $paid = $paidByMonth->get($i);

            $data_array[] = [
                "month" => date("M", mktime(0, 0, 0, $i, 10)),
                "total" => (int) ($expected->all_mandates ?? 0),
                "paid" => (int) ($paid->paid_mandates ?? 0),
                "total_amount" => (float) ($expected->expected_amount ?? 0),
                "paid_amount" => (float) ($paid->paid_amount ?? 0),
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array], 200);
    }

    //Weekly Collection
    public function weekly_collected_mandates(Request $request)
    {
        $channel = $request->filled('channel') ? $request->channel : null;
        $current_date = $request->filled('curr_date')
            ? date('Y-m-d', strtotime($request->input('curr_date')))
            : date('Y-m-d');

        //get dates
        $dates = $this->get_dates($current_date);

        $startDate = min($dates);
        $endDate = max($dates);

        $expectedByDate = $this->dashboardCollectionQuery($channel)
            ->select([
                DB::raw('DATE(installments.payment_date) as day_date'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
            ])
            ->whereBetween('installments.payment_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(installments.payment_date)'))
            ->pluck('total_amount', 'day_date');

        $paidByDate = $this->dashboardCollectionQuery($channel)
            ->select([
                DB::raw('DATE(installments.effective_payment_date) as day_date'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'),
            ])
            ->whereBetween('installments.effective_payment_date', [$startDate, $endDate])
            ->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->groupBy(DB::raw('DATE(installments.effective_payment_date)'))
            ->pluck('paid_amount', 'day_date');

        $data_array = [];
        foreach ($dates as $v) {
            $data_array[] = [
                "date" => date("d-m", strtotime($v)),
                "total" => (float) ($expectedByDate[$v] ?? 0),
                "paid" => (float) ($paidByDate[$v] ?? 0),
            ];
        }

        //response
        return response()->json(['error' => FALSE, 'chart' => $data_array], 200);
    }

    protected function dashboardCollectionQuery(?string $channel = null)
    {
        return Installment::filterCondition()
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where('debit_orders.approved', 1)
                    ->when($channel !== null, function ($q) use ($channel) {
                        $q->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            });
    }

    //mandate per channel
    public function mandate_channel(Request $request)
    {
        //acounts
        $accounts = Account::toBase()->get();

        //declare array
        $data_array = [];
        $summaryStatuses = [
            DebitOrder::SUMMARY_PENDING,
            DebitOrder::SUMMARY_APPROVED,
            DebitOrder::SUMMARY_FAILED,
        ];

        //channel
        $channel = null;
        if ($request->has('channel'))
            $channel = $request->channel;

        foreach ($accounts as $key => $val) {
            //query per channel
            $debit_orders = DebitOrder::filterCondition()->select('debit_orders.lifecycle_status', DB::raw('COALESCE(COUNT(debit_orders.id), 0) as number_of_approved'))
                ->whereHas('customerAccount', function ($q) use ($val) {
                    $q->where('account_id', $val->id);
                })
                ->whereIn('debit_orders.lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
                ->when($channel != null, function ($q) use ($channel) {
                    $q->where('debit_orders.channel', $channel);
                })
                ->groupBy('debit_orders.lifecycle_status')->orderBy('debit_orders.lifecycle_status');

            if ($request->has('month')) {
                $debit_orders = $debit_orders->whereMonth('debit_orders.created_at', $request->month)
                    ->whereYear('debit_orders.created_at', date('Y'));
            }

            $debit_orders = $debit_orders->get();

            // Map over all approved statuses to ensure complete results
            $results = collect($summaryStatuses)->mapWithKeys(function ($summary) use ($debit_orders) {
                $count = collect($debit_orders)
                    ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary($summary), true))
                    ->sum('number_of_approved');

                return [$summary => (int) $count];
            });

            $pending = $results[DebitOrder::SUMMARY_PENDING] ?? 0;
            $approved = $results[DebitOrder::SUMMARY_APPROVED] ?? 0;
            $rejected = $results[DebitOrder::SUMMARY_FAILED] ?? 0;

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
