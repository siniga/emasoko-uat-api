<?php

namespace App\Services\Reports;

use App\Services\Reports\Mappers\CollectionReportMapper;
use App\Services\Reports\Mappers\CrbReportMapper;
use App\Services\Reports\Mappers\AccountingReportMapper;
use App\Services\Reports\Mappers\DelinquentAgeAnalysisReportMapper;
use App\Services\Reports\Mappers\MandateLifecycleReportMapper;
use App\Services\Reports\Mappers\MandateReportMapper;
use App\Services\Reports\Mappers\OverdueInstallmentReportMapper;
use App\Services\Reports\Mappers\PartnerPerformanceReportMapper;
use App\Services\Reports\Mappers\ReportMapper;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
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
    public const TYPE_CRB_REPORT = 'CRB_REPORT';
    public const TYPE_ACCOUNTING_REPORT = 'ACCOUNTING_REPORT';
    public const TYPE_DELINQUENT_AGE_ANALYSIS = 'DELINQUENT_AGE_ANALYSIS';

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
                'description' => 'Net posted settlement cash based on effective payment dates; reversals appear as negative events.',
                'status_filter' => 'collection',
            ],
            self::TYPE_OVERDUE_INSTALLMENTS => [
                'label' => 'Overdue Installments',
                'description' => 'Overdue unpaid installment exposure across mandates and providers.',
                'status_filter' => 'none',
            ],
            self::TYPE_PARTNER_PERFORMANCE => [
                'label' => 'Partner Performance',
                'description' => 'Provider schedule performance: scheduled installments versus net settlement amounts allocated to them.',
                'status_filter' => 'none',
            ],
            self::TYPE_MANDATE_LIFECYCLE => [
                'label' => 'Mandate Lifecycle Summary',
                'description' => 'Lifecycle distribution of mandates using app-owned mandate statuses.',
                'status_filter' => 'none',
            ],
            self::TYPE_CRB_REPORT => [
                'label' => 'CRB Report',
                'description' => 'Borrower exposure extract showing customer identity, mandate exposure, and delinquency classification.',
                'status_filter' => 'mandate',
            ],
            self::TYPE_ACCOUNTING_REPORT => [
                'label' => 'Accounting Report',
                'description' => 'Operational accounting-style report for expected, paid, and outstanding collection lines.',
                'status_filter' => 'collection',
            ],
            self::TYPE_DELINQUENT_AGE_ANALYSIS => [
                'label' => 'Delinquent Age Analysis',
                'description' => 'Ageing-based delinquency report with days past due, buckets, and highlight levels.',
                'status_filter' => 'none',
            ],
        ];
    }

    public function mandateStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'active' => 'Active',
            'cancellation_requested' => 'Cancellation Requested',
            'cancelled' => 'Cancelled',
            'closed' => 'Closed',
            'failed' => 'Failed',
        ];
    }

    public function collectionStatusOptions(): array
    {
        return [
            'pending' => 'Pending Collection',
            'paid' => 'Paid',
            'partial_paid' => 'Partial Paid',
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
        if ($type === self::TYPE_EXPECTED_COLLECTION) {
            return $this->expectedCollectionPreview($filters);
        }

        if ($type === self::TYPE_ACTUAL_COLLECTION) {
            return $this->actualCollectionPreview($filters);
        }

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

    protected function expectedCollectionPreview(array $filters): array
    {
        $query = $this->expectedCollectionBaseQuery($filters);
        $mapper = new CollectionReportMapper(false);

        $totals = (clone $query)
            ->reorder()
            ->select([])
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as expected_amount')
            ->selectRaw('COALESCE(SUM(LEAST(GREATEST(COALESCE(settlement_totals.ledger_paid_amount, 0), 0), installments.installment_amount)), 0) as paid_amount')
            ->selectRaw('COALESCE(SUM(installments.installment_amount - LEAST(GREATEST(COALESCE(settlement_totals.ledger_paid_amount, 0), 0), installments.installment_amount)), 0) as outstanding_amount')
            ->first();

        $totalRecords = (int) ($totals->total_records ?? 0);
        $records = $query
            ->with([
                'debitOrder.partner',
                'debitOrder.customerAccount.customer',
            ])
            ->orderBy('installments.payment_date', 'ASC')
            ->limit(100)
            ->get();

        return [
            'type' => self::TYPE_EXPECTED_COLLECTION,
            'title' => $this->reportDefinitions()[self::TYPE_EXPECTED_COLLECTION]['label'],
            'headings' => $mapper->headings(),
            'summary' => [
                ['label' => 'Installments', 'value' => number_format($totalRecords), 'tone' => 'primary'],
                ['label' => 'Expected Amount', 'value' => number_format((float) ($totals->expected_amount ?? 0), 2), 'tone' => 'primary'],
                ['label' => 'Outstanding', 'value' => number_format((float) ($totals->outstanding_amount ?? 0), 2), 'tone' => 'warning'],
                ['label' => 'Paid Amount', 'value' => number_format((float) ($totals->paid_amount ?? 0), 2), 'tone' => 'info'],
            ],
            'rows' => $mapper->rows($records),
            'total_records' => $totalRecords,
            'preview_limited' => $totalRecords > 100,
        ];
    }

    protected function actualCollectionPreview(array $filters): array
    {
        $query = $this->actualCollectionBaseQuery($filters);
        $mapper = new CollectionReportMapper(true);
        $totals = (clone $query)
            ->reorder()
            ->select([])
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw('COUNT(DISTINCT payment_settlements.installment_id) as paid_installments')
            ->selectRaw("SUM(CASE WHEN payment_settlements.normalized_status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_events")
            ->selectRaw('COALESCE(SUM(payment_settlements.paid_amount), 0) as paid_amount')
            ->first();
        $totalRecords = (int) ($totals->total_records ?? 0);
        $paidAmount = (float) ($totals->paid_amount ?? 0);
        $records = $query
            ->orderBy('payment_settlements.effective_payment_date', 'ASC')
            ->limit(100)
            ->get();

        return [
            'type' => self::TYPE_ACTUAL_COLLECTION,
            'title' => $this->reportDefinitions()[self::TYPE_ACTUAL_COLLECTION]['label'],
            'headings' => $mapper->headings(),
            'summary' => [
                ['label' => 'Payment Events', 'value' => number_format($totalRecords), 'tone' => 'primary'],
                ['label' => 'Collected Amount', 'value' => number_format($paidAmount, 2), 'tone' => 'success'],
                ['label' => 'Paid Installments', 'value' => number_format((int) ($totals->paid_installments ?? 0)), 'tone' => 'primary'],
                ['label' => 'Partial Events', 'value' => number_format((int) ($totals->partial_events ?? 0)), 'tone' => 'warning'],
            ],
            'rows' => $mapper->rows($records),
            'total_records' => $totalRecords,
            'preview_limited' => $totalRecords > 100,
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

    public function collectionExportQuery(string $type, array $filters): Builder
    {
        $query = match ($type) {
            self::TYPE_EXPECTED_COLLECTION => $this->expectedCollectionBaseQuery($filters)
                ->orderBy('installments.payment_date', 'ASC'),
            self::TYPE_ACTUAL_COLLECTION => $this->actualCollectionBaseQuery($filters)
                ->orderBy('payment_settlements.effective_payment_date', 'ASC'),
            default => throw new \InvalidArgumentException("Report type [{$type}] is not a collection report."),
        };

        return $query->with([
            'debitOrder.partner',
            'debitOrder.customerAccount.customer',
        ]);
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
            self::TYPE_CRB_REPORT => "CRB_Report_{$stamp}.xlsx",
            self::TYPE_ACCOUNTING_REPORT => "Accounting_Report_{$stamp}.xlsx",
            self::TYPE_DELINQUENT_AGE_ANALYSIS => "Delinquent_Age_Analysis_{$stamp}.xlsx",
            default => "Report_{$stamp}.xlsx",
        };
    }

    public function records(string $type, array $filters): Collection
    {
        return match ($type) {
            self::TYPE_MANDATE => $this->mandateBaseQuery($filters)->orderBy('debit_orders.created_at', 'DESC')->get(),
            self::TYPE_EXPECTED_COLLECTION => $this->expectedCollectionBaseQuery($filters)->orderBy('installments.payment_date', 'ASC')->get(),
            self::TYPE_ACTUAL_COLLECTION => $this->actualCollectionBaseQuery($filters)->orderBy('payment_settlements.effective_payment_date', 'ASC')->get(),
            self::TYPE_OVERDUE_INSTALLMENTS => $this->overdueInstallmentsBaseQuery($filters)->orderBy('installments.payment_date', 'ASC')->get(),
            self::TYPE_PARTNER_PERFORMANCE => $this->partnerPerformanceRecords($filters),
            self::TYPE_MANDATE_LIFECYCLE => $this->mandateLifecycleRecords($filters),
            self::TYPE_CRB_REPORT => $this->crbRecords($filters),
            self::TYPE_ACCOUNTING_REPORT => $this->accountingRecords($filters),
            self::TYPE_DELINQUENT_AGE_ANALYSIS => $this->delinquentAgeAnalysisRecords($filters),
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
            self::TYPE_CRB_REPORT => new CrbReportMapper(),
            self::TYPE_ACCOUNTING_REPORT => new AccountingReportMapper(),
            self::TYPE_DELINQUENT_AGE_ANALYSIS => new DelinquentAgeAnalysisReportMapper(),
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

        $this->applyMandateStatusFilter($query, $filters['mandate_status'] ?? null);

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
                'partial_paid' => 'PARTIAL_PAID',
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
        $mappedStatus = match ($filters['collection_status'] ?? null) {
            'paid' => [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_REVERSED],
            'partial_paid' => [PaymentSettlement::STATUS_PARTIAL, PaymentSettlement::STATUS_REVERSED],
            null, '' => PaymentSettlement::reportableStatuses(),
            default => [],
        };

        $authorizedInstallments = Installment::filterCondition()->select('installments.id');
        $settlementTotals = PaymentSettlement::query()
            ->select('installment_id')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as ledger_paid_amount')
            ->reportable()
            ->groupBy('installment_id');

        $query = PaymentSettlement::filterCondition()
            ->leftJoinSub($settlementTotals, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.installment_id', '=', 'payment_settlements.installment_id');
            })
            ->select('payment_settlements.*')
            ->selectRaw('COALESCE(settlement_totals.ledger_paid_amount, 0) as ledger_paid_amount')
            ->with([
                'debitOrder.partner',
                'debitOrder.customerAccount.customer',
                'installment',
            ])
            ->whereIn('payment_settlements.installment_id', $authorizedInstallments)
            ->reportable('payment_settlements')
            ->whereIn('payment_settlements.normalized_status', $mappedStatus);

        $this->applyDateRange($query, 'payment_settlements.effective_payment_date', $filters['start_at'], $filters['end_at']);
        $this->applyInstallmentRelationFilters($query, $filters);

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
        $settlementTotals = PaymentSettlement::query()
            ->select('installment_id')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as ledger_paid_amount')
            ->reportable()
            ->groupBy('installment_id');

        $query = Installment::filterCondition()
            ->leftJoinSub($settlementTotals, 'settlement_totals', function ($join) {
                $join->on('settlement_totals.installment_id', '=', 'installments.id');
            })
            ->select(
                'installments.id',
                'installments.debit_order_id',
                'installments.installment_amount',
                'installments.last_paid_amount',
                'installments.current_balance',
                'installments.outstanding_amount',
                'installments.status',
                'installments.remarks',
                'installments.payment_date',
                'installments.effective_payment_date',
                'installments.created_at'
            )
            ->selectRaw('COALESCE(settlement_totals.ledger_paid_amount, 0) as ledger_paid_amount')
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
                    'collected_amount' => $items->sum(fn ($item) => min((float) $item->ledger_paid_amount, (float) $item->installment_amount)),
                    'outstanding_amount' => $items->sum(fn ($item) => max(
                        (float) $item->installment_amount - min((float) $item->ledger_paid_amount, (float) $item->installment_amount),
                        0
                    )),
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

    protected function crbRecords(array $filters): Collection
    {
        return $this->mandateBaseQuery($filters)
            ->with([
                'partner:id,business_name',
                'customerAccount:id,customer_id,account_number,account_name,phone',
                'customerAccount.customer:id,first_name,middle_name,last_name,fullname,dob,id_number,address',
                'installments:id,debit_order_id,payment_date,outstanding_amount,status',
            ])
            ->get()
            ->map(function (DebitOrder $record) {
                $customer = optional($record->customerAccount)->customer;
                $openOverdues = $record->installments
                    ->filter(function ($installment) {
                        return !in_array($installment->status, ['CLOSED', 'PARTIAL_PAID'], true)
                            && !empty($installment->payment_date)
                            && Carbon::parse($installment->payment_date)->lt(now()->startOfDay());
                    });

                $maxDaysPastDue = $openOverdues->max(function ($installment) {
                    return Carbon::parse($installment->payment_date)->startOfDay()->diffInDays(now()->startOfDay());
                }) ?? 0;

                return [
                    'provider_name' => $record->partner->business_name ?? '',
                    'customer_name' => trim((string) ($customer->full_name ?? $customer->fullname ?? '')),
                    'id_number' => $customer->id_number ?? '',
                    'phone' => $record->customerAccount->phone ?? '',
                    'account_number' => $record->customerAccount->account_number ?? '',
                    'mandate_reference' => $record->reference,
                    'facility_status' => strtoupper(str_replace('_', ' ', $record->resolved_lifecycle_status)),
                    'total_exposure' => (float) ($record->total_amount ?? 0),
                    'paid_amount' => (float) ($record->current_balance ?? 0),
                    'outstanding_amount' => (float) ($record->outstanding_amount ?? 0),
                    'installment_amount' => (float) ($record->installment_amount == 0 ? $record->max_installment_amount : $record->installment_amount),
                    'start_date' => $record->start_date,
                    'end_date' => $record->end_date,
                    'overdue_installments' => $openOverdues->count(),
                    'max_days_past_due' => (int) $maxDaysPastDue,
                    'crb_classification' => $this->delinquencyHighlight($maxDaysPastDue),
                    'customer_address' => $customer->address ?? '',
                    'dob' => $customer->dob ?? null,
                ];
            })
            ->values();
    }

    protected function accountingRecords(array $filters): Collection
    {
        $query = PaymentSettlement::filterCondition()
            ->with([
                'debitOrder:id,reference,partner_id,customer_account_id,start_date,end_date,channel',
                'debitOrder.partner:id,business_name',
                'debitOrder.customerAccount:id,customer_id,account_id,account_name,account_number',
                'debitOrder.customerAccount.customer:id,first_name,middle_name,last_name,fullname',
                'debitOrder.customerAccount.account:id,name',
                'installment:id,debit_order_id,payment_date,outstanding_amount',
            ])
            ->whereIn('normalized_status', ['SUCCESS', 'PARTIAL', 'FAILED', 'FUNDSCHECK', 'UNALLOCATED']);

        $this->applyDateRange($query, 'effective_payment_date', $filters['start_at'], $filters['end_at']);

        $query->when($filters['partner_id'] ?? null, function ($q, $partnerId) {
            $q->whereHas('debitOrder', function ($debitOrderQuery) use ($partnerId) {
                $debitOrderQuery->where('partner_id', $partnerId);
            });
        });

        $query->when($filters['account_id'] ?? null, function ($q, $accountId) {
            $q->whereHas('debitOrder.customerAccount', function ($accountQuery) use ($accountId) {
                $accountQuery->where('account_id', $accountId);
            });
        });

        $query->when($filters['account_type_id'] ?? null, function ($q, $accountTypeId) {
            $q->whereHas('debitOrder.customerAccount', function ($accountQuery) use ($accountTypeId) {
                $accountQuery->where('account_type_id', $accountTypeId);
            });
        });

        $query->when($filters['collection_status'] ?? null, function ($q, $status) {
            $mapped = match ($status) {
                'paid' => ['SUCCESS'],
                'failed' => ['FAILED'],
                'fund_check' => ['FUNDSCHECK'],
                default => null,
            };

            if ($mapped) {
                $q->whereIn('normalized_status', $mapped);
            }
        });

        return $query
            ->orderBy('effective_payment_date', 'ASC')
            ->get()
            ->map(function (PaymentSettlement $record) {
                $status = strtoupper((string) $record->normalized_status);

                $entryClassification = match ($status) {
                    'CLOSED' => 'CLEARED',
                    'SUCCESS' => 'CLEARED',
                    'PARTIAL' => 'PARTIAL',
                    'UNALLOCATED' => 'RECONCILIATION',
                    'PARTIAL_PAID' => 'PARTIAL',
                    'FAILED', 'FUNDSCHECK' => 'EXCEPTION',
                    default => 'RECEIVABLE',
                };

                return [
                    'provider_name' => $record->debitOrder->partner->business_name ?? '',
                    'mandate_reference' => $record->mandate_reference ?? $record->debitOrder->reference ?? '',
                    'customer_name' => trim((string) (optional($record->debitOrder->customerAccount->customer)->full_name ?? optional($record->debitOrder->customerAccount->customer)->fullname ?? '')),
                    'account_number' => $record->debitOrder->customerAccount->account_number ?? '',
                    'payment_channel' => $record->channel ?: ($record->debitOrder->customerAccount->account->name ?? ''),
                    'due_date' => optional($record->installment)->payment_date,
                    'effective_date' => $record->effective_payment_date,
                    'expected_amount' => (float) ($record->expected_amount ?? 0),
                    'paid_amount' => (float) ($record->paid_amount ?? 0),
                    'outstanding_amount' => (float) ($record->closing_installment_balance ?? optional($record->installment)->outstanding_amount ?? 0),
                    'collection_status' => $status,
                    'entry_classification' => $record->settlement_type ?: $entryClassification,
                    'remarks' => $record->remarks ?? '',
                ];
            })
            ->values();
    }

    protected function delinquentAgeAnalysisRecords(array $filters): Collection
    {
        return $this->overdueInstallmentsBaseQuery($filters)
            ->with([
                'debitOrder:id,reference,partner_id,customer_account_id',
                'debitOrder.partner:id,business_name',
                'debitOrder.customerAccount:id,customer_id,account_name,account_number',
                'debitOrder.customerAccount.customer:id,first_name,middle_name,last_name,fullname,id_number,address',
            ])
            ->get()
            ->map(function (Installment $record) {
                $daysPastDue = !empty($record->payment_date)
                    ? Carbon::parse($record->payment_date)->startOfDay()->diffInDays(now()->startOfDay())
                    : 0;

                return [
                    'provider_name' => $record->debitOrder->partner->business_name ?? '',
                    'mandate_reference' => $record->debitOrder->reference ?? '',
                    'customer_name' => trim((string) (optional($record->debitOrder->customerAccount->customer)->full_name ?? optional($record->debitOrder->customerAccount->customer)->fullname ?? '')),
                    'id_number' => $record->debitOrder->customerAccount->customer->id_number ?? '',
                    'account_number' => $record->debitOrder->customerAccount->account_number ?? '',
                    'due_date' => $record->payment_date,
                    'days_past_due' => $daysPastDue,
                    'age_bucket' => $this->ageBucket($daysPastDue),
                    'risk_highlight' => $this->delinquencyHighlight($daysPastDue),
                    'installment_amount' => (float) ($record->installment_amount ?? 0),
                    'outstanding_amount' => (float) ($record->outstanding_amount ?? 0),
                    'status' => strtoupper((string) $record->status),
                    'customer_address' => $record->debitOrder->customerAccount->customer->address ?? '',
                ];
            })
            ->values();
    }

    protected function ageBucket(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue <= 0 => 'CURRENT',
            $daysPastDue <= 30 => '1-30 DAYS',
            $daysPastDue <= 60 => '31-60 DAYS',
            $daysPastDue <= 90 => '61-90 DAYS',
            $daysPastDue <= 180 => '91-180 DAYS',
            default => '180+ DAYS',
        };
    }

    protected function delinquencyHighlight(int $daysPastDue): string
    {
        return match (true) {
            $daysPastDue <= 0 => 'CURRENT',
            $daysPastDue <= 30 => 'WATCHLIST',
            $daysPastDue <= 60 => 'SPECIAL MENTION',
            $daysPastDue <= 90 => 'SUBSTANDARD',
            $daysPastDue <= 180 => 'DOUBTFUL',
            default => 'LOSS',
        };
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

    protected function applyMandateStatusFilter(Builder $query, ?string $status): void
    {
        if (empty($status)) {
            return;
        }

        match ($status) {
            'pending' => $query->where(function ($statusQuery) {
                $statusQuery->whereIn('lifecycle_status', DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_PENDING))
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('lifecycle_status')
                            ->where('approved', 0);
                    });
            }),
            'active' => $query->where(function ($statusQuery) {
                $statusQuery->where('lifecycle_status', DebitOrder::LIFECYCLE_ACTIVE)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('lifecycle_status')
                            ->where('approved', 1)
                            ->where('contract_status', 'OPEN')
                            ->where('cancelled', 0)
                            ->where('closed', 0);
                    });
            }),
            'cancellation_requested' => $query->where('lifecycle_status', DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED),
            'cancelled' => $query->where(function ($statusQuery) {
                $statusQuery->where('lifecycle_status', DebitOrder::LIFECYCLE_CANCELLED)
                    ->orWhere('cancelled', 1);
            }),
            'closed' => $query->where(function ($statusQuery) {
                $statusQuery->where('lifecycle_status', DebitOrder::LIFECYCLE_CLOSED)
                    ->orWhere('closed', 1)
                    ->orWhere('contract_status', 'CLOSED');
            }),
            'failed' => $query->where(function ($statusQuery) {
                $statusQuery->where('lifecycle_status', DebitOrder::LIFECYCLE_FAILED)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('lifecycle_status')
                            ->where('approved', 2);
                    });
            }),
            default => null,
        };
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
