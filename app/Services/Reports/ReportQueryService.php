<?php

namespace App\Services\Reports;

use App\Services\Reports\Mappers\CollectionReportMapper;
use App\Services\Reports\Mappers\MandateLifecycleReportMapper;
use App\Services\Reports\Mappers\MandateReportMapper;
use App\Services\Reports\Mappers\OverdueInstallmentReportMapper;
use App\Services\Reports\Mappers\PartnerPerformanceReportMapper;
use App\Services\Reports\Mappers\ReportMapper;
use App\Models\DebitOrder;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportQueryService
{
    public const TYPE_MANDATE = 'MANDATE';
    public const TYPE_EXPECTED_COLLECTION = 'EXPECTED_COLLECTION';
    public const TYPE_ACTUAL_COLLECTION = 'ACTUAL_COLLECTION';
    public const TYPE_OVERDUE_INSTALLMENTS = 'OVERDUE_INSTALLMENTS';
    public const TYPE_PARTNER_PERFORMANCE = 'PARTNER_PERFORMANCE';
    public const TYPE_MANDATE_LIFECYCLE = 'MANDATE_LIFECYCLE';

    public function reportDefinitions(): array
    {
        return [
            self::TYPE_MANDATE => [
                'label' => 'Debit Mandate',
                'description' => 'Mandate portfolio based on debit order lifecycle and contractual values.',
                'status_filter' => 'mandate',
            ],
            self::TYPE_EXPECTED_COLLECTION => [
                'label' => 'Expected Payment Collection',
                'description' => 'Scheduled collection demand based on installment payment dates.',
                'status_filter' => 'collection',
            ],
            self::TYPE_ACTUAL_COLLECTION => [
                'label' => 'Actual Payment Collection',
                'description' => 'Realized collections based on effective payment dates.',
                'status_filter' => 'collection',
            ],
            self::TYPE_OVERDUE_INSTALLMENTS => [
                'label' => 'Overdue Installments',
                'description' => 'Overdue unpaid installment exposure across mandates and providers.',
                'status_filter' => 'none',
            ],
            self::TYPE_PARTNER_PERFORMANCE => [
                'label' => 'Partner Performance',
                'description' => 'Provider-level expected versus collected performance for the selected period.',
                'status_filter' => 'none',
            ],
            self::TYPE_MANDATE_LIFECYCLE => [
                'label' => 'Mandate Lifecycle Summary',
                'description' => 'Lifecycle distribution of mandates using app-owned mandate statuses.',
                'status_filter' => 'none',
            ],
        ];
    }

    public function mandateStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'closed' => 'Closed',
        ];
    }

    public function collectionStatusOptions(): array
    {
        return [
            'pending' => 'Pending Upload',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'fund_check' => 'Fund Check',
        ];
    }

    public function filters(array $input): array
    {
        return [
            'report_type' => $input['report_type'] ?? null,
            'mandate_status' => $input['mandate_status'] ?? null,
            'collection_status' => $input['collection_status'] ?? null,
            'start_at' => $input['start_at'] ?? null,
            'end_at' => $input['end_at'] ?? null,
            'account_type_id' => $input['account_type_id'] ?? null,
            'account_id' => $input['account_id'] ?? null,
            'partner_id' => $input['partner_id'] ?? null,
        ];
    }

    public function preview(string $type, array $filters): array
    {
        $records = $this->records($type, $filters);
        $mapper = $this->mapper($type);

        return [
            'type' => $type,
            'title' => $this->reportDefinitions()[$type]['label'] ?? 'Report Preview',
            'headings' => $mapper->headings(),
            'summary' => $mapper->summary($records),
            'rows' => $mapper->previewRows($records, 100),
            'total_records' => $records->count(),
            'preview_limited' => $records->count() > 100,
        ];
    }

    public function exportPayload(string $type, array $filters): array
    {
        $records = $this->records($type, $filters);
        $mapper = $this->mapper($type);

        return [
            'headings' => $mapper->headings(),
            'rows' => $mapper->rows($records),
            'filename' => $this->filename($type),
        ];
    }

    public function filename(string $type): string
    {
        $stamp = date('YmdHis');

        return match ($type) {
            self::TYPE_MANDATE => "Debit_Mandates_{$stamp}.xlsx",
            self::TYPE_EXPECTED_COLLECTION => "Expected_Payment_Collection_{$stamp}.xlsx",
            self::TYPE_ACTUAL_COLLECTION => "Actual_Payment_Collection_{$stamp}.xlsx",
            self::TYPE_OVERDUE_INSTALLMENTS => "Overdue_Installments_{$stamp}.xlsx",
            self::TYPE_PARTNER_PERFORMANCE => "Partner_Performance_{$stamp}.xlsx",
            self::TYPE_MANDATE_LIFECYCLE => "Mandate_Lifecycle_Summary_{$stamp}.xlsx",
            default => "Report_{$stamp}.xlsx",
        };
    }

    public function records(string $type, array $filters): Collection
    {
        return match ($type) {
            self::TYPE_MANDATE => $this->mandateBaseQuery($filters)->orderBy('debit_orders.created_at', 'DESC')->get(),
            self::TYPE_EXPECTED_COLLECTION => $this->expectedCollectionBaseQuery($filters)->orderBy('installments.payment_date', 'ASC')->get(),
            self::TYPE_ACTUAL_COLLECTION => $this->actualCollectionBaseQuery($filters)->orderBy('installments.effective_payment_date', 'ASC')->get(),
            self::TYPE_OVERDUE_INSTALLMENTS => $this->overdueInstallmentsBaseQuery($filters)->orderBy('installments.payment_date', 'ASC')->get(),
            self::TYPE_PARTNER_PERFORMANCE => $this->partnerPerformanceRecords($filters),
            self::TYPE_MANDATE_LIFECYCLE => $this->mandateLifecycleRecords($filters),
            default => collect(),
        };
    }

    protected function mapper(string $type): ReportMapper
    {
        return match ($type) {
            self::TYPE_MANDATE => new MandateReportMapper(),
            self::TYPE_EXPECTED_COLLECTION => new CollectionReportMapper(false),
            self::TYPE_ACTUAL_COLLECTION => new CollectionReportMapper(true),
            self::TYPE_OVERDUE_INSTALLMENTS => new OverdueInstallmentReportMapper(),
            self::TYPE_PARTNER_PERFORMANCE => new PartnerPerformanceReportMapper(),
            self::TYPE_MANDATE_LIFECYCLE => new MandateLifecycleReportMapper(),
            default => new MandateReportMapper(),
        };
    }

    protected function mandateBaseQuery(array $filters): Builder
    {
        $query = DebitOrder::filterCondition()
            ->select(
                'id',
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
                'created_at',
                'created_by',
                'cancelled',
                'closed',
                'lifecycle_status'
            )
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', 1);
            });

        $this->applyDateRange($query, 'debit_orders.created_at', $filters['start_at'], $filters['end_at']);
        $this->applyMandateFilters($query, $filters);

        if (!empty($filters['mandate_status'])) {
            match ($filters['mandate_status']) {
                'pending' => $query->where(['approved' => 0]),
                'approved' => $query->where(['approved' => 1]),
                'failed' => $query->where(['approved' => 2]),
                'cancelled' => $query->where(['cancelled' => 1]),
                'closed' => $query->where(['closed' => 1]),
                default => null,
            };
        }

        return $query;
    }

    protected function expectedCollectionBaseQuery(array $filters): Builder
    {
        $query = $this->collectionBaseQuery($filters);
        $this->applyDateRange($query, 'installments.payment_date', $filters['start_at'], $filters['end_at']);

        if (!empty($filters['collection_status'])) {
            $mappedStatus = match ($filters['collection_status']) {
                'pending' => 'OPEN',
                'paid' => 'CLOSED',
                'failed' => 'FAILED',
                'fund_check' => 'FUNDSCHECK',
                default => null,
            };

            if ($mappedStatus) {
                $query->where('installments.status', $mappedStatus);
            }
        }

        return $query;
    }

    protected function actualCollectionBaseQuery(array $filters): Builder
    {
        $query = $this->collectionBaseQuery($filters);
        $query->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID']);
        $this->applyDateRange($query, 'installments.effective_payment_date', $filters['start_at'], $filters['end_at']);

        return $query;
    }

    protected function overdueInstallmentsBaseQuery(array $filters): Builder
    {
        $query = $this->collectionBaseQuery($filters);
        $query->whereNotIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->whereDate('installments.payment_date', '<', now()->toDateString());

        if (!empty($filters['start_at']) && !empty($filters['end_at'])) {
            $this->applyDateRange($query, 'installments.payment_date', $filters['start_at'], $filters['end_at']);
        }

        return $query;
    }

    protected function collectionBaseQuery(array $filters): Builder
    {
        $query = Installment::filterCondition()
            ->select('id', 'debit_order_id', 'installment_amount', 'last_paid_amount', 'current_balance', 'outstanding_amount', 'status', 'remarks', 'payment_date', 'effective_payment_date', 'created_at')
            ->whereHas('debitOrder', function ($query) {
                $query->where('debit_orders.approved', '=', 1)
                    ->whereHas('partner', function ($q) {
                        $q->where('partners.approved', 1);
                    });
            });

        $this->applyInstallmentRelationFilters($query, $filters);

        return $query;
    }

    protected function partnerPerformanceRecords(array $filters): Collection
    {
        return $this->expectedCollectionBaseQuery($filters)
            ->get()
            ->groupBy('debitOrder.partner_id')
            ->map(function (Collection $items) {
                $partner = optional(optional($items->first())->debitOrder)->partner;

                return [
                    'partner_name' => $partner->business_name ?? 'Unknown',
                    'expected_amount' => $items->sum('installment_amount'),
                    'collected_amount' => $items->sum('current_balance'),
                    'outstanding_amount' => $items->sum('outstanding_amount'),
                    'installments' => $items->count(),
                ];
            })
            ->values();
    }

    protected function mandateLifecycleRecords(array $filters): Collection
    {
        return $this->mandateBaseQuery($filters)
            ->get()
            ->groupBy(fn ($record) => $record->lifecycle_status ?: $record->resolved_lifecycle_status)
            ->map(function (Collection $items, $status) {
                return [
                    'lifecycle_status' => $status,
                    'mandates' => $items->count(),
                    'total_amount' => $items->sum('total_amount'),
                    'paid_amount' => $items->sum('current_balance'),
                    'outstanding_amount' => $items->sum('outstanding_amount'),
                ];
            })
            ->values();
    }

    protected function applyMandateFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['partner_id'])) {
            $query->where('debit_orders.partner_id', $filters['partner_id']);
        }

        if (!empty($filters['account_type_id'])) {
            $query->whereHas('customerAccount', function ($q) use ($filters) {
                $q->where('account_type_id', $filters['account_type_id']);
            });
        }

        if (!empty($filters['account_id'])) {
            $query->whereHas('customerAccount', function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id']);
            });
        }
    }

    protected function applyInstallmentRelationFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['partner_id'])) {
            $query->whereHas('debitOrder', function ($q) use ($filters) {
                $q->where('debit_orders.partner_id', $filters['partner_id']);
            });
        }

        if (!empty($filters['account_type_id'])) {
            $query->whereHas('debitOrder', function ($query) use ($filters) {
                $query->whereHas('customerAccount', function ($q) use ($filters) {
                    $q->where('account_type_id', $filters['account_type_id']);
                });
            });
        }

        if (!empty($filters['account_id'])) {
            $query->whereHas('debitOrder', function ($query) use ($filters) {
                $query->whereHas('customerAccount', function ($q) use ($filters) {
                    $q->where('account_id', $filters['account_id']);
                });
            });
        }
    }

    protected function applyDateRange(Builder $query, string $column, ?string $startAt, ?string $endAt): void
    {
        if (!$startAt || !$endAt) {
            return;
        }

        $start = Carbon::createFromFormat('Y-m-d', $startAt)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', $endAt)->endOfDay();

        $query->whereBetween($column, [$start, $end]);
    }
}
