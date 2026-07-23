<?php

namespace App\Console\Commands;

use App\Models\CollectionDebitOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCollectionDebitOrderAccountMonthKeys extends Command
{
    protected $signature = 'collection-debit-orders:backfill-account-month-keys
                            {--only-null : Only update rows where account_month_unique_key is null}
                            {--dry-run : Show counts without updating rows}';

    protected $description = 'Backfill account number, payment month, and provider-scoped account-month keys on collection debit order lines';

    public function handle(): int
    {
        $onlyNull = (bool) $this->option('only-null');
        $dryRun = (bool) $this->option('dry-run');
        if (!$onlyNull && !$dryRun) {
            DB::table('collection_debit_orders')->update(['account_month_unique_key' => null]);
        }

        $seenKeys = $this->existingKeys($onlyNull);
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'missing_data' => 0,
            'duplicate_key' => 0,
        ];

        $query = DB::table('collection_debit_orders')
            ->leftJoin('collection_batches', 'collection_batches.id', '=', 'collection_debit_orders.collection_batch_id')
            ->leftJoin('debit_orders', 'debit_orders.id', '=', 'collection_debit_orders.debit_order_id')
            ->leftJoin('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
            ->leftJoin('installments', 'installments.id', '=', 'collection_debit_orders.installment_id')
            ->when($onlyNull, function ($query) {
                $query->whereNull('collection_debit_orders.account_month_unique_key');
            })
            ->orderBy('collection_debit_orders.id')
            ->select([
                'collection_debit_orders.id',
                'collection_debit_orders.payment_at',
                'collection_debit_orders.account_number as line_account_number',
                'collection_batches.partner_id as batch_partner_id',
                'debit_orders.partner_id as mandate_partner_id',
                'customer_accounts.account_number as customer_account_number',
                'installments.effective_payment_date',
                'installments.payment_date',
            ]);

        $query->chunkById(500, function ($rows) use (&$seenKeys, &$stats, $dryRun) {
            foreach ($rows as $row) {
                $stats['processed']++;

                $accountNumber = CollectionDebitOrder::normalizeAccountNumber(
                    $row->line_account_number ?: $row->customer_account_number
                );
                $paymentDate = $row->payment_at ?: $row->effective_payment_date ?: $row->payment_date;
                $partnerId = $row->batch_partner_id ?: $row->mandate_partner_id;

                if ($accountNumber === '' || empty($paymentDate) || empty($partnerId)) {
                    $stats['missing_data']++;
                    $updates = [
                        'account_number' => $accountNumber !== '' ? $accountNumber : null,
                        'payment_month' => $paymentDate ? Carbon::parse($paymentDate)->format('Y-m') : null,
                        'account_month_unique_key' => null,
                    ];

                    if (!$dryRun) {
                        DB::table('collection_debit_orders')->where('id', $row->id)->update($updates);
                    }

                    $stats['updated']++;
                    continue;
                }

                $paymentMonth = Carbon::parse($paymentDate)->format('Y-m');
                $key = CollectionDebitOrder::accountMonthUniqueKey($accountNumber, $paymentDate, (int) $partnerId);
                $uniqueKey = (string) $key;

                if (isset($seenKeys[$uniqueKey]) && (int) $seenKeys[$uniqueKey] !== (int) $row->id) {
                    $stats['duplicate_key']++;
                    $key = null;
                } else {
                    $seenKeys[$uniqueKey] = (int) $row->id;
                }

                if (!$dryRun) {
                    DB::table('collection_debit_orders')->where('id', $row->id)->update([
                        'account_number' => $accountNumber,
                        'payment_month' => $paymentMonth,
                        'account_month_unique_key' => $key,
                    ]);
                }

                $stats['updated']++;
            }
        }, 'collection_debit_orders.id', 'id');

        $this->info(($dryRun ? 'Dry run complete.' : 'Backfill complete.'));
        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }

    private function existingKeys(bool $onlyNull): array
    {
        if (!$onlyNull) {
            return [];
        }

        return DB::table('collection_debit_orders')
            ->whereNotNull('account_month_unique_key')
            ->pluck('id', 'account_month_unique_key')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
