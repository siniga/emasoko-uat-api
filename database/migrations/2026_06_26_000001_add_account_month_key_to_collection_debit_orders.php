<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_debit_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_debit_orders', 'account_number')) {
                $table->string('account_number')->nullable()->after('debit_order_id');
            }

            if (!Schema::hasColumn('collection_debit_orders', 'payment_month')) {
                $table->string('payment_month', 7)->nullable()->after('payment_at');
            }

            if (!Schema::hasColumn('collection_debit_orders', 'account_month_unique_key')) {
                $table->string('account_month_unique_key', 80)->nullable()->after('payment_month');
            }
        });

        $seenAccountMonths = [];

        DB::table('collection_debit_orders')
            ->join('debit_orders', 'debit_orders.id', '=', 'collection_debit_orders.debit_order_id')
            ->join('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id')
            ->whereNotNull('collection_debit_orders.payment_at')
            ->whereNotNull('customer_accounts.account_number')
            ->orderBy('collection_debit_orders.id')
            ->select([
                'collection_debit_orders.id',
                'collection_debit_orders.payment_at',
                'customer_accounts.account_number',
            ])
            ->chunkById(500, function ($rows) use (&$seenAccountMonths) {
                foreach ($rows as $row) {
                    $accountNumber = preg_replace('/\s+/', '', trim((string) $row->account_number)) ?? '';
                    $paymentMonth = date('Y-m', strtotime((string) $row->payment_at));
                    $accountMonthKey = $accountNumber !== '' ? "{$accountNumber}|{$paymentMonth}" : null;

                    DB::table('collection_debit_orders')
                        ->where('id', $row->id)
                        ->update([
                            'account_number' => $accountNumber !== '' ? $accountNumber : null,
                            'payment_month' => $paymentMonth,
                            'account_month_unique_key' => $accountMonthKey && !isset($seenAccountMonths[$accountMonthKey])
                                ? $accountMonthKey
                                : null,
                        ]);

                    if ($accountMonthKey) {
                        $seenAccountMonths[$accountMonthKey] = true;
                    }
                }
            }, 'collection_debit_orders.id', 'id');

        Schema::table('collection_debit_orders', function (Blueprint $table) {
            $table->unique('account_month_unique_key', 'collection_debit_orders_account_month_unique');
        });
    }

    public function down(): void
    {
        Schema::table('collection_debit_orders', function (Blueprint $table) {
            $table->dropUnique('collection_debit_orders_account_month_unique');

            foreach (['account_month_unique_key', 'payment_month', 'account_number'] as $column) {
                if (Schema::hasColumn('collection_debit_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
