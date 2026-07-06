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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CollectionBatchService
{
    private const REPAIRABLE_FAILED_REMARKS = [
        'Item Can Be Collected And Has Been Tracked Successfully.',
        'Duplicate Account No Found In File',
        'Insufficient funds Archive',
        'Posting Successful',
        'Insufficient Funds',
        'Insufficient funds',
        'Insufficient funds.',
    ];

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

        return $this->buildCandidates($installments, $debitDate)->map(function (array $candidate) {
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

    public function canRepairFailedBankBatch(CollectionBatch $sourceBatch): bool
    {
        return $this->failedBankRepairEligibility($sourceBatch)['allowed'];
    }

    public function failedBankRepairEligibility(CollectionBatch $sourceBatch): array
    {
        if ((int) $sourceBatch->deleted === 1) {
            return ['allowed' => false, 'reason' => 'Archived batches cannot be repaired.'];
        }

        if ((int) $sourceBatch->recreated === 1) {
            return ['allowed' => false, 'reason' => 'This batch has already been recreated.'];
        }

        if (empty($sourceBatch->collectionDate)) {
            return ['allowed' => false, 'reason' => 'The batch has no collection date.'];
        }

        if (empty($sourceBatch->created_at)
            || !Carbon::parse($sourceBatch->created_at)->isSameMonth(now(), true)) {
            return ['allowed' => false, 'reason' => 'Same-month repair is available only for batches created this month.'];
        }

        if (!$this->repairableFailedInstallmentsQuery($sourceBatch->installment_id_list)->exists()) {
            return ['allowed' => false, 'reason' => 'The batch has no repairable failed installments.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function repairFailedBankBatch(CollectionBatch $sourceBatch, ?User $actor = null): ?CollectionBatch
    {
        $this->candidateSkipReasons = [];

        return DB::transaction(function () use ($sourceBatch, $actor) {
            $sourceBatch = CollectionBatch::query()
                ->whereKey($sourceBatch->id)
                ->lockForUpdate()
                ->first();

            if (!$sourceBatch || !$this->canRepairFailedBankBatch($sourceBatch)) {
                return null;
            }

            $installments = $this->repairableFailedInstallmentsQuery($sourceBatch->installment_id_list)
                ->get()
                ->each(fn (Installment $installment) => $this->normalizeFailedInstallmentAmount($installment));
            $collectionDate = Carbon::parse($sourceBatch->collectionDate);

            $batch = $this->createRepairBatchFromInstallments(
                $sourceBatch->partner,
                $installments,
                $actor,
                [
                    'forced_collection_date' => $collectionDate,
                    'batch_collection_date' => $collectionDate->toDateString(),
                    'source_batch_id' => $sourceBatch->id,
                ]
            );

            if (!$batch) {
                return null;
            }

            $sourceBatch->deleted = 1;
            $sourceBatch->recreated = 1;
            $sourceBatch->lifecycle_status = CollectionBatch::LIFECYCLE_RECREATED;
            $sourceBatch->save();

            return $batch;
        });
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
            $batchAccountNumbers = [];

            foreach ($candidates as $candidate) {
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

                if (!$this->isOpenDebitOrder($debitOrder)) {
                    $this->recordCandidateSkip($installment, 'contract_not_open_after_lock', [
                        'contract_status' => $debitOrder->contract_status,
                        'lifecycle_status' => $debitOrder->resolved_lifecycle_status,
                        'cancelled' => (int) $debitOrder->cancelled,
                        'deleted' => (int) $debitOrder->deleted,
                    ]);
                    continue;
                }

                $debitOrder->loadMissing('customerAccount');
                $accountNumber = $this->normalizeAccountNumber($debitOrder->customerAccount?->account_number);

                if ($accountNumber === '') {
                    $this->recordCandidateSkip($installment, 'missing_account_number_after_lock');
                    continue;
                }

                if (in_array($accountNumber, $batchAccountNumbers, true)) {
                    $this->recordCandidateSkip($installment, 'duplicate_account_in_batch_after_lock', [
                        'account_number' => $accountNumber,
                    ]);
                    continue;
                }

                if ($duplicate = $this->collectionAccountMonthDuplicate($accountNumber, $candidate['collection_date'], $attributes['source_batch_id'] ?? null)) {
                    $this->recordCandidateSkip($installment, 'active_monthly_account_duplicate_after_lock', [
                        'account_number' => $accountNumber,
                        'collection_date' => $candidate['collection_date']->toDateString(),
                    ] + $this->duplicateContext($duplicate));
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
                    'account_number' => $accountNumber,
                    'status' => 'CREATED',
                    'bank_status' => 'CREATED',
                    'payment_at' => $candidate['collection_date']->toDateString(),
                    'payment_month' => $candidate['collection_date']->format('Y-m'),
                    'account_month_unique_key' => $this->accountMonthUniqueKey($accountNumber, $candidate['collection_date']),
                ];

                $repairDuplicateContext = [];
                $line = !empty($attributes['source_batch_id'])
                    ? $this->createRepairCollectionLine($lineAttributes, $attributes['source_batch_id'], $installment, $repairDuplicateContext)
                    : CollectionDebitOrder::createForCycle($lineAttributes);

                if (!$line) {
                    $this->recordCandidateSkip($installment, 'existing_unique_line_in_other_batch', [
                        'collection_date' => $candidate['collection_date']->toDateString(),
                        'mandate_reference' => $candidate['mandate_reference'],
                    ] + $repairDuplicateContext);

                    continue;
                }

                if ((string) $line->collection_batch_id !== (string) $batch->id) {
                    $this->recordCandidateSkip($installment, 'existing_cycle_line_returned', [
                        'collection_batch_id' => $line->collection_batch_id,
                        'collection_date' => $candidate['collection_date']->toDateString(),
                    ]);
                    continue;
                }

                $batchAccountNumbers[] = $accountNumber;
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

    public function recreateFailedBatch(CollectionBatch $sourceBatch, ?User $actor = null, ?Carbon $paymentDate = null): ?CollectionBatch
    {
        if (empty($sourceBatch->collectionDate)) {
            Log::warning('Cannot recreate failed batch without a collection date.', [
                'source_batch_id' => $sourceBatch->id,
                'source_reference' => $sourceBatch->collectionReference,
            ]);

            return null;
        }

        $batchCollectionDate = Carbon::parse($sourceBatch->collectionDate);
        $paymentDate = $paymentDate ? $paymentDate->copy() : now();
        $installmentIds = $sourceBatch->installment_id_list;

        $installments = Installment::whereIn('id', $installmentIds)
            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
            ->where(function ($query) {
                $query->whereNull('remarks')
                    ->orWhere('remarks', '!=', 'Account Is Valid');
            })
            ->get();

        $batch = $this->createRepairBatchFromInstallments(
            $sourceBatch->partner,
            $installments,
            $actor,
            [
                'forced_collection_date' => $paymentDate,
                'batch_collection_date' => $batchCollectionDate->toDateString(),
                'source_batch_id' => $sourceBatch->id,
            ]
        );

        if ($batch) {
            $sourceBatch->deleted = 1;
            $sourceBatch->recreated = 1;
            $sourceBatch->lifecycle_status = CollectionBatch::LIFECYCLE_RECREATED;
            $sourceBatch->save();
        }

        return $batch;
    }

    public function recreateFailedBatchesForMonth(int $month, int $year, ?User $actor = null): int
    {
        $count = 0;

        $batches = CollectionBatch::whereMonth('collectionDate', $month)
            ->whereYear('collectionDate', $year)
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
            ->whereMonth('collectionDate', $month)
            ->whereYear('collectionDate', $year)
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

    public function collectionAccountMonthDuplicate(string $accountNumber, Carbon $collectionDate, ?string $excludedBatchId = null): ?CollectionDebitOrder
    {
        $normalizedAccountNumber = $this->normalizeAccountNumber($accountNumber);

        if ($normalizedAccountNumber === '') {
            return null;
        }

        $accountMonthUniqueKey = $this->accountMonthUniqueKey($normalizedAccountNumber, $collectionDate);

        return CollectionDebitOrder::query()
            ->with([
                'batch:id,collectionReference,deleted',
                'debitOrder:id,reference,customer_account_id',
                'debitOrder.customerAccount:id,account_number',
            ])
            ->where(function ($query) use ($accountMonthUniqueKey, $normalizedAccountNumber, $collectionDate) {
                $query->where('account_month_unique_key', $accountMonthUniqueKey)
                    ->orWhere(function ($legacyQuery) use ($normalizedAccountNumber, $collectionDate) {
                        $legacyQuery
                            ->whereNull('account_month_unique_key')
                            ->whereMonth('payment_at', $collectionDate->format('m'))
                            ->whereYear('payment_at', $collectionDate->format('Y'))
                            ->whereHas('debitOrder.customerAccount', function ($accountQuery) use ($normalizedAccountNumber) {
                                $accountQuery->whereRaw("REPLACE(account_number, ' ', '') = ?", [$normalizedAccountNumber]);
                            });
                    });
            })
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
        $collectionAccountNumbers = [];

        return collect($installments)
            ->map(function (Installment $installment) use (&$collectionCycles, &$collectionAccountNumbers, $forcedCollectionDate, $excludedBatchId, $skipCycleDuplicateCheck) {
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

                $accountNumber = $this->normalizeAccountNumber($account->account_number);
                if ($accountNumber === '') {
                    $this->recordCandidateSkip($installment, 'missing_account_number');

                    return null;
                }

                $paymentDate = Carbon::parse($installment->payment_date);
                $collectionDate = $forcedCollectionDate
                    ? Carbon::parse($forcedCollectionDate)
                    : Carbon::parse($this->deductionService->createCollectionDate($paymentDate));
                $cycleKey = $debitOrder->id . ':' . $collectionDate->format('Y-m');
                $accountMonthKey = $this->accountMonthUniqueKey($accountNumber, $collectionDate);

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

                if (!$this->isOpenDebitOrder($debitOrder)) {
                    $this->recordCandidateSkip($installment, 'contract_not_open', [
                        'contract_status' => $debitOrder->contract_status,
                        'lifecycle_status' => $debitOrder->resolved_lifecycle_status,
                        'cancelled' => (int) $debitOrder->cancelled,
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

                if (in_array($accountMonthKey, $collectionAccountNumbers, true)) {
                    $this->recordCandidateSkip($installment, 'duplicate_account_in_candidate_list', [
                        'account_number' => $accountNumber,
                        'collection_date' => $collectionDate->toDateString(),
                    ]);

                    return null;
                }

                if ($duplicate = $this->collectionAccountMonthDuplicate($accountNumber, $collectionDate, $excludedBatchId)) {
                    $this->recordCandidateSkip($installment, 'active_monthly_account_duplicate', [
                        'account_number' => $accountNumber,
                        'collection_date' => $collectionDate->toDateString(),
                    ] + $this->duplicateContext($duplicate));

                    return null;
                }

                if (!$skipCycleDuplicateCheck && $duplicate = $this->collectionCycleDuplicate($installment, $collectionDate, $excludedBatchId)) {
                    $this->recordCandidateSkip($installment, 'active_cycle_duplicate', [
                        'collection_date' => $collectionDate->toDateString(),
                    ] + $this->duplicateContext($duplicate));

                    return null;
                }

                $collectionCycles[] = $cycleKey;
                $collectionAccountNumbers[] = $accountMonthKey;

                return [
                    'installment' => $installment,
                    'account_name' => strtoupper($account->account_name),
                    'account_number' => $accountNumber,
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
            'mandate_reference' => $installment?->debitOrder?->reference,
        ], $context);
    }

    protected function isOpenDebitOrder(DebitOrder $debitOrder): bool
    {
        return strtoupper((string) $debitOrder->contract_status) === 'OPEN'
            && (int) $debitOrder->deleted === 0
            && (int) $debitOrder->cancelled === 0
            && $debitOrder->resolved_lifecycle_status === DebitOrder::LIFECYCLE_ACTIVE;
    }

    protected function repairableFailedInstallmentsQuery(array $installmentIds): Builder
    {
        return Installment::query()
            ->whereIn('id', $installmentIds)
            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
            ->where(function ($query) {
                $query->whereNull('remarks')
                    ->orWhereIn('remarks', self::REPAIRABLE_FAILED_REMARKS);
            });
    }

    protected function normalizeFailedInstallmentAmount(Installment $installment): void
    {
        $debitOrder = $installment->debitOrder;
        $expectedAmount = strtoupper((string) $debitOrder->debit_type) === 'FIXED'
            ? $debitOrder->installment_amount
            : $debitOrder->max_installment_amount;

        if ((float) $installment->installment_amount === (float) $expectedAmount) {
            return;
        }

        $installment->installment_amount = $expectedAmount;
        $installment->min_installment_amount = $debitOrder->min_installment_amount;
        $installment->max_installment_amount = $debitOrder->max_installment_amount;
        $installment->current_balance = 0;
        $installment->outstanding_amount = $expectedAmount;
        $installment->save();
    }

    protected function duplicateContext(CollectionDebitOrder $duplicate): array
    {
        return [
            'duplicate_collection_debit_order_id' => $duplicate->id,
            'duplicate_batch_id' => $duplicate->collection_batch_id,
            'duplicate_batch_reference' => $duplicate->batch?->collectionReference,
            'duplicate_mandate_reference' => $duplicate->debitOrder?->reference,
        ];
    }

    protected function normalizeAccountNumber(?string $accountNumber): string
    {
        return CollectionDebitOrder::normalizeAccountNumber($accountNumber);
    }

    protected function accountMonthUniqueKey(string $accountNumber, Carbon $collectionDate): string
    {
        return CollectionDebitOrder::accountMonthUniqueKey($accountNumber, $collectionDate);
    }

    protected function createRepairCollectionLine(array $attributes, string $sourceBatchId, Installment $installment, array &$blockedContext = []): ?CollectionDebitOrder
    {
        $attributes['payment_at'] = Carbon::parse($attributes['payment_at'])->toDateString();

        if (!empty($attributes['account_month_unique_key'])) {
            $existingAccountMonth = CollectionDebitOrder::query()
                ->with('batch:id,collectionReference,deleted')
                ->forAccountMonth($attributes['account_month_unique_key'])
                ->lockForUpdate()
                ->first();

            if ($existingAccountMonth
                && ((int) $existingAccountMonth->installment_id !== (int) $attributes['installment_id']
                    || Carbon::parse($existingAccountMonth->payment_at)->toDateString() !== $attributes['payment_at'])) {
                $blockedContext = $this->duplicateContext($existingAccountMonth);

                return null;
            }
        }

        $existing = CollectionDebitOrder::query()
            ->with('batch:id,collectionReference,deleted')
            ->where('installment_id', $attributes['installment_id'])
            ->whereDate('payment_at', $attributes['payment_at'])
            ->lockForUpdate()
            ->first();

        if (!$existing) {
            return CollectionDebitOrder::create($attributes);
        }

        $existingBatchId = (string) $existing->collection_batch_id;
        if ((string) $existing->collection_batch_id !== (string) $sourceBatchId
            && (string) $existing->collection_batch_id !== (string) $attributes['collection_batch_id']) {
            $blockedContext = $this->duplicateContext($existing);

            if (!$this->isCollectableRepairRemark($installment->remarks)) {
                return null;
            }

            Log::info('Moving collectable repair line from another batch into repaired batch.', [
                'installment_id' => $installment->id,
                'debit_order_id' => $installment->debit_order_id,
                'mandate_reference' => $installment->debitOrder?->reference,
                'remark' => $installment->remarks,
                'from_batch_id' => $existing->collection_batch_id,
                'from_batch_reference' => $existing->batch?->collectionReference,
                'to_batch_id' => $attributes['collection_batch_id'],
                'payment_at' => $attributes['payment_at'],
            ]);
        }

        $existing->fill($attributes);
        $existing->save();

        if ($existingBatchId !== (string) $existing->collection_batch_id) {
            $this->refreshBatchLineSummary($existingBatchId);
        }

        return $existing;
    }

    protected function isCollectableRepairRemark(?string $remark): bool
    {
        return trim((string) $remark) === 'Item Can Be Collected And Has Been Tracked Successfully.';
    }

    protected function refreshBatchLineSummary(?string $batchId): void
    {
        if (empty($batchId)) {
            return;
        }

        $installmentIds = CollectionDebitOrder::query()
            ->where('collection_batch_id', $batchId)
            ->whereNotNull('installment_id')
            ->pluck('installment_id')
            ->filter()
            ->values();

        CollectionBatch::whereKey($batchId)->update([
            'number_of_collections' => $installmentIds->count(),
            'installment_ids' => $installmentIds->implode(','),
        ]);
    }
}
