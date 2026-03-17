<?php

namespace App\Services;

use App\Models\DebitOrder;
use App\Models\User;
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
                'debit_type',
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

        return $query->withFilters($filters);
    }
}
