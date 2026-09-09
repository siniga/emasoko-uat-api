<?php

namespace App\Services;

use App\Models\DebitOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class PaymentMandateQueryService
{
    public function baseQuery(array $filters = [], ?User $user = null): Builder
    {
        $query = DebitOrder::filterCondition(includeDeleted: true)
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', '=', 1);
            });

        return $this->applyFilters($query, $filters);
    }

    public function listQuery(array $filters = [], ?User $user = null): Builder
    {
        $query = $this->baseQuery($filters, $user)
            ->with([
                'customerAccount:id,account_name,account_number,phone,customer_id,account_id',
                'customerAccount.customer:id,id_number',
                'customerAccount.account:id,name',
            ])
            ->select(
                'id',
                'uuid',
                'reference',
                'partner_id',
                'customer_account_id',
                'channel',
                'debit_type',
                'total_amount',
                'paid_amount',
                'outstanding_amount',
                'installment_amount',
                'min_installment_amount',
                'max_installment_amount',
                'installment_number',
                'periodicity',
                'start_date',
                'end_date',
                'contract_status',
                'status',
                'remarks',
                'approved',
                'cancelled',
                'closed',
                'archived',
                'lifecycle_status',
                'created_at',
                'created_by'
            );

        return $this->applyOptionalMandateTypeSelects($query);
    }

    public function exportQuery(array $filters = [], ?User $user = null): Builder
    {
        $query = $this->baseQuery($filters, $user)
            ->with([
                'partner:id,business_name',
                'customerAccount:id,account_name,account_number,phone,customer_id,account_id',
                'customerAccount.account:id,name',
                'userCreated:id,name',
            ])
            ->select(
                'id',
                'uuid',
                'reference',
                'partner_id',
                'customer_account_id',
                'debit_type',
                'total_amount',
                'paid_amount',
                'outstanding_amount',
                'current_balance',
                'installment_amount',
                'min_installment_amount',
                'max_installment_amount',
                'installment_number',
                'periodicity',
                'start_date',
                'end_date',
                'contract_status',
                'status',
                'remarks',
                'approved',
                'cancelled',
                'closed',
                'archived',
                'lifecycle_status',
                'created_at',
                'created_by'
            );

        return $this->applyOptionalMandateTypeSelects($query);
    }

    protected function applyOptionalMandateTypeSelects(Builder $query): Builder
    {
        foreach (['mandate_type', 'mandate_service', 'service_type'] as $column) {
            if (Schema::hasColumn('debit_orders', $column)) {
                $query->addSelect("debit_orders.{$column}");
            }
        }

        return $query;
    }

    public function stats(array $filters = [], ?User $user = null): array
    {
        $query = $this->baseQuery($filters, $user);
        $stats = ['total' => (clone $query)->count()];

        foreach (DebitOrder::lifecycleStatusLabels() as $status => $label) {
            if ($status === DebitOrder::SUMMARY_PENDING) {
                $stats[$status] = (clone $query)
                    ->whereIn('lifecycle_status', DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_PENDING))
                    ->count();
                continue;
            }

            $stats[$status] = (clone $query)->where('lifecycle_status', $status)->count();
        }

        return $stats;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $activeFilters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

        if (empty($activeFilters)) {
            return $query;
        }

        return $query
            ->when($filters['start_at'] ?? null, function ($query, $startDate) use ($filters) {
                $startAt = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
                $endDate = $filters['end_at'] ?? null;

                if ($endDate) {
                    $endAt = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
                    $query->whereBetween('debit_orders.created_at', [$startAt, $endAt]);
                    return;
                }

                $query->where('debit_orders.created_at', '>=', $startAt);
            })
            ->when(empty($filters['start_at']) && !empty($filters['end_at']), function ($query) use ($filters) {
                $endAt = Carbon::createFromFormat('Y-m-d', $filters['end_at'])->endOfDay();
                $query->where('debit_orders.created_at', '<=', $endAt);
            })
            ->when($filters['payment_channel'] ?? null, function ($query, $paymentChannel) {
                $query->where('debit_orders.channel', $paymentChannel);
            })
            ->when($filters['partner_id'] ?? null, function ($query, $partnerId) {
                $query->where('debit_orders.partner_id', $partnerId);
            })
            ->when($filters['corporate_reference'] ?? null, function ($query, $corporateReference) {
                $query->whereHas('partner', function ($partnerQuery) use ($corporateReference) {
                    $partnerQuery->where('partners.reference', $corporateReference);
                });
            })
            ->when($filters['lifecycle_status'] ?? $filters['status'] ?? null, function ($query, $lifecycleStatus) {
                $this->applyLifecycleStatusFilter($query, $lifecycleStatus);
            })
            ->when($filters['keyword'] ?? null, function ($query, $keyword) {
                $lowered = strtolower($keyword);

                $query->where(function ($query) use ($lowered) {
                    $query->whereRaw('LOWER(reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereHas('customerAccount', function ($customerAccountQuery) use ($lowered) {
                            $customerAccountQuery->where(function ($accountQuery) use ($lowered) {
                                $accountQuery->whereRaw('LOWER(account_name) LIKE ?', ["%{$lowered}%"])
                                    ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$lowered}%"]);
                            });
                        });
                });
            });
    }

    protected function applyLifecycleStatusFilter(Builder $query, string $lifecycleStatus): void
    {
        $lifecycleStatus = DebitOrder::normalizeLifecycleStatus($lifecycleStatus);

        match ($lifecycleStatus) {
            DebitOrder::SUMMARY_PENDING => $query->whereIn(
                'debit_orders.lifecycle_status',
                DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_PENDING)
            ),
            DebitOrder::SUMMARY_APPROVED => $query->whereIn(
                'debit_orders.lifecycle_status',
                DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_APPROVED)
            ),
            DebitOrder::SUMMARY_FAILED => $query->whereIn(
                'debit_orders.lifecycle_status',
                DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_FAILED)
            ),
            DebitOrder::LIFECYCLE_CANCELLED => $query->where(function ($statusQuery) {
                $statusQuery->where('debit_orders.lifecycle_status', DebitOrder::LIFECYCLE_CANCELLED)
                    ->orWhere('debit_orders.cancelled', 1);
            }),
            DebitOrder::LIFECYCLE_CLOSED => $query->where(function ($statusQuery) {
                $statusQuery->where('debit_orders.lifecycle_status', DebitOrder::LIFECYCLE_CLOSED)
                    ->orWhere('debit_orders.closed', 1)
                    ->orWhere('debit_orders.contract_status', 'CLOSED');
            }),
            DebitOrder::LIFECYCLE_ARCHIVED => $query->where(function ($statusQuery) {
                $statusQuery->where('debit_orders.lifecycle_status', DebitOrder::LIFECYCLE_ARCHIVED)
                    ->orWhere('debit_orders.archived', 1);
            }),
            default => $query->where('debit_orders.lifecycle_status', $lifecycleStatus),
        };
    }
}
