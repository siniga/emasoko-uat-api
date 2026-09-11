<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Services\DashboardReportingQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardChartController extends Controller
{
    public function __construct(private DashboardReportingQueryService $reporting)
    {
        $this->middleware('auth');
    }

    public function mandate_values(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $filters = $this->filters($request);
        $grouped = $this->reporting->mandates($request->user(), $filters)
            ->select('mandate_created_month as month_number', 'lifecycle_status')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->whereIn('lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->where('mandate_created_year', $year)
            ->groupBy('mandate_created_month', 'lifecycle_status')
            ->get()
            ->groupBy('month_number');

        $chart = collect(range(1, 12))->map(function ($month) use ($grouped) {
            $rows = collect($grouped->get($month, []));
            $pending = $this->summaryAmount($rows, DebitOrder::SUMMARY_PENDING);
            $approved = $this->summaryAmount($rows, DebitOrder::SUMMARY_APPROVED);
            $failed = $this->summaryAmount($rows, DebitOrder::SUMMARY_FAILED);

            return [
                'month' => Carbon::create(null, $month, 1)->format('M'),
                'total' => (int) ($pending + $approved + $failed),
                'approved' => (int) $approved,
                'rejected' => (int) $failed,
                'pending' => (int) $pending,
            ];
        })->all();

        return response()->json(['error' => false, 'chart' => $chart]);
    }

    public function mandate_percentages(Request $request)
    {
        $rows = $this->reporting->mandates($request->user(), $this->filters($request))
            ->select('lifecycle_status')
            ->selectRaw('COUNT(debit_order_id) as number_of_mandate')
            ->whereIn('lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->groupBy('lifecycle_status')
            ->get();

        $pending = $this->summaryCount($rows, DebitOrder::SUMMARY_PENDING);
        $approved = $this->summaryCount($rows, DebitOrder::SUMMARY_APPROVED);
        $failed = $this->summaryCount($rows, DebitOrder::SUMMARY_FAILED);
        $total = max($pending + $approved + $failed, 1);

        return response()->json([
            'error' => false,
            'approved' => round(($approved / $total) * 100, 2),
            'rejected' => round(($failed / $total) * 100, 2),
            'pending' => round(($pending / $total) * 100, 2),
        ]);
    }

    public function collected_mandates(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $filters = $this->filters($request);

        $expected = $this->reporting->expectedCollections($request->user(), $filters)
            ->select('expected_month as month_number')
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as expected_amount')
            ->where('expected_year', $year)
            ->groupBy('expected_month')
            ->get()
            ->keyBy('month_number');

        $posted = $this->reporting->collectionPostings($request->user(), $filters)
            ->select('posted_month as month_number')
            ->selectRaw('COUNT(DISTINCT debit_order_id) as posted_mandates')
            ->where('posted_year', $year)
            ->groupBy('posted_month')
            ->get()
            ->keyBy('month_number');

        $paid = $this->reporting->paymentCollections($request->user(), $filters)
            ->select('collected_month as month_number')
            ->selectRaw('COUNT(DISTINCT debit_order_id) as deducted_mandates')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->where('collected_year', $year)
            ->groupBy('collected_month')
            ->get()
            ->keyBy('month_number');

        $chart = collect(range(1, 12))->map(function ($month) use ($expected, $posted, $paid) {
            $expectedMonth = $expected->get($month);
            $postedMonth = $posted->get($month);
            $paidMonth = $paid->get($month);
            $postedMandates = (int) ($postedMonth->posted_mandates ?? 0);
            $deductedMandates = (int) ($paidMonth->deducted_mandates ?? 0);

            return [
                'month' => Carbon::create(null, $month, 1)->format('M'),
                'posted' => $postedMandates,
                'deducted' => $deductedMandates,
                'total' => $postedMandates,
                'paid' => $deductedMandates,
                'total_amount' => (float) ($expectedMonth->expected_amount ?? 0),
                'paid_amount' => (float) ($paidMonth->paid_amount ?? 0),
            ];
        })->all();

        return response()->json(['error' => false, 'chart' => $chart]);
    }

    public function weekly_collected_mandates(Request $request)
    {
        $currentDate = $request->filled('curr_date')
            ? Carbon::parse($request->curr_date)->toDateString()
            : now()->toDateString();
        $dates = $this->get_dates($currentDate);
        $filters = $this->filters($request);

        $expected = $this->reporting->expectedCollections($request->user(), $filters)
            ->select('expected_date as day_date')
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
            ->whereBetween('expected_date', [min($dates), max($dates)])
            ->groupBy('expected_date')
            ->pluck('total_amount', 'day_date');

        $paid = $this->reporting->paymentCollections($request->user(), $filters)
            ->select('collected_date as day_date')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
            ->whereBetween('collected_date', [min($dates), max($dates)])
            ->groupBy('collected_date')
            ->pluck('paid_amount', 'day_date');

        $chart = collect($dates)->map(fn ($date) => [
            'date' => Carbon::parse($date)->format('d-m'),
            'total' => (float) ($expected[$date] ?? 0),
            'paid' => (float) ($paid[$date] ?? 0),
        ])->all();

        return response()->json(['error' => false, 'chart' => $chart]);
    }

    public function mandate_channel(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->month : null;
        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $filters = $this->filters($request);

        $chart = Account::query()->get()->map(function ($account) use ($request, $filters, $month, $year) {
            $rows = $this->reporting->mandates($request->user(), $filters)
                ->select('lifecycle_status')
                ->selectRaw('COUNT(debit_order_id) as number_of_mandate')
                ->where('account_id', $account->id)
                ->whereIn('lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
                ->when($month, function ($query) use ($month, $year) {
                    $query->where('mandate_created_month', $month)
                        ->where('mandate_created_year', $year);
                })
                ->groupBy('lifecycle_status')
                ->get();

            return [
                'name' => $account->name,
                'pending' => $this->summaryCount($rows, DebitOrder::SUMMARY_PENDING),
                'approved' => $this->summaryCount($rows, DebitOrder::SUMMARY_APPROVED),
                'rejected' => $this->summaryCount($rows, DebitOrder::SUMMARY_FAILED),
            ];
        })->all();

        return response()->json(['error' => false, 'chart' => $chart]);
    }

    public function monthly_collection_rate($uuid)
    {
        Partner::where('uuid', $uuid)->firstOrFail();

        return response()->json(['error' => false]);
    }

    public function get_dates($currentDate)
    {
        return collect(range(0, 6))
            ->map(fn ($offset) => Carbon::parse($currentDate)->subDays($offset)->toDateString())
            ->reverse()
            ->values()
            ->all();
    }

    private function filters(Request $request): array
    {
        return ['channel' => $request->filled('channel') ? $request->channel : null];
    }

    private function summaryAmount($rows, string $summary): float
    {
        return (float) collect($rows)
            ->filter(fn ($row) => in_array(
                $row->lifecycle_status,
                DebitOrder::lifecycleStatusesForSummary($summary),
                true
            ))
            ->sum('total_amount');
    }

    private function summaryCount($rows, string $summary): int
    {
        return (int) collect($rows)
            ->filter(fn ($row) => in_array(
                $row->lifecycle_status,
                DebitOrder::lifecycleStatusesForSummary($summary),
                true
            ))
            ->sum('number_of_mandate');
    }
}
