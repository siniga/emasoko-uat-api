<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function index(Request $request)
    {
        $title = 'Dashboard';

        $approvedStatuses = [0, 1, 2]; // status

        //current year and month
        $current_year = date('Y');
        $current_month = date('m');
        $channel = $request->channel;

        //query
        $debit_orders = DebitOrder::mandateApprovalStats($channel);

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
        $this_month = Installment::thisMonthStats($current_month, $current_year, $channel);

        // Today’s Collection
        $today = Installment::todayStats(now()->toDateString(), $channel);

        //form action
        $payment_channels = Account::where('active', '=', 1)->get();
        $form_action = route('dashboard');

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

        $pipeline_total = (float) $total_mandate_value;
        $highest_single_mandate = DebitOrder::filterCondition()
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', 1);
            })
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->max('total_amount') ?? 0;

        $mandate_pipeline = [
            [
                'label' => 'Total Mandate',
                'amount' => (float) $stats['total_mandate_value'],
                'percent' => 100,
                'tone' => 'primary',
            ],
            [
                'label' => 'Approved',
                'amount' => (float) $stats['approved_mandate_value'],
                'percent' => $pipeline_total > 0 ? round(((float) $stats['approved_mandate_value'] / $pipeline_total) * 100, 1) : 0,
                'tone' => 'success',
            ],
            [
                'label' => 'Pending',
                'amount' => (float) $stats['pending_mandate_value'],
                'percent' => $pipeline_total > 0 ? round(((float) $stats['pending_mandate_value'] / $pipeline_total) * 100, 1) : 0,
                'tone' => 'warning',
            ],
            [
                'label' => 'Failed',
                'amount' => (float) $stats['failed_mandate_value'],
                'percent' => $pipeline_total > 0 ? round(((float) $stats['failed_mandate_value'] / $pipeline_total) * 100, 1) : 0,
                'tone' => 'danger',
            ],
        ];

        $channelBaseQuery = Installment::filterCondition()
            ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
            ->where('debit_orders.approved', 1)
            ->whereHas('debitOrder', function ($query) {
                $query->whereHas('partner', function ($q) {
                    $q->where('partners.approved', 1);
                });
            });

        $expectedByChannel = (clone $channelBaseQuery)
            ->select([
                'debit_orders.channel',
                DB::raw("COALESCE(SUM(CASE
                    WHEN MONTH(installments.payment_date) = {$current_month}
                    AND YEAR(installments.payment_date) = {$current_year}
                    THEN installments.installment_amount ELSE 0 END), 0) as expected_amount"),
            ])
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->groupBy('debit_orders.channel')
            ->pluck('expected_amount', 'debit_orders.channel');

        $paidByChannel = (clone $channelBaseQuery)
            ->select([
                'debit_orders.channel',
                DB::raw("COALESCE(SUM(CASE
                    WHEN MONTH(installments.effective_payment_date) = {$current_month}
                    AND YEAR(installments.effective_payment_date) = {$current_year}
                    AND installments.status IN ('CLOSED', 'PARTIAL_PAID')
                    THEN installments.current_balance ELSE 0 END), 0) as paid_amount"),
            ])
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->groupBy('debit_orders.channel')
            ->pluck('paid_amount', 'debit_orders.channel');

        $mandateCountByChannel = DebitOrder::filterCondition()
            ->select([
                'debit_orders.channel',
                DB::raw('COUNT(debit_orders.id) as mandates'),
            ])
            ->where('debit_orders.approved', 1)
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', 1);
            })
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->groupBy('debit_orders.channel')
            ->pluck('mandates', 'debit_orders.channel');

        $bank_channel_performance = $payment_channels
            ->when($channel != null, function ($collection) use ($channel) {
                return $collection->where('slug', $channel);
            })
            ->map(function ($paymentChannel) use ($expectedByChannel, $paidByChannel, $mandateCountByChannel) {
                $slug = $paymentChannel->slug;
                $expected = (float) ($expectedByChannel[$slug] ?? 0);
                $paid = (float) ($paidByChannel[$slug] ?? 0);
                $mandates = (int) ($mandateCountByChannel[$slug] ?? 0);
                $rate = $expected > 0 ? round(($paid / $expected) * 100) : null;
                $isLive = $expected > 0 || $paid > 0;

                return [
                    'slug' => strtoupper(substr((string) $slug, 0, 3)),
                    'name' => $paymentChannel->name,
                    'subtitle' => $isLive
                        ? 'Monthly success · ' . number_format($mandates) . ' mandates'
                        : 'Onboarding',
                    'rate' => $rate,
                    'status' => $isLive ? $rate . '%' : 'Soon',
                    'bar_width' => $isLive ? max(min($rate, 100), 8) : 12,
                    'tone' => $isLive ? ($rate >= 60 ? 'success' : ($rate >= 35 ? 'warning' : 'danger')) : 'muted',
                ];
            })
            ->values();

        //post data
        $post_data = ['year' => $current_year, 'month' => $current_month, 'channel' => $channel];

        //render view
        return view('dashboard', compact(
            'title',
            'stats',
            'payment_channels',
            'form_action',
            'post_data',
            'mandate_pipeline',
            'highest_single_mandate',
            'bank_channel_performance'
        ));
    }
}
