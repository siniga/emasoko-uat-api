<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\PaymentCollectionController as ExternalPaymentCollectionController;
use App\Http\Resources\InstallmentResource;
use App\Models\Installment;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

#[Group('Payment Collections', description: 'Mobile APIs for viewing and filtering payment collections.', weight: 3)]
class PaymentCollectionController extends ExternalPaymentCollectionController
{
    public function all(Request $request): JsonResponse
    {
        $filters = $this->collectionFilters($request);
        $collections = $this->expectedCollectionQuery($filters)
            ->orderBy('installments.payment_date', 'ASC')
            ->get();

        return $this->successResponse('Collections fetched successfully.', [
            'collections' => InstallmentResource::collection($collections),
            'summary' => $this->summaryPayload($filters),
            'filters' => $this->filterPayload($filters),
        ]);
    }

    public function filtering(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required_without:uuid|string',
            'uuid' => 'required_without:reference|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $filters = $this->collectionFilters($request);
        $collections = $this->expectedCollectionQuery($filters)
            ->orderBy('installments.payment_date', 'ASC')
            ->get();

        return $this->successResponse('Collections fetched successfully.', [
            'collections' => InstallmentResource::collection($collections),
            'summary' => $this->summaryPayload($filters),
            'filters' => $this->filterPayload($filters),
        ]);
    }

