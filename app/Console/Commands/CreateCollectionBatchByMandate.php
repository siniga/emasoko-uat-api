<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use App\Services\CollectionBatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CreateCollectionBatchByMandate extends Command
{
    protected $signature = 'create:batches-by-mandate
                            {references* : Mandate references, separated by spaces or commas}';

    protected $description = 'Create NMB collection batches for selected mandate references';

    public function __construct(protected CollectionBatchService $collectionBatchService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $references = $this->mandateReferences();

        if ($references->isEmpty()) {
            $this->error('Pass at least one mandate reference.');

            return self::FAILURE;
        }

        $this->info('== Creating NMB collection batches by mandate reference ==');

        $installmentGroups = $this->installmentsForReferences($references);

        if ($installmentGroups->isEmpty()) {
            $this->warn('No eligible installments found for the supplied mandate references.');

            return self::SUCCESS;
        }

        $created = 0;
        $createdReferences = [];

        foreach ($installmentGroups as $groupKey => $installments) {
            [$partnerId, $collectionDate] = explode('|', (string) $groupKey, 2);
            $partner = Partner::find($partnerId);

            if (!$partner) {
                $this->warn("Skipping partner {$partnerId}: service provider was not found.");
                continue;
            }

            $this->line("Service provider: {$partner->business_name} | Collection date: {$collectionDate}");

            foreach ($installments->chunk(250) as $chunk) {
                $batch = $this->collectionBatchService->createBatchFromInstallments(
                    $partner,
                    $chunk->values(),
                    null,
                    [
                        'forced_collection_date' => $collectionDate,
                        'batch_collection_date' => $collectionDate,
                    ]
                );

                if ($batch) {
                    $created++;
                    $createdReferences[] = $batch->collectionReference;
                    $this->info("Created batch {$batch->collectionReference} with {$batch->number_of_collections} collection(s).");
                } else {
                    $this->warn('No batch created for one chunk after applying batch creation rules.');
                    $this->reportSkippedCandidates();
                }
            }
        }

        if ($created === 0) {
            $this->warn('No collection batch was created.');
            $this->reportSkippedCandidates();

            return self::SUCCESS;
        }

        $this->info('Created collection batch' . ($created > 1 ? 'es' : '') . ': ' . implode(', ', $createdReferences));

        return self::SUCCESS;
    }

    private function mandateReferences(): Collection
    {
        return collect($this->argument('references'))
            ->flatMap(fn ($reference) => explode(',', (string) $reference))
            ->map(fn ($reference) => trim($reference))
            ->filter()
            ->unique()
            ->values();
    }

    private function installmentsForReferences(Collection $references): Collection
    {
        $orders = DebitOrder::query()
            ->with(['installments', 'customerAccount', 'partner'])
            ->eligibleForCollection('NMB')
            ->whereIn('reference', $references->all())
            ->whereNotNull('next_debit_date')
            ->whereHas('partner', function ($query) {
                $query->where('approved', 1);
            })
            ->orderBy('partner_id')
            ->orderBy('next_debit_date')
            ->orderBy('id')
            ->get();

        $foundReferences = $orders->pluck('reference')->map(fn ($reference) => (string) $reference);
        $references->diff($foundReferences)->each(function (string $reference) {
            $exists = DebitOrder::query()
                ->where('reference', $reference)
                ->exists();

            $message = $exists
                ? "Mandate {$reference} is not eligible for NMB collection or has no next debit date."
                : "Mandate {$reference} was not found.";

            $this->warn($message);
        });

        return $orders
            ->map(function (DebitOrder $debitOrder) {
                $collectionDate = $debitOrder->next_debit_date;

                return $collectionDate
                    ? $this->selectedInstallment($debitOrder, $collectionDate)
                    : null;
            })
            ->filter()
            ->groupBy(function (Installment $installment) {
                return $installment->debitOrder->partner_id . '|' . $installment->debitOrder->next_debit_date;
            });
    }

    private function selectedInstallment(DebitOrder $debitOrder, string $collectionDate): ?Installment
    {
        $open = $debitOrder->installments()
            ->where('installments.status', 'OPEN')
            ->where('installments.created', 0)
            ->whereDate('installments.payment_date', '<=', $collectionDate)
            ->orderByRaw("CASE WHEN DATE(installments.payment_date) = ? THEN 0 ELSE 1 END", [$collectionDate])
            ->orderBy('installments.payment_date', 'desc')
            ->first();

        if ($open) {
            return $open;
        }

        return $debitOrder->installments()
            ->whereIn('status', ['OPEN', 'FAILED', 'FUNDSCHECK'])
            ->where(function ($query) {
                $query->where('status', '!=', 'OPEN')
                    ->orWhere('created', 0);
            })
            ->where(function ($query) {
                $query->whereNull('remarks')
                    ->orWhere('remarks', '!=', 'Account Is Valid');
            })
            ->whereDate('payment_date', '<', $collectionDate)
            ->orderBy('installments.payment_date')
            ->first();
    }

    private function reportSkippedCandidates(): void
    {
        $reasons = collect($this->collectionBatchService->candidateSkipReasons());

        if ($reasons->isEmpty()) {
            return;
        }

        $this->line('Skipped candidate reasons:');

        $reasons
            ->groupBy('reason')
            ->map(fn ($items) => $items->count())
            ->sortDesc()
            ->each(fn (int $count, string $reason) => $this->line(" - {$reason}: {$count}"));
    }
}
