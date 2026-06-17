<?php

namespace App\Services;

use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionBatchService
{
    protected array $candidateSkipReasons = [];

    public function __construct(protected DeductionService $deductionService)
    {
    }

    public function candidateSkipReasons(): array
    {
        return $this->candidateSkipReasons;
    }

    public function previewInstallments(Partner $partner, string $debitDate, int $limit = 250): array
    {
        $installments = $this->deductionService->getInstallments($partner, $debitDate, $limit);

        return $this->buildCandidates($installments)->map(function (array $candidate) {
            return [
                'id' => $candidate['installment']->id,
                'reference' => $candidate['mandate_reference'],
                'account_name' => $candidate['account_name'],
                'account_number' => $candidate['account_number'],
                'amount' => $candidate['payment_amount'],
                'current_balance' => $candidate['installment']->current_balance,
                'outstanding_amount' => $candidate['installment']->outstanding_amount,
                'payment_date' => $candidate['payment_date']->toDateString(),
                'collection_date' => $candidate['collection_date']->toDateString(),
            ];
        })->values()->all();
    }

    public function createManualBatch(int $partnerId, array $installmentIds, ?User $actor = null): ?CollectionBatch
    {
        $partner = Partner::findOrFail($partnerId);
        $installments = Installment::whereIn('id', $installmentIds)->get();

        return $this->createBatchFromInstallments($partner, $installments, $actor);
    }

    public function createRepairBatchFromInstallments(Partner $partner, Collection $installments, ?User $actor = null, array $attributes = []): ?CollectionBatch
    {
        return $this->createBatchFromInstallments($partner, $installments, $actor, array_merge($attributes, [
            'skip_cycle_duplicate_check' => true,
        ]));
    }

    public function createBatchFromInstallments(Partner $partner, Collection $installments, ?User $actor = null, array $attributes = []): ?CollectionBatch
    {
        $this->candidateSkipReasons = [];

        $candidates = $this->buildCandidates(
            $installments,
            $attributes['forced_collection_date'] ?? null,
            $attributes['source_batch_id'] ?? null,
            (bool) ($attributes['skip_cycle_duplicate_check'] ?? false)
        );

        if ($candidates->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($partner, $candidates, $actor, $attributes) {
            $now = now();
            $userId = $actor?->id ?? Auth::user()->id ?? 1;

            $batch = CollectionBatch::create([
                'partner_id' => $partner->id,
                'collectionReference' => $this->deductionService->getCollectionReference(),
                'corporateReference' => $partner->reference,
                'selectedPostingDate' => $now,
                'createdBy' => $userId,
                'verifiedBy' => $userId,
                'collectionDate' => $attributes['batch_collection_date'] ?? $candidates->max(fn ($row) => $row['collection_date'])->toDateString(),
                'createdDate' => $now,
                'verifyDate' => $now,
                'lifecycle_status' => CollectionBatch::LIFECYCLE_READY,
            ]);

            $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";
            $installmentIds = [];

            foreach ($candidates as $candidate) {
                /** @var \App\Models\Installment $installment */
                $installment = Installment::query()
                    ->whereKey($candidate['installment']->id)
                    ->lockForUpdate()
                    ->first();

                if (!$installment) {
                    $this->recordCandidateSkip(null, 'installment_not_found_after_lock', [
                        'installment_id' => $candidate['installment']->id,
                    ]);
                    continue;
                }

                $debitOrder = DebitOrder::query()
                    ->whereKey($installment->debit_order_id)
                    ->lockForUpdate()
                    ->first();

                if (!$debitOrder) {
                    $this->recordCandidateSkip($installment, 'debit_order_not_found_after_lock');
                    continue;
                }

                if (empty($attributes['skip_cycle_duplicate_check']) && $duplicate = $this->collectionCycleDuplicate($installment, $candidate['collection_date'], $attributes['source_batch_id'] ?? null)) {
                    $this->recordCandidateSkip($installment, 'active_cycle_duplicate_after_lock', [
                        'collection_date' => $candidate['collection_date']->toDateString(),
                    ] + $this->duplicateContext($duplicate));
                    continue;
                }

                $lineAttributes = [
                    'collection_batch_id' => $batch->id,
                    'debit_order_id' => $installment->debit_order_id,
                    'installment_id' => $installment->id,
                    'status' => 'CREATED',
                    'bank_status' => 'CREATED',
                    'payment_at' => $candidate['collection_date']->toDateString(),
                ];

                $line = !empty($attributes['source_batch_id'])
                    ? CollectionDebitOrder::create($lineAttributes)
                    : CollectionDebitOrder::createForCycle($lineAttributes);

                if ((string) $line->collection_batch_id !== (string) $batch->id) {
                    $this->recordCandidateSkip($installment, 'existing_cycle_line_returned', [
                        'collection_batch_id' => $line->collection_batch_id,
                        'collection_date' => $candidate['collection_date']->toDateString(),
                    ]);
                    continue;
                }

                $csv .= "{$installment->id};016;NMB;{$candidate['mandate_reference']};{$candidate['account_name']};{$candidate['account_number']};{$candidate['payment_amount']}\n";

                $installment->effective_payment_date = $candidate['collection_date']->toDateString();
                $installment->created = 1;
                $installment->save();

                $installmentIds[] = $installment->id;
            }

            if (empty($installmentIds)) {
                $batch->delete();

                return null;
            }

            $batch->request_csv = $csv;
            $batch->number_of_collections = count($installmentIds);
            $batch->installment_ids = implode(',', $installmentIds);
            $batch->save();

            return $batch->fresh();
        });
    }

    public function recreateFailedBatch(CollectionBatch $sourceBatch, ?User $actor = null, ?Carbon $targetCollectionDate = null): ?CollectionBatch
    {
        $targetCollectionDate = $targetCollectionDate ?: Carbon::parse($sourceBatch->collectionDate)->addMonth();
        $installmentIds = $sourceBatch->installment_id_list;

        $installments = Installment::whereIn('id', $installmentIds)
            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
            ->where(function ($query) {
                $query->whereNull('remarks')
                    ->orWhere('remarks', '!=', 'Account Is Valid');
            })
            ->get();

        $batch = $this->createBatchFromInstallments(
            $sourceBatch->partner,
            $installments,
            $actor,
            [
                'forced_collection_date' => $targetCollectionDate,
                'batch_collection_date' => $targetCollectionDate->toDateString(),
            ]
        );

        if ($batch) {
            $sourceBatch->recreated = 1;
            $sourceBatch->lifecycle_status = CollectionBatch::LIFECYCLE_RECREATED;
            $sourceBatch->save();
        }

        return $batch;
    }

    public function recreateFailedBatchesForMonth(int $month, int $year, ?User $actor = null): int
    {
        $count = 0;

        $batches = CollectionBatch::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('recreated', 0)
            ->where('deleted', 0)
            ->orderBy('collectionReference', 'ASC')
            ->get();

        foreach ($batches as $batch) {
            if ($this->recreateFailedBatch($batch, $actor)) {
                $count++;
            }
        }

        return $count;
    }

    public function lastMonthFailedBatchEligibility(): array
    {
        $previousMonth = now()->subMonth();
        $month = (int) $previousMonth->format('m');
        $year = (int) $previousMonth->format('Y');

        $batches = CollectionBatch::filterCondition()
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('deleted', 0)
            ->orderByDesc('collectionDate')
            ->get();

        $eligible = $batches->filter(function (CollectionBatch $batch) {
            return (int) $batch->recreated === 0
                && (
                    (int) ($batch->failed ?? 0) === 1
                    || (int) ($batch->number_of_failed ?? 0) > 0
                );
        })->values();

        $alreadyRecreated = $batches->filter(function (CollectionBatch $batch) {
            return (int) $batch->recreated === 1;
        })->count();

        $allowed = $eligible->isNotEmpty();
        $reason = null;

        if (!$allowed) {
            $reason = $alreadyRecreated > 0
                ? "Monthly failed collections for {$previousMonth->format('F Y')} were already created."
                : "No eligible failed batches found for {$previousMonth->format('F Y')}.";
        }

        return [
            'allowed' => $allowed,
            'eligible_count' => $eligible->count(),
            'month' => $month,
            'year' => $year,
            'month_label' => $previousMonth->format('F Y'),
            'reason' => $reason,
        ];
    }

    public function candidatePartnersForDate($collectionDate)
    {
        return Partner::whereHas('debitOrders', function ($query) use ($collectionDate) {
            return $query->eligibleForCollection('NMB')
                ->whereDate('next_debit_date', '=', $collectionDate);
        })->get();
    }

    public function hasCollectionCycleDuplicate(Installment $installment, Carbon $collectionDate, ?string $excludedBatchId = null): bool
    {
        return $this->collectionCycleDuplicate($installment, $collectionDate, $excludedBatchId) !== null;
    }

    public function collectionCycleDuplicate(Installment $installment, Carbon $collectionDate, ?string $excludedBatchId = null): ?CollectionDebitOrder
    {
        return CollectionDebitOrder::query()
            ->with('batch:id,collectionReference,deleted')
            ->forCycle($installment->debit_order_id, $collectionDate)
            ->when($excludedBatchId, function ($query) use ($excludedBatchId) {
                $query->where('collection_batch_id', '!=', $excludedBatchId);
            })
            ->whereHas('batch', function ($query) {
                $query->where('deleted', 0);
            })
            ->first();
    }

    protected function buildCandidates(iterable $installments, string|Carbon|null $forcedCollectionDate = null, ?string $excludedBatchId = null, bool $skipCycleDuplicateCheck = false): Collection
    {
        $collectionCycles = [];

        return collect($installments)
            ->map(function (Installment $installment) use (&$collectionCycles, $forcedCollectionDate, $excludedBatchId, $skipCycleDuplicateCheck) {
                $debitOrder = $installment->debitOrder;
                if (!$debitOrder) {
                    $this->recordCandidateSkip($installment, 'missing_debit_order');

                    return null;
                }

                $account = $debitOrder->customerAccount;
                if (!$account) {
                    $this->recordCandidateSkip($installment, 'missing_customer_account');

                    return null;
                }

                $paymentDate = Carbon::parse($installment->payment_date);
                $collectionDate = $forcedCollectionDate
                    ? Carbon::parse($forcedCollectionDate)
                    : Carbon::parse($this->deductionService->createCollectionDate($paymentDate));
                $cycleKey = $debitOrder->id . ':' . $collectionDate->format('Y-m');

                if (strtoupper((string) $debitOrder->periodicity) !== 'MONTHLY') {
                    $this->recordCandidateSkip($installment, 'unsupported_periodicity', [
                        'periodicity' => $debitOrder->periodicity,
                    ]);

                    return null;
                }

                if ((int) $debitOrder->deleted === 1) {
                    $this->recordCandidateSkip($installment, 'debit_order_deleted');

                    return null;
                }

                if (strtoupper((string) $debitOrder->contract_status) !== 'OPEN') {
                    $this->recordCandidateSkip($installment, 'contract_not_open', [
                        'contract_status' => $debitOrder->contract_status,
                    ]);

                    return null;
                }

                if (!empty($debitOrder->start_date) && Carbon::parse($debitOrder->start_date)->gt($collectionDate)) {
                    $this->recordCandidateSkip($installment, 'mandate_not_started_on_collection_date', [
                        'start_date' => Carbon::parse($debitOrder->start_date)->toDateString(),
                        'collection_date' => $collectionDate->toDateString(),
                    ]);

                    return null;
                }

                if (!empty($debitOrder->end_date) && Carbon::parse($debitOrder->end_date)->lt($collectionDate)) {
                    $this->recordCandidateSkip($installment, 'mandate_ended_before_collection_date', [
                        'end_date' => Carbon::parse($debitOrder->end_date)->toDateString(),
                        'collection_date' => $collectionDate->toDateString(),
                    ]);

                    return null;
                }

                if (in_array($cycleKey, $collectionCycles, true)) {
                    $this->recordCandidateSkip($installment, 'duplicate_in_candidate_list', [
                        'cycle_key' => $cycleKey,
                    ]);

                    return null;
                }

                if (!$skipCycleDuplicateCheck && $duplicate = $this->collectionCycleDuplicate($installment, $collectionDate, $excludedBatchId)) {
                    $this->recordCandidateSkip($installment, 'active_cycle_duplicate', [
                        'collection_date' => $collectionDate->toDateString(),
                    ] + $this->duplicateContext($duplicate));

                    return null;
                }

                $collectionCycles[] = $cycleKey;

                return [
                    'installment' => $installment,
                    'account_name' => strtoupper($account->account_name),
                    'account_number' => $account->account_number,
                    'mandate_reference' => $debitOrder->reference,
                    'payment_amount' => $installment->installment_amount,
                    'payment_date' => $paymentDate,
                    'collection_date' => $collectionDate,
                ];
            })
            ->filter()
            ->values();
    }

    protected function recordCandidateSkip(?Installment $installment, string $reason, array $context = []): void
    {
        $this->candidateSkipReasons[] = array_merge([
            'reason' => $reason,
            'installment_id' => $installment?->id,
            'debit_order_id' => $installment?->debit_order_id,
        ], $context);
    }

    protected function duplicateContext(CollectionDebitOrder $duplicate): array
    {
        return [
            'duplicate_collection_debit_order_id' => $duplicate->id,
            'duplicate_batch_id' => $duplicate->collection_batch_id,
            'duplicate_batch_reference' => $duplicate->batch?->collectionReference,
        ];
    }
}