    public function actualCollections(Request $request): JsonResponse
    {
        $filters = $this->collectionFilters($request);
        [$startAt, $endAt] = $this->periodRange($filters);
        $paidStatuses = $this->actualStatuses($filters);

        $dueDateExpression = 'installments.payment_date BETWEEN ? AND ?';
        $paidDateExpression = 'installments.effective_payment_date BETWEEN ? AND ? AND installments.status IN (' . $this->placeholders($paidStatuses) . ')';

        $bindingsForPaidExpression = array_merge([$startAt, $endAt], $paidStatuses);

        $collections = Installment::filterCondition()
            ->join('debit_orders', 'debit_orders.id', '=', 'installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->where('debit_orders.approved', 1)
            ->where('partners.approved', 1)
            ->where(function ($query) use ($startAt, $endAt, $paidStatuses) {
                $query->whereBetween('installments.payment_date', [$startAt, $endAt])
                    ->orWhere(function ($paidQuery) use ($startAt, $endAt, $paidStatuses) {
                        $paidQuery->whereBetween('installments.effective_payment_date', [$startAt, $endAt])
                            ->whereIn('installments.status', $paidStatuses);
                    });
            });

        $this->applyJoinedFilters($collections, $filters);

        $collections = $collections
            ->select('partners.uuid', 'partners.reference', 'partners.business_name as partner_name')
            ->selectRaw("COALESCE(SUM(CASE WHEN {$dueDateExpression} THEN 1 ELSE 0 END), 0) as number_of_collection", [$startAt, $endAt])
            ->selectRaw("COALESCE(SUM(CASE WHEN {$dueDateExpression} THEN installments.installment_amount ELSE 0 END), 0) as total_amount", [$startAt, $endAt])
            ->selectRaw("COALESCE(SUM(CASE WHEN {$dueDateExpression} THEN installments.outstanding_amount ELSE 0 END), 0) as outstanding_amount", [$startAt, $endAt])
            ->selectRaw("COALESCE(SUM(CASE WHEN {$paidDateExpression} THEN installments.current_balance ELSE 0 END), 0) as paid_amount", $bindingsForPaidExpression)
            ->groupBy('partners.id', 'partners.uuid', 'partners.reference', 'partners.business_name')
            ->having('paid_amount', '>', 0)
            ->orderByDesc('number_of_collection')
            ->orderByDesc('paid_amount')
            ->get()
            ->map(function ($row) {
                $totalAmount = (float) $row->total_amount;
                $paidAmount = (float) $row->paid_amount;

                return [
                    'corporate_reference' => $row->reference,
                    'partner_uuid' => $row->uuid,
                    'partner_name' => $row->partner_name,
                    'number_of_collection' => (int) $row->number_of_collection,
                    'total_amount' => $totalAmount,
                    'outstanding_amount' => (float) $row->outstanding_amount,
                    'paid_amount' => $paidAmount,
                    'percent' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0,
                ];
            })
            ->values()
            ->all();

        return $this->successResponse('Actual collections fetched successfully.', [
            'collections' => $collections,
            'summary' => $this->summaryPayload($filters),
            'filters' => $this->filterPayload($filters),
        ]);
    }

    protected function expectedCollectionQuery(array $filters): Builder
    {
        [$startAt, $endAt] = $this->periodRange($filters);

        $query = $this->baseCollectionQuery($filters)
            ->whereBetween('installments.payment_date', [$startAt, $endAt]);

        $this->applyStatusFilter($query, $filters);

        return $query;
    }

    protected function actualCollectionQuery(array $filters): Builder
    {
        [$startAt, $endAt] = $this->periodRange($filters);
        $statuses = $this->actualStatuses($filters);

        return $this->baseCollectionQuery($filters)
            ->whereBetween('installments.effective_payment_date', [$startAt, $endAt])
            ->whereIn('installments.status', $statuses);
    }

    protected function baseCollectionQuery(array $filters): Builder
    {
        $query = Installment::filterCondition()
            ->with([
                'debitOrder.partner:id,uuid,reference,business_name',
                'debitOrder.customerAccount:id,account_name,account_number,phone,customer_id,account_id',
                'debitOrder.customerAccount.account:id,name,slug',
            ])
            ->whereHas('debitOrder', function ($query) {
                $query->where('debit_orders.approved', 1)
                    ->whereHas('partner', function ($partnerQuery) {
                        $partnerQuery->where('partners.approved', 1);
                    });
            });

        $this->applyRelationFilters($query, $filters);

        return $query;
    }

    protected function applyRelationFilters(Builder $query, array $filters): void
    {
        $query->when($filters['payment_channel'] ?? null, function ($query, $channel) {
            $query->whereHas('debitOrder', function ($debitOrderQuery) use ($channel) {
                $debitOrderQuery->where('debit_orders.channel', $channel);
            });
        });

        $query->when($filters['partner_id'] ?? null, function ($query, $partnerId) {
            $query->whereHas('debitOrder', function ($debitOrderQuery) use ($partnerId) {
                $debitOrderQuery->where('debit_orders.partner_id', $partnerId);
            });
        });

        $query->when($filters['corporate_reference'] ?? null, function ($query, $corporateReference) {
            $query->whereHas('debitOrder.partner', function ($partnerQuery) use ($corporateReference) {
                $partnerQuery->where('partners.reference', $corporateReference);
            });
        });

        $query->when($filters['reference'] ?? null, function ($query, $reference) {
            $query->whereHas('debitOrder', function ($debitOrderQuery) use ($reference) {
                $debitOrderQuery->where('debit_orders.reference', $reference)
                    ->orWhere('debit_orders.uuid', $reference);
            });
        });

        $query->when($filters['keyword'] ?? null, function ($query, $keyword) {
            $lowered = strtolower($keyword);

            $query->where(function ($searchQuery) use ($lowered) {
                $searchQuery->whereRaw('LOWER(installments.payment_reference) LIKE ?', ["%{$lowered}%"])
                    ->orWhereHas('debitOrder', function ($debitOrderQuery) use ($lowered) {
                        $debitOrderQuery->whereRaw('LOWER(debit_orders.reference) LIKE ?', ["%{$lowered}%"])
                            ->orWhereHas('customerAccount', function ($accountQuery) use ($lowered) {
                                $accountQuery->whereRaw('LOWER(account_name) LIKE ?', ["%{$lowered}%"])
                                    ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$lowered}%"]);
                            });
                    });
            });
        });
    }

