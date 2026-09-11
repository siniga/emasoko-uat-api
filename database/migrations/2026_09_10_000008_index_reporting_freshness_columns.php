<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            $table->index('updated_at', 'payment_settlements_reporting_freshness_index');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->index('updated_at', 'installments_reporting_freshness_index');
        });

        Schema::table('debit_orders', function (Blueprint $table) {
            $table->index('updated_at', 'debit_orders_reporting_freshness_index');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            $table->dropIndex('payment_settlements_reporting_freshness_index');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex('installments_reporting_freshness_index');
        });

        Schema::table('debit_orders', function (Blueprint $table) {
            $table->dropIndex('debit_orders_reporting_freshness_index');
        });
    }
};
