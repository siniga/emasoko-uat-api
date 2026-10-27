<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_batches', 'lifecycle_status')) {
                $table->string('lifecycle_status')->nullable()->after('response');
            }
            if (!Schema::hasColumn('collection_batches', 'last_pushed_at')) {
                $table->timestamp('last_pushed_at')->nullable()->after('lifecycle_status');
            }
            if (!Schema::hasColumn('collection_batches', 'last_status_checked_at')) {
                $table->timestamp('last_status_checked_at')->nullable()->after('last_pushed_at');
            }
            if (!Schema::hasColumn('collection_batches', 'push_correlation_id')) {
                $table->string('push_correlation_id')->nullable()->after('last_status_checked_at');
            }
            if (!Schema::hasColumn('collection_batches', 'status_payload_hash')) {
                $table->string('status_payload_hash')->nullable()->after('push_correlation_id');
            }
        });

        Schema::table('collection_debit_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_debit_orders', 'installment_id')) {
                $table->unsignedBigInteger('installment_id')->nullable()->after('debit_order_id');
            }
            if (!Schema::hasColumn('collection_debit_orders', 'payment_at')) {
                $table->date('payment_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('collection_debit_orders', 'pushed_at')) {
                $table->timestamp('pushed_at')->nullable()->after('payment_at');
            }
            if (!Schema::hasColumn('collection_debit_orders', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('pushed_at');
            }
            if (!Schema::hasColumn('collection_debit_orders', 'bank_status')) {
                $table->string('bank_status')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('collection_debit_orders', 'bank_message')) {
                $table->text('bank_message')->nullable()->after('bank_status');
            }
            if (!Schema::hasColumn('collection_debit_orders', 'response_hash')) {
                $table->string('response_hash')->nullable()->after('bank_message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collection_batches', function (Blueprint $table) {
            foreach (['lifecycle_status', 'last_pushed_at', 'last_status_checked_at', 'push_correlation_id', 'status_payload_hash'] as $column) {
                if (Schema::hasColumn('collection_batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('collection_debit_orders', function (Blueprint $table) {
            foreach (['installment_id', 'payment_at', 'pushed_at', 'processed_at', 'bank_status', 'bank_message', 'response_hash'] as $column) {
                if (Schema::hasColumn('collection_debit_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