    protected function applyJoinedFilters(Builder $query, array $filters): void
    {
        $query->when($filters['payment_channel'] ?? null, function ($query, $channel) {
            $query->where('debit_orders.channel', $channel);
        });

        $query->when($filters['partner_id'] ?? null, function ($query, $partnerId) {
            $query->where('debit_orders.partner_id', $partnerId);
        });

        $query->when($filters['corporate_reference'] ?? null, function ($query, $corporateReference) {
            $query->where('partners.reference', $corporateReference);
        });

        $query->when($filters['reference'] ?? null, function ($query, $reference) {
            $query->where(function ($referenceQuery) use ($reference) {
                $referenceQuery->where('debit_orders.reference', $reference)
                    ->orWhere('debit_orders.uuid', $reference);
            });
        });

        $query->when($filters['keyword'] ?? null, function ($query, $keyword) {
            $lowered = strtolower($keyword);

            $query->leftJoin('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
                ->where(function ($searchQuery) use ($lowered) {
                    $searchQuery->whereRaw('LOWER(installments.payment_reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereRaw('LOWER(debit_orders.reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereRaw('LOWER(customer_accounts.account_name) LIKE ?', ["%{$lowered}%"])
                        ->orWhereRaw('LOWER(customer_accounts.account_number) LIKE ?', ["%{$lowered}%"]);
                });
        });
    }

    protected function applyStatusFilter(Builder $query, array $filters): void
    {
        if (!empty($filters['collection_status'])) {
            $query->whereIn('installments.status', $this->normalizeCollectionStatuses($filters['collection_status']));
        }
    }

    protected function summaryPayload(array $filters): array
    {
        $expectedQuery = $this->expectedCollectionQuery($filters);
        $actualQuery = $this->actualCollectionQuery($filters);
        $statusRows = (clone $expectedQuery)
            ->select('installments.status')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as amount')
            ->groupBy('installments.status')
            ->get()
            ->mapWithKeys(function ($row) {
                $status = strtoupper((string) ($row->status ?? 'OPEN'));

                return [$status => [
                    'count' => (int) $row->count,
                    'amount' => (float) $row->amount,
                ]];
            });

        $expectedCount = (clone $expectedQuery)->count();
        $expectedAmount = (float) (clone $expectedQuery)->sum('installments.installment_amount');
        $paidCount = (clone $actualQuery)->count();
        $paidAmount = (float) (clone $actualQuery)->sum('installments.current_balance');
        $outstandingAmount = (float) (clone $expectedQuery)->sum('installments.outstanding_amount');

        return [
            'expected_count' => $expectedCount,
            'paid_count' => $paidCount,
            'failed_count' => (int) data_get($statusRows, 'FAILED.count', 0),
            'fundscheck_count' => (int) data_get($statusRows, 'FUNDSCHECK.count', 0),
            'expected_amount' => $expectedAmount,
            'paid_amount' => $paidAmount,
            'outstanding_amount' => $outstandingAmount,
            'success_rate' => $expectedAmount > 0 ? round(($paidAmount / $expectedAmount) * 100, 2) : 0,
            'statuses' => $statusRows,
        ];
    }

    protected function filterPayload(array $filters): array
    {
        [$startAt, $endAt] = $this->periodRange($filters);

        return [
            'current' => array_merge($filters, [
                'start_at' => Carbon::parse($startAt)->toDateString(),
                'end_at' => Carbon::parse($endAt)->toDateString(),
            ]),
            'collection_statuses' => [
                ['value' => 'OPEN', 'label' => 'Open'],
                ['value' => 'CLOSED', 'label' => 'Fully Paid'],
                ['value' => 'PARTIAL_PAID', 'label' => 'Partial Paid'],
                ['value' => 'FAILED', 'label' => 'Failed'],
                ['value' => 'FUNDSCHECK', 'label' => 'Fundscheck'],
                ['value' => 'COLLECTED', 'label' => 'Collected'],
            ],
        ];
    }

    protected function collectionFilters(Request $request): array
    {
        return [
            'year' => (int) $request->input('year', now()->year),
            'month' => (int) $request->input('month', now()->month),
            'start_at' => $request->input('start_at'),
            'end_at' => $request->input('end_at'),
            'collection_status' => $request->input('collection_status', $request->input('status')),
            'payment_channel' => $request->input('payment_channel', $request->input('channel')),
            'partner_id' => $request->input('partner_id'),
            'corporate_reference' => $request->input('corporate_reference'),
            'reference' => $request->input('reference', $request->input('uuid')),
            'keyword' => $request->input('keyword'),
        ];
    }

    protected function periodRange(array $filters): array
    {
        if (!empty($filters['start_at']) && !empty($filters['end_at'])) {
            return [
                Carbon::parse($filters['start_at'])->startOfDay(),
                Carbon::parse($filters['end_at'])->endOfDay(),
            ];
        }

        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);
        $startAt = Carbon::create($year, $month, 1)->startOfDay();

        return [$startAt, $startAt->copy()->endOfMonth()];
    }

    protected function actualStatuses(array $filters): array
    {
        if (empty($filters['collection_status'])) {
            return ['CLOSED', 'PARTIAL_PAID'];
        }

        $statuses = array_values(array_intersect(
            $this->normalizeCollectionStatuses($filters['collection_status']),
            ['CLOSED', 'PARTIAL_PAID']
        ));

        return $statuses ?: ['__NO_ACTUAL_STATUS__'];
    }

    protected function placeholders(array $items): string
    {
        return implode(', ', array_fill(0, count($items), '?'));
    }
}
