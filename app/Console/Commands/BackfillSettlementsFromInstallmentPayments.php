<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\PaymentSettlement;
use App\Services\CollectionIntegrityService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackfillSettlementsFromInstallmentPayments extends Command
{
    private const RECONCILIATION_TOLERANCE = 1.00;

    protected $signature = 'payments:backfill-from-installment-payments
        {--apply : Persist verified settlement allocations}
        {--replace-snapshots : Archive and replace strictly reconciled legacy balance snapshots}
        {--cutoff= : Only process legacy rows created before this timestamp; defaults to the first payment settlement timestamp}
        {--channel=* : Limit processing to NMB or CRDB}
        {--mandate=* : Process only the specified mandate references}
        {--use-created-at : Use created_at when a legacy payment has no payment_at value}
        {--limit= : Limit source rows for validation or testing}
        {--report= : Exception and allocation CSV path relative to storage/app}';

    protected $description = 'Backfill NMB and CRDB payment settlements from legacy installment payments with installment verification';

    private array $stats = [
        'source_rows' => 0,
        'verified_rows' => 0,
        'mandate_only_rows' => 0,
        'already_settled' => 0,
        'duplicate_rows' => 0,
        'exception_rows' => 0,
        'planned_allocations' => 0,
        'created_allocations' => 0,
        'eligible_mandates' => 0,
        'held_mandates' => 0,
        'held_rows' => 0,
        'snapshot_rows_selected' => 0,
        'archived_snapshot_rows' => 0,
    ];

    private float $verifiedAmount = 0;

    private float $exceptionAmount = 0;

    private float $heldAmount = 0;

    private float $replacedSnapshotAmount = 0;

    private array $channelStats = [
        'NMB' => ['rows' => 0, 'amount' => 0.0],
        'CRDB' => ['rows' => 0, 'amount' => 0.0],
    ];

    private array $holdReasonStats = [];

    private $reportHandle;

    private array $debitOrderById = [];

    private array $debitOrdersByReference = [];

    private array $installmentsById = [];

    private array $mandatesWithInstallments = [];

    private array $existingReferenceKeys = [];

    private array $resolvedDebitOrdersByPaymentId = [];

    private array $snapshotSummaryByDebitOrder = [];

    private array $historicalSettlementSummaryByDebitOrder = [];

    private array $invalidMandateReferences = [];

    private string $runId;

    public function handle(CollectionIntegrityService $integrityService): int
    {
        $cutoff = $this->resolveCutoff();
        if (!$cutoff) {
            $this->components->error('No payment settlement cutoff exists. Pass --cutoff explicitly.');

            return self::FAILURE;
        }

        if ($this->option('apply') && !$this->option('replace-snapshots')) {
            $this->components->error(
                'Apply blocked: pass --replace-snapshots to confirm archival and strict snapshot replacement.'
            );

            return self::FAILURE;
        }

        if ($this->option('apply') && $this->option('limit')) {
            $this->components->error('Apply blocked: --limit can truncate a mandate history and is dry-run only.');

            return self::FAILURE;
        }

        if ($this->option('apply') && !Schema::hasTable('payment_settlement_backfill_archives')) {
            $this->components->error('Apply blocked: run migrations to create the settlement backfill archive table.');

            return self::FAILURE;
        }

        $this->runId = (string) Str::uuid();

        $channels = collect($this->option('channel'))
            ->map(fn ($channel) => strtoupper(trim((string) $channel)))
            ->filter()
            ->values();

        if ($channels->isEmpty()) {
            $channels = collect(['NMB', 'CRDB']);
        }

        $invalidChannels = $channels->diff(['NMB', 'CRDB']);
        if ($invalidChannels->isNotEmpty()) {
            $this->components->error('Supported channels are NMB and CRDB only.');

            return self::FAILURE;
        }

        $reportPath = $this->reportPath();
        $this->openReport($reportPath);

        $this->components->info(($this->option('apply') ? 'Applying' : 'Dry run for')
            . " legacy settlement backfill before {$cutoff->format('Y-m-d H:i:s')}.");
        $this->line('Channels: ' . $channels->implode(', '));
        $this->line('Missing payment dates: ' . ($this->option('use-created-at') ? 'use created_at and flag source' : 'report as exceptions'));

        $existingLegacyIds = $this->existingLegacyPaymentIds();
        $query = InstallmentPayment::query()
            ->select([
                'id',
                'mandate_reference',
                'payment_reference',
                'installment_id',
                'amount',
                'payment_at',
                'created_at',
            ])
            ->where('created_at', '<', $cutoff)
            ->orderBy('mandate_reference')
            ->orderByRaw('COALESCE(payment_at, created_at)')
            ->orderBy('id');

        if ($this->option('limit')) {
            $query->limit(max(1, (int) $this->option('limit')));
        }

        $payments = $query->toBase()->get();
        $this->primeResolutionCaches($payments);
        $paymentGroups = [];
        $mandateFilters = collect($this->option('mandate'))
            ->map(fn ($reference) => trim((string) $reference))
            ->filter();

        foreach ($payments as $payment) {
            if (!$this->paymentMatchesMandateFilter($payment, $mandateFilters)) {
                continue;
            }

            $this->stats['source_rows']++;
            $debitOrder = $this->resolveDebitOrder($payment);

            if (!$debitOrder) {
                continue;
            }

            $this->resolvedDebitOrdersByPaymentId[(int) $payment->id] = $debitOrder;

            $groupKey = (string) $debitOrder->id;
            if (!isset($paymentGroups[$groupKey])) {
                $paymentGroups[$groupKey] = [
                    'debit_order' => $debitOrder,
                    'payments' => collect(),
                ];
            }

            $paymentGroups[$groupKey]['payments']->push($payment);
        }

        $this->primeProcessingCaches($paymentGroups, $cutoff);

        foreach ($paymentGroups as $group) {
            $this->processMandatePayments(
                $group['debit_order'],
                $group['payments'],
                $channels,
                $existingLegacyIds,
                $cutoff
            );
        }

        fclose($this->reportHandle);

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            collect($this->stats)->map(fn ($value, $label) => [
                str($label)->replace('_', ' ')->title()->toString(),
                number_format($value),
            ])->values()->all()
        );
        $this->table(
            ['Amount', 'Value'],
            [
                ['Verified', number_format($this->verifiedAmount, 2)],
                ['Exceptions', number_format($this->exceptionAmount, 2)],
                ['Held for reconciliation', number_format($this->heldAmount, 2)],
                ['Legacy snapshots replaced', number_format($this->replacedSnapshotAmount, 2)],
            ]
        );
        $this->table(
            ['Channel', 'Verified rows', 'Verified amount'],
            collect($this->channelStats)->map(fn ($values, $channel) => [
                $channel,
                number_format($values['rows']),
                number_format($values['amount'], 2),
            ])->values()->all()
        );
        if ($this->holdReasonStats !== []) {
            $this->table(
                ['Hold reason', 'Mandates', 'Rows', 'Amount'],
                collect($this->holdReasonStats)->map(fn ($values, $reason) => [
                    $reason,
                    number_format($values['mandates']),
                    number_format($values['rows']),
                    number_format($values['amount'], 2),
                ])->values()->all()
            );
        }
        $this->line('Run ID: ' . $this->runId);
        $this->line('Report: ' . storage_path('app/' . $reportPath));

        if (!$this->option('apply')) {
            $this->components->warn(
                'Dry run only. Review held mandates, migrate the archive table, then use --apply --replace-snapshots.'
            );
        }

        if ($this->option('apply') && $this->stats['created_allocations'] > 0) {
            $range = PaymentSettlement::where('backfill_run_id', $this->runId)
                ->selectRaw('MIN(effective_payment_date) as start_at, MAX(effective_payment_date) as end_at')
                ->first();

            if ($range?->start_at && $range?->end_at) {
                $audit = $integrityService->audit(Carbon::parse($range->start_at), Carbon::parse($range->end_at));
                $this->line('Post-backfill integrity audit: ' . $audit['run_id']);
                $this->line('Post-backfill issues detected: ' . number_format($audit['detected_count']));

                if (abs($audit['accounting']['identity_difference']) > 0.01) {
                    $this->components->error('Post-backfill accounting identity failed.');
                    return self::FAILURE;
                }
            }
        }

        return $this->stats['exception_rows'] > 0 || $this->stats['held_mandates'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function paymentMatchesMandateFilter(object $payment, Collection $mandateFilters): bool
    {
        if ($mandateFilters->isEmpty()) {
            return true;
        }

        $sourceReference = trim((string) $payment->mandate_reference);
        $installmentId = $payment->installment_id ? (int) $payment->installment_id : null;

        if ($installmentId && ($installment = $this->installmentsById[$installmentId] ?? null)) {
            $debitOrder = $this->debitOrderById[(int) $installment->debit_order_id] ?? null;
            if ($debitOrder) {
                return $mandateFilters->contains(trim((string) $debitOrder->reference));
            }
        }

        return $sourceReference !== '' && $mandateFilters->contains($sourceReference);
    }

    private function primeResolutionCaches(Collection $payments): void
    {
        $installmentIds = $payments
            ->pluck('installment_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($installmentIds as $installmentId) {
            $this->installmentsById[$installmentId] = null;
        }

        foreach ($installmentIds->chunk(1000) as $ids) {
            Installment::query()
                ->select(['id', 'debit_order_id', 'installment_amount'])
                ->whereIn('id', $ids->all())
                ->toBase()
                ->get()
                ->each(function (object $installment) {
                    $this->installmentsById[(int) $installment->id] = $installment;
                });
        }

        $debitOrderIds = collect($this->installmentsById)
            ->filter()
            ->pluck('debit_order_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($debitOrderIds as $debitOrderId) {
            $this->debitOrderById[$debitOrderId] = null;
        }

        foreach ($debitOrderIds->chunk(1000) as $ids) {
            DebitOrder::query()
                ->select(['id', 'reference', 'channel'])
                ->whereIn('id', $ids->all())
                ->toBase()
                ->get()
                ->each(function (object $debitOrder) {
                    $this->debitOrderById[(int) $debitOrder->id] = $debitOrder;
                });
        }

        $references = $payments
            ->whereNull('installment_id')
            ->pluck('mandate_reference')
            ->map(fn ($reference) => trim((string) $reference))
            ->filter()
            ->unique()
            ->values();

        foreach ($references as $reference) {
            $this->debitOrdersByReference[$reference] = collect();
        }

        foreach ($references->chunk(1000) as $referenceChunk) {
            DebitOrder::query()
                ->select(['id', 'reference', 'channel'])
                ->whereIn('reference', $referenceChunk->all())
                ->toBase()
                ->get()
                ->groupBy(fn (object $debitOrder) => trim((string) $debitOrder->reference))
                ->each(function (Collection $debitOrders, string $reference) {
                    $this->debitOrdersByReference[$reference] = $debitOrders;
                });
        }
    }

    private function resolveDebitOrder(object $payment): ?object
    {
        $sourceReference = trim((string) $payment->mandate_reference);
        $installmentId = $payment->installment_id ? (int) $payment->installment_id : null;

        if ($installmentId !== null) {
            if (!array_key_exists($installmentId, $this->installmentsById)) {
                $this->installmentsById[$installmentId] = Installment::query()
                    ->select(['id', 'debit_order_id', 'installment_amount'])
                    ->toBase()
                    ->find($installmentId);
            }

            $installment = $this->installmentsById[$installmentId];
            if (!$installment) {
                $this->markMandateInvalid($sourceReference);
                $this->exception($payment, 'Linked installment does not exist.');

                return null;
            }

            $debitOrderId = (int) $installment->debit_order_id;
            if (!array_key_exists($debitOrderId, $this->debitOrderById)) {
                $this->debitOrderById[$debitOrderId] = DebitOrder::query()
                    ->select(['id', 'reference', 'channel'])
                    ->toBase()
                    ->find($debitOrderId);
            }

            $debitOrder = $this->debitOrderById[$debitOrderId];
            if (!$debitOrder) {
                $this->markMandateInvalid($sourceReference);
                $this->exception($payment, 'Linked installment has no valid mandate.');

                return null;
            }

            if ($sourceReference !== '' && $sourceReference !== trim((string) $debitOrder->reference)) {
                $this->markMandateInvalid($sourceReference);
                $this->markMandateInvalid(trim((string) $debitOrder->reference));
                $this->exception($payment, 'Linked installment and mandate reference belong to different mandates.');

                return null;
            }

            return $debitOrder;
        }

        if ($sourceReference === '') {
            $this->exception($payment, 'Both mandate reference and installment ID are missing.');

            return null;
        }

        if (!array_key_exists($sourceReference, $this->debitOrdersByReference)) {
            $this->debitOrdersByReference[$sourceReference] = DebitOrder::query()
                ->select(['id', 'reference', 'channel'])
                ->where('reference', $sourceReference)
                ->toBase()
                ->get();
        }

        $debitOrders = $this->debitOrdersByReference[$sourceReference];
        if ($debitOrders->count() !== 1) {
            $this->exception(
                $payment,
                $debitOrders->isEmpty()
                    ? 'Mandate reference was not found.'
                    : 'Mandate reference is duplicated and cannot be resolved safely.'
            );

            return null;
        }

        return $debitOrders->first();
    }

    private function markMandateInvalid(string $reference): void
    {
        if ($reference !== '') {
            $this->invalidMandateReferences[$reference] = true;
        }
    }

    private function primeProcessingCaches(array $paymentGroups, Carbon $cutoff): void
    {
        $debitOrderIds = collect($paymentGroups)
            ->pluck('debit_order.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($debitOrderIds->chunk(1000) as $ids) {
            Installment::query()
                ->whereIn('debit_order_id', $ids->all())
                ->distinct()
                ->pluck('debit_order_id')
                ->each(function ($debitOrderId) {
                    $this->mandatesWithInstallments[(int) $debitOrderId] = true;
                });
        }

        $references = collect($paymentGroups)
            ->pluck('payments')
            ->flatten(1)
            ->pluck('payment_reference')
            ->map(fn ($reference) => trim((string) $reference))
            ->filter()
            ->unique()
            ->values();

        foreach ($references->chunk(500) as $referenceChunk) {
            PaymentSettlement::query()
                ->select(['debit_order_id', 'payment_reference', 'external_reference'])
                ->where(function ($query) use ($referenceChunk) {
                    $query->whereIn('payment_reference', $referenceChunk->all())
                        ->orWhereIn('external_reference', $referenceChunk->all());
                })
                ->toBase()
                ->get()
                ->each(function (object $settlement) {
                    foreach ([$settlement->payment_reference, $settlement->external_reference] as $reference) {
                        $reference = trim((string) $reference);
                        if ($reference !== '') {
                            $this->existingReferenceKeys[$settlement->debit_order_id . '|' . $reference] = true;
                        }
                    }
                });
        }

        $cutoffDate = $cutoff->toDateString();

        foreach ($debitOrderIds->chunk(1000) as $ids) {
            PaymentSettlement::query()
                ->selectRaw(
                    'debit_order_id, COUNT(*) rows_count, SUM(paid_amount) total_amount, '
                    . 'SUM(CASE WHEN effective_payment_date <= ? THEN 1 ELSE 0 END) replaceable_rows, '
                    . 'SUM(CASE WHEN effective_payment_date <= ? THEN paid_amount ELSE 0 END) replaceable_amount, '
                    . 'SUM(CASE WHEN effective_payment_date > ? OR effective_payment_date IS NULL THEN 1 ELSE 0 END) later_rows',
                    [$cutoffDate, $cutoffDate, $cutoffDate]
                )
                ->whereIn('debit_order_id', $ids->all())
                ->where('settlement_type', 'LEGACY_INSTALLMENT_BALANCE')
                ->groupBy('debit_order_id')
                ->toBase()
                ->get()
                ->each(function (object $summary) {
                    $this->snapshotSummaryByDebitOrder[(int) $summary->debit_order_id] = $summary;
                });

            PaymentSettlement::query()
                ->selectRaw('debit_order_id, COUNT(*) rows_count, SUM(paid_amount) total_amount')
                ->whereIn('debit_order_id', $ids->all())
                ->whereIn('normalized_status', [
                    PaymentSettlement::STATUS_SUCCESS,
                    PaymentSettlement::STATUS_PARTIAL,
                ])
                ->whereDate('effective_payment_date', '<=', $cutoffDate)
                ->where(function ($query) {
                    $query->whereNull('settlement_type')
                        ->orWhere('settlement_type', '!=', 'LEGACY_INSTALLMENT_BALANCE');
                })
                ->groupBy('debit_order_id')
                ->toBase()
                ->get()
                ->each(function (object $summary) {
                    $this->historicalSettlementSummaryByDebitOrder[(int) $summary->debit_order_id] = $summary;
                });
        }
    }

    private function processMandatePayments(
        object $debitOrder,
        Collection $payments,
        Collection $channels,
        array $existingLegacyIds,
        Carbon $cutoff
    ): void {
        $channel = strtoupper((string) $debitOrder->channel);
        $mandates = collect($this->option('mandate'))
            ->map(fn ($reference) => trim((string) $reference))
            ->filter();

        if (!$channels->contains($channel)
            || ($mandates->isNotEmpty() && !$mandates->contains(trim((string) $debitOrder->reference)))) {
            return;
        }

        if (!isset($this->mandatesWithInstallments[(int) $debitOrder->id])) {
            $payments->each(fn ($payment) => $this->exception($payment, 'Mandate has no installments.'));

            return;
        }

        $duplicatePaymentIds = $payments
            ->groupBy(fn ($payment) => $this->fingerprint($payment))
            ->filter(fn ($rows) => $rows->count() > 1)
            ->flatMap(fn ($rows) => $rows->sortBy('id')->skip(1)->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->flip();

        $plans = [];
        $groupInvalid = isset($this->invalidMandateReferences[trim((string) $debitOrder->reference)]);

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);

            if ($amount <= 0) {
                $this->exception($payment, 'Payment amount must be greater than zero.');
                $groupInvalid = true;
                continue;
            }

            if (isset($duplicatePaymentIds[(int) $payment->id])) {
                $this->stats['duplicate_rows']++;
                $this->exception($payment, 'Duplicate legacy payment row.');
                continue;
            }

            if (isset($existingLegacyIds[$payment->id])) {
                $this->alreadySettled($payment, 'Legacy payment was already backfilled.');
                continue;
            }

            $legacyReference = trim((string) $payment->payment_reference);
            $existingReferenceKey = $debitOrder->id . '|' . $legacyReference;
            if ($legacyReference !== '' && isset($this->existingReferenceKeys[$existingReferenceKey])) {
                $this->alreadySettled($payment, 'Matching settlement payment reference already exists.');
                continue;
            }

            [$paymentDate, $dateSource] = $this->paymentDate($payment);
            if (!$paymentDate) {
                $this->exception($payment, 'Payment date is missing. Use --use-created-at only after reviewing the source date.');
                $groupInvalid = true;
                continue;
            }

            $installment = $payment->installment_id
                ? ($this->installmentsById[(int) $payment->installment_id] ?? null)
                : null;

            if ($payment->installment_id && !$installment) {
                $this->exception($payment, 'Linked installment does not belong to the resolved mandate.');
                $groupInvalid = true;
                continue;
            }

            $plans[] = [
                'payment' => $payment,
                'date_source' => $dateSource,
                'attributes' => [
                    'channel' => $channel,
                    'debit_order_id' => $debitOrder->id,
                    'installment_id' => $installment?->id,
                    'mandate_reference' => $debitOrder->reference,
                    'payment_reference' => 'LEGACY-IP-' . $payment->id,
                    'external_reference' => $legacyReference ?: null,
                    'raw_status' => PaymentSettlement::STATUS_SUCCESS,
                    'normalized_status' => PaymentSettlement::STATUS_SUCCESS,
                    'settlement_type' => $dateSource === 'payment_at'
                        ? 'LEGACY_INSTALLMENT_PAYMENT'
                        : 'LEGACY_INSTALLMENT_PAYMENT_CREATED_AT',
                    'applied_to_balances' => true,
                    'reconciliation_status' => 'POSTED',
                    'backfill_run_id' => $this->runId,
                    'expected_amount' => $installment
                        ? round((float) $installment->installment_amount, 2)
                        : $amount,
                    'paid_amount' => $amount,
                    'effective_payment_date' => $paymentDate,
                    'transaction_at' => Carbon::parse($paymentDate)->startOfDay(),
                    'remarks' => $this->settlementRemarks($installment, $dateSource),
                    'raw_payload_hash' => hash(
                        'sha256',
                        "installment_payments:{$payment->id}:{$amount}:" . ($installment?->id ?? 'none')
                    ),
                ],
            ];
        }

        if ($plans === []) {
            return;
        }

        $debitOrderId = (int) $debitOrder->id;
        $snapshot = $this->snapshotSummaryByDebitOrder[$debitOrderId] ?? null;
        $historical = $this->historicalSettlementSummaryByDebitOrder[$debitOrderId] ?? null;
        $sourceAmount = round((float) collect($plans)->sum('attributes.paid_amount'), 2);
        $snapshotAmount = round((float) ($snapshot->replaceable_amount ?? 0), 2);
        $competingRows = (int) ($historical->rows_count ?? 0);
        $holdReason = null;

        if ($groupInvalid) {
            $holdReason = 'One or more source payments for this mandate failed validation.';
        } elseif ($snapshot && (int) $snapshot->later_rows > 0) {
            $holdReason = 'Mandate has legacy snapshot rows after the backfill cutoff.';
        } elseif ($competingRows > 0) {
            $holdReason = 'Mandate has non-snapshot successful settlements on or before the cutoff.';
        } elseif ($snapshot && abs($sourceAmount - $snapshotAmount) > self::RECONCILIATION_TOLERANCE) {
            $holdReason = 'Source payment total does not match the replaceable snapshot total.';
        }

        if ($holdReason) {
            $this->holdPlans($plans, $holdReason, $sourceAmount, $snapshotAmount, $competingRows);

            return;
        }

        $selectedSnapshotRows = (int) ($snapshot->replaceable_rows ?? 0);

        if ($this->option('apply')) {
            try {
                $applied = $this->persistReconciledMandate($debitOrder, $plans, $cutoff, $snapshotAmount);
                $this->stats['created_allocations'] += count($plans);
                $this->stats['archived_snapshot_rows'] += $applied['archived_rows'];
            } catch (\Throwable $exception) {
                $this->holdPlans(
                    $plans,
                    'Apply-time reconciliation failed: ' . $exception->getMessage(),
                    $sourceAmount,
                    $snapshotAmount,
                    $competingRows
                );

                return;
            }
        }

        $this->stats['eligible_mandates']++;
        $this->stats['snapshot_rows_selected'] += $selectedSnapshotRows;
        $this->replacedSnapshotAmount += $snapshotAmount;

        foreach ($plans as $plan) {
            $payment = $plan['payment'];
            $installmentId = (string) ($plan['attributes']['installment_id'] ?? '');
            $this->stats['verified_rows']++;
            $this->stats['planned_allocations']++;
            if ($installmentId === '') {
                $this->stats['mandate_only_rows']++;
            }
            $this->verifiedAmount += round((float) $payment->amount, 2);
            $this->channelStats[$channel]['rows']++;
            $this->channelStats[$channel]['amount'] += round((float) $payment->amount, 2);
            $this->writeReport(
                $payment,
                $this->option('apply') ? 'CREATED' : 'VERIFIED',
                $installmentId === '' ? 'Mandate verified; source has no installment ID.' : '',
                $installmentId,
                $plan['date_source']
            );
        }
    }

    private function holdPlans(
        array $plans,
        string $reason,
        float $sourceAmount,
        float $snapshotAmount,
        int $competingRows
    ): void {
        $detail = $reason
            . ' Source: ' . number_format($sourceAmount, 2, '.', '')
            . '; snapshot: ' . number_format($snapshotAmount, 2, '.', '')
            . '; competing rows: ' . $competingRows . '.';

        $this->stats['held_mandates']++;
        $this->stats['held_rows'] += count($plans);
        $this->heldAmount += $sourceAmount;
        if (!isset($this->holdReasonStats[$reason])) {
            $this->holdReasonStats[$reason] = ['mandates' => 0, 'rows' => 0, 'amount' => 0.0];
        }
        $this->holdReasonStats[$reason]['mandates']++;
        $this->holdReasonStats[$reason]['rows'] += count($plans);
        $this->holdReasonStats[$reason]['amount'] += $sourceAmount;

        foreach ($plans as $plan) {
            $this->writeReport(
                $plan['payment'],
                'RECONCILIATION_HOLD',
                $detail,
                (string) ($plan['attributes']['installment_id'] ?? ''),
                $plan['date_source']
            );
        }
    }

    private function persistReconciledMandate(
        object $debitOrder,
        array $plans,
        Carbon $cutoff,
        float $expectedSnapshotAmount
    ): array {
        return DB::transaction(function () use ($debitOrder, $plans, $cutoff, $expectedSnapshotAmount) {
            $cutoffDate = $cutoff->toDateString();
            $snapshots = PaymentSettlement::query()
                ->where('debit_order_id', $debitOrder->id)
                ->where('settlement_type', 'LEGACY_INSTALLMENT_BALANCE')
                ->lockForUpdate()
                ->get();

            if ($snapshots->contains(fn ($settlement) => !$settlement->effective_payment_date
                || Carbon::parse($settlement->effective_payment_date)->gt($cutoffDate))) {
                throw new \RuntimeException('Legacy snapshot dates changed after the dry-run check.');
            }

            $actualSnapshotAmount = round((float) $snapshots->sum('paid_amount'), 2);
            if (abs($actualSnapshotAmount - $expectedSnapshotAmount) > self::RECONCILIATION_TOLERANCE) {
                throw new \RuntimeException('Legacy snapshot amount changed after the dry-run check.');
            }

            $hasCompetingSettlements = PaymentSettlement::query()
                ->where('debit_order_id', $debitOrder->id)
                ->whereIn('normalized_status', [
                    PaymentSettlement::STATUS_SUCCESS,
                    PaymentSettlement::STATUS_PARTIAL,
                ])
                ->whereDate('effective_payment_date', '<=', $cutoffDate)
                ->where(function ($query) {
                    $query->whereNull('settlement_type')
                        ->orWhere('settlement_type', '!=', 'LEGACY_INSTALLMENT_BALANCE');
                })
                ->lockForUpdate()
                ->exists();

            if ($hasCompetingSettlements) {
                throw new \RuntimeException('A competing historical settlement appeared after the dry-run check.');
            }

            if ($snapshots->isNotEmpty()) {
                $archivedAt = now();
                $archiveRows = $snapshots->map(fn (PaymentSettlement $settlement) => [
                    'run_id' => $this->runId,
                    'payment_settlement_id' => $settlement->id,
                    'debit_order_id' => $settlement->debit_order_id,
                    'settlement_payload' => json_encode($settlement->getAttributes(), JSON_THROW_ON_ERROR),
                    'archived_at' => $archivedAt,
                ])->all();

                DB::table('payment_settlement_backfill_archives')->insert($archiveRows);
                PaymentSettlement::query()->whereKey($snapshots->pluck('id'))->delete();
            }

            foreach ($plans as $plan) {
                PaymentSettlement::create($plan['attributes']);
            }

            return [
                'archived_rows' => $snapshots->count(),
                'archived_amount' => $actualSnapshotAmount,
            ];
        });
    }

    private function settlementRemarks(?object $installment, string $dateSource): string
    {
        $remarks = $installment
            ? 'Backfilled from installment_payments with verified installment linkage.'
            : 'Backfilled from installment_payments with verified mandate linkage; source installment ID was unavailable.';

        if ($dateSource === 'created_at') {
            $remarks .= ' payment_at was missing, so created_at was used.';
        }

        return $remarks;
    }

    private function paymentDate(object $payment): array
    {
        if ($payment->payment_at) {
            return [Carbon::parse($payment->payment_at)->toDateString(), 'payment_at'];
        }

        if ($this->option('use-created-at') && $payment->created_at) {
            return [Carbon::parse($payment->created_at)->toDateString(), 'created_at'];
        }

        return [null, null];
    }

    private function resolveCutoff(): ?Carbon
    {
        if ($this->option('cutoff')) {
            return Carbon::parse((string) $this->option('cutoff'));
        }

        $firstSettlement = PaymentSettlement::query()->min('created_at');

        return $firstSettlement ? Carbon::parse($firstSettlement) : null;
    }

    private function existingLegacyPaymentIds(): array
    {
        return PaymentSettlement::query()
            ->where('payment_reference', 'like', 'LEGACY-IP-%')
            ->pluck('payment_reference')
            ->map(function ($reference) {
                preg_match('/^LEGACY-IP-(\d+)/', (string) $reference, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })
            ->filter()
            ->unique()
            ->flip()
            ->all();
    }

    private function fingerprint(object $payment): string
    {
        return implode('|', [
            trim((string) $payment->mandate_reference),
            trim((string) $payment->payment_reference),
            (string) $payment->installment_id,
            number_format((float) $payment->amount, 2, '.', ''),
            (string) ($payment->payment_at ?: $payment->created_at),
        ]);
    }

    private function exception(object $payment, string $reason): void
    {
        $this->stats['exception_rows']++;
        $this->exceptionAmount += max(round((float) $payment->amount, 2), 0);
        $this->writeReport($payment, 'EXCEPTION', $reason);
    }

    private function alreadySettled(object $payment, string $reason): void
    {
        $this->stats['already_settled']++;
        $this->writeReport($payment, 'SKIPPED', $reason);
    }

    private function reportPath(): string
    {
        $path = trim((string) $this->option('report'));

        return $path !== ''
            ? ltrim($path, '/')
            : 'reports/payment-settlement-backfill-' . now()->format('Ymd-His') . '.csv';
    }

    private function openReport(string $path): void
    {
        Storage::disk('local')->makeDirectory(dirname($path));
        $this->reportHandle = fopen(storage_path('app/' . $path), 'w');

        if (!$this->reportHandle) {
            throw new \RuntimeException('Unable to create the backfill report.');
        }

        fputcsv($this->reportHandle, [
            'installment_payment_id',
            'source_mandate_reference',
            'resolved_mandate_reference',
            'debit_order_id',
            'channel',
            'legacy_payment_reference',
            'source_installment_id',
            'amount',
            'payment_at',
            'created_at',
            'result',
            'reason',
            'allocated_installment_ids',
            'date_source',
        ], ',', '"', '');
    }

    private function writeReport(
        object $payment,
        string $result,
        string $reason = '',
        string $allocatedInstallmentIds = '',
        string $dateSource = ''
    ): void {
        $debitOrder = $this->resolvedDebitOrdersByPaymentId[(int) $payment->id] ?? null;

        fputcsv($this->reportHandle, [
            $payment->id,
            $payment->mandate_reference,
            $debitOrder?->reference,
            $debitOrder?->id,
            $debitOrder?->channel,
            $payment->payment_reference,
            $payment->installment_id,
            number_format((float) $payment->amount, 2, '.', ''),
            $payment->payment_at,
            $payment->created_at,
            $result,
            $reason,
            $allocatedInstallmentIds,
            $dateSource,
        ], ',', '"', '');
    }
}
