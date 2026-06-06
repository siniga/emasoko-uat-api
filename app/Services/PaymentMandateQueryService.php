<?php

namespace App\Services;

use App\Models\DebitOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PaymentMandateQueryService
{
    public function baseQuery(array $filters = [], ?User $user = null): Builder
    {
        $query = DebitOrder::filterCondition()
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', '=', 1);
            });

        return $this->applyFilters($query, $filters);
    }

    public function listQuery(array $filters = [], ?User $user = null): Builder
    {
        return $this->baseQuery($filters, $user)
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
                'lifecycle_status',
                'created_at',
                'created_by'
            );
    }

    public function exportQuery(array $filters = [], ?User $user = null): Builder
    {
        return $this->baseQuery($filters, $user)
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
                'lifecycle_status',
                'created_at',
                'created_by'
            );
    }

    public function stats(array $filters = [], ?User $user = null): array
    {
        $query = $this->baseQuery($filters, $user);

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('lifecycle_status', DebitOrder::LIFECYCLE_DRAFT)->count(),
            'submitted' => (clone $query)->where('lifecycle_status', DebitOrder::LIFECYCLE_SUBMITTED)->count(),
            'awaiting_otp' => (clone $query)->where('lifecycle_status', DebitOrder::LIFECYCLE_AWAITING_OTP)->count(),
            'active' => (clone $query)->where('lifecycle_status', DebitOrder::LIFECYCLE_ACTIVE)->count(),
            'failed' => (clone $query)->where('lifecycle_status', DebitOrder::LIFECYCLE_FAILED)->count(),
        ];
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
            ->when($filters['corporate_reference'] ?? null, function ($query, $corporateReference) {
                $query->whereHas('partner', function ($partnerQuery) use ($corporateReference) {
                    $partnerQuery->where('partners.reference', $corporateReference);
                });
            })
            ->when($filters['lifecycle_status'] ?? null, function ($query, $lifecycleStatus) {
                $query->where('debit_orders.lifecycle_status', $lifecycleStatus);
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
}
