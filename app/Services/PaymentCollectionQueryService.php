<?php

namespace App\Services;

use App\Models\PaymentSettlement;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentCollectionQueryService
{
    public function __construct(private DashboardReportingQueryService $reporting)
    {
    }

    public function query(User $user, array $filters): Builder
    {
        $query = $this->reporting->paymentCollections($user, [
            'partner_id' => $filters['partner_id'] ?? null,
            'channel' => $filters['channel'] ?? null,
            'mandate_type' => $filters['mandate_type'] ?? null,
            'keyword' => $filters['keyword'] ?? null,
        ]);

        $query->whereBetween('effective_payment_date', [
            $filters['start_at'],
            $filters['end_at'],
        ]);

        if (!empty($filters['collection_status'])) {
            $query->where('collection_status', $filters['collection_status']);
        }

        return $query->select([
            'settlement_id',
            'partner_id',
            'partner_name',
            'channel',
            'mandate_type',
            'mandate_reference',
            'collection_payment_reference',
            'raw_status',
            'collection_status',
            'expected_amount',
            'paid_amount',
            'effective_payment_date',
            'collection_remarks',
        ]);
    }

    public function summary(Builder $query): object
    {
        return (clone $query)
            ->reorder()
            ->select([
                DB::raw('COUNT(settlement_id) as collection_count'),
                DB::raw('COALESCE(SUM(expected_amount), 0) as expected_amount'),
                DB::raw('COALESCE(SUM(paid_amount), 0) as paid_amount'),
            ])
            ->first();
    }

    public function accessibleProviders(User $user): Collection
    {
        return $this->reporting->mandates($user)
            ->select('partner_id', 'partner_name')
            ->distinct()
            ->orderBy('partner_name')
            ->get();
    }

    public static function statuses(): array
    {
        return [
            PaymentSettlement::STATUS_SUCCESS,
            PaymentSettlement::STATUS_PARTIAL,
            PaymentSettlement::STATUS_REVERSED,
        ];
    }
}
