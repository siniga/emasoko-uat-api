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
use Illuminate\Support\Facades\DB;

class CollectionBatchService
{
    public function __construct(protected DeductionService $deductionService)
    {
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

    public function createBatchFromInstallments(Partner $partner, Collection $installments, ?User $actor = null, array $attributes = []): ?CollectionBatch
    {
        $candidates = $this->buildCandidates($installments, $attributes['forced_collection_date'] ?? null);

        if ($candidates->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($partner, $candidates, $actor, $attributes) {
            $now = now();
            $userId = $actor?->id ?? auth()->id() ?? 1;

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
                $installment = $candidate['installment']->fresh();

                $csv .= "{$installment->id};016;NMB;{$candidate['mandate_reference']};{$candidate['account_name']};{$candidate['account_number']};{$candidate['payment_amount']}\n";

                $installment->effective_payment_date = $candidate['collection_date']->toDateString();
                $installment->created = 1;
                $installment->save();

                CollectionDebitOrder::createForCycle([
                    'collection_batch_id' => $batch->id,
                    'debit_order_id' => $installment->debit_order_id,
                    'installment_id' => $installment->id,
                    'status' => 'CREATED',
                    'bank_status' => 'CREATED',
                    'payment_at' => $candidate['collection_date']->toDateString(),
                ]);

                $installmentIds[] = $installment->id;
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
            ->where('remarks', '!=', 'Account Is Valid')
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
        })->where('partners.status', '=', 202)->get();
    }

    public function hasCollectionCycleDuplicate(Installment $installment, Carbon $collectionDate): bool
    {
        return CollectionDebitOrder::query()
            ->forCycle($installment->id, $collectionDate)
            ->whereHas('batch', function ($query) {
                $query->where('deleted', 0);
            })
            ->exists();
    }

    protected function buildCandidates(iterable $installments, string|Carbon|null $forcedCollectionDate = null): Collection
    {
        $accountNumbers = [];

        return collect($installments)
            ->map(function (Installment $installment) use (&$accountNumbers, $forcedCollectionDate) {
                $debitOrder = $installment->debitOrder;
                $account = $debitOrder->customerAccount;
                $paymentDate = Carbon::parse($installment->payment_date);
                $collectionDate = $forcedCollectionDate
                    ? Carbon::parse($forcedCollectionDate)
                    : Carbon::parse($this->deductionService->createCollectionDate($paymentDate));

                if (strtoupper((string) $debitOrder->periodicity) !== 'MONTHLY') {
                    return null;
                }

                if ((int) $debitOrder->deleted === 1 || strtoupper((string) $debitOrder->contract_status) !== 'OPEN') {
                    return null;
                }

                if (Carbon::parse($debitOrder->start_date)->gt(now()) || Carbon::parse($debitOrder->end_date)->lt(now())) {
                    return null;
                }

                if (in_array($account->account_number, $accountNumbers, true)) {
                    return null;
                }

                if ($this->hasCollectionCycleDuplicate($installment, $collectionDate)) {
                    return null;
                }

                $accountNumbers[] = $account->account_number;

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
}
