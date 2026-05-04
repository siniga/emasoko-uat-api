<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BIController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $title = 'BI Dashboard';
        $today = Carbon::today();
        $selectedYear = $request->filled('year') ? (int) $request->input('year') : (int) $today->format('Y');
        $selectedMonth = $request->filled('month') ? (int) $request->input('month') : null;
        $channel = $request->input('channel') ?: null;
        $selectedSubscriberId = $request->filled('subscriber_id') ? (int) $request->input('subscriber_id') : null;
        $selectedReconciliationStatus = $request->input('recon_status') ?: null;
        [$periodStart, $periodEnd] = $this->resolvePeriodBounds($selectedYear, $selectedMonth);

        $paymentChannels = Account::where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $subscriberOptions = Partner::filterCondition()
            ->where('approved', 1)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        $mandates = $this->buildMandateSummary($channel, $selectedYear);
        $periodCollection = $this->periodCollectionStats($periodStart, $periodEnd, $channel);
        $collectionSeries = $this->buildCollectionSeries($selectedYear, $channel);
        $mandateValueSeries = $this->buildMandateValueSeries($selectedYear, $channel);
        $newMandatesSeries = $this->buildNewMandatesSeries($selectedYear, $channel);
        $successRateSeries = $this->buildSuccessRateSeries($collectionSeries);
        $channelMix = $this->buildChannelMix($channel);
        $channelPerformance = $this->buildChannelPerformance($periodStart, $periodEnd, $channel);
        $statusBreakdown = $this->buildStatusBreakdown($periodStart, $periodEnd, $channel);
        $partnerAttention = $this->buildPartnerAttentionData($channel, $periodStart, $periodEnd);

        $overdue = $this->buildOverdueStats($today, $channel);
        $failedPeriod = $this->buildFailedPeriodStats($periodStart, $periodEnd, $channel);
        $partialPaid = $this->buildPartialPaidStats($channel);
        $upcoming = $this->buildUpcomingStats($today, $channel);

        $growthSummary = [
            'partners_count' => (int) Partner::filterCondition()->where('approved', 1)->count(),
            'active_mandate_count' => $this->activeMandatesQuery($channel)->count(),
            'active_mandate_value' => (float) $this->activeMandatesQuery($channel)->sum('debit_orders.total_amount'),
            'period_expected' => (float) ($periodCollection->expected_amount ?? 0),
            'period_collected' => (float) ($periodCollection->collected_amount ?? 0),
            'period_success_rate' => $this->percentage($periodCollection->collected_amount ?? 0, $periodCollection->expected_amount ?? 0),
            'year_collected' => (float) collect($collectionSeries)->sum('paid'),
            'year_expected' => (float) collect($collectionSeries)->sum('expected'),
            'year_success_rate' => $this->percentage(
                collect($collectionSeries)->sum('paid'),
                collect($collectionSeries)->sum('expected')
            ),
        ];

        $bestChannel = $channelPerformance->sortByDesc('success_rate')->first();
        $atRiskPartners = collect($partnerAttention)->filter(fn ($item) => $item['rate'] < 50)->count();

        $collectionScore = [
            'average_rate' => $growthSummary['period_success_rate'],
            'overdue_count' => (int) ($overdue->overdue_count ?? 0),
            'overdue_amount' => (float) ($overdue->overdue_amount ?? 0),
            'partial_paid_count' => (int) ($partialPaid->partial_paid_count ?? 0),
            'partial_paid_amount' => (float) ($partialPaid->partial_paid_amount ?? 0),
            'best_channel' => $bestChannel,
            'at_risk_partners' => $atRiskPartners,
            'failed_period_count' => (int) ($failedPeriod->failed_count ?? 0),
            'failed_period_amount' => (float) ($failedPeriod->failed_amount ?? 0),
            'upcoming_count' => (int) ($upcoming->upcoming_count ?? 0),
            'upcoming_amount' => (float) ($upcoming->upcoming_amount ?? 0),
        ];

        $reconciliationSummary = $this->buildReconciliationSummary($periodStart, $periodEnd, $channel, $selectedSubscriberId);
        $reconciliationDetail = $this->buildReconciliationDetailRows(
            $periodStart,
            $periodEnd,
            $channel,
            $selectedSubscriberId,
            $selectedReconciliationStatus
        );

        $selectedSubscriberName = optional($subscriberOptions->firstWhere('id', $selectedSubscriberId))->business_name;
        $reconciliationFilters = [
            'subscriber_id' => $selectedSubscriberId,
            'recon_status' => $selectedReconciliationStatus,
        ];

        $highestSingleMandate = (float) $this->baseMandateQuery($channel)->max('debit_orders.total_amount');
        $pipelineTotal = (float) $mandates['total_value'];
        $mandatePipeline = [
            [
                'label' => 'Approved',
                'amount' => (float) $mandates['approved_value'],
                'percent' => $pipelineTotal > 0 ? $this->percentage($mandates['approved_value'], $pipelineTotal) : 0,
                'tone' => 'success',
            ],
            [
                'label' => 'Pending',
                'amount' => (float) $mandates['pending_value'],
                'percent' => $pipelineTotal > 0 ? $this->percentage($mandates['pending_value'], $pipelineTotal) : 0,
                'tone' => 'warning',
            ],
            [
                'label' => 'Failed',
                'amount' => (float) $mandates['failed_value'],
                'percent' => $pipelineTotal > 0 ? $this->percentage($mandates['failed_value'], $pipelineTotal) : 0,
                'tone' => 'danger',
            ],
        ];

        $chartPayload = [
            'growth' => [
                'months' => collect($collectionSeries)->pluck('month')->values(),
                'views' => [
                    'collections' => [
                        'label' => 'Expected vs collected',
                        'type' => 'stacked-bar',
                        'series' => [
                            [
                                'name' => 'Collected',
                                'data' => collect($collectionSeries)->pluck('paid')->values(),
                            ],
                            [
                                'name' => 'Gap (Not Collected)',
                                'data' => collect($collectionSeries)
                                    ->map(fn ($row) => max(((float) $row['expected']) - ((float) $row['paid']), 0))
                                    ->values(),
                            ],
                        ],
                        'colors' => ['#2E7D32', '#F8BBD0'],
                    ],
                    'mandates' => [
                        'label' => 'Mandate values',
                        'type' => 'bar',
                        'series' => [
                            ['name' => 'Total', 'data' => collect($mandateValueSeries)->pluck('total')->values()],
                            ['name' => 'Approved', 'data' => collect($mandateValueSeries)->pluck('approved')->values()],
                            ['name' => 'Pending', 'data' => collect($mandateValueSeries)->pluck('pending')->values()],
                            ['name' => 'Failed', 'data' => collect($mandateValueSeries)->pluck('failed')->values()],
                        ],
                        'colors' => ['#1565C0', '#66BB6A', '#F9A825', '#C62828'],
                    ],
                    'success_rate' => [
                        'label' => 'Collection success rate',
                        'type' => 'line',
                        'series' => [
                            ['name' => 'Success rate %', 'data' => collect($successRateSeries)->pluck('rate')->values()],
                        ],
                        'colors' => ['#388E3C'],
                    ],
                    'new_mandates' => [
                        'label' => 'New mandates',
                        'type' => 'line',
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
            'status_breakdown' => [
                'categories' => collect($statusBreakdown)->pluck('label')->values(),
                'series' => [
                    [
                        'name' => 'Installments',
                        'data' => collect($statusBreakdown)->pluck('count')->values(),
                    ],
                ],
                'colors' => collect($statusBreakdown)->pluck('color')->values(),
            ],
        ];

        $filters = [
            'year' => $selectedYear,
            'month' => $selectedMonth,
            'channel' => $channel,
        ];

        $selectedChannelLabel = optional($paymentChannels->firstWhere('slug', $channel))->name ?? $channel;
        $headerMeta = $this->buildHeaderMeta($selectedYear, $selectedMonth, $selectedChannelLabel);

        return view('bi.dashboard', compact(
            'title',
            'filters',
            'headerMeta',
            'paymentChannels',
            'subscriberOptions',
            'reconciliationFilters',
            'selectedSubscriberName',
            'growthSummary',
            'mandatePipeline',
            'highestSingleMandate',
            'collectionScore',
            'reconciliationSummary',
            'reconciliationDetail',
            'channelPerformance',
            'partnerAttention',
            'chartPayload'
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

    private function baseMandateQuery(?string $channel = null): Builder
    {
        return DebitOrder::filterCondition()
            ->whereHas('partner', function ($query) {
                $query->where('partners.approved', 1);
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            });
    }

    private function activeMandatesQuery(?string $channel = null): Builder
    {
        return $this->baseMandateQuery($channel)
            ->where('debit_orders.approved', 1)
            ->where('debit_orders.status', 240)
            ->where('debit_orders.deleted', 0)
            ->where('debit_orders.contract_status', 'OPEN');
    }

    private function operationalInstallments(?string $channel, bool $openContractsOnly): Builder
    {
        return Installment::filterCondition()
            ->whereHas('debitOrder', function ($query) use ($channel, $openContractsOnly) {
                $query->where('debit_orders.approved', 1)
                    ->where('debit_orders.status', 240)
                    ->where('debit_orders.deleted', 0)
                    ->when($openContractsOnly, function ($innerQuery) {
                        $innerQuery->where('debit_orders.contract_status', 'OPEN');
                    })
                    ->when($channel, function ($innerQuery) use ($channel) {
                        $innerQuery->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($partnerQuery) {
                        $partnerQuery->where('partners.approved', 1);
                    });
            });
    }

    private function buildMandateSummary(?string $channel, ?int $year = null): array
    {
        $approvedStatuses = [0, 1, 2];
        $debitOrders = DebitOrder::mandateApprovalStats($channel, $year);

        $mandates = collect($approvedStatuses)->mapWithKeys(function ($status) use ($debitOrders) {
            $order = $debitOrders->firstWhere('approved', $status);

            return [
                $status => [
                    'count' => $order ? (int) $order->number_of_mandate : 0,
                    'value' => $order ? (float) $order->total_amount : 0,
                ],
            ];
        });

        return [
            'pending_count' => $mandates[0]['count'],
            'approved_count' => $mandates[1]['count'],
            'failed_count' => $mandates[2]['count'],
            'total_count' => $mandates[0]['count'] + $mandates[1]['count'] + $mandates[2]['count'],
            'pending_value' => $mandates[0]['value'],
            'approved_value' => $mandates[1]['value'],
            'failed_value' => $mandates[2]['value'],
            'total_value' => $mandates[0]['value'] + $mandates[1]['value'] + $mandates[2]['value'],
        ];
    }

    private function periodCollectionStats(?Carbon $start, ?Carbon $end, ?string $channel): object
    {
        $query = Installment::filterCondition()
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where('debit_orders.approved', 1)
                    ->where('debit_orders.deleted', 0)
                    ->when($channel, function ($innerQuery) use ($channel) {
                        $innerQuery->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($partnerQuery) {
                        $partnerQuery->where('partners.approved', 1);
                    });
            });

        if ($start && $end) {
            $query->selectRaw(
                'COALESCE(SUM(CASE WHEN installments.payment_date BETWEEN ? AND ? THEN installments.installment_amount ELSE 0 END), 0) as expected_amount',
                [$start->toDateString(), $end->toDateString()]
            )->selectRaw(
                "COALESCE(SUM(CASE WHEN installments.effective_payment_date BETWEEN ? AND ? AND installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN installments.current_balance ELSE 0 END), 0) as collected_amount",
                [$start->toDateString(), $end->toDateString()]
            );
        } else {
            $query->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as expected_amount')
                ->selectRaw("COALESCE(SUM(CASE WHEN installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN installments.current_balance ELSE 0 END), 0) as collected_amount");
        }

        return $query->first();
    }

    private function buildCollectionSeries(?int $year, ?string $channel): array
    {
        $expectedByMonth = Installment::filterCondition()
            ->select([
                DB::raw('MONTH(installments.payment_date) as month_number'),
                DB::raw('COALESCE(SUM(installments.installment_amount), 0) as total_amount'),
            ])
            ->when($year, function ($query) use ($year) {
                $query->whereYear('installments.payment_date', $year);
            })
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where('debit_orders.approved', 1)
                    ->where('debit_orders.deleted', 0)
                    ->when($channel, function ($innerQuery) use ($channel) {
                        $innerQuery->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($partnerQuery) {
                        $partnerQuery->where('partners.approved', 1);
                    });
            })
            ->groupBy(DB::raw('MONTH(installments.payment_date)'))
            ->pluck('total_amount', 'month_number');

        $paidByMonth = Installment::filterCondition()
            ->select([
                DB::raw('MONTH(installments.effective_payment_date) as month_number'),
                DB::raw('COALESCE(SUM(installments.current_balance), 0) as paid_amount'),
            ])
            ->when($year, function ($query) use ($year) {
                $query->whereYear('installments.effective_payment_date', $year);
            })
            ->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->whereHas('debitOrder', function ($query) use ($channel) {
                $query->where('debit_orders.approved', 1)
                    ->where('debit_orders.deleted', 0)
                    ->when($channel, function ($innerQuery) use ($channel) {
                        $innerQuery->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($partnerQuery) {
                        $partnerQuery->where('partners.approved', 1);
                    });
            })
            ->groupBy(DB::raw('MONTH(installments.effective_payment_date)'))
            ->pluck('paid_amount', 'month_number');

        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $rows[] = [
                'month' => date('M', mktime(0, 0, 0, $month, 10)),
                'expected' => (float) ($expectedByMonth[$month] ?? 0),
                'paid' => (float) ($paidByMonth[$month] ?? 0),
            ];
        }

        return $rows;
    }

    private function buildSuccessRateSeries(array $collectionSeries): array
    {
        return collect($collectionSeries)
            ->map(function ($row) {
                return [
                    'month' => $row['month'],
                    'rate' => $this->percentage($row['paid'], $row['expected']),
                ];
            })
            ->all();
    }

    private function buildMandateValueSeries(?int $year, ?string $channel): array
    {
        $summaryStatuses = [
            DebitOrder::SUMMARY_PENDING,
            DebitOrder::SUMMARY_APPROVED,
            DebitOrder::SUMMARY_FAILED,
        ];

        $grouped = $this->baseMandateQuery($channel)
            ->select([
                DB::raw('MONTH(debit_orders.created_at) as month_number'),
                'debit_orders.lifecycle_status',
                DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'),
            ])
            ->whereIn('debit_orders.lifecycle_status', array_merge(...array_values(DebitOrder::lifecycleSummaryMap())))
            ->when($year, function ($query) use ($year) {
                $query->whereYear('debit_orders.created_at', $year);
            })
            ->groupBy(DB::raw('MONTH(debit_orders.created_at)'), 'debit_orders.lifecycle_status')
            ->orderBy(DB::raw('MONTH(debit_orders.created_at)'))
            ->get()
            ->groupBy('month_number');

        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthRows = collect($grouped->get($month, []));
            $results = collect($summaryStatuses)->mapWithKeys(function ($summary) use ($monthRows) {
                $amount = $monthRows
                    ->filter(fn ($row) => in_array($row->lifecycle_status, DebitOrder::lifecycleStatusesForSummary($summary), true))
                    ->sum('total_amount');

                return [$summary => (float) $amount];
            });

            $pending = (float) ($results[DebitOrder::SUMMARY_PENDING] ?? 0);
            $approved = (float) ($results[DebitOrder::SUMMARY_APPROVED] ?? 0);
            $failed = (float) ($results[DebitOrder::SUMMARY_FAILED] ?? 0);

            $rows[] = [
                'month' => date('M', mktime(0, 0, 0, $month, 10)),
                'total' => $pending + $approved + $failed,
                'pending' => $pending,
                'approved' => $approved,
                'failed' => $failed,
            ];
        }

        return $rows;
    }

    private function buildNewMandatesSeries(?int $year, ?string $channel): array
    {
        $createdByMonth = $this->baseMandateQuery($channel)
            ->selectRaw('MONTH(debit_orders.created_at) as month_number')
            ->selectRaw('COUNT(*) as total_count')
            ->when($year, function ($query) use ($year) {
                $query->whereYear('debit_orders.created_at', $year);
            })
            ->groupBy(DB::raw('MONTH(debit_orders.created_at)'))
            ->pluck('total_count', 'month_number');

        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $rows[] = [
                'month' => date('M', mktime(0, 0, 0, $month, 10)),
                'count' => (int) ($createdByMonth[$month] ?? 0),
            ];
        }

        return $rows;
    }

    private function buildChannelMix(?string $channel): array
    {
        return $this->activeMandatesQuery($channel)
            ->leftJoin('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'customer_accounts.account_id')
            ->selectRaw("COALESCE(accounts.name, 'Unassigned') as label")
            ->selectRaw('COUNT(*) as value')
            ->groupBy('accounts.name')
            ->get()
            ->map(function ($row) {
                return [
                    'label' => $row->label,
                    'value' => (int) $row->value,
                ];
            })
            ->sortByDesc('value')
            ->take(6)
            ->values()
            ->all();
    }

    private function buildChannelPerformance(?Carbon $start, ?Carbon $end, ?string $channel)
    {
        $query = Installment::filterCondition()
            ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->leftJoin('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'customer_accounts.account_id')
            ->where('partners.approved', 1)
            ->where('debit_orders.approved', 1)
            ->where('debit_orders.deleted', 0)
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->selectRaw("COALESCE(accounts.name, 'Unassigned') as label");

        if ($start && $end) {
            $query->selectRaw(
                'COALESCE(SUM(CASE WHEN installments.payment_date BETWEEN ? AND ? THEN installments.installment_amount ELSE 0 END), 0) as expected_amount',
                [$start->toDateString(), $end->toDateString()]
            )->selectRaw(
                "COALESCE(SUM(CASE WHEN installments.effective_payment_date BETWEEN ? AND ? AND installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN installments.current_balance ELSE 0 END), 0) as collected_amount",
                [$start->toDateString(), $end->toDateString()]
            );
        } else {
            $query->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as expected_amount')
                ->selectRaw("COALESCE(SUM(CASE WHEN installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN installments.current_balance ELSE 0 END), 0) as collected_amount");
        }

        return $query
            ->groupBy('accounts.name')
            ->get()
            ->map(function ($row) {
                $expected = (float) $row->expected_amount;
                $collected = (float) $row->collected_amount;

                return [
                    'label' => $row->label,
                    'expected_amount' => $expected,
                    'collected_amount' => $collected,
                    'success_rate' => $this->percentage($collected, $expected),
                ];
            })
            ->sortByDesc('expected_amount')
            ->values();
    }

    private function buildStatusBreakdown(?Carbon $start, ?Carbon $end, ?string $channel): array
    {
        $row = Installment::filterCondition()
            ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->where('partners.approved', 1)
            ->where('debit_orders.approved', 1)
            ->where('debit_orders.deleted', 0)
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('installments.payment_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->selectRaw("SUM(CASE WHEN installments.status = 'CLOSED' THEN 1 ELSE 0 END) as paid_count")
            ->selectRaw("SUM(CASE WHEN installments.status = 'PARTIAL_PAID' THEN 1 ELSE 0 END) as partial_count")
            ->selectRaw("SUM(CASE WHEN installments.status IN ('FAILED', 'FUNDSCHECK') THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN installments.status = 'OPEN' THEN 1 ELSE 0 END) as open_count")
            ->first();

        return collect([
            ['label' => 'Paid', 'count' => (int) ($row->paid_count ?? 0), 'color' => '#388E3C'],
            ['label' => 'Partial', 'count' => (int) ($row->partial_count ?? 0), 'color' => '#d97706'],
            ['label' => 'Failed', 'count' => (int) ($row->failed_count ?? 0), 'color' => '#C62828'],
            ['label' => 'Open', 'count' => (int) ($row->open_count ?? 0), 'color' => '#153567'],
        ])->map(function ($config) {
            return [
                'label' => $config['label'],
                'count' => $config['count'],
                'color' => $config['color'],
            ];
        })->values()->all();
    }

    private function buildPartnerAttentionData(?string $channel, ?Carbon $start = null, ?Carbon $end = null): array
    {
        return Installment::filterCondition()
            ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->where('partners.approved', 1)
            ->where('debit_orders.approved', 1)
            ->where('debit_orders.deleted', 0)
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->where(function ($innerQuery) use ($start, $end) {
                    $innerQuery->whereBetween('installments.payment_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhereBetween('installments.effective_payment_date', [$start->toDateString(), $end->toDateString()]);
                });
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->select('partners.id', 'partners.uuid')
            ->selectRaw('COALESCE(partners.business_name, ?) as partner_name', ['Unknown'])
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as expected_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN installments.current_balance ELSE 0 END), 0) as collected_amount")
            ->selectRaw('COALESCE(SUM(installments.outstanding_amount), 0) as outstanding_amount')
            ->selectRaw('COUNT(*) as number_of_collection')
            ->groupBy('partners.id', 'partners.uuid', 'partners.business_name')
            ->get()
            ->map(function ($row) {
                $expected = (float) $row->expected_amount;
                $collected = (float) $row->collected_amount;

                return [
                    'uuid' => $row->uuid,
                    'partner_name' => $row->partner_name,
                    'expected_amount' => $expected,
                    'collected_amount' => $collected,
                    'outstanding_amount' => (float) $row->outstanding_amount,
                    'number_of_collection' => (int) $row->number_of_collection,
                    'rate' => $this->percentage($collected, $expected),
                ];
            })
            ->filter(fn ($item) => $item['expected_amount'] > 0)
            ->sortBy('rate')
            ->sortByDesc('outstanding_amount')
            ->take(8)
            ->values()
            ->all();
    }

    private function reconciliationBaseQuery(?Carbon $start, ?Carbon $end, ?string $channel, ?int $subscriberId): Builder
    {
        $settlementSummary = PaymentSettlement::query()
            ->select('installment_id')
            ->selectRaw("MAX(CASE WHEN reconciliation_status = 'POSTED' OR applied_to_balances = 1 THEN 1 ELSE 0 END) as is_matched")
            ->selectRaw('MAX(payment_reference) as payment_reference')
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
            ->where('debit_orders.deleted', 0)
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('installments.payment_date', [$start->toDateString(), $end->toDateString()]);
            })
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->when($subscriberId, function ($query) use ($subscriberId) {
                $query->where('partners.id', $subscriberId);
            });
    }

    private function applyReconciliationStatusFilter(Builder $query, ?string $status): Builder
    {
        return $query->when($status, function ($innerQuery) use ($status) {
            return match ($status) {
                'COLLECTED' => $innerQuery->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID']),
                'FAILED' => $innerQuery->whereIn('installments.status', ['FAILED', 'FUNDSCHECK']),
                'PENDING' => $innerQuery->where('installments.status', 'OPEN'),
                default => $innerQuery,
            };
        });
    }

    private function buildReconciliationSummary(?Carbon $start, ?Carbon $end, ?string $channel, ?int $subscriberId): array
    {
        $row = $this->reconciliationBaseQuery($start, $end, $channel, $subscriberId)
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN 1 ELSE 0 END) as collected_count")
            ->selectRaw("SUM(CASE WHEN installments.status IN ('FAILED', 'FUNDSCHECK') THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw("SUM(CASE WHEN installments.status = 'OPEN' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN installments.status IN ('CLOSED', 'PARTIAL_PAID') THEN installments.current_balance ELSE 0 END), 0) as collected_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN installments.status IN ('FAILED', 'FUNDSCHECK') THEN installments.installment_amount ELSE 0 END), 0) as failed_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN installments.status = 'OPEN' THEN installments.outstanding_amount ELSE installments.installment_amount END), 0) as pending_amount")
            ->selectRaw("SUM(CASE WHEN COALESCE(settlement_summary.is_matched, 0) = 0 THEN 1 ELSE 0 END) as unmatched_count")
            ->selectRaw("SUM(CASE WHEN COALESCE(settlement_summary.is_matched, 0) = 1 THEN 1 ELSE 0 END) as matched_count")
            ->first();

        $total = (int) ($row->total_count ?? 0);
        $collectedCount = (int) ($row->collected_count ?? 0);
        $failedCount = (int) ($row->failed_count ?? 0);
        $pendingCount = (int) ($row->pending_count ?? 0);

        return [
            'total_count' => $total,
            'collected_count' => $collectedCount,
            'failed_count' => $failedCount,
            'pending_count' => $pendingCount,
            'collected_amount' => (float) ($row->collected_amount ?? 0),
            'failed_amount' => (float) ($row->failed_amount ?? 0),
            'pending_amount' => (float) ($row->pending_amount ?? 0),
            'matched_count' => (int) ($row->matched_count ?? 0),
            'unmatched_count' => (int) ($row->unmatched_count ?? 0),
            'collected_percent' => $this->percentage($collectedCount, $total),
            'failed_percent' => $this->percentage($failedCount, $total),
            'pending_percent' => $this->percentage($pendingCount, $total),
        ];
    }

    private function buildReconciliationDetailRows(
        ?Carbon $start,
        ?Carbon $end,
        ?string $channel,
        ?int $subscriberId,
        ?string $status
    ): array {
        return $this->applyReconciliationStatusFilter(
            $this->reconciliationBaseQuery($start, $end, $channel, $subscriberId),
            $status
        )
            ->selectRaw("COALESCE(customers.fullname, customer_accounts.account_name, 'Unknown Borrower') as borrower_name")
            ->selectRaw("COALESCE(customers.id_number, '-') as national_id")
            ->selectRaw("COALESCE(debit_orders.crdb_reference, debit_orders.otp_reference, debit_orders.reference) as loan_reference")
            ->addSelect([
                'installments.installment_amount',
                'installments.current_balance',
                'installments.payment_date',
                'installments.status',
                'debit_orders.reference as emakato_reference',
                'debit_orders.channel',
                'partners.business_name as subscriber_name',
                DB::raw('COALESCE(settlement_summary.payment_reference, "-") as statement_reference'),
                DB::raw('COALESCE(settlement_summary.is_matched, 0) as statement_matched'),
            ])
            ->orderByDesc('installments.payment_date')
            ->limit(150)
            ->get()
            ->map(function ($row) {
                return [
                    'borrower_name' => $row->borrower_name,
                    'national_id' => $row->national_id,
                    'loan_reference' => $row->loan_reference,
                    'scheduled_amount' => (float) $row->installment_amount,
                    'collected_amount' => in_array($row->status, ['CLOSED', 'PARTIAL_PAID'], true) ? (float) $row->current_balance : 0,
                    'payment_date' => $row->payment_date,
                    'channel' => $row->channel,
                    'emakato_reference' => $row->emakato_reference,
                    'subscriber_name' => $row->subscriber_name,
                    'status' => $row->status,
                    'statement_reference' => $row->statement_reference,
                    'statement_matched' => (int) $row->statement_matched === 1,
                ];
            })
            ->all();
    }

    private function buildOverdueStats(Carbon $asOfDate, ?string $channel): object
    {
        return $this->operationalInstallments($channel, true)
            ->whereDate('installments.payment_date', '<', $asOfDate->toDateString())
            ->whereIn('installments.status', ['OPEN', 'FAILED', 'FUNDSCHECK'])
            ->selectRaw('COUNT(*) as overdue_count')
            ->selectRaw('COALESCE(SUM(installments.outstanding_amount), 0) as overdue_amount')
            ->first();
    }

    private function buildFailedPeriodStats(?Carbon $start, ?Carbon $end, ?string $channel): object
    {
        $query = $this->operationalInstallments($channel, true)
            ->whereIn('installments.status', ['FAILED', 'FUNDSCHECK']);

        if ($start && $end) {
            $query->whereBetween('installments.payment_date', [$start->toDateString(), $end->toDateString()]);
        }

        return $query
            ->selectRaw('COUNT(*) as failed_count')
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as failed_amount')
            ->first();
    }

    private function buildPartialPaidStats(?string $channel): object
    {
        return $this->operationalInstallments($channel, false)
            ->where('installments.status', 'PARTIAL_PAID')
            ->selectRaw('COUNT(*) as partial_paid_count')
            ->selectRaw('COALESCE(SUM(installments.current_balance), 0) as partial_paid_amount')
            ->first();
    }

    private function buildUpcomingStats(Carbon $asOfDate, ?string $channel): object
    {
        return $this->operationalInstallments($channel, true)
            ->whereBetween('installments.payment_date', [
                $asOfDate->copy()->addDay()->toDateString(),
                $asOfDate->copy()->addDays(7)->toDateString(),
            ])
            ->where('installments.status', 'OPEN')
            ->selectRaw('COUNT(*) as upcoming_count')
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as upcoming_amount')
            ->first();
    }

    private function percentage($numerator, $denominator): float
    {
        if ((float) $denominator <= 0) {
            return 0;
        }

        return round((((float) $numerator) / ((float) $denominator)) * 100, 2);
    }
}
