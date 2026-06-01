<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_debit_orders', function (Blueprint $table) {
            $table->unique(['installment_id', 'payment_at'], 'collection_debit_orders_installment_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::table('collection_debit_orders', function (Blueprint $table) {
            $table->dropUnique('collection_debit_orders_installment_payment_unique');
        });
    }
};
