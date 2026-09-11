<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\PaymentSettlement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class CollectionAnalyticsService
{
    public const TYPE_EXPECTED = 'expected';
    public const TYPE_ACTUAL = 'actual';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_DAILY = 'daily';

    protected const ACTUAL_STATUSES = ['CLOSED', 'PARTIAL_PAID'];
    protected const SUCCESS_STATUSES = ['CLOSED', 'PARTIAL_PAID'];
    protected const FAILED_STATUSES = ['FAILED', 'FUNDSCHECK'];
    protected const FULLY_PAID_STATUSES = ['CLOSED'];
    protected const PARTIAL_PAID_STATUSES = ['PARTIAL_PAID'];

    public function __construct(private DashboardReportingQueryService $reporting)
    {
    }

    public function paymentChannels()
    {
        return Account::where('active', 1)->get();
    }

    public function summary(string $type, string $period, array $filters): array
    {
        if ($type === self::TYPE_ACTUAL) {
            return $this->actualSummary($period, $filters);
        }

        return $this->expectedSummary($period, $filters);
    }

    protected function expectedSummary(string $period, array $filters): array
    {
        $viewFilters = ['channel' => $filters['channel'] ?? null];
        $paymentTotals = $this->reporting->paymentCollections(Auth::user(), $viewFilters)
            ->select('installment_id')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as ledger_paid_amount')
            ->groupBy('installment_id');

        $query = $this->reporting->expectedCollections(Auth::user(), $viewFilters)
            ->leftJoinSub($paymentTotals, 'payment_totals', fn ($join) => $join->on('payment_totals.installment_id', '=', 'vw_dashboard_expected_collections.installment_id'))
            ->select('partner_uuid as uuid', 'partner_name')
            ->selectRaw('COUNT(*) as number_of_collection')
            ->selectRaw('SUM(CASE WHEN COALESCE(payment_totals.ledger_paid_amount, 0) > 0 THEN 1 ELSE 0 END) as success_count')
            ->selectRaw("SUM(CASE WHEN COALESCE(payment_totals.ledger_paid_amount, 0) = 0 AND collection_status IN ('FAILED', 'FUNDSCHECK') THEN 1 ELSE 0 END) as failed_count")
            ->selectRaw('SUM(CASE WHEN COALESCE(payment_totals.ledger_paid_amount, 0) > 0 AND COALESCE(payment_totals.ledger_paid_amount, 0) >= installment_amount THEN 1 ELSE 0 END) as fully_paid_count')
            ->selectRaw('SUM(CASE WHEN COALESCE(payment_totals.ledger_paid_amount, 0) > 0 AND COALESCE(payment_totals.ledger_paid_amount, 0) < installment_amount THEN 1 ELSE 0 END) as partial_paid_count')
            ->selectRaw('COALESCE(SUM(installment_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(LEAST(GREATEST(COALESCE(payment_totals.ledger_paid_amount, 0), 0), installment_amount)), 0) as paid_amount')
            ->selectRaw('COALESCE(SUM(installment_amount - LEAST(GREATEST(COALESCE(payment_totals.ledger_paid_amount, 0), 0), installment_amount)), 0) as outstanding_amount')
            ->groupBy('partner_id', 'partner_uuid', 'partner_name');

        if ($period === self::PERIOD_MONTHLY) {
            $query->where('expected_year', $filters['year'])
                ->where('expected_month', $filters['month']);
        } else {
            $query->whereDate('payment_date', $filters['payment_at']);
        }

        $rows = $query->get()
            ->map(function ($row) {
                $expectedAmount = (float) $row->total_amount;
                $paidAmount = (float) $row->paid_amount;

                return [
                    'uuid' => $row->uuid,
                    'partner_name' => $row->partner_name ?? 'Unknown',
                    'number_of_collection' => (int) $row->number_of_collection,
                    'success_count' => (int) $row->success_count,
                    'failed_count' => (int) $row->failed_count,
                    'fully_paid_count' => (int) $row->fully_paid_count,
                    'partial_paid_count' => (int) $row->partial_paid_count,
                    'total_amount' => $expectedAmount,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => (float) $row->outstanding_amount,
                    'percent' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        $expectedAmount = (float) $rows->sum('total_amount');
        $paidAmount = (float) $rows->sum('paid_amount');

        return [
            'rows' => $rows->all(),
            'stats' => [
                'providers' => $rows->count(),
                'collections' => (int) $rows->sum('number_of_collection'),
                'success_collections' => (int) $rows->sum('success_count'),
                'failed_collections' => (int) $rows->sum('failed_count'),
                'fully_paid_collections' => (int) $rows->sum('fully_paid_count'),
                'partial_paid_collections' => (int) $rows->sum('partial_paid_count'),
                'expected_amount' => $expectedAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => max($expectedAmount - $paidAmount, 0),
                'success_rate' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
            ],
        ];

    }
    public function detail(string $type, string $period, string $partnerUuid, array $filters): array
    {
        if ($type === self::TYPE_ACTUAL) {
            return $this->actualDetail($period, $partnerUuid, $filters);
        }

        $partner = Partner::where('uuid', $partnerUuid)->firstOrFail();
        $records = $this->records($type, $period, $filters, $partner);
        $expectedRecords = $records;
        $periodStatusRecords = $records;
        $displayRecords = $records;

        $rows = $displayRecords->map(function (Installment $installment) {
            return $this->mapInstallment($installment);
        })->values();
        $paidAmount = (float) $records->sum(fn (Installment $installment) => $this->ledgerPaidAmount($installment));

        return [
            'partner' => $partner,
            'rows' => $rows->all(),
            'stats' => [
                'collections' => $expectedRecords->count(),
                'success_collections' => $this->countSuccessfulCollections($periodStatusRecords),
                'failed_collections' => $this->countFailedCollections($periodStatusRecords),
                'fully_paid_collections' => $this->countFullyPaidCollections($periodStatusRecords),
                'partial_paid_collections' => $this->countPartialPaidCollections($periodStatusRecords),
                'expected_amount' => (float) $expectedRecords->sum('installment_amount'),
                'paid_amount' => $paidAmount,
                'outstanding_amount' => (float) $expectedRecords->sum(fn(Installment $installment) => $this->derivedOutstanding($installment)),
                'success_rate' => $expectedRecords->sum('installment_amount') > 0
                    ? round(($paidAmount / $expectedRecords->sum('installment_amount')) * 100, 2)
                    : 0,
            ],
        ];
    }

    public function exportRows(string $type, string $period, string $partnerUuid, array $filters): array
    {
        $detail = $this->detail($type, $period, $partnerUuid, $filters);

        return array_map(function (array $row) {
            return [
                'business_name' => $row['business_name'],
                'reference' => $row['reference'],
                'periodicity' => $row['periodicity'],
                'channel' => $row['channel'],
                'account_name' => $row['account_name'],
                'account_number' => $row['account_number'],
                'customer_address' => $row['customer_address'],
                'amount' => $row['amount'],
                'last_paid_amount' => $row['last_paid_amount'],
                'current_balance' => $row['current_balance'],
                'outstanding_amount' => $row['outstanding_amount'],
                'status' => $row['status_label'],
                'payment_date' => $row['payment_date'],
                'effective_payment_date' => $row['effective_payment_date'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'remarks' => $row['remarks'],
            ];
        }, $detail['rows']);
    }

    public function defaultFilters(string $period, array $input): array
    {
        if ($period === self::PERIOD_MONTHLY) {
            return [
                'channel' => $input['channel'] ?? null,
                'year' => !empty($input['year']) ? (int) $input['year'] : (int) date('Y'),
                'month' => !empty($input['month']) ? (int) $input['month'] : (int) date('m'),
            ];
        }

        return [
            'channel' => $input['channel'] ?? null,
            'payment_at' => !empty($input['payment_at'])
                ? Carbon::parse($input['payment_at'])->format('Y-m-d')
                : Carbon::now()->format('Y-m-d'),
        ];
    }

    protected function records(string $type, string $period, array $filters, ?Partner $partner = null): Collection
    {
        $query = $this->baseQuery($type, $filters, $partner);
        $this->applyPeriod($query, $type, $period, $filters);

        return $query
            ->orderBy(
                $type === self::TYPE_ACTUAL ? 'installments.effective_payment_date' : 'installments.payment_date'
            )
            ->get();
    }

    protected function periodStatusRecords(string $period, array $filters, ?Partner $partner = null): Collection
    {
        $query = $this->baseQuery(self::TYPE_EXPECTED, $filters, $partner)
            ->whereIn('installments.status', array_merge(self::SUCCESS_STATUSES, self::FAILED_STATUSES));

        $this->applyPeriodToColumn($query, 'installments.effective_payment_date', $period, $filters);

        return $query
            ->orderBy('installments.effective_payment_date')
            ->get();
    }

    protected function expectedPeriodRecords(string $period, array $filters, ?Partner $partner = null): Collection
    {
        $query = $this->baseQuery(self::TYPE_EXPECTED, $filters, $partner);
        $this->applyPeriodToColumn($query, 'installments.payment_date', $period, $filters);

        return $query
            ->orderBy('installments.payment_date')
            ->get();
    }

    protected function baseQuery(string $type, array $filters, ?Partner $partner = null): Builder
    {
        $settlementTotals = PaymentSettlement::query()
            ->select('installment_id')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as ledger_paid_amount')
            ->selectRaw('MAX(effective_payment_date) as ledger_effective_payment_date')
            ->reportable()
            ->groupBy('installment_id');

        $query = Installment::filterCondition()
            ->leftJoinSub($settlementTotals, 'settlement_totals', fn ($join) => $join->on('settlement_totals.installment_id', '=', 'installments.id'))
            ->select('installments.*')
            ->selectRaw('COALESCE(settlement_totals.ledger_paid_amount, 0) as ledger_paid_amount, settlement_totals.ledger_effective_payment_date')
            ->with([
                'debitOrder:id,reference,periodicity,channel,start_date,end_date,partner_id,customer_account_id,contract_status,deleted',
                'debitOrder.partner:id,uuid,business_name',
                'debitOrder.customerAccount:id,account_name,account_number,customer_id',
                'debitOrder.customerAccount.customer:id,address',
            ])
            ->whereHas('debitOrder', function ($query) use ($type, $filters, $partner) {
                $query->where('debit_orders.approved', 1)
                    ->when($type === self::TYPE_EXPECTED, function ($q) {
                        $q->where('debit_orders.approved', 1);
                    })
                    ->when(!empty($filters['channel']), function ($q) use ($filters) {
                        $q->where('debit_orders.channel', $filters['channel']);
                    })
                    ->when($partner, function ($q) use ($partner) {
                        $q->where('debit_orders.partner_id', $partner->id);
                    })
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            });

        if ($type === self::TYPE_ACTUAL) {
            $query->whereIn('installments.status', self::ACTUAL_STATUSES);
        }

        return $query;
    }

    protected function applyPeriod(Builder $query, string $type, string $period, array $filters): void
    {
        $dateColumn = $type === self::TYPE_ACTUAL
            ? 'installments.effective_payment_date'
            : 'installments.payment_date';

        $this->applyPeriodToColumn($query, $dateColumn, $period, $filters);
    }

    protected function applyPeriodToColumn(Builder $query, string $dateColumn, string $period, array $filters): void
    {
        if ($period === self::PERIOD_MONTHLY) {
            $query->whereMonth($dateColumn, $filters['month'])
                ->whereYear($dateColumn, $filters['year']);

            return;
        }

        $query->whereDate($dateColumn, $filters['payment_at']);
    }

    protected function mapInstallment(Installment $installment): array
    {
        $debitOrder = $installment->debitOrder;
        $account = optional($debitOrder)->customerAccount;
        $customer = optional($account)->customer;

        $paidAmount = $this->ledgerPaidAmount($installment);
        $status = match (true) {
            $paidAmount >= (float) $installment->installment_amount => 'CLOSED',
            $paidAmount > 0 => 'PARTIAL_PAID',
            default => $installment->status,
        };
        return [
            'id' => $installment->id,
            'business_name' => optional(optional($debitOrder)->partner)->business_name,
            'reference' => $debitOrder->reference ?? '',
            'channel' => $debitOrder->channel ?? '',
            'periodicity' => $debitOrder->periodicity ?? '',
            'account_name' => strtoupper((string) ($account->account_name ?? '')),
            'account_number' => $account->account_number ?? '',
            'customer_address' => $customer->address ?? '',
            'amount' => (float) $installment->installment_amount,
            'last_paid_amount' => (float) $installment->last_paid_amount,
            'current_balance' => $paidAmount,
            'outstanding_amount' => $this->derivedOutstanding($installment),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'remarks' => $installment->remarks,
            'payment_date' => $installment->payment_date ? Carbon::parse($installment->payment_date)->format('d-m-Y') : null,
            'effective_payment_date' => $installment->ledger_effective_payment_date ? Carbon::parse($installment->ledger_effective_payment_date)->format('d-m-Y') : null,
            'start_date' => $debitOrder && $debitOrder->start_date ? Carbon::parse($debitOrder->start_date)->format('d-m-Y') : null,
            'end_date' => $debitOrder && $debitOrder->end_date ? Carbon::parse($debitOrder->end_date)->format('d-m-Y') : null,
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'CLOSED' => 'PAID',
            'PARTIAL_PAID' => 'PARTIAL PAID',
            'FAILED' => 'FUNDSCHECK',
            'FUNDSCHECK' => 'FUNDSCHECK',
            'OPEN' => 'PENDING',
            default => strtoupper((string) $status),
        };
    }

    protected function derivedOutstanding(Installment $installment): float
    {
        return max((float) $installment->installment_amount - $this->ledgerPaidAmount($installment), 0);
    }

    protected function ledgerPaidAmount(Installment $installment): float
    {
        return min(max((float) ($installment->ledger_paid_amount ?? 0), 0), (float) $installment->installment_amount);
    }

    protected function countSuccessfulCollections(Collection $records): int
    {
        return $records->filter(fn(Installment $installment) => $this->isSuccessful($installment))->count();
    }

    protected function countFailedCollections(Collection $records): int
    {
        return $records->filter(fn(Installment $installment) =>
            $this->ledgerPaidAmount($installment) <= 0
            && in_array(strtoupper((string) $installment->status), self::FAILED_STATUSES, true)
        )->count();
    }

    protected function countFullyPaidCollections(Collection $records): int
    {
        return $records->filter(fn(Installment $installment) =>
            $this->ledgerPaidAmount($installment) > 0
            && $this->ledgerPaidAmount($installment) >= (float) $installment->installment_amount
        )->count();
    }

    protected function countPartialPaidCollections(Collection $records): int
    {
        return $records->filter(fn(Installment $installment) =>
            $this->ledgerPaidAmount($installment) > 0
            && $this->ledgerPaidAmount($installment) < (float) $installment->installment_amount
        )->count();
    }

    protected function isSuccessful(Installment $installment): bool
    {
        return $this->ledgerPaidAmount($installment) > 0;
    }

    protected function actualSummary(string $period, array $filters): array
    {
        $settledInstallments = $this->settlementQuery($period, $filters)
            ->join('installments', 'installments.id', '=', 'payment_settlements.installment_id')
            ->select('payment_settlements.debit_order_id', 'payment_settlements.installment_id')
            ->selectRaw('MAX(installments.installment_amount) as expected_amount')
            ->selectRaw('SUM(payment_settlements.paid_amount) as paid_amount')
            ->selectRaw('COUNT(payment_settlements.id) as payment_event_count')
            ->groupBy('payment_settlements.debit_order_id', 'payment_settlements.installment_id')
            ->havingRaw('SUM(payment_settlements.paid_amount) <> 0');

        $rows = DB::query()
            ->fromSub($settledInstallments, 'settled_installments')
            ->join('debit_orders', 'debit_orders.id', '=', 'settled_installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->select('partners.uuid', 'partners.business_name as partner_name')
            ->selectRaw('COUNT(settled_installments.installment_id) as number_of_collection')
            ->selectRaw('SUM(settled_installments.payment_event_count) as payment_event_count')
            ->selectRaw('SUM(CASE WHEN settled_installments.paid_amount >= settled_installments.expected_amount THEN 1 ELSE 0 END) as fully_paid_count')
            ->selectRaw('SUM(CASE WHEN settled_installments.paid_amount < settled_installments.expected_amount THEN 1 ELSE 0 END) as partial_paid_count')
            ->selectRaw('COALESCE(SUM(settled_installments.expected_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(settled_installments.paid_amount), 0) as paid_amount')
            ->groupBy('partners.id', 'partners.uuid', 'partners.business_name')
            ->get()
            ->map(function ($row) {
                $expectedAmount = (float) $row->total_amount;
                $paidAmount = (float) $row->paid_amount;

                return [
                    'uuid' => $row->uuid,
                    'partner_name' => $row->partner_name ?? 'Unknown',
                    'number_of_collection' => (int) $row->number_of_collection,
                    'payment_event_count' => (int) $row->payment_event_count,
                    'success_count' => (int) $row->number_of_collection,
                    'failed_count' => 0,
                    'fully_paid_count' => (int) $row->fully_paid_count,
                    'partial_paid_count' => (int) $row->partial_paid_count,
                    'total_amount' => $expectedAmount,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => max($expectedAmount - $paidAmount, 0),
                    'percent' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('paid_amount')
            ->values();

        $expectedAmount = (float) $rows->sum('total_amount');
        $paidAmount = (float) $rows->sum('paid_amount');

        return [
            'rows' => $rows->all(),
            'stats' => [
                'providers' => $rows->count(),
                'collections' => (int) $rows->sum('number_of_collection'),
                'payment_events' => (int) $rows->sum('payment_event_count'),
                'success_collections' => (int) $rows->sum('success_count'),
                'failed_collections' => 0,
                'fully_paid_collections' => (int) $rows->sum('fully_paid_count'),
                'partial_paid_collections' => (int) $rows->sum('partial_paid_count'),
                'expected_amount' => $expectedAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => max($expectedAmount - $paidAmount, 0),
                'success_rate' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
            ],
        ];
    }

    protected function actualDetail(string $period, string $partnerUuid, array $filters): array
    {
        $partner = Partner::where('uuid', $partnerUuid)->firstOrFail();
        $records = $this->settlementRecords($period, $filters, $partner);
        $rows = $records->map(fn (PaymentSettlement $settlement) => $this->mapSettlement($settlement))->values();
        $byInstallment = $records->groupBy('installment_id');
        $expectedAmount = (float) $byInstallment->sum(function (Collection $items) {
            return (float) (optional($items->first()->installment)->installment_amount ?? 0);
        });
        $paidAmount = (float) $records->sum('paid_amount');
        $fullyPaidCount = $byInstallment->filter(fn (Collection $items) =>
            (float) $items->sum('paid_amount') >= (float) (optional($items->first()->installment)->installment_amount ?? 0)
        )->count();

        return [
            'partner' => $partner,
            'rows' => $rows->all(),
            'stats' => [
                'collections' => $byInstallment->count(),
                'payment_events' => $records->count(),
                'success_collections' => $byInstallment->count(),
                'failed_collections' => 0,
                'fully_paid_collections' => $fullyPaidCount,
                'partial_paid_collections' => $byInstallment->count() - $fullyPaidCount,
                'expected_amount' => $expectedAmount,
                'paid_amount' => $paidAmount,
                'outstanding_amount' => max($expectedAmount - $paidAmount, 0),
                'success_rate' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
            ],
        ];
    }

    protected function settlementRecords(string $period, array $filters, ?Partner $partner = null): Collection
    {
        return $this->settlementQuery($period, $filters, $partner)
            ->with([
                'debitOrder:id,reference,periodicity,channel,start_date,end_date,partner_id,customer_account_id',
                'debitOrder.partner:id,uuid,business_name',
                'debitOrder.customerAccount:id,account_name,account_number,customer_id',
                'debitOrder.customerAccount.customer:id,address',
                'installment:id,debit_order_id,installment_amount,payment_date',
            ])
            ->orderBy('payment_settlements.effective_payment_date')
            ->get();
    }

    protected function settlementQuery(string $period, array $filters, ?Partner $partner = null): Builder
    {
        $authorizedInstallments = Installment::filterCondition()->select('installments.id');
        $query = PaymentSettlement::filterCondition()
            ->whereIn('payment_settlements.installment_id', $authorizedInstallments)
            ->whereNotNull('payment_settlements.installment_id')
            ->reportable('payment_settlements')
            ->when(!empty($filters['channel']), function ($query) use ($filters) {
                $query->whereHas('debitOrder', fn ($debitOrder) => $debitOrder->where('channel', $filters['channel']));
            })
            ->when($partner, function ($query) use ($partner) {
                $query->whereHas('debitOrder', fn ($debitOrder) => $debitOrder->where('partner_id', $partner->id));
            });

        $this->applyPeriodToColumn($query, 'payment_settlements.effective_payment_date', $period, $filters);

        return $query;
    }

    protected function mapSettlement(PaymentSettlement $settlement): array
    {
        $debitOrder = $settlement->debitOrder;
        $installment = $settlement->installment;
        $account = optional($debitOrder)->customerAccount;
        $customer = optional($account)->customer;
        $expectedAmount = $this->settlementExpectedAmount($settlement);
        $paidAmount = (float) $settlement->paid_amount;
        $status = match ($settlement->normalized_status) {
            PaymentSettlement::STATUS_SUCCESS => 'CLOSED',
            PaymentSettlement::STATUS_REVERSED => 'REVERSED',
            default => 'PARTIAL_PAID',
        };

        return [
            'id' => $settlement->id,
            'business_name' => optional(optional($debitOrder)->partner)->business_name,
            'reference' => $debitOrder->reference ?? '',
            'channel' => $debitOrder->channel ?? '',
            'periodicity' => $debitOrder->periodicity ?? '',
            'account_name' => strtoupper((string) ($account->account_name ?? '')),
            'account_number' => $account->account_number ?? '',
            'customer_address' => $customer->address ?? '',
            'amount' => $expectedAmount,
            'last_paid_amount' => $paidAmount,
            'current_balance' => $paidAmount,
            'outstanding_amount' => max($expectedAmount - $paidAmount, 0),
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'remarks' => $settlement->remarks,
            'payment_date' => $installment?->payment_date ? Carbon::parse($installment->payment_date)->format('d-m-Y') : null,
            'effective_payment_date' => $settlement->effective_payment_date ? Carbon::parse($settlement->effective_payment_date)->format('d-m-Y') : null,
            'start_date' => $debitOrder?->start_date ? Carbon::parse($debitOrder->start_date)->format('d-m-Y') : null,
            'end_date' => $debitOrder?->end_date ? Carbon::parse($debitOrder->end_date)->format('d-m-Y') : null,
        ];
    }

    protected function settlementExpectedAmount(PaymentSettlement $settlement): float
    {
        $expected = (float) ($settlement->expected_amount ?? 0);

        return $expected > 0 ? $expected : (float) $settlement->paid_amount;
    }
}
