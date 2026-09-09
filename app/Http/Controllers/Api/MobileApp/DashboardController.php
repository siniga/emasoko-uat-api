<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\MandateResource;
use App\Models\Account;
use App\Models\CompanyUser;
use App\Models\DebitOrder;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use RespondsWithApi;

    public function index(Request $request): JsonResponse
    {
        $filters = $this->filters($request);
        $selectedYear = $request->filled('year') ? $filters['year'] : null;
        $collectionYear = $selectedYear ?? now()->year;
        $selectedToday = $this->selectedToday($collectionYear);

        $mandateStats = $this->mandateStats($filters, $selectedYear);
        $collectionStats = $this->collectionStats($filters, $collectionYear, $filters['month'], $selectedToday);
        $yearSummary = $this->yearCollectionSummary($filters, $selectedYear);
        $yearSuccessRate = $this->percentage($yearSummary['paid_amount'], $yearSummary['expected_amount']);
        $paymentChannels = Account::where('active', 1)->orderBy('name')->get();

        $recentMandates = DebitOrder::filterCondition(includeDeleted: true)
            ->with([
                'customerAccount:id,account_name,account_number,phone,customer_id,account_id',
                'customerAccount.customer:id,id_number',
                'customerAccount.account:id,name',
            ])
            ->whereHas('partner', function ($query) {
                $query->where('partners.approved', 1);
            })
            ->when($filters['channel'], function ($query, $channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->when($filters['partner_id'], function ($query, $partnerId) {
                $query->where('debit_orders.partner_id', $partnerId);
            })
            ->when($filters['corporate_reference'], function ($query, $reference) {
                $query->whereHas('partner', function ($partnerQuery) use ($reference) {
                    $partnerQuery->where('partners.reference', $reference);
                });
            })
            ->when($filters['keyword'], function ($query, $keyword) {
                $lowered = strtolower($keyword);

                $query->where(function ($searchQuery) use ($lowered) {
                    $searchQuery->whereRaw('LOWER(debit_orders.reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereHas('customerAccount', function ($accountQuery) use ($lowered) {
                            $accountQuery->whereRaw('LOWER(account_name) LIKE ?', ["%{$lowered}%"])
                                ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$lowered}%"]);
                        });
                });
            })
            ->orderBy('debit_orders.created_at', 'DESC')
            ->limit(5)
            ->get();

        return $this->successResponse('Dashboard fetched successfully.', [
            'filters' => [
                'year' => $selectedYear ?? now()->year,
                'month' => $filters['month'],
                'channel' => $filters['channel'],
                'payment_channel' => $filters['channel'],
                'partner_id' => $filters['partner_id'],
                'corporate_reference' => $filters['corporate_reference'],
                'mandate_type' => $filters['mandate_type'],
                'keyword' => $filters['keyword'],
                'date' => $selectedToday,
            ],
            'stats' => array_merge($mandateStats, $collectionStats),
            'mandate_pipeline' => $this->mandatePipeline($mandateStats),
            'year_summary' => $yearSummary,
            'year_success_rate' => $yearSuccessRate,
            'service_providers' => [
                'total' => Partner::filterCondition()->count(),
                'active' => Partner::filterCondition()->where('approved', 1)->count(),
            ],
            'highest_single_mandate' => $this->highestSingleMandate($filters),
            'bank_channel_performance' => $this->bankChannelPerformance($paymentChannels, $filters, $collectionYear, $filters['month']),
            'charts' => [
                'mandate_values' => $this->mandateValuesChart($filters, $collectionYear),
                'mandate_percentages' => $this->mandatePercentages($filters),
                'collected_mandates' => $this->collectedMandatesChart($filters, $collectionYear),
                'weekly_collections' => $this->weeklyCollectionsChart($filters, $filters['date']),
                'mandates_by_channel' => $this->mandatesByChannel($paymentChannels, $filters, $filters['month'], $collectionYear),
            ],
            'payment_channels' => $paymentChannels->map(function ($channel) {
                return [
                    'id' => $channel->id,
                    'name' => $channel->name,
                    'slug' => $channel->slug,
                    'logo' => $channel->logo ? asset($channel->logo) : null,
                ];
            })->values(),
            'recent_mandates' => MandateResource::collection($recentMandates),
        ]);
    }

    protected function filters(Request $request): array
    {
        return [
            'year' => (int) $request->input('year', now()->year),
            'month' => (int) $request->input('month', now()->month),
            'channel' => $request->input('payment_channel', $request->input('channel')),
            'partner_id' => $request->input('partner_id'),
            'corporate_reference' => $request->input('corporate_reference'),
            'mandate_type' => $request->input('mandate_type'),
            'collection_status' => $request->input('collection_status', $request->input('status')),
            'keyword' => $request->input('keyword'),
            'date' => $request->filled('date')
                ? Carbon::parse($request->input('date'))->toDateString()
                : now()->toDateString(),
        ];
    }

    protected function mandateStats(array $filters, ?int $year): array
    {
        $rows = $this->mandateViewQuery($filters)
            ->select('mandate_approved')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->selectRaw('COUNT(debit_order_id) as number_of_mandate')
            ->whereIn('mandate_approved', [0, 1, 2])
            ->when($year !== null, function ($query) use ($year) {
                $query->whereYear('created_at', $year);
            })
            ->groupBy('mandate_approved')
            ->orderBy('mandate_approved')
            ->get();

        $summary = collect([0, 1, 2])->mapWithKeys(function ($status) use ($rows) {
            $row = $rows->firstWhere('mandate_approved', $status);

            return [$status => [
                'count' => (int) ($row->number_of_mandate ?? 0),
                'amount' => (float) ($row->total_amount ?? 0),
            ]];
        });

        return [
            'number_of_pending_mandate' => $summary[0]['count'],
            'number_of_approved_mandate' => $summary[1]['count'],
            'number_of_failed_mandate' => $summary[2]['count'],
            'total_number_of_mandate' => $summary->sum('count'),
            'pending_mandate_value' => $summary[0]['amount'],
            'approved_mandate_value' => $summary[1]['amount'],
            'failed_mandate_value' => $summary[2]['amount'],
            'total_mandate_value' => $summary->sum('amount'),
        ];
    }

    protected function collectionStats(array $filters, int $year, int $month, string $todayDate): array
    {
        $monthStats = $this->collectionViewQuery($filters)
            ->selectRaw('COUNT(CASE WHEN MONTH(payment_date) = ? AND YEAR(payment_date) = ? THEN 1 END) as total_collections', [$month, $year])
            ->selectRaw('COALESCE(SUM(CASE WHEN MONTH(payment_date) = ? AND YEAR(payment_date) = ? THEN installment_amount ELSE 0 END), 0) as total_amount', [$month, $year])
            ->selectRaw('COUNT(CASE WHEN MONTH(effective_payment_date) = ? AND YEAR(effective_payment_date) = ? AND collection_status IN (?, ?) THEN 1 END) as successful_collections', [$month, $year, ...$this->paidStatuses()])
            ->selectRaw('COALESCE(SUM(CASE WHEN MONTH(effective_payment_date) = ? AND YEAR(effective_payment_date) = ? AND collection_status IN (?, ?) THEN current_balance ELSE 0 END), 0) as paid_amount', [$month, $year, ...$this->paidStatuses()])
            ->first();

        $todayStats = $this->collectionViewQuery($filters)
            ->selectRaw('COUNT(CASE WHEN DATE(payment_date) = ? THEN 1 END) as total_collections', [$todayDate])
            ->selectRaw('COALESCE(SUM(CASE WHEN DATE(payment_date) = ? THEN installment_amount ELSE 0 END), 0) as total_amount', [$todayDate])
            ->selectRaw('COUNT(CASE WHEN DATE(effective_payment_date) = ? AND collection_status IN (?, ?) THEN 1 END) as successful_collections', [$todayDate, ...$this->paidStatuses()])
            ->selectRaw('COALESCE(SUM(CASE WHEN DATE(effective_payment_date) = ? AND collection_status IN (?, ?) THEN current_balance ELSE 0 END), 0) as paid_amount', [$todayDate, ...$this->paidStatuses()])
            ->first();

        return [
            'month' => Carbon::create($year, $month, 1)->format('F'),
            'this_month_total_collections' => (int) ($monthStats->total_collections ?? 0),
            'this_month_expected' => (float) ($monthStats->total_amount ?? 0),
            'this_month_successful_collections' => (int) ($monthStats->successful_collections ?? 0),
            'this_month_paid' => (float) ($monthStats->paid_amount ?? 0),
            'today_total_collections' => (int) ($todayStats->total_collections ?? 0),
            'today_expected' => (float) ($todayStats->total_amount ?? 0),
            'today_successful_collections' => (int) ($todayStats->successful_collections ?? 0),
            'today_paid' => (float) ($todayStats->paid_amount ?? 0),
        ];
    }

    protected function yearCollectionSummary(array $filters, ?int $year): array
    {
        $query = $this->collectionViewQuery($filters);

        if ($year !== null) {
            $summary = $query
                ->selectRaw('COALESCE(SUM(CASE WHEN YEAR(payment_date) = ? THEN installment_amount ELSE 0 END), 0) as expected_amount', [$year])
                ->selectRaw('COALESCE(SUM(CASE WHEN YEAR(effective_payment_date) = ? AND collection_status IN (?, ?) THEN current_balance ELSE 0 END), 0) as paid_amount', [$year, ...$this->paidStatuses()])
                ->first();
        } else {
            $summary = $query
                ->selectRaw('COALESCE(SUM(installment_amount), 0) as expected_amount')
                ->selectRaw('COALESCE(SUM(CASE WHEN collection_status IN (?, ?) THEN current_balance ELSE 0 END), 0) as paid_amount', $this->paidStatuses())
                ->first();
        }

        return [
            'expected_amount' => (float) ($summary->expected_amount ?? 0),
            'paid_amount' => (float) ($summary->paid_amount ?? 0),
        ];
    }

    protected function mandatePipeline(array $stats): array
    {
        $total = (float) ($stats['total_mandate_value'] ?? 0);

        return [
            ['label' => 'Total Mandate', 'amount' => (float) $stats['total_mandate_value'], 'percent' => 100, 'tone' => 'primary'],
            ['label' => 'Approved', 'amount' => (float) $stats['approved_mandate_value'], 'percent' => $this->percentage($stats['approved_mandate_value'], $total, 1), 'tone' => 'success'],
            ['label' => 'Pending', 'amount' => (float) $stats['pending_mandate_value'], 'percent' => $this->percentage($stats['pending_mandate_value'], $total, 1), 'tone' => 'warning'],
            ['label' => 'Failed', 'amount' => (float) $stats['failed_mandate_value'], 'percent' => $this->percentage($stats['failed_mandate_value'], $total, 1), 'tone' => 'danger'],
        ];
    }

    protected function highestSingleMandate(array $filters): float
    {
        return (float) ($this->mandateViewQuery($filters)->max('total_amount') ?? 0);
    }

    protected function bankChannelPerformance($paymentChannels, array $filters, int $year, int $month)
    {
        $amounts = $this->collectionViewQuery($filters)
            ->select('channel')
            ->selectRaw('COALESCE(SUM(CASE WHEN MONTH(payment_date) = ? AND YEAR(payment_date) = ? THEN installment_amount ELSE 0 END), 0) as expected_amount', [$month, $year])
            ->selectRaw('COALESCE(SUM(CASE WHEN MONTH(effective_payment_date) = ? AND YEAR(effective_payment_date) = ? AND collection_status IN (?, ?) THEN current_balance ELSE 0 END), 0) as paid_amount', [$month, $year, ...$this->paidStatuses()])
            ->groupBy('channel')
            ->get()
            ->keyBy(fn ($row) => $this->normalizeChannelLookupKey($row->channel));

        $mandates = $this->mandateViewQuery($filters)
            ->select('channel')
            ->selectRaw('COUNT(debit_order_id) as mandates')
            ->where('mandate_approved', 1)
            ->groupBy('channel')
            ->get()
            ->keyBy(fn ($row) => $this->normalizeChannelLookupKey($row->channel));

        return $paymentChannels
            ->when($filters['channel'], fn ($items) => $items->where('slug', $filters['channel']))
            ->map(function ($paymentChannel) use ($amounts, $mandates) {
                $lookupKey = $this->normalizeChannelLookupKey($paymentChannel->slug ?: $paymentChannel->name);
                $expected = (float) data_get($amounts, "{$lookupKey}.expected_amount", 0);
                $paid = (float) data_get($amounts, "{$lookupKey}.paid_amount", 0);
                $rate = $expected > 0 ? round(($paid / $expected) * 100, 1) : null;
                $isLive = $expected > 0 || $paid > 0;

                return [
                    'slug' => strtoupper(substr((string) ($paymentChannel->slug ?: $paymentChannel->name), 0, 3)),
                    'logo' => $paymentChannel->logo ? asset($paymentChannel->logo) : null,
                    'name' => $paymentChannel->name,
                    'mandates' => (int) data_get($mandates, "{$lookupKey}.mandates", 0),
                    'expected_amount' => $expected,
                    'paid_amount' => $paid,
                    'rate' => $rate,
                    'status' => $isLive ? $rate . '%' : 'No data',
                    'bar_width' => $isLive ? max(min((float) $rate, 100), 4) : 0,
                    'tone' => $isLive ? ($rate >= 60 ? 'success' : ($rate >= 35 ? 'warning' : 'danger')) : 'muted',
                ];
            })
            ->values();
    }

    protected function mandateValuesChart(array $filters, int $year): array
    {
        $summaryStatuses = [DebitOrder::SUMMARY_PENDING, DebitOrder::SUMMARY_APPROVED, DebitOrder::SUMMARY_FAILED];
        $grouped = $this->mandateViewQuery($filters)
            ->select(DB::raw('MONTH(created_at) as month_number'), 'lifecycle_status')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->whereIn('lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'), 'lifecycle_status')
            ->get()
            ->groupBy('month_number');

        return collect(range(1, 12))->map(function ($month) use ($grouped, $summaryStatuses) {
            $monthRows = collect($grouped->get($month, []));
            $results = collect($summaryStatuses)->mapWithKeys(function ($summary) use ($monthRows) {
                return [$summary => (float) $monthRows
                    ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary($summary), true))
                    ->sum('total_amount')];
            });

            $pending = $results[DebitOrder::SUMMARY_PENDING] ?? 0;
            $approved = $results[DebitOrder::SUMMARY_APPROVED] ?? 0;
            $failed = $results[DebitOrder::SUMMARY_FAILED] ?? 0;

            return [
                'month' => Carbon::create(null, $month, 1)->format('M'),
                'total' => $pending + $approved + $failed,
                'approved' => $approved,
                'rejected' => $failed,
                'pending' => $pending,
            ];
        })->values()->all();
    }

    protected function mandatePercentages(array $filters): array
    {
        $rows = $this->mandateViewQuery($filters)
            ->select('lifecycle_status')
            ->selectRaw('COALESCE(COUNT(debit_order_id), 0) as number_of_approved')
            ->whereIn('lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->groupBy('lifecycle_status')
            ->get();

        $pending = $rows->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_PENDING), true))->sum('number_of_approved');
        $approved = $rows->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_APPROVED), true))->sum('number_of_approved');
        $failed = $rows->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_FAILED), true))->sum('number_of_approved');
        $total = max($pending + $approved + $failed, 1);

        return [
            'approved' => round(($approved / $total) * 100, 2),
            'rejected' => round(($failed / $total) * 100, 2),
            'pending' => round(($pending / $total) * 100, 2),
        ];
    }

    protected function collectedMandatesChart(array $filters, int $year): array
    {
        $expectedByMonth = $this->collectionViewQuery($filters)
            ->select(DB::raw('MONTH(payment_date) as month_number'))
            ->selectRaw('COALESCE(COUNT(installment_id), 0) as all_mandates')
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as expected_amount')
            ->whereYear('payment_date', $year)
            ->groupBy(DB::raw('MONTH(payment_date)'))
            ->get()
            ->keyBy('month_number');

        $paidByMonth = $this->collectionViewQuery($filters)
            ->select(DB::raw('MONTH(effective_payment_date) as month_number'))
            ->selectRaw('COALESCE(COUNT(installment_id), 0) as paid_mandates')
            ->selectRaw('COALESCE(SUM(current_balance), 0) as paid_amount')
            ->whereYear('effective_payment_date', $year)
            ->whereIn('collection_status', $this->paidStatuses())
            ->groupBy(DB::raw('MONTH(effective_payment_date)'))
            ->get()
            ->keyBy('month_number');

        return collect(range(1, 12))->map(function ($month) use ($expectedByMonth, $paidByMonth) {
            $expected = $expectedByMonth->get($month);
            $paid = $paidByMonth->get($month);

            return [
                'month' => Carbon::create(null, $month, 1)->format('M'),
                'total' => (int) ($expected->all_mandates ?? 0),
                'paid' => (int) ($paid->paid_mandates ?? 0),
                'total_amount' => (float) ($expected->expected_amount ?? 0),
                'paid_amount' => (float) ($paid->paid_amount ?? 0),
            ];
        })->values()->all();
    }

    protected function weeklyCollectionsChart(array $filters, string $date): array
    {
        $dates = $this->weekDates($date);
        $startDate = min($dates);
        $endDate = max($dates);

        $expectedByDate = $this->collectionViewQuery($filters)
            ->select(DB::raw('DATE(payment_date) as day_date'))
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->pluck('total_amount', 'day_date');

        $paidByDate = $this->collectionViewQuery($filters)
            ->select(DB::raw('DATE(effective_payment_date) as day_date'))
            ->selectRaw('COALESCE(SUM(current_balance), 0) as paid_amount')
            ->whereBetween('effective_payment_date', [$startDate, $endDate])
            ->whereIn('collection_status', $this->paidStatuses())
            ->groupBy(DB::raw('DATE(effective_payment_date)'))
            ->pluck('paid_amount', 'day_date');

        return collect($dates)->map(fn ($day) => [
            'date' => Carbon::parse($day)->format('d-m'),
            'full_date' => $day,
            'total' => (float) ($expectedByDate[$day] ?? 0),
            'paid' => (float) ($paidByDate[$day] ?? 0),
        ])->values()->all();
    }

    protected function mandatesByChannel($paymentChannels, array $filters, int $month, int $year): array
    {
        $summaryStatuses = [DebitOrder::SUMMARY_PENDING, DebitOrder::SUMMARY_APPROVED, DebitOrder::SUMMARY_FAILED];

        return $paymentChannels
            ->when($filters['channel'], fn ($items) => $items->where('slug', $filters['channel']))
            ->map(function ($account) use ($summaryStatuses, $filters, $month, $year) {
                $rows = $this->mandateViewQuery($filters)
                    ->select('lifecycle_status')
                    ->selectRaw('COALESCE(COUNT(debit_order_id), 0) as number_of_approved')
                    ->where('account_id', $account->id)
                    ->whereIn('lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
                    ->when($month, function ($query) use ($month, $year) {
                        $query->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year);
                    })
                    ->groupBy('lifecycle_status')
                    ->get();

                $results = collect($summaryStatuses)->mapWithKeys(function ($summary) use ($rows) {
                    return [$summary => (int) $rows
                        ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary($summary), true))
                        ->sum('number_of_approved')];
                });

                return [
                    'name' => $account->name,
                    'pending' => $results[DebitOrder::SUMMARY_PENDING] ?? 0,
                    'approved' => $results[DebitOrder::SUMMARY_APPROVED] ?? 0,
                    'rejected' => $results[DebitOrder::SUMMARY_FAILED] ?? 0,
                ];
            })
            ->values()
            ->all();
    }

    protected function mandateViewQuery(array $filters): QueryBuilder
    {
        $query = DB::table('vw_dashboard_mandates')
            ->where('partner_approved', 1);

        $this->applyUserScope($query);
        $this->applyDashboardFilters($query, $filters);

        return $query;
    }

    protected function collectionViewQuery(array $filters): QueryBuilder
    {
        $query = DB::table('vw_dashboard_collections')
            ->where('partner_approved', 1)
            ->where('mandate_approved', 1);

        $this->applyUserScope($query);
        $this->applyDashboardFilters($query, $filters);

        if (!empty($filters['collection_status'])) {
            $query->whereIn('collection_status', $this->normalizeCollectionStatuses($filters['collection_status']));
        }

        return $query;
    }

    protected function applyUserScope(QueryBuilder $query): void
    {
        $user = request()->user();

        if (!$user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('Partner')) {
            $companyId = CompanyUser::where('user_id', $user->id)->value('company_id');
            $companyId ? $query->where('company_id', $companyId) : $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('Company_Approver')) {
            $partnerIds = $user->partners
                ->where('authorize', 1)
                ->pluck('partner_id')
                ->values()
                ->all();

            $partnerIds ? $query->whereIn('partner_id', $partnerIds) : $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasRole('Company_User')) {
            $query->where('created_by', $user->id);
        }
    }

    protected function applyDashboardFilters(QueryBuilder $query, array $filters): void
    {
        $query->when($filters['channel'] ?? null, function ($query, $channel) {
            $query->where('channel', $channel);
        });

        $query->when($filters['partner_id'] ?? null, function ($query, $partnerId) {
            $query->where('partner_id', $partnerId);
        });

        $query->when($filters['corporate_reference'] ?? null, function ($query, $corporateReference) {
            $query->where('corporate_reference', $corporateReference);
        });

        $query->when($filters['mandate_type'] ?? null, function ($query, $mandateType) {
            $query->whereRaw('LOWER(mandate_type) = ?', [strtolower($mandateType)]);
        });

        $query->when($filters['keyword'] ?? null, function ($query, $keyword) {
            $lowered = strtolower($keyword);

            $query->where(function ($searchQuery) use ($lowered) {
                $searchQuery->whereRaw('LOWER(mandate_reference) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(corporate_reference) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(partner_name) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(account_name) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$lowered}%"])
                    ->orWhereRaw('LOWER(payment_reference) LIKE ?', ["%{$lowered}%"]);
            });
        });
    }

    protected function normalizeCollectionStatuses($status): array
    {
        return collect(is_array($status) ? $status : explode(',', (string) $status))
            ->flatMap(function ($value) {
                $normalized = strtoupper(trim((string) $value));

                return $normalized === 'COLLECTED' ? $this->paidStatuses() : [$normalized];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function paidStatuses(): array
    {
        return ['CLOSED', 'PARTIAL_PAID'];
    }

    protected function selectedToday(int $year): string
    {
        $today = now();
        $day = min((int) $today->day, Carbon::create($year, (int) $today->month, 1)->daysInMonth);

        return Carbon::create($year, (int) $today->month, $day)->toDateString();
    }

    protected function weekDates(string $currentDate): array
    {
        $dates = [];

        for ($i = 0; $i < 7; $i++) {
            $dates[] = Carbon::parse($currentDate)->subDays($i)->toDateString();
        }

        return array_reverse($dates);
    }

    protected function percentage(float $value, float $total, int $precision = 2): float
    {
        return $total > 0 ? round(($value / $total) * 100, $precision) : 0;
    }

    protected function normalizeChannelLookupKey(?string $value): string
    {
        $normalized = strtoupper((string) $value);
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);

        return $normalized ?: 'UNKNOWN';
    }
}
