<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillCollectionBatchLines extends Command
{
    protected $signature = "collection-batches:backfill-lines
                            {--apply : Persist missing normalized collection lines}
                            {--batch= : Limit the operation to one batch UUID}";

    protected $description = "Backfill normalized collection lines from legacy collection batch installment IDs";

    public function handle(): int
    {
        $apply = (bool) $this->option("apply");
        $stats = [
            "batches_scanned" => 0,
            "lines_created" => 0,
            "conflicts" => 0,
            "missing_installments" => 0,
            "missing_data" => 0,
        ];

        CollectionBatch::query()
            ->when($this->option("batch"), fn ($query, $id) => $query->whereKey($id))
            ->whereDoesntHave("debits")
            ->whereNotNull("installment_ids")
            ->where("installment_ids", "!=", "")
            ->orderBy("created_at")
            ->chunkById(100, function ($batches) use ($apply, &$stats) {
                foreach ($batches as $batch) {
                    $stats["batches_scanned"]++;
                    $ids = collect(explode(",", (string) $batch->installment_ids))
                        ->map(fn ($id) => (int) trim($id))
                        ->filter()
                        ->unique();
                    $installments = Installment::with("debitOrder.customerAccount")
                        ->whereIn("id", $ids)
                        ->get()
                        ->keyBy("id");
                    $stats["missing_installments"] += $ids->diff($installments->keys())->count();

                    foreach ($installments as $installment) {
                        $debitOrder = $installment->debitOrder;
                        $accountNumber = CollectionDebitOrder::normalizeAccountNumber(
                            $debitOrder?->customerAccount?->account_number
                        );
                        $paymentAt = $batch->collectionDate
                            ?: $installment->effective_payment_date
                            ?: $installment->payment_date;

                        if (!$debitOrder || $accountNumber === "" || !$paymentAt || !$batch->partner_id) {
                            $stats["missing_data"]++;
                            continue;
                        }

                        $attributes = [
                            "collection_batch_id" => $batch->id,
                            "debit_order_id" => $debitOrder->id,
                            "installment_id" => $installment->id,
                            "account_number" => $accountNumber,
                            "status" => "CREATED",
                            "bank_status" => "CREATED",
                            "payment_at" => Carbon::parse($paymentAt)->toDateString(),
                            "payment_month" => Carbon::parse($paymentAt)->format("Y-m"),
                            "account_month_unique_key" => CollectionDebitOrder::accountMonthUniqueKey(
                                $accountNumber,
                                $paymentAt,
                                (int) $debitOrder->partner_id
                            ),
                        ];

                        $existing = CollectionDebitOrder::query()
                            ->where(function ($query) use ($attributes) {
                                $query->where("account_month_unique_key", $attributes["account_month_unique_key"])
                                    ->orWhere(function ($cycle) use ($attributes) {
                                        $cycle->where("installment_id", $attributes["installment_id"])
                                            ->whereDate("payment_at", $attributes["payment_at"]);
                                    });
                            })
                            ->first();

                        if ($existing) {
                            if ((string) $existing->collection_batch_id !== (string) $batch->id) {
                                $stats["conflicts"]++;
                            }
                            continue;
                        }

                        if ($apply) {
                            CollectionDebitOrder::create($attributes);
                        }
                        $stats["lines_created"]++;
                    }
                }
            });

        $this->info($apply ? "Backfill complete." : "Dry run complete. Re-run with --apply to persist changes.");
        $this->table(["Metric", "Count"], collect($stats)->map(fn ($count, $name) => [$name, $count])->values()->all());

        return $stats["conflicts"] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
