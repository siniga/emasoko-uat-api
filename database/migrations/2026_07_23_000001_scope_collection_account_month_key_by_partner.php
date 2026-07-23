<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('collection_debit_orders', 'account_month_unique_key')) {
            return;
        }

        $this->dropAccountMonthUniqueIndex();
        $this->refreshAccountMonthKeys(true);

        Schema::table('collection_debit_orders', function (Blueprint $table) {
            $table->unique('account_month_unique_key', 'collection_debit_orders_account_month_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('collection_debit_orders', 'account_month_unique_key')) {
            return;
        }

        $this->dropAccountMonthUniqueIndex();
        $this->refreshAccountMonthKeys(false);

        Schema::table('collection_debit_orders', function (Blueprint $table) {
            $table->unique('account_month_unique_key', 'collection_debit_orders_account_month_unique');
        });
    }

    private function refreshAccountMonthKeys(bool $includePartner): void
    {
        $seen = [];

        DB::table('collection_debit_orders')
            ->leftJoin('collection_batches', 'collection_batches.id', '=', 'collection_debit_orders.collection_batch_id')
            ->leftJoin('debit_orders', 'debit_orders.id', '=', 'collection_debit_orders.debit_order_id')
            ->leftJoin('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
            ->whereNotNull('collection_debit_orders.payment_at')
            ->orderBy('collection_debit_orders.id')
            ->select([
                'collection_debit_orders.id',
                'collection_debit_orders.payment_at',
                'collection_batches.partner_id',
                DB::raw('COALESCE(collection_debit_orders.account_number, customer_accounts.account_number) as account_number'),
            ])
            ->chunkById(500, function ($rows) use (&$seen, $includePartner) {
                foreach ($rows as $row) {
                    $accountNumber = preg_replace('/\s+/', '', trim((string) $row->account_number)) ?? '';
                    $paymentMonth = date('Y-m', strtotime((string) $row->payment_at));
                    $partnerId = $row->partner_id ? (int) $row->partner_id : null;
                    $key = null;

                    if ($accountNumber !== '') {
                        $key = $accountNumber . '|' . $paymentMonth;

                        if ($includePartner && $partnerId !== null) {
                            $key = $partnerId . '|' . $key;
                        }
                    }

                    DB::table('collection_debit_orders')
                        ->where('id', $row->id)
                        ->update([
                            'account_number' => $accountNumber !== '' ? $accountNumber : null,
                            'payment_month' => $paymentMonth,
                            'account_month_unique_key' => $key && !isset($seen[$key]) ? $key : null,
                        ]);

                    if ($key) {
                        $seen[$key] = true;
                    }
                }
            }, 'collection_debit_orders.id', 'id');
    }

    private function dropAccountMonthUniqueIndex(): void
    {
        try {
            Schema::table('collection_debit_orders', function (Blueprint $table) {
                $table->dropUnique('collection_debit_orders_account_month_unique');
            });
        } catch (Throwable $exception) {
            // The index may not exist in older or partially migrated databases.
        }
    }
};
