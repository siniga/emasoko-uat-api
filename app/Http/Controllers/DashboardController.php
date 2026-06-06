<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        $selected_year = $request->filled('year') ? (int) $request->year : null;
        $current_year = (int) date('Y');
        $current_month = date('m');
        $channel = $request->channel;
        $today = now();
        $collection_year = $selected_year ?? $current_year;
        $selected_day = min((int) $today->day, Carbon::create($collection_year, (int) $today->month, 1)->daysInMonth);
        $selected_today = Carbon::create($collection_year, (int) $today->month, $selected_day)->toDateString();

        //query
        $debit_orders = DebitOrder::mandateApprovalStats($channel, $selected_year);

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
        $this_month = Installment::thisMonthStats($current_month, $collection_year, $channel);

        // Today’s Collection
        $today = Installment::todayStats($selected_today, $channel);

        //form action
        $payment_channels = Account::where('active', '=', 1)->get();
        $form_action = route('dashboard');
        $userId = auth()->id() ?? 0;

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
            'month' => date('F', mktime(0, 0, 0, (int) $current_month, 1)),
            'this_month_total_collections' => $this_month->total_collections,
            'this_month_expected' => $this_month->total_amount,
            'this_month_successful_collections' => $this_month->successful_collections,
            'this_month_paid' => $this_month->paid_amount,

            'today_total_collections' => $today->total_collections,
            'today_expected' => $today->total_amount,
            'today_successful_collections' => $today->successful_collections,
            'today_paid' => $today->paid_amount
        ];

        $dashboardSupplementary = Cache::remember(
            "dashboard:supplementary:{$userId}:" . ($channel ?: 'all') . ':' . ($selected_year ?: 'all-years') . ":{$current_month}",
            now()->addMinutes(5),
            function () use ($channel, $selected_year, $current_month, $collection_year) {
                $yearSummaryQuery = Installment::filterCondition()
                    ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
                    ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
                    ->where('debit_orders.approved', 1)
                    ->where('partners.approved', 1)
                    ->when($channel != null, function ($query) use ($channel) {
                        $query->where('debit_orders.channel', $channel);
                    });

                $year_summary = (clone $yearSummaryQuery)
                    ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as expected_amount')
                    ->selectRaw("COALESCE(SUM(CASE
                        WHEN installments.status IN ('CLOSED', 'PARTIAL_PAID')
                        THEN installments.current_balance ELSE 0 END), 0) as paid_amount")
                    ->when($selected_year != null, function ($query) use ($selected_year) {
                        $query->whereYear('installments.payment_date', $selected_year);
                    })
                    ->first();

                $service_provider_count = Partner::filterCondition()->count();

                $active_service_provider_count = Partner::filterCondition()
                    ->where('approved', 1)
                    ->count();

                $highest_single_mandate = DebitOrder::filterCondition()
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    })
                    ->when($channel != null, function ($q) use ($channel) {
                        $q->where('debit_orders.channel', $channel);
                    })
                    ->max('total_amount') ?? 0;

                $channelAmounts = Installment::filterCondition()
                    ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
                    ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
                    ->where('debit_orders.approved', 1)
                    ->where('partners.approved', 1)
                    ->when($channel != null, function ($q) use ($channel) {
                        $q->where('debit_orders.channel', $channel);
                    })
                    ->select([
                        'debit_orders.channel',
                        DB::raw("COALESCE(SUM(CASE
                            WHEN MONTH(installments.payment_date) = {$current_month}
                            AND YEAR(installments.payment_date) = {$collection_year}
                            THEN installments.installment_amount ELSE 0 END), 0) as expected_amount"),
                        DB::raw("COALESCE(SUM(CASE
                            WHEN MONTH(installments.effective_payment_date) = {$current_month}
                            AND YEAR(installments.effective_payment_date) = {$collection_year}
                            AND installments.status IN ('CLOSED', 'PARTIAL_PAID')
                            THEN installments.current_balance ELSE 0 END), 0) as paid_amount"),
                    ])
                    ->groupBy('debit_orders.channel')
                    ->get()
                    ->keyBy('channel');

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
                    ->get()
                    ->pluck('mandates', 'channel');

                $channelAmounts = $channelAmounts
                    ->mapWithKeys(function ($row, $channelKey) {
                        return [
                            $this->normalizeChannelLookupKey($channelKey) => [
                                'expected_amount' => (float) ($row->expected_amount ?? 0),
                                'paid_amount' => (float) ($row->paid_amount ?? 0),
                            ],
                        ];
                    })
                    ->toArray();

                $mandateCountByChannel = $mandateCountByChannel
                    ->mapWithKeys(function ($value, $channelKey) {
                        return [$this->normalizeChannelLookupKey($channelKey) => (int) $value];
                    })
                    ->toArray();

                return [
                    'year_summary' => [
                        'expected_amount' => (float) ($year_summary->expected_amount ?? 0),
                        'paid_amount' => (float) ($year_summary->paid_amount ?? 0),
                    ],
                    'service_provider_count' => (int) $service_provider_count,
                    'active_service_provider_count' => (int) $active_service_provider_count,
                    'highest_single_mandate' => (float) $highest_single_mandate,
                    'channel_amounts' => $channelAmounts,
                    'channel_mandates' => $mandateCountByChannel,
                ];
            }
        );

        $year_summary = (object) $dashboardSupplementary['year_summary'];
        $year_success_rate = (float) ($year_summary->expected_amount ?? 0) > 0
            ? round((((float) $year_summary->paid_amount) / ((float) $year_summary->expected_amount)) * 100, 2)
            : 0;
        $service_provider_count = (int) ($dashboardSupplementary['service_provider_count'] ?? 0);
        $active_service_provider_count = (int) ($dashboardSupplementary['active_service_provider_count'] ?? 0);
        $pipeline_total = (float) $total_mandate_value;
        $highest_single_mandate = (float) $dashboardSupplementary['highest_single_mandate'];

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

        $bank_channel_performance = $payment_channels
            ->when($channel != null, function ($collection) use ($channel) {
                return $collection->where('slug', $channel);
            })
            ->map(function ($paymentChannel) use ($dashboardSupplementary) {
                $lookupKey = $this->normalizeChannelLookupKey($paymentChannel->slug ?: $paymentChannel->name);
                $expected = (float) ($dashboardSupplementary['channel_amounts'][$lookupKey]['expected_amount'] ?? 0);
                $paid = (float) ($dashboardSupplementary['channel_amounts'][$lookupKey]['paid_amount'] ?? 0);
                $mandates = (int) ($dashboardSupplementary['channel_mandates'][$lookupKey] ?? 0);
                $rate = $expected > 0 ? round(($paid / $expected) * 100) : null;
                $isLive = $expected > 0 || $paid > 0;

                return [
                    'slug' => strtoupper(substr((string) ($paymentChannel->slug ?: $paymentChannel->name), 0, 3)),
                    'logo' => $paymentChannel->logo ? asset($paymentChannel->logo) : null,
                    'name' => $paymentChannel->name,
                    'subtitle' => $isLive
                        ? 'Monthly success · ' . number_format($mandates) . ' mandates · TZS ' . number_format($paid, 0)
                        : 'Onboarding',
                    'rate' => $rate,
                    'status' => $isLive ? $rate . '%' : 'Soon',
                    'bar_width' => $isLive ? max(min($rate, 100), 8) : 12,
                    'tone' => $isLive ? ($rate >= 60 ? 'success' : ($rate >= 35 ? 'warning' : 'danger')) : 'muted',
                ];
            })
            ->values();

        //post data
        $post_data = ['year' => $selected_year ?? $current_year, 'month' => $current_month, 'channel' => $channel];

        //render view
        return view('dashboard', compact(
            'title',
            'stats',
            'payment_channels',
            'form_action',
            'post_data',
            'mandate_pipeline',
            'highest_single_mandate',
            'bank_channel_performance',
            'year_summary',
            'year_success_rate',
            'current_year',
            'service_provider_count',
            'active_service_provider_count'
        ));
    }

    private function normalizeChannelLookupKey(?string $value): string
    {
        $normalized = strtoupper((string) $value);
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);

        return $normalized ?: 'UNKNOWN';
    }
}
