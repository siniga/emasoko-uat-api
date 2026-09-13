<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Models\Partner;
use App\Models\User;
use App\Services\DashboardReportingQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BIController extends Controller
{
    protected User $authenticatedUser;

    public function __construct(private DashboardReportingQueryService $reporting)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:' . (now()->year + 1)],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'channel' => ['nullable', 'string', 'exists:accounts,slug'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'recon_status' => ['nullable', 'in:COLLECTED,PARTIAL,FAILED,PENDING'],
            'active_tab' => ['nullable', 'in:growth,reconciliation'],
        ]);

        $this->authenticatedUser = $request->user();
        $title = 'BI Dashboard';
        $today = Carbon::today();
        $selectedYear = isset($validated['year']) ? (int) $validated['year'] : (int) $today->format('Y');
        $selectedMonth = isset($validated['month']) ? (int) $validated['month'] : null;
        $channel = $validated['channel'] ?? null;
        $selectedPartnerId = isset($validated['partner_id']) ? (int) $validated['partner_id'] : null;
        $selectedReconciliationStatus = $validated['recon_status'] ?? null;
        [$periodStart, $periodEnd] = $this->resolvePeriodBounds($selectedYear, $selectedMonth);
        [$comparisonStart, $comparisonEnd] = $this->resolveComparisonBounds(
            $periodStart,
            $periodEnd,
            $selectedMonth
        );
        $comparisonLabel = $selectedMonth ? 'previous month' : 'previous year';

        $paymentChannels = Account::where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $serviceProviderOptions = Partner::filterCondition()
            ->where('approved', 1)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);
        $selectedServiceProviderName = optional(
            $serviceProviderOptions->firstWhere('id', $selectedPartnerId)
        )->business_name;

        $mandates = $this->buildMandateSummary($channel, $periodStart, $periodEnd, $selectedPartnerId);
        $cachedReporting = $this->reporting->remember(
            $this->authenticatedUser,
            'bi-collections-v2',
            [
                'year' => $selectedYear,
                'month' => $selectedMonth,
                'channel' => $channel,
                'partner_id' => $selectedPartnerId,
            ],
            function () use ($periodStart, $periodEnd, $selectedYear, $selectedMonth, $channel, $selectedPartnerId) {
                $periodCollection = $this->periodCollectionStats(
                    $periodStart,
                    $periodEnd,
                    $channel,
                    $selectedPartnerId
                );
                $collectionSeries = $this->buildCollectionSeries(
                    $selectedYear,
                    $selectedMonth,
                    $channel,
                    $selectedPartnerId
                );

                return [
                    'period_collection' => $periodCollection,
                    'collection_series' => $collectionSeries,
                    'realization_series' => $this->buildSuccessRateSeries($collectionSeries),
                ];
            }
        );

        $periodCollection = $cachedReporting['period_collection'];
        $comparisonCollection = $this->periodCollectionStats(
            $comparisonStart,
            $comparisonEnd,
            $channel,
            $selectedPartnerId
        );
        $collectionSeries = $cachedReporting['collection_series'];
        $realizationSeries = $cachedReporting['realization_series'];
        $mandateValueSeries = $this->buildMandateValueSeries(
            $selectedYear,
            $selectedMonth,
            $channel,
            $selectedPartnerId
        );
        $newMandatesSeries = $this->buildNewMandatesSeries(
            $selectedYear,
            $selectedMonth,
            $channel,
            $selectedPartnerId
        );
        $channelMix = $this->buildChannelMix($channel, $periodStart, $periodEnd, $selectedPartnerId);

        $approvedProviders = Partner::filterCondition()
            ->where('approved', 1)
            ->when($selectedPartnerId, fn ($query, $partnerId) => $query->where('partners.id', $partnerId))
            ->whereBetween('partners.created_at', [$periodStart, $periodEnd]);
        $approvedMandates = $this->approvedMandatesQuery(
            $channel,
            $periodStart,
            $periodEnd,
            $selectedPartnerId
        );

        $previousApprovedProviders = Partner::filterCondition()
            ->where('approved', 1)
            ->when($selectedPartnerId, fn ($query, $partnerId) => $query->where('partners.id', $partnerId))
            ->whereBetween('partners.created_at', [$comparisonStart, $comparisonEnd])
            ->count();
        $previousApprovedMandates = $this->approvedMandatesQuery(
            $channel,
            $comparisonStart,
            $comparisonEnd,
            $selectedPartnerId
        )->count();

        $growthSummary = [
            'partners_count' => (int) (clone $approvedProviders)->count(),
            'approved_mandate_count' => (clone $approvedMandates)->count(),
            'approved_mandate_value' => (float) (clone $approvedMandates)->sum('total_amount'),
            'period_expected' => (float) ($periodCollection->expected_amount ?? 0),
            'period_collected' => (float) ($periodCollection->collected_amount ?? 0),
            'period_realization_rate' => $this->percentage(
                $periodCollection->collected_amount ?? 0,
                $periodCollection->expected_amount ?? 0
            ),
            'selected_expected' => (float) collect($collectionSeries)->sum('expected'),
            'selected_collected' => (float) collect($collectionSeries)->sum('paid'),
            'selected_realization_rate' => $this->percentage(
                collect($collectionSeries)->sum('paid'),
                collect($collectionSeries)->sum('expected')
            ),
            'partners_change' => $this->percentageChange(
                (clone $approvedProviders)->count(),
                $previousApprovedProviders
            ),
            'approved_mandates_change' => $this->percentageChange(
                (clone $approvedMandates)->count(),
                $previousApprovedMandates
            ),
            'expected_change' => $this->percentageChange(
                $periodCollection->expected_amount ?? 0,
                $comparisonCollection->expected_amount ?? 0
            ),
            'collected_change' => $this->percentageChange(
                $periodCollection->collected_amount ?? 0,
                $comparisonCollection->collected_amount ?? 0
            ),
        ];

        $reconciliationSummary = $this->buildReconciliationSummary(
            $periodStart,
            $periodEnd,
            $channel,
            $selectedPartnerId,
            $selectedReconciliationStatus
        );
        $reconciliationDetail = $this->buildReconciliationDetailRows(
            $periodStart,
            $periodEnd,
            $channel,
            $selectedPartnerId,
            $selectedReconciliationStatus
        );
        $reconciliationFilters = [
            'partner_id' => $selectedPartnerId,
            'recon_status' => $selectedReconciliationStatus,
        ];

        $highestSingleMandate = (float) $this->periodMandatesQuery(
            $channel,
            $periodStart,
            $periodEnd,
            $selectedPartnerId
        )->max('total_amount');
        $pipelineTotal = (float) $mandates['total_value'];
        $mandatePipeline = [
            [
                'label' => 'Approved',
                'count' => (int) $mandates['approved_count'],
                'amount' => (float) $mandates['approved_value'],
                'percent' => $pipelineTotal > 0 ? $this->percentage($mandates['approved_value'], $pipelineTotal) : 0,
                'tone' => 'success',
            ],
            [
                'label' => 'Pending',
                'count' => (int) $mandates['pending_count'],
                'amount' => (float) $mandates['pending_value'],
                'percent' => $pipelineTotal > 0 ? $this->percentage($mandates['pending_value'], $pipelineTotal) : 0,
                'tone' => 'warning',
            ],
            [
                'label' => 'Failed',
                'count' => (int) $mandates['failed_count'],
                'amount' => (float) $mandates['failed_value'],
                'percent' => $pipelineTotal > 0 ? $this->percentage($mandates['failed_value'], $pipelineTotal) : 0,
                'tone' => 'danger',
            ],
        ];

        $chartPayload = [
            'growth' => [
                'categories' => collect($collectionSeries)->pluck('period')->values(),
                'views' => [
                    'collections' => [
                        'label' => 'Expected collections vs cash received',
                        'type' => 'bar',
                        'unit' => 'money',
                        'series' => [
                            [
                                'name' => 'Expected by due date',
                                'data' => collect($collectionSeries)->pluck('expected')->values(),
                            ],
                            [
                                'name' => 'Cash received by settlement date',
                                'data' => collect($collectionSeries)->pluck('paid')->values(),
                            ],
                        ],
                        'colors' => ['#1565C0', '#2E7D32'],
                    ],
                    'mandates' => [
                        'label' => 'Mandate approval values',
                        'type' => 'bar',
                        'unit' => 'money',
                        'series' => [
                                ['name' => 'Approved', 'data' => collect($mandateValueSeries)->pluck('approved')->values()],
                            ['name' => 'Pending', 'data' => collect($mandateValueSeries)->pluck('pending')->values()],
                            ['name' => 'Failed', 'data' => collect($mandateValueSeries)->pluck('failed')->values()],
                        ],
                        'colors' => ['#1565C0', '#66BB6A', '#F9A825', '#C62828'],
                    ],
                    'success_rate' => [
                        'label' => 'Cash realization rate',
                        'type' => 'line',
                        'unit' => 'percent',
                        'series' => [
                            ['name' => 'Cash realization %', 'data' => collect($realizationSeries)->pluck('rate')->values()],
                        ],
                        'colors' => ['#388E3C'],
                    ],
                    'new_mandates' => [
                        'label' => 'New mandates created',
                        'type' => 'line',
                        'unit' => 'count',
                        'series' => [
                            ['name' => 'New mandates', 'data' => collect($newMandatesSeries)->pluck('count')->values()],
                        ],
                        'colors' => ['#153567'],
                    ],
                ],
            ],
            'channel_mix' => [
                'labels' => collect($channelMix)->pluck('label')->values(),
                'series' => collect($channelMix)->pluck('value')->values(),
                'colors' => ['#153567', '#1976D2', '#388E3C', '#d97706', '#66BB6A', '#9E9E9E'],
            ],
        ];

        $filters = [
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'channel' => $channel,
            'partner_id' => $selectedPartnerId,
        ];

        $selectedChannelLabel = optional($paymentChannels->firstWhere('slug', $channel))->name ?? $channel;
        $headerMeta = $this->buildHeaderMeta($selectedYear, $selectedMonth, $selectedChannelLabel);
        if ($selectedServiceProviderName) {
            $headerMeta .= ' · ' . $selectedServiceProviderName;
        }
        $dataFreshAt = $this->reporting->lastUpdatedAt();

        return view('bi.dashboard', compact(
            'title',
            'filters',
            'headerMeta',
            'paymentChannels',
            'serviceProviderOptions',
            'reconciliationFilters',
            'selectedServiceProviderName',
            'growthSummary',
            'mandatePipeline',
            'highestSingleMandate',
            'reconciliationSummary',
            'reconciliationDetail',
            'chartPayload',
            'dataFreshAt',
            'comparisonLabel'
        ));
    }

    private function resolvePeriodBounds(?int $year, ?int $month): array
    {
        if ($year && $month) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();

            return [$start, $start->copy()->endOfMonth()];
        }

        if ($year) {
            $start = Carbon::create($year, 1, 1)->startOfYear();

            return [$start, $start->copy()->endOfYear()];
        }

        return [null, null];
    }

    private function resolveComparisonBounds(Carbon $start, Carbon $end, ?int $month): array
    {
        if ($month) {
            $comparisonStart = $start->copy()->subMonthNoOverflow()->startOfMonth();

            return [$comparisonStart, $comparisonStart->copy()->endOfMonth()];
        }

        return [$start->copy()->subYear()->startOfYear(), $end->copy()->subYear()->endOfYear()];
    }

    private function buildHeaderMeta(?int $year, ?int $month, ?string $channelLabel): string
    {
        $scope = 'All Available Data';

        if ($year && $month) {
            $scope = date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;
        } elseif ($year) {
            $scope = (string) $year;
        }

        return sprintf('%s · %s', $scope, $channelLabel ?: 'All Channels');
    }

    private function baseMandateQuery(?string $channel = null, ?int $partnerId = null): QueryBuilder
    {
        return $this->reporting->mandates($this->authenticatedUser, [
            'channel' => $channel,
            'partner_id' => $partnerId,
        ]);
    }

    private function periodMandatesQuery(
        ?string $channel,
        ?Carbon $start,
        ?Carbon $end,
        ?int $partnerId = null
    ): QueryBuilder
    {
        return $this->baseMandateQuery($channel, $partnerId)
            ->when($start && $end, fn ($query) => $query->whereBetween('mandate_created_date', [$start, $end]));
    }

    private function approvedMandatesQuery(
        ?string $channel,
        ?Carbon $start,
        ?Carbon $end,
        ?int $partnerId = null
    ): QueryBuilder
    {
        return $this->periodMandatesQuery($channel, $start, $end, $partnerId)
            ->where('mandate_approved', 1);
    }

    private function buildMandateSummary(
        ?string $channel,
        ?Carbon $start,
        ?Carbon $end,
        ?int $partnerId = null
    ): array
    {
        $approvalStatuses = [0, 1, 2];
        $mandateRows = $this->periodMandatesQuery($channel, $start, $end, $partnerId)
            ->whereIn('mandate_approved', $approvalStatuses)
            ->select('mandate_approved')
            ->selectRaw('COUNT(*) as number_of_mandate')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupBy('mandate_approved')
            ->get();

        $mandates = collect($approvalStatuses)->mapWithKeys(function ($status) use ($mandateRows) {
            $mandate = $mandateRows->firstWhere('mandate_approved', $status);

            return [
                $status => [
                    'count' => $mandate ? (int) $mandate->number_of_mandate : 0,
                    'value' => $mandate ? (float) $mandate->total_amount : 0,
                ],
            ];
        });

        return [
            'pending_count' => $mandates[0]['count'],
            'approved_count' => $mandates[1]['count'],
            'failed_count' => $mandates[2]['count'],
            'total_count' => $mandates->sum('count'),
            'pending_value' => $mandates[0]['value'],
            'approved_value' => $mandates[1]['value'],
            'failed_value' => $mandates[2]['value'],
            'total_value' => $mandates->sum('value'),
        ];
    }

    private function periodCollectionStats(
        ?Carbon $start,
        ?Carbon $end,
        ?string $channel,
        ?int $partnerId = null
    ): object
    {
        $filters = ['channel' => $channel, 'partner_id' => $partnerId];
        $expected = $this->reporting->expectedCollections($this->authenticatedUser, $filters);
        $collected = $this->reporting->paymentCollections($this->authenticatedUser, $filters);

        if ($start && $end) {
            $expected->whereBetween('payment_date', [$start, $end]);
            $collected->whereBetween('effective_payment_date', [$start, $end]);
        }

        return (object) [
            'expected_amount' => (float) $expected->sum('installment_amount'),
            'collected_amount' => (float) $collected->sum('paid_amount'),
        ];
    }

    private function buildCollectionSeries(
        int $year,
        ?int $month,
        ?string $channel,
        ?int $partnerId = null
    ): array
    {
        $filters = ['channel' => $channel, 'partner_id' => $partnerId];
        $expectedQuery = $this->reporting->expectedCollections($this->authenticatedUser, $filters);
        $paidQuery = $this->reporting->paymentCollections($this->authenticatedUser, $filters);

        if ($month) {
            $expectedByPeriod = $expectedQuery
                ->selectRaw('DAY(expected_date) as period_number')
                ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
                ->where('expected_year', $year)
                ->where('expected_month', $month)
                ->groupByRaw('DAY(expected_date)')
                ->pluck('total_amount', 'period_number');
            $paidByPeriod = $paidQuery
                ->selectRaw('DAY(collected_date) as period_number')
                ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
                ->where('collected_year', $year)
                ->where('collected_month', $month)
                ->groupByRaw('DAY(collected_date)')
                ->pluck('paid_amount', 'period_number');
            $periods = range(1, Carbon::create($year, $month, 1)->daysInMonth);
        } else {
            $expectedByPeriod = $expectedQuery
                ->select('expected_month as period_number')
                ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
                ->where('expected_year', $year)
                ->groupBy('expected_month')
                ->pluck('total_amount', 'period_number');
            $paidByPeriod = $paidQuery
                ->select('collected_month as period_number')
                ->selectRaw('COALESCE(SUM(paid_amount), 0) as paid_amount')
                ->where('collected_year', $year)
                ->groupBy('collected_month')
                ->pluck('paid_amount', 'period_number');
            $periods = range(1, 12);
        }

        return collect($periods)->map(function ($period) use ($expectedByPeriod, $paidByPeriod, $year, $month) {
            return [
                'period' => $month
                    ? Carbon::create($year, $month, $period)->format('d M')
                    : Carbon::create($year, $period, 1)->format('M'),
                'expected' => (float) ($expectedByPeriod[$period] ?? 0),
                'paid' => (float) ($paidByPeriod[$period] ?? 0),
            ];
        })->all();
    }

    private function buildSuccessRateSeries(array $collectionSeries): array
    {
        return collect($collectionSeries)
            ->map(function ($row) {
                return [
                    'period' => $row['period'],
                    'rate' => (float) $row['expected'] > 0
                        ? $this->percentage($row['paid'], $row['expected'])
                        : null,
                ];
            })
            ->all();
    }

    private function buildMandateValueSeries(
        int $year,
        ?int $month,
        ?string $channel,
        ?int $partnerId = null
    ): array
    {
        $periodExpression = $month
            ? 'DAY(mandate_created_date)'
            : 'mandate_created_month';
        $grouped = $this->baseMandateQuery($channel, $partnerId)
            ->selectRaw("{$periodExpression} as period_number")
            ->addSelect('mandate_approved')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->whereIn('mandate_approved', [0, 1, 2])
            ->where('mandate_created_year', $year)
            ->when($month, fn ($query, $value) => $query->where('mandate_created_month', $value))
            ->groupBy(DB::raw($periodExpression), 'mandate_approved')
            ->get()
            ->groupBy('period_number');
        $periods = $month
            ? range(1, Carbon::create($year, $month, 1)->daysInMonth)
            : range(1, 12);

        return collect($periods)->map(function ($period) use ($grouped, $year, $month) {
            $periodRows = collect($grouped->get($period, []));
            $pending = (float) optional($periodRows->firstWhere('mandate_approved', 0))->total_amount;
            $approved = (float) optional($periodRows->firstWhere('mandate_approved', 1))->total_amount;
            $failed = (float) optional($periodRows->firstWhere('mandate_approved', 2))->total_amount;

            return [
                'period' => $month
                    ? Carbon::create($year, $month, $period)->format('d M')
                    : Carbon::create($year, $period, 1)->format('M'),
                'total' => $pending + $approved + $failed,
                'pending' => $pending,
                'approved' => $approved,
                'failed' => $failed,
            ];
        })->all();
    }

    private function buildNewMandatesSeries(
        int $year,
        ?int $month,
        ?string $channel,
        ?int $partnerId = null
    ): array
    {
        $periodExpression = $month
            ? 'DAY(mandate_created_date)'
            : 'mandate_created_month';
        $createdByPeriod = $this->baseMandateQuery($channel, $partnerId)
            ->selectRaw("{$periodExpression} as period_number")
            ->selectRaw('COUNT(*) as total_count')
            ->where('mandate_created_year', $year)
            ->when($month, fn ($query, $value) => $query->where('mandate_created_month', $value))
            ->groupBy(DB::raw($periodExpression))
            ->pluck('total_count', 'period_number');
        $periods = $month
            ? range(1, Carbon::create($year, $month, 1)->daysInMonth)
            : range(1, 12);

        return collect($periods)->map(function ($period) use ($createdByPeriod, $year, $month) {
            return [
                'period' => $month
                    ? Carbon::create($year, $month, $period)->format('d M')
                    : Carbon::create($year, $period, 1)->format('M'),
                'count' => (int) ($createdByPeriod[$period] ?? 0),
            ];
        })->all();
    }

    private function buildChannelMix(
        ?string $channel,
        ?Carbon $start,
        ?Carbon $end,
        ?int $partnerId = null
    ): array
    {
        return $this->approvedMandatesQuery($channel, $start, $end, $partnerId)
            ->selectRaw("COALESCE(channel_name, 'Unassigned') as label")
            ->selectRaw('COUNT(*) as value')
            ->groupBy('channel_name')
            ->orderByDesc('value')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])
            ->all();
    }

    private function reconciliationBaseQuery(?Carbon $start, ?Carbon $end, ?string $channel, ?int $partnerId): Builder
    {
        $settlementSummary = PaymentSettlement::query()
            ->select('installment_id')
            ->selectRaw('MAX(payment_reference) as payment_reference')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as collected_amount')
            ->reportable()
            ->groupBy('installment_id');

        return Installment::filterCondition()
            ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->leftJoin('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
            ->leftJoin('customers', 'customers.id', '=', 'customer_accounts.customer_id')
            ->leftJoinSub($settlementSummary, 'settlement_summary', function ($join) {
                $join->on('settlement_summary.installment_id', '=', 'installments.id');
            })
            ->where('partners.approved', 1)
            ->where('debit_orders.approved', 1)
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('installments.payment_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->when($partnerId, function ($query) use ($partnerId) {
                $query->where('partners.id', $partnerId);
            });
    }

    private function applyReconciliationStatusFilter(Builder $query, ?string $status): Builder
    {
        return $query->when($status, function ($innerQuery) use ($status) {
            return match ($status) {
                'COLLECTED' => $innerQuery->whereRaw(
                    'COALESCE(settlement_summary.collected_amount, 0) > 0 AND settlement_summary.collected_amount >= installments.installment_amount'
                ),
                'PARTIAL' => $innerQuery
                    ->whereRaw('COALESCE(settlement_summary.collected_amount, 0) > 0')
                    ->whereColumn('settlement_summary.collected_amount', '<', 'installments.installment_amount'),
                'FAILED' => $innerQuery
                    ->whereRaw('COALESCE(settlement_summary.collected_amount, 0) <= 0')
                    ->whereIn('installments.status', ['FAILED', 'FUNDSCHECK']),
                'PENDING' => $innerQuery
                    ->whereRaw('COALESCE(settlement_summary.collected_amount, 0) <= 0')
                    ->whereNotIn(DB::raw("COALESCE(installments.status, 'OPEN')"), ['FAILED', 'FUNDSCHECK']),
                default => $innerQuery,
            };
        });
    }

    private function buildReconciliationSummary(
        ?Carbon $start,
        ?Carbon $end,
        ?string $channel,
        ?int $partnerId,
        ?string $status
    ): array
    {
        $row = $this->applyReconciliationStatusFilter(
            $this->reconciliationBaseQuery($start, $end, $channel, $partnerId),
            $status
        )
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) > 0 AND settlement_summary.collected_amount >= installments.installment_amount THEN 1 ELSE 0 END) as collected_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) > 0 AND settlement_summary.collected_amount < installments.installment_amount THEN 1 ELSE 0 END) as partial_count')
            ->selectRaw("SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) <= 0 AND installments.status IN ('FAILED', 'FUNDSCHECK') THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) <= 0 AND COALESCE(installments.status, 'OPEN') NOT IN ('FAILED', 'FUNDSCHECK') THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) > 0 AND settlement_summary.collected_amount >= installments.installment_amount THEN LEAST(settlement_summary.collected_amount, installments.installment_amount) ELSE 0 END), 0) as collected_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) > 0 AND settlement_summary.collected_amount < installments.installment_amount THEN settlement_summary.collected_amount ELSE 0 END), 0) as partial_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) <= 0 AND installments.status IN ('FAILED', 'FUNDSCHECK') THEN installments.outstanding_amount ELSE 0 END), 0) as failed_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) <= 0 AND COALESCE(installments.status, 'OPEN') NOT IN ('FAILED', 'FUNDSCHECK') THEN installments.outstanding_amount ELSE 0 END), 0) as pending_amount")
            ->selectRaw('SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) > 0 THEN 1 ELSE 0 END) as matched_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(settlement_summary.collected_amount, 0) <= 0 THEN 1 ELSE 0 END) as unmatched_count')
            ->first();

        $total = (int) ($row->total_count ?? 0);
        $collectedCount = (int) ($row->collected_count ?? 0);
        $partialCount = (int) ($row->partial_count ?? 0);
        $failedCount = (int) ($row->failed_count ?? 0);
        $pendingCount = (int) ($row->pending_count ?? 0);

        return [
            'total_count' => $total,
            'collected_count' => $collectedCount,
            'partial_count' => $partialCount,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'collected_amount' => (float) ($row->collected_amount ?? 0),
            'partial_amount' => (float) ($row->partial_amount ?? 0),
            'failed_amount' => (float) ($row->failed_amount ?? 0),
            'pending_amount' => (float) ($row->pending_amount ?? 0),
            'matched_count' => (int) ($row->matched_count ?? 0),
            'unmatched_count' => (int) ($row->unmatched_count ?? 0),
            'collected_percent' => $this->percentage($collectedCount, $total),
            'partial_percent' => $this->percentage($partialCount, $total),
            'failed_percent' => $this->percentage($failedCount, $total),
            'pending_percent' => $this->percentage($pendingCount, $total),
        ];
    }

    private function buildReconciliationDetailRows(
        ?Carbon $start,
        ?Carbon $end,
        ?string $channel,
        ?int $partnerId,
        ?string $status
    ): LengthAwarePaginator
    {
        $paginator = $this->applyReconciliationStatusFilter(
            $this->reconciliationBaseQuery($start, $end, $channel, $partnerId),
            $status
        )
            ->selectRaw("COALESCE(customers.fullname, customer_accounts.account_name, 'Unknown Borrower') as borrower_name")
            ->selectRaw("COALESCE(customers.id_number, '-') as national_id")
            ->selectRaw("COALESCE(NULLIF(debit_orders.crdb_reference, ''), debit_orders.reference) as loan_reference")
            ->addSelect([
                'installments.installment_amount',
                'installments.payment_date',
                'installments.status',
                'debit_orders.reference as emakato_reference',
                'debit_orders.channel',
                'partners.business_name as service_provider_name',
                DB::raw('LEAST(GREATEST(COALESCE(settlement_summary.collected_amount, 0), 0), installments.installment_amount) as settled_amount'),
                DB::raw('COALESCE(settlement_summary.payment_reference, "-") as statement_reference'),
            ])
            ->orderByDesc('installments.payment_date')
            ->orderBy('installments.id')
            ->paginate(50)
            ->withQueryString();

        return $paginator->through(function ($row) {
            $settledAmount = (float) $row->settled_amount;
            $installmentAmount = (float) $row->installment_amount;
            $status = $row->status;

            if ($settledAmount > 0) {
                $status = $settledAmount >= $installmentAmount ? 'CLOSED' : 'PARTIAL_PAID';
            }

            return [
                'borrower_name' => $row->borrower_name,
                'national_id' => $row->national_id,
                'loan_reference' => $row->loan_reference,
                'scheduled_amount' => $installmentAmount,
                'collected_amount' => $settledAmount,
                'payment_date' => $row->payment_date,
                'channel' => $row->channel,
                'emakato_reference' => $row->emakato_reference,
                'service_provider_name' => $row->service_provider_name,
                'status' => $status,
                'statement_reference' => $row->statement_reference,
                'statement_matched' => $settledAmount > 0,
            ];
        });
    }

    private function percentageChange($current, $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round(((((float) $current) - ((float) $previous)) / abs((float) $previous)) * 100, 2);
    }

    private function percentage($numerator, $denominator): float
    {
        if ((float) $denominator <= 0) {
            return 0;
        }

        return round((((float) $numerator) / ((float) $denominator)) * 100, 2);
    }
}
