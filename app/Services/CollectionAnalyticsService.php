<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Installment;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CollectionAnalyticsService
{
    public const TYPE_EXPECTED = 'expected';
    public const TYPE_ACTUAL = 'actual';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_DAILY = 'daily';

    protected const ACTUAL_STATUSES = ['CLOSED', 'PARTIAL_PAID'];

    public function paymentChannels()
    {
        return Account::where('active', 1)->get();
    }

    public function summary(string $type, string $period, array $filters): array
    {
        $records = $this->records($type, $period, $filters);

        $rows = $records
            ->groupBy(fn (Installment $installment) => optional($installment->debitOrder)->partner_id)
            ->map(function (Collection $items) {
                $partner = optional(optional($items->first())->debitOrder)->partner;
                $expectedAmount = (float) $items->sum('installment_amount');
                $paidAmount = (float) $items->sum('current_balance');
                $outstandingAmount = (float) $items->sum(fn (Installment $installment) => $this->derivedOutstanding($installment));

                return [
                    'uuid' => $partner->uuid ?? null,
                    'partner_name' => $partner->business_name ?? 'Unknown',
                    'number_of_collection' => $items->count(),
                    'total_amount' => $expectedAmount,
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $outstandingAmount,
                    'percent' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
                ];
            })
            ->filter(fn (array $row) => $row['uuid'] !== null && $row['number_of_collection'] > 0)
            ->sortBy([
                ['number_of_collection', 'desc'],
                ['total_amount', 'desc'],
                ['paid_amount', 'desc'],
            ])
            ->values();

        return [
            'rows' => $rows->all(),
            'stats' => [
                'providers' => $rows->count(),
                'collections' => (int) $rows->sum('number_of_collection'),
                'expected_amount' => (float) $rows->sum('total_amount'),
                'paid_amount' => (float) $rows->sum('paid_amount'),
                'outstanding_amount' => (float) $rows->sum('outstanding_amount'),
                'success_rate' => $rows->sum('total_amount') > 0
                    ? round(($rows->sum('paid_amount') / $rows->sum('total_amount')) * 100, 2)
                    : 0,
            ],
        ];
    }

    public function detail(string $type, string $period, string $partnerUuid, array $filters): array
    {
        $partner = Partner::where('uuid', $partnerUuid)->firstOrFail();
        $records = $this->records($type, $period, $filters, $partner);

        $rows = $records->map(function (Installment $installment) {
            return $this->mapInstallment($installment);
        })->values();

        return [
            'partner' => $partner,
            'rows' => $rows->all(),
            'stats' => [
                'collections' => $records->count(),
                'expected_amount' => (float) $records->sum('installment_amount'),
                'paid_amount' => (float) $records->sum('current_balance'),
                'outstanding_amount' => (float) $records->sum(fn (Installment $installment) => $this->derivedOutstanding($installment)),
                'success_rate' => $records->sum('installment_amount') > 0
                    ? round(($records->sum('current_balance') / $records->sum('installment_amount')) * 100, 2)
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

    protected function baseQuery(string $type, array $filters, ?Partner $partner = null): Builder
    {
        $query = Installment::filterCondition()
            ->with([
                'debitOrder:id,reference,periodicity,channel,start_date,end_date,partner_id,customer_account_id',
                'debitOrder.partner:id,uuid,business_name',
                'debitOrder.customerAccount:id,account_name,account_number,customer_id',
                'debitOrder.customerAccount.customer:id,address',
            ])
            ->whereHas('debitOrder', function ($query) use ($filters, $partner) {
                $query->where('debit_orders.approved', 1)
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
            'current_balance' => (float) $installment->current_balance,
            'outstanding_amount' => $this->derivedOutstanding($installment),
            'status' => $installment->status,
            'status_label' => $this->statusLabel($installment->status),
            'remarks' => $installment->remarks,
            'payment_date' => $installment->payment_date ? Carbon::parse($installment->payment_date)->format('d-m-Y') : null,
            'effective_payment_date' => $installment->effective_payment_date ? Carbon::parse($installment->effective_payment_date)->format('d-m-Y') : null,
            'start_date' => $debitOrder && $debitOrder->start_date ? Carbon::parse($debitOrder->start_date)->format('d-m-Y') : null,
            'end_date' => $debitOrder && $debitOrder->end_date ? Carbon::parse($debitOrder->end_date)->format('d-m-Y') : null,
        ];
    }

    protected function statusLabel(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'CLOSED' => 'PAID',
            'PARTIAL_PAID' => 'PARTIAL PAID',
            'FAILED' => 'FAILED',
            'FUNDSCHECK' => 'FUNDSCHECK',
            'OPEN' => 'OPEN',
            default => strtoupper((string) $status),
        };
    }

    protected function derivedOutstanding(Installment $installment): float
    {
        return max((float) $installment->installment_amount - (float) $installment->current_balance, 0);
    }
}
