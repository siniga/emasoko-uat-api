<?php

namespace App\Services;

use App\Models\CollectionIntegrityIssue;
use App\Models\PaymentSettlement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectionIntegrityService
{
    public const ISSUE_ORPHAN_MANDATE = 'ORPHAN_MANDATE';
    public const ISSUE_ORPHAN_INSTALLMENT = 'ORPHAN_INSTALLMENT';
    public const ISSUE_MISSING_INSTALLMENT = 'MISSING_INSTALLMENT';
    public const ISSUE_DUPLICATE_PAYMENT = 'DUPLICATE_PAYMENT';
    public const ISSUE_OVERALLOCATED = 'OVERALLOCATED_INSTALLMENT';
    public const ISSUE_INVALID_POSTED = 'INVALID_POSTED_SETTLEMENT';

    public function audit(Carbon $start, Carbon $end): array
    {
        $runId = (string) Str::uuid();
        $now = now();
        $types = $this->issueTypes();

        CollectionIntegrityIssue::open()
            ->whereIn('issue_type', $types)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->update([
                'resolved_at' => $now,
                'resolution_note' => 'Not detected by audit ' . $runId,
                'updated_at' => $now,
            ]);

        $detected = collect();
        $detected = $detected->merge($this->orphanMandates($start, $end));
        $detected = $detected->merge($this->orphanInstallments($start, $end));
        $detected = $detected->merge($this->missingInstallments($start, $end));
        $detected = $detected->merge($this->duplicatePayments($start, $end));
        $detected = $detected->merge($this->overallocatedInstallments($start, $end));
        $detected = $detected->merge($this->invalidPostedSettlements($start, $end));

        $this->persist($detected, $runId, $now);

        $summary = $this->periodSummary($start, $end);

        return [
            'run_id' => $runId,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'detected_count' => $detected->count(),
            'detected_amount' => round((float) $detected->sum('amount'), 2),
            'by_type' => $detected->countBy('issue_type')->all(),
            'accounting' => $summary,
        ];
    }

    public function periodSummary(Carbon $start, Carbon $end): array
    {
        $expected = DB::table('vw_dashboard_expected_collections')
            ->whereBetween('expected_date', [$start->toDateString(), $end->toDateString()])
            ->where('partner_approved', 1)
            ->where('mandate_approved', 1)
            ->selectRaw('COUNT(*) as installment_count, COALESCE(SUM(installment_amount), 0) as expected_amount')
            ->first();

        $cashQuery = DB::table('vw_dashboard_payment_collections')
            ->whereBetween('collected_date', [$start->toDateString(), $end->toDateString()])
            ->where('applied_to_balances', 1)
            ->where('reconciliation_status', 'POSTED');
        $cash = PaymentSettlement::constrainReportable($cashQuery, 'collection_status', 'paid_amount')
            ->selectRaw('COUNT(*) as event_count, COUNT(DISTINCT installment_id) as paid_installment_count, COALESCE(SUM(paid_amount), 0) as cash_received')
            ->first();

        $settlementTotals = DB::table('payment_settlements')
            ->select('installment_id')
            ->selectRaw('SUM(paid_amount) as paid_amount')
            ->where('applied_to_balances', 1)
            ->where('reconciliation_status', 'POSTED')
            ->groupBy('installment_id');
        PaymentSettlement::constrainReportable(
            $settlementTotals,
            'normalized_status',
            'paid_amount'
        );

        $allocated = DB::table('vw_dashboard_expected_collections as expected')
            ->leftJoinSub(
                $settlementTotals,
                'settled',
                'settled.installment_id',
                '=',
                'expected.installment_id'
            )
            ->whereBetween('expected.expected_date', [$start->toDateString(), $end->toDateString()])
            ->where('expected.partner_approved', 1)
            ->where('expected.mandate_approved', 1)
            ->selectRaw('COALESCE(SUM(LEAST(expected.installment_amount, GREATEST(COALESCE(settled.paid_amount, 0), 0))), 0) as schedule_paid')
            ->first();

        $expectedAmount = round((float) ($expected->expected_amount ?? 0), 2);
        $schedulePaid = round((float) ($allocated->schedule_paid ?? 0), 2);
        $outstanding = round(max($expectedAmount - $schedulePaid, 0), 2);

        return [
            'installment_count' => (int) ($expected->installment_count ?? 0),
            'expected_amount' => $expectedAmount,
            'schedule_paid' => $schedulePaid,
            'schedule_outstanding' => $outstanding,
            'identity_difference' => round($expectedAmount - $schedulePaid - $outstanding, 2),
            'payment_event_count' => (int) ($cash->event_count ?? 0),
            'paid_installment_count' => (int) ($cash->paid_installment_count ?? 0),
            'cash_received' => round((float) ($cash->cash_received ?? 0), 2),
        ];
    }

    private function reportableSettlements(Carbon $start, Carbon $end)
    {
        $query = DB::table('payment_settlements')
            ->whereBetween('payment_settlements.effective_payment_date', [$start->toDateString(), $end->toDateString()])
            ->where('payment_settlements.applied_to_balances', 1)
            ->where('payment_settlements.reconciliation_status', 'POSTED');

        return PaymentSettlement::constrainReportable(
            $query,
            'payment_settlements.normalized_status',
            'payment_settlements.paid_amount'
        );
    }

    private function orphanMandates(Carbon $start, Carbon $end): Collection
    {
        return $this->reportableSettlements($start, $end)
            ->leftJoin('debit_orders', 'debit_orders.id', '=', 'payment_settlements.debit_order_id')
            ->whereNull('debit_orders.id')
            ->select('payment_settlements.*')
            ->get()
            ->map(fn ($row) => $this->issue(
                self::ISSUE_ORPHAN_MANDATE,
                $row,
                'Settlement cannot be attributed because its mandate does not exist.'
            ));
    }

    private function orphanInstallments(Carbon $start, Carbon $end): Collection
    {
        return $this->reportableSettlements($start, $end)
            ->whereNotNull('payment_settlements.installment_id')
            ->join('debit_orders', 'debit_orders.id', '=', 'payment_settlements.debit_order_id')
            ->leftJoin('installments', 'installments.id', '=', 'payment_settlements.installment_id')
            ->whereNull('installments.id')
            ->select('payment_settlements.*')
            ->get()
            ->map(fn ($row) => $this->issue(
                self::ISSUE_ORPHAN_INSTALLMENT,
                $row,
                'Settlement references an installment that does not exist.'
            ));
    }

    private function missingInstallments(Carbon $start, Carbon $end): Collection
    {
        return $this->reportableSettlements($start, $end)
            ->whereNull('installment_id')
            ->get()
            ->map(fn ($row) => $this->issue(
                self::ISSUE_MISSING_INSTALLMENT,
                $row,
                'Settlement has no installment reference and is excluded from schedule performance.'
            ));
    }

    private function duplicatePayments(Carbon $start, Carbon $end): Collection
    {
        return $this->reportableSettlements($start, $end)
            ->select('channel', 'payment_reference', 'installment_id')
            ->selectRaw('MIN(id) as settlement_id, MIN(debit_order_id) as debit_order_id')
            ->selectRaw('MIN(mandate_reference) as mandate_reference, MIN(effective_payment_date) as effective_payment_date')
            ->selectRaw('SUM(paid_amount) as paid_amount, COUNT(*) as duplicate_count')
            ->groupBy('channel', 'payment_reference', 'installment_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(fn ($row) => $this->issue(
                self::ISSUE_DUPLICATE_PAYMENT,
                $row,
                'Payment identity occurs ' . $row->duplicate_count . ' times.',
                ['duplicate_count' => (int) $row->duplicate_count, 'channel' => $row->channel]
            ));
    }

    private function overallocatedInstallments(Carbon $start, Carbon $end): Collection
    {
        return $this->reportableSettlements($start, $end)
            ->join('installments', 'installments.id', '=', 'payment_settlements.installment_id')
            ->select('payment_settlements.installment_id')
            ->selectRaw('MIN(payment_settlements.id) as settlement_id')
            ->selectRaw('MIN(payment_settlements.debit_order_id) as debit_order_id')
            ->selectRaw('MIN(payment_settlements.mandate_reference) as mandate_reference')
            ->selectRaw('MIN(payment_settlements.payment_reference) as payment_reference')
            ->selectRaw('MAX(payment_settlements.effective_payment_date) as effective_payment_date')
            ->selectRaw('SUM(payment_settlements.paid_amount) as paid_amount')
            ->selectRaw('MAX(installments.installment_amount) as installment_amount')
            ->groupBy('payment_settlements.installment_id')
            ->havingRaw('SUM(payment_settlements.paid_amount) > MAX(installments.installment_amount) + 0.01')
            ->get()
            ->map(function ($row) {
                $totalPaid = round((float) $row->paid_amount, 2);
                $excess = round($totalPaid - (float) $row->installment_amount, 2);
                $row->paid_amount = $excess;

                return $this->issue(
                    self::ISSUE_OVERALLOCATED,
                    $row,
                    'Posted payments exceed the scheduled installment amount.',
                    [
                        'installment_amount' => round((float) $row->installment_amount, 2),
                        'total_paid_amount' => $totalPaid,
                        'overallocated_amount' => $excess,
                    ]
                );
            });
    }

    private function invalidPostedSettlements(Carbon $start, Carbon $end): Collection
    {
        return DB::table('payment_settlements')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where('applied_to_balances', 1)
            ->where('reconciliation_status', 'POSTED')
            ->where(function ($invalid) {
                $invalid->whereNull('effective_payment_date')
                    ->orWhere(function ($positiveStatus) {
                        $positiveStatus
                            ->whereIn('normalized_status', [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL])
                            ->where('paid_amount', '<=', 0);
                    })
                    ->orWhere(function ($reversalStatus) {
                        $reversalStatus
                            ->where('normalized_status', PaymentSettlement::STATUS_REVERSED)
                            ->where('paid_amount', '>=', 0);
                    });
            })
            ->get()
            ->map(fn ($row) => $this->issue(
                self::ISSUE_INVALID_POSTED,
                $row,
                'Posted settlement has a missing date or an amount sign inconsistent with its status.',
                ['normalized_status' => $row->normalized_status]
            ));
    }

    private function issue(string $type, object $row, string $description, array $context = []): array
    {
        $settlementId = $row->settlement_id ?? $row->id ?? null;
        $installmentId = $row->installment_id ?? null;
        $identity = implode('|', [
            $type,
            $settlementId ?? 'none',
            $installmentId ?? 'none',
            $row->payment_reference ?? 'none',
        ]);

        return [
            'fingerprint' => hash('sha256', $identity),
            'issue_type' => $type,
            'severity' => in_array($type, [self::ISSUE_DUPLICATE_PAYMENT, self::ISSUE_OVERALLOCATED], true) ? 'CRITICAL' : 'ERROR',
            'settlement_id' => $settlementId,
            'debit_order_id' => $row->debit_order_id ?? null,
            'installment_id' => $installmentId,
            'mandate_reference' => $row->mandate_reference ?? null,
            'payment_reference' => $row->payment_reference ?? null,
            'amount' => abs(round((float) ($row->paid_amount ?? 0), 2)),
            'event_date' => $row->effective_payment_date ?? (isset($row->created_at) ? Carbon::parse($row->created_at)->toDateString() : null),
            'description' => $description,
            'context' => $context,
        ];
    }

    private function persist(Collection $issues, string $runId, $now): void
    {
        $issues
            ->map(function (array $issue) use ($runId, $now) {
                $issue['audit_run_id'] = $runId;
                $issue['context'] = $issue['context'] ? json_encode($issue['context']) : null;
                $issue['detected_at'] = $now;
                $issue['last_detected_at'] = $now;
                $issue['resolved_at'] = null;
                $issue['resolution_note'] = null;
                $issue['created_at'] = $now;
                $issue['updated_at'] = $now;

                return $issue;
            })
            ->chunk(500)
            ->each(function (Collection $chunk) {
                DB::table('collection_integrity_issues')->upsert(
                    $chunk->all(),
                    ['fingerprint'],
                    [
                        'audit_run_id',
                        'issue_type',
                        'severity',
                        'settlement_id',
                        'debit_order_id',
                        'installment_id',
                        'mandate_reference',
                        'payment_reference',
                        'amount',
                        'event_date',
                        'description',
                        'context',
                        'last_detected_at',
                        'resolved_at',
                        'resolution_note',
                        'updated_at',
                    ]
                );
            });
    }

    private function issueTypes(): array
    {
        return [
            self::ISSUE_ORPHAN_MANDATE,
            self::ISSUE_ORPHAN_INSTALLMENT,
            self::ISSUE_MISSING_INSTALLMENT,
            self::ISSUE_DUPLICATE_PAYMENT,
            self::ISSUE_OVERALLOCATED,
            self::ISSUE_INVALID_POSTED,
        ];
    }
}
