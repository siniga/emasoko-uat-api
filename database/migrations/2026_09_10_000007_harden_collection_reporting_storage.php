<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_settlements', 'reverses_settlement_id')) {
                $table->unsignedBigInteger('reverses_settlement_id')->nullable()->after('backfill_run_id')->index();
            }

            $table->index(
                ['applied_to_balances', 'reconciliation_status', 'normalized_status', 'effective_payment_date'],
                'payment_settlements_reporting_period_index'
            );
            $table->index(
                ['installment_id', 'applied_to_balances', 'reconciliation_status', 'normalized_status'],
                'payment_settlements_reporting_installment_index'
            );
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->index(['payment_date', 'debit_order_id'], 'installments_reporting_period_index');
            $table->index(['debit_order_id', 'payment_date'], 'installments_reporting_mandate_index');
        });

        // Legacy installment money columns were FLOAT. DECIMAL prevents totals changing
        // because of binary floating-point rounding.
        DB::statement('ALTER TABLE installments
            MODIFY installment_amount DECIMAL(18,2) NULL,
            MODIFY min_installment_amount DECIMAL(18,2) NULL,
            MODIFY max_installment_amount DECIMAL(18,2) NULL,
            MODIFY current_balance DECIMAL(18,2) NULL,
            MODIFY outstanding_amount DECIMAL(18,2) NULL,
            MODIFY last_paid_amount DECIMAL(18,2) NULL,
            MODIFY past_due_amount DECIMAL(18,2) NULL');
    }

    public function down(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            $table->dropIndex('payment_settlements_reporting_period_index');
            $table->dropIndex('payment_settlements_reporting_installment_index');

            if (Schema::hasColumn('payment_settlements', 'reverses_settlement_id')) {
                $table->dropIndex(['reverses_settlement_id']);
                $table->dropColumn('reverses_settlement_id');
            }
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex('installments_reporting_period_index');
            $table->dropIndex('installments_reporting_mandate_index');
        });
    }
};
