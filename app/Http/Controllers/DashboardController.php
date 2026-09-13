<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Partner;
use App\Services\DashboardReportingQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(private DashboardReportingQueryService $reporting)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $title = 'Dashboard';
        $user = Auth::user();
        $selected_year = $request->filled('year') ? (int) $request->year : null;
        $current_year = (int) date('Y');
        $current_month = (int) date('m');
        $collection_year = $selected_year ?? $current_year;
        $channel = $request->channel;
        $filters = ['channel' => $channel];
        $now = now();
        $selectedDay = min($now->day, Carbon::create($collection_year, $now->month, 1)->daysInMonth);
        $selectedToday = Carbon::create($collection_year, $now->month, $selectedDay)->toDateString();

        $mandateRows = $this->reporting->mandates($user, $filters)
            ->select('mandate_approved')
            ->selectRaw('COUNT(debit_order_id) as number_of_mandate')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->whereIn('mandate_approved', [0, 1, 2])
            ->when($selected_year, fn ($query, $year) => $query->where('mandate_created_year', $year))
            ->groupBy('mandate_approved')
            ->get()
            ->keyBy('mandate_approved');

        $mandates = collect([0, 1, 2])->mapWithKeys(function ($status) use ($mandateRows) {
            $row = $mandateRows->get($status);

            return [$status => [
                'number_of_mandate' => (int) ($row->number_of_mandate ?? 0),
                'total_amount' => (float) ($row->total_amount ?? 0),
            ]];
        });

        $monthExpected = $this->reporting->expectedCollections($user, $filters)
            ->where('expected_year', $collection_year)
            ->where('expected_month', $current_month)
            ->selectRaw('COUNT(installment_id) as total_collections')
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
            ->first();

        $monthPaid = $this->reporting->paymentCollections($user, $filters)
            ->where('collected_year', $collection_year)
            ->where('collected_month', $current_month)
            ->selectRaw('COUNT(settlement_id) as successful_collections')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->first();

        $todayExpected = $this->reporting->expectedCollections($user, $filters)
            ->whereDate('expected_date', $selectedToday)
            ->selectRaw('COUNT(installment_id) as total_collections')
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
            ->first();

        $todayPaid = $this->reporting->paymentCollections($user, $filters)
            ->whereDate('collected_date', $selectedToday)
            ->selectRaw('COUNT(settlement_id) as successful_collections')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->first();

        $stats = [
            'number_of_pending_mandate' => $mandates[0]['number_of_mandate'],
            'number_of_approved_mandate' => $mandates[1]['number_of_mandate'],
            'number_of_failed_mandate' => $mandates[2]['number_of_mandate'],
            'total_number_of_mandate' => $mandates->sum('number_of_mandate'),
            'pending_mandate_value' => $mandates[0]['total_amount'],
            'approved_mandate_value' => $mandates[1]['total_amount'],
            'failed_mandate_value' => $mandates[2]['total_amount'],
            'total_mandate_value' => $mandates->sum('total_amount'),
            'month' => Carbon::create($collection_year, $current_month, 1)->format('F'),
            'this_month_total_collections' => (int) ($monthExpected->total_collections ?? 0),
            'this_month_expected' => (float) ($monthExpected->total_amount ?? 0),
            'this_month_successful_collections' => (int) ($monthPaid->successful_collections ?? 0),
            'this_month_paid' => (float) ($monthPaid->paid_amount ?? 0),
            'today_total_collections' => (int) ($todayExpected->total_collections ?? 0),
            'today_expected' => (float) ($todayExpected->total_amount ?? 0),
            'today_successful_collections' => (int) ($todayPaid->successful_collections ?? 0),
            'today_paid' => (float) ($todayPaid->paid_amount ?? 0),
        ];

        $payment_channels = Account::where('active', 1)->get();
        $form_action = route('dashboard');
        $cacheKey = implode(':', [
            'dashboard',
            'supplementary-v6',
            $user->id,
            $channel ?: 'all',
            $selected_year ?: 'all-years',
            $current_month,
        ]);

        $dashboardSupplementary = Cache::remember($cacheKey, now()->addMinutes(5), function () use (
            $user,
            $filters,
            $selected_year,
            $collection_year,
            $current_month
        ) {
            $expectedSummary = $this->reporting->expectedCollections($user, $filters)
                ->when($selected_year, fn ($query, $year) => $query->where('expected_year', $year))
                ->selectRaw('COALESCE(SUM(installment_amount), 0) as expected_amount')
                ->first();

            $paidSummary = $this->reporting->paymentCollections($user, $filters)
                ->when($selected_year, fn ($query, $year) => $query->where('collected_year', $year))
                ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
                ->first();

            $expectedByChannel = $this->reporting->expectedCollections($user, $filters)
                ->where('expected_year', $collection_year)
                ->where('expected_month', $current_month)
                ->select('channel')
                ->selectRaw('COUNT(installment_id) as expected_count')
                ->selectRaw('COALESCE(SUM(installment_amount), 0) as expected_amount')
                ->groupBy('channel')
                ->get()
                ->keyBy(fn ($row) => $this->normalizeChannelLookupKey($row->channel));

            $paidByChannel = $this->reporting->paymentCollections($user, $filters)
                ->where('collected_year', $collection_year)
                ->where('collected_month', $current_month)
                ->select('channel')
                ->selectRaw('COUNT(settlement_id) as collection_count')
                ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
                ->groupBy('channel')
                ->get()
                ->keyBy(fn ($row) => $this->normalizeChannelLookupKey($row->channel));

            $channelKeys = $expectedByChannel->keys()->merge($paidByChannel->keys())->unique();
            $channelAmounts = $channelKeys->mapWithKeys(fn ($key) => [$key => [
                'expected_count' => (int) ($expectedByChannel->get($key)->expected_count ?? 0),
                'collection_count' => (int) ($paidByChannel->get($key)->collection_count ?? 0),
                'expected_amount' => (float) ($expectedByChannel->get($key)->expected_amount ?? 0),
                'paid_amount' => (float) ($paidByChannel->get($key)->paid_amount ?? 0),
            ]])->all();

            return [
                'year_summary' => [
                    'expected_amount' => (float) ($expectedSummary->expected_amount ?? 0),
                    'paid_amount' => (float) ($paidSummary->paid_amount ?? 0),
                ],
                'service_provider_count' => Partner::filterCondition()->count(),
                'active_service_provider_count' => Partner::filterCondition()->where('approved', 1)->count(),
                'highest_single_mandate' => (float) ($this->reporting->mandates($user, $filters)
                    ->when($selected_year, fn ($query, $year) => $query->where('mandate_created_year', $year))
                    ->max('total_amount') ?? 0),
                'channel_amounts' => $channelAmounts,
            ];
        });

        $year_summary = (object) $dashboardSupplementary['year_summary'];
        $year_success_rate = $year_summary->expected_amount > 0
            ? round(($year_summary->paid_amount / $year_summary->expected_amount) * 100, 2)
            : 0;
        $service_provider_count = (int) $dashboardSupplementary['service_provider_count'];
        $active_service_provider_count = (int) $dashboardSupplementary['active_service_provider_count'];
        $highest_single_mandate = (float) $dashboardSupplementary['highest_single_mandate'];
        $pipelineTotal = (float) $stats['total_mandate_value'];

        $mandate_pipeline = collect([
            ['label' => 'Total Mandates', 'amount' => $stats['total_mandate_value'], 'count' => $stats['total_number_of_mandate'], 'tone' => 'primary', 'is_total' => true],
            ['label' => 'Approved', 'amount' => $stats['approved_mandate_value'], 'count' => $stats['number_of_approved_mandate'], 'tone' => 'success'],
            ['label' => 'Pending', 'amount' => $stats['pending_mandate_value'], 'count' => $stats['number_of_pending_mandate'], 'tone' => 'warning'],
            ['label' => 'Failed', 'amount' => $stats['failed_mandate_value'], 'count' => $stats['number_of_failed_mandate'], 'tone' => 'danger'],
        ])->map(function ($item) use ($pipelineTotal) {
            $item['percent'] = ($item['is_total'] ?? false)
                ? 100
                : ($pipelineTotal > 0 ? round(($item['amount'] / $pipelineTotal) * 100, 1) : 0);
            unset($item['is_total']);

            return $item;
        })->all();

        $bank_channel_performance = $payment_channels
            ->when($channel, fn ($items) => $items->where('slug', $channel))
            ->map(function ($paymentChannel) use ($dashboardSupplementary) {
                $key = $this->normalizeChannelLookupKey($paymentChannel->slug ?: $paymentChannel->name);
                $expected = (float) ($dashboardSupplementary['channel_amounts'][$key]['expected_amount'] ?? 0);
                $paid = (float) ($dashboardSupplementary['channel_amounts'][$key]['paid_amount'] ?? 0);
                $expectedCount = (int) ($dashboardSupplementary['channel_amounts'][$key]['expected_count'] ?? 0);
                $collectionCount = (int) ($dashboardSupplementary['channel_amounts'][$key]['collection_count'] ?? 0);
                $rate = $expected > 0 ? round(($paid / $expected) * 100, 1) : null;
                $isLive = $expected > 0 || $paid > 0;

                return [
                    'slug' => strtoupper(substr((string) ($paymentChannel->slug ?: $paymentChannel->name), 0, 3)),
                    'logo' => $paymentChannel->logo ? asset($paymentChannel->logo) : null,
                    'name' => $paymentChannel->name,
                    'expected_count' => $expectedCount,
                    'collection_count' => $collectionCount,
                    'expected_amount' => $expected,
                    'paid_amount' => $paid,
                    'rate' => $rate,
                    'status' => $isLive ? $rate . '%' : 'No data',
                    'bar_width' => $isLive ? max(min((float) $rate, 100), 4) : 0,
                    'tone' => $isLive ? ($rate >= 60 ? 'success' : ($rate >= 35 ? 'warning' : 'danger')) : 'muted',
                ];
            })
            ->values();

        $post_data = ['year' => $collection_year, 'month' => $current_month, 'channel' => $channel];
        $dataFreshAt = $this->reporting->lastUpdatedAt();

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
            'active_service_provider_count',
            'dataFreshAt'
        ));
    }

    private function normalizeChannelLookupKey(?string $value): string
    {
        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));

        return $normalized ?: 'UNKNOWN';
    }
}
