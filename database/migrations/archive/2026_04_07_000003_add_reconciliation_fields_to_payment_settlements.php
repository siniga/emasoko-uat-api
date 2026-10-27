<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_settlements', 'applied_to_balances')) {
                $table->boolean('applied_to_balances')->default(true)->after('settlement_type');
            }
            if (!Schema::hasColumn('payment_settlements', 'reconciliation_status')) {
                $table->string('reconciliation_status', 30)->default('PENDING')->after('applied_to_balances');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            foreach (['applied_to_balances', 'reconciliation_status'] as $column) {
                if (Schema::hasColumn('payment_settlements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
