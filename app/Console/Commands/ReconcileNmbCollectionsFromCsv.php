<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Services\SettlementPostingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReconcileNmbCollectionsFromCsv extends Command
{
    protected $signature = 'collections:reconcile-nmb
        {file : CSV file path, absolute or relative to the project base path}
        {--month= : Collection month in YYYY-MM format, for example 2026-08}
        {--apply : Apply settlement updates. Without this option the command only previews matches}
        {--amount-tolerance=1 : Allowed difference between CSV credit and installment amount}
        {--allow-duplicate-references : Allow more than one CSV row for the same mandate reference}
        {--send-sms : Send customer SMS for successful reconciled settlements}
        {--limit= : Limit the number of CSV rows processed}
        {--report= : Optional output CSV report path}';

    protected $description = 'Reconcile NMB collection CSV rows to installments using mandate references from narration.';

    protected array $reservedInstallmentIds = [];
    protected array $affectedBatchIds = [];
    protected array $reportRows = [];
    protected array $counters = [];
    protected array $csvReferenceCounts = [];
    protected array $processedReferenceCounts = [];
    protected $debitOrdersByReference;
    protected $installmentsByDebitOrderId;
    protected $settlementsByMandateReference;

    public function handle(SettlementPostingService $settlementPostingService): int
    {
        $path = $this->resolveFilePath((string) $this->argument('file'));

        if (!is_file($path)) {
            $this->error("CSV file not found: {$path}");
            return self::FAILURE;
        }

        $rows = $this->readCsv($path);

        if (empty($rows)) {
            $this->error('CSV file has no data rows.');
            return self::FAILURE;
        }

        $month = $this->resolveMonth($rows);
        if (!$month) {
            $this->error('Unable to determine collection month. Pass --month=YYYY-MM.');
            return self::FAILURE;
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $apply = (bool) $this->option('apply');
        $sendSms = (bool) $this->option('send-sms');
        $allowDuplicateReferences = (bool) $this->option('allow-duplicate-references');
        $tolerance = (float) $this->option('amount-tolerance');

        $this->preloadReferenceData($rows, $month);

        $this->info(($apply ? 'Applying' : 'Dry run for') . " NMB CSV reconciliation for {$month->format('F Y')}");
        $this->line('SMS notifications: ' . ($sendSms ? 'enabled' : 'disabled'));
        $this->line('Duplicate references: ' . ($allowDuplicateReferences ? 'allowed' : 'second and later rows skipped'));

        foreach ($rows as $index => $row) {
            if ($limit && $index >= $limit) {
                break;
            }

            $this->increment('rows');
            $result = $this->processRow($row, $month, $tolerance, $apply, $sendSms, $allowDuplicateReferences, $settlementPostingService);
            $this->recordReportRow($row, $result);
        }

        if ($apply) {
            $this->refreshAffectedBatches();
        }

        $this->writeReportIfRequested();
        $this->renderSummary();

        return self::SUCCESS;
    }

    protected function processRow(
        array $row,
        Carbon $month,
        float $tolerance,
        bool $apply,
        bool $sendSms,
        bool $allowDuplicateReferences,
        SettlementPostingService $settlementPostingService
    ): array {
        $narration = (string) ($row['Narration'] ?? '');
        $mandateReference = $this->extractMandateReference($narration);
        $creditAmount = $this->parseMoney($row['Credit'] ?? null);
        $effectiveDate = $this->parseDate($row['Value Date'] ?? null) ?: $this->parseDate($row['Book Date'] ?? null);
        $bookDate = $this->parseDate($row['Book Date'] ?? null) ?: $effectiveDate;

        if (!$mandateReference) {
            return $this->skip('missing_reference', 'No mandate reference found in narration.');
        }

        if ($creditAmount <= 0) {
            return $this->skip('missing_amount', 'CSV credit amount is missing or zero.', $mandateReference);
        }

        $duplicateCount = $this->csvReferenceCounts[$mandateReference] ?? 0;
        $processedCount = $this->processedReferenceCounts[$mandateReference] ?? 0;

        if (!$allowDuplicateReferences && $duplicateCount > 1 && $processedCount > 0) {
            $this->processedReferenceCounts[$mandateReference] = $processedCount + 1;

            return $this->skip(
                'duplicate_csv_reference',
                'Mandate reference appears more than once in this CSV; second and later rows are skipped.',
                $mandateReference
            );
        }

        $this->processedReferenceCounts[$mandateReference] = $processedCount + 1;

        if (!$effectiveDate) {
            return $this->skip('missing_date', 'CSV value/book date could not be parsed.', $mandateReference);
        }

        $debitOrder = $this->debitOrdersByReference->get($mandateReference);
        if (!$debitOrder) {
            return $this->skip('missing_debit_order', 'No debit order found for mandate reference.', $mandateReference);
        }

        if (strtoupper((string) $debitOrder->channel) !== 'NMB') {
            return $this->skip('not_nmb', 'Debit order channel is not NMB.', $mandateReference, $debitOrder->id);
        }

        $existingSettlement = $this->findExistingCommandSettlement($debitOrder, $month, $creditAmount, $tolerance);
        if ($existingSettlement && $existingSettlement->installment) {
            $installment = $existingSettlement->installment;
            $batch = $apply ? $this->resolveBatch($installment) : null;

            if ($batch) {
                $this->affectedBatchIds[$batch->id] = $batch->id;
            }

            $this->reserveInstallment($installment);
            $this->increment('already_posted');

            return [
                'status' => 'already_posted',
                'message' => 'Settlement was already posted by this command.',
                'mandate_reference' => $mandateReference,
                'debit_order_id' => $debitOrder->id,
                'installment_id' => $installment->id,
                'batch_reference' => $batch?->collectionReference,
                'amount' => $creditAmount,
                'match_type' => 'existing_command_settlement',
                'effective_payment_date' => $existingSettlement->effective_payment_date,
            ];
        }

        $candidate = $this->findInstallmentCandidate($debitOrder, $month, $creditAmount, $tolerance);
        if (!$candidate['installment']) {
            return $this->skip($candidate['code'], $candidate['message'], $mandateReference, $debitOrder->id);
        }

        /** @var Installment $installment */
        $installment = $candidate['installment'];
        $paymentReference = 'NMB-CSV-' . $month->format('Ym') . '-' . $installment->id;

        $existing = PaymentSettlement::where([
            'channel' => 'NMB',
            'payment_reference' => $paymentReference,
            'installment_id' => $installment->id,
        ])->first();

        if ($existing && strtoupper((string) $existing->normalized_status) === PaymentSettlement::STATUS_SUCCESS) {
            $batch = $apply ? $this->resolveBatch($installment) : null;

            if ($batch) {
                $this->affectedBatchIds[$batch->id] = $batch->id;
            }

            $this->reserveInstallment($installment);
            $this->increment('already_posted');

            return [
                'status' => 'already_posted',
                'message' => 'Settlement was already posted by this command.',
                'mandate_reference' => $mandateReference,
                'debit_order_id' => $debitOrder->id,
                'installment_id' => $installment->id,
                'batch_reference' => $batch?->collectionReference,
                'amount' => $creditAmount,
                'match_type' => $candidate['match_type'],
            ];
        }

        $batch = null;
        $line = null;

        if ($apply) {
            $batch = $this->resolveBatch($installment);
            $line = $this->resolveCollectionLine($installment, $batch);
        }
        $openingOutstanding = (float) ($installment->outstanding_amount ?: $installment->installment_amount);
        $normalizedStatus = $creditAmount + $tolerance < $openingOutstanding
            ? PaymentSettlement::STATUS_PARTIAL
            : PaymentSettlement::STATUS_SUCCESS;
        $payloadHash = hash('sha256', json_encode([
            $row,
            'mandate_reference' => $mandateReference,
            'installment_id' => $installment->id,
        ]));

        if ($apply) {
            try {
                $settlementPostingService->post([
                    'channel' => 'NMB',
                    'batch' => $batch,
                    'line' => $line,
                    'debit_order' => $debitOrder,
                    'installment' => $installment,
                    'amount' => $creditAmount,
                    'expected_amount' => (float) $installment->installment_amount,
                    'raw_status' => 'SUCCESS',
                    'normalized_status' => $normalizedStatus,
                    'remarks' => 'Manual NMB CSV reconciliation',
                    'effective_payment_date' => $effectiveDate->toDateString(),
                    'transaction_at' => $bookDate->toDateString(),
                    'payment_reference' => $paymentReference,
                    'external_reference' => $row['Reference'] ?? null,
                    'raw_payload_hash' => $payloadHash,
                    'send_sms' => $sendSms,
                ]);

                if ($batch) {
                    $this->affectedBatchIds[$batch->id] = $batch->id;
                }
            } catch (Throwable $exception) {
                return $this->skip(
                    'posting_failed',
                    $exception->getMessage(),
                    $mandateReference,
                    $debitOrder->id,
                    $installment->id
                );
            }
        }

        $this->reserveInstallment($installment);
        $this->increment($apply ? 'posted' : 'matched');

        return [
            'status' => $apply ? 'posted' : 'matched',
            'message' => $apply ? 'Settlement posted.' : 'Installment matched. Use --apply to update.',
            'mandate_reference' => $mandateReference,
            'debit_order_id' => $debitOrder->id,
            'installment_id' => $installment->id,
            'batch_reference' => $batch?->collectionReference,
            'amount' => $creditAmount,
            'match_type' => $candidate['match_type'],
            'effective_payment_date' => $effectiveDate->toDateString(),
        ];
    }

    protected function preloadReferenceData(array $rows, Carbon $month): void
    {
        $referenceValues = collect($rows)
            ->map(fn (array $row) => $this->extractMandateReference((string) ($row['Narration'] ?? '')))
            ->filter()
            ->values();

        $this->csvReferenceCounts = $referenceValues
            ->countBy()
            ->all();

        $references = $referenceValues
            ->unique()
            ->values();

        $debitOrders = DebitOrder::whereIn('reference', $references)->get();
        $this->debitOrdersByReference = $debitOrders->keyBy('reference');

        $this->installmentsByDebitOrderId = Installment::whereIn('debit_order_id', $debitOrders->pluck('id'))
            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get()
            ->groupBy('debit_order_id');

        $this->settlementsByMandateReference = PaymentSettlement::with('installment')
            ->whereIn('mandate_reference', $references)
            ->where('channel', 'NMB')
            ->where('payment_reference', 'like', 'NMB-CSV-' . $month->format('Ym') . '-%')
            ->whereIn('normalized_status', [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL])
            ->orderBy('installment_id')
            ->get()
            ->groupBy('mandate_reference');
    }

    protected function findExistingCommandSettlement(DebitOrder $debitOrder, Carbon $month, float $amount, float $tolerance): ?PaymentSettlement
    {
        return ($this->settlementsByMandateReference->get($debitOrder->reference) ?? collect())
            ->reject(fn (PaymentSettlement $settlement) => in_array((int) $settlement->installment_id, $this->reservedInstallmentIds, true))
            ->filter(function (PaymentSettlement $settlement) use ($amount, $tolerance) {
                return abs((float) $settlement->paid_amount - $amount) <= $tolerance;
            })
            ->sortBy(fn (PaymentSettlement $settlement) => $settlement->installment?->payment_date ?? $settlement->installment_id)
            ->first();
    }

    protected function findInstallmentCandidate(DebitOrder $debitOrder, Carbon $month, float $amount, float $tolerance): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $candidates = ($this->installmentsByDebitOrderId->get($debitOrder->id) ?? collect())
            ->reject(fn (Installment $installment) => in_array($installment->id, $this->reservedInstallmentIds, true))
            ->filter(function (Installment $installment) use ($monthStart, $monthEnd) {
                if (empty($installment->payment_date)) {
                    return false;
                }

                $paymentDate = Carbon::parse($installment->payment_date);

                return $paymentDate->betweenIncluded($monthStart, $monthEnd)
                    && in_array($installment->status, ['FAILED', 'FUNDSCHECK'], true);
            })
            ->values();

        $amountMatches = $candidates
            ->filter(fn (Installment $installment) => $this->amountMatches($installment, $amount, $tolerance))
            ->values();

        if ($amountMatches->count() === 1) {
            return [
                'installment' => $amountMatches->first(),
                'match_type' => 'august_failed_fundscheck_amount_match',
            ];
        }

        if ($amountMatches->count() > 1) {
            return [
                'installment' => null,
                'code' => 'duplicate_installment_candidate',
                'message' => 'More than one August FAILED/FUNDSCHECK installment matches the same CSV amount.',
            ];
        }

        if ($candidates->count() > 0) {
            return [
                'installment' => null,
                'code' => 'amount_mismatch',
                'message' => 'August FAILED/FUNDSCHECK installment exists, but amount does not match CSV credit.',
            ];
        }

        return [
            'installment' => null,
            'code' => 'no_august_candidate',
            'message' => 'No August installment found with status FAILED or FUNDSCHECK.',
        ];
    }

    protected function amountMatches(Installment $installment, float $amount, float $tolerance): bool
    {
        $expected = (float) ($installment->outstanding_amount ?: $installment->installment_amount);

        return abs($expected - $amount) <= $tolerance;
    }

    protected function resolveCollectionLine(Installment $installment, ?CollectionBatch $batch = null): ?CollectionDebitOrder
    {
        $query = CollectionDebitOrder::where('installment_id', $installment->id);

        if ($batch) {
            $query->where('collection_batch_id', $batch->id);
        }

        return $query
            ->orderByRaw('CASE WHEN processed_at IS NULL THEN 0 ELSE 1 END')
            ->latest('id')
            ->first();
    }

    protected function resolveBatch(Installment $installment): ?CollectionBatch
    {
        $line = CollectionDebitOrder::where('installment_id', $installment->id)
            ->orderByRaw('CASE WHEN processed_at IS NULL THEN 0 ELSE 1 END')
            ->latest('id')
            ->first();

        if ($line) {
            return CollectionBatch::find($line->collection_batch_id);
        }

        return CollectionBatch::whereRaw('FIND_IN_SET(?, installment_ids)', [$installment->id])
            ->latest('created_at')
            ->first();
    }

    protected function refreshAffectedBatches(): void
    {
        foreach ($this->affectedBatchIds as $batchId) {
            $batch = CollectionBatch::find($batchId);

            if (!$batch) {
                continue;
            }

            $lines = CollectionDebitOrder::where('collection_batch_id', $batch->id)->get();
            $installmentIds = $lines->pluck('installment_id')->filter()->unique()->values();

            if ($installmentIds->isEmpty() && !empty($batch->installment_ids)) {
                $installmentIds = collect(explode(',', $batch->installment_ids))
                    ->map(fn ($id) => trim($id))
                    ->filter()
                    ->unique()
                    ->values();
            }

            if ($installmentIds->isEmpty()) {
                continue;
            }

            $successCount = Installment::whereIn('id', $installmentIds)
                ->whereIn('status', ['CLOSED', 'PARTIAL_PAID'])
                ->count();
            $failedCount = Installment::whereIn('id', $installmentIds)
                ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
                ->count();
            $totalCount = $installmentIds->count();

            if (Schema::hasColumn('collection_batches', 'number_of_collections')) {
                $batch->number_of_collections = $totalCount;
            }

            if (Schema::hasColumn('collection_batches', 'number_of_success')) {
                $batch->number_of_success = $successCount;
            }

            if (Schema::hasColumn('collection_batches', 'number_of_failed')) {
                $batch->number_of_failed = $failedCount;
            }

            if (Schema::hasColumn('collection_batches', 'failed')) {
                $batch->failed = $failedCount;
            }

            if (Schema::hasColumn('collection_batches', 'processed')) {
                $batch->processed = $successCount + $failedCount >= $totalCount ? 1 : $batch->processed;
            }

            if (Schema::hasColumn('collection_batches', 'remarks')) {
                $batch->remarks = 'Updated from manual NMB CSV reconciliation';
            }

            $batch->save();
        }
    }

    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $rows = [];

        while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($values === [null] || $values === false) {
                continue;
            }

            $values = array_pad($values, count($headers), null);
            $rows[] = array_combine($headers, array_slice($values, 0, count($headers)));
        }

        fclose($handle);

        return $rows;
    }

    protected function resolveMonth(array $rows): ?Carbon
    {
        if ($this->option('month')) {
            try {
                return Carbon::createFromFormat('!Y-m', (string) $this->option('month'))->startOfMonth();
            } catch (Throwable) {
                return null;
            }
        }

        foreach ($rows as $row) {
            $date = $this->parseDate($row['Value Date'] ?? null) ?: $this->parseDate($row['Book Date'] ?? null);
            if ($date) {
                return $date->copy()->startOfMonth();
            }
        }

        return null;
    }

    protected function parseDate($value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['!d/m/Y', '!j/n/Y', '!d/m/y', '!j/n/y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date;
                }
            } catch (Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function parseMoney($value): float
    {
        return (float) str_replace([',', ' '], '', (string) $value);
    }

    protected function extractMandateReference(string $narration): ?string
    {
        if (preg_match('/-\s*(\d+)\s*$/', $narration, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function skip(string $status, string $message, ?string $mandateReference = null, $debitOrderId = null, $installmentId = null): array
    {
        $this->increment($status);

        return [
            'status' => $status,
            'message' => $message,
            'mandate_reference' => $mandateReference,
            'debit_order_id' => $debitOrderId,
            'installment_id' => $installmentId,
        ];
    }

    protected function reserveInstallment(Installment $installment): void
    {
        $this->reservedInstallmentIds[] = $installment->id;
    }

    protected function increment(string $key): void
    {
        $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;
    }

    protected function recordReportRow(array $row, array $result): void
    {
        $this->reportRows[] = [
            'book_date' => $row['Book Date'] ?? null,
            'value_date' => $row['Value Date'] ?? null,
            'mandate_reference' => $result['mandate_reference'] ?? null,
            'debit_order_id' => $result['debit_order_id'] ?? null,
            'installment_id' => $result['installment_id'] ?? null,
            'batch_reference' => $result['batch_reference'] ?? null,
            'csv_credit' => $result['amount'] ?? $this->parseMoney($row['Credit'] ?? null),
            'duplicate_reference_count' => !empty($result['mandate_reference'])
                ? ($this->csvReferenceCounts[$result['mandate_reference']] ?? 0)
                : 0,
            'status' => $result['status'] ?? null,
            'match_type' => $result['match_type'] ?? null,
            'message' => $result['message'] ?? null,
            'narration' => $row['Narration'] ?? null,
        ];
    }

    protected function writeReportIfRequested(): void
    {
        $reportPath = $this->option('report');
        if (!$reportPath) {
            return;
        }

        $path = $this->resolveFilePath((string) $reportPath);
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $handle = fopen($path, 'w');
        if (!$handle) {
            $this->warn("Unable to write report: {$path}");
            return;
        }

        $headers = array_keys($this->reportRows[0] ?? []);
        fputcsv($handle, $headers);

        foreach ($this->reportRows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
        $this->info("Report written: {$path}");
    }

    protected function renderSummary(): void
    {
        $summary = collect($this->counters)
            ->sortKeys()
            ->map(fn ($value, $key) => [$key, $value])
            ->values()
            ->all();

        $this->newLine();
        $this->table(['Result', 'Count'], $summary);
    }

    protected function resolveFilePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
