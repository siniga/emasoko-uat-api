<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->index();
            $table->uuid('collection_batch_id')->nullable()->index();
            $table->unsignedBigInteger('collection_debit_order_id')->nullable()->index();
            $table->unsignedBigInteger('debit_order_id')->index();
            $table->unsignedBigInteger('installment_id')->nullable()->index();
            $table->string('mandate_reference')->index();
            $table->string('payment_reference')->index();
            $table->string('external_reference')->nullable()->index();
            $table->string('raw_status', 50)->nullable();
            $table->string('normalized_status', 50)->index();
            $table->string('settlement_type', 50)->nullable();
            $table->decimal('expected_amount', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->decimal('opening_installment_balance', 18, 2)->nullable();
            $table->decimal('closing_installment_balance', 18, 2)->nullable();
            $table->decimal('opening_debit_order_balance', 18, 2)->nullable();
            $table->decimal('closing_debit_order_balance', 18, 2)->nullable();
            $table->date('effective_payment_date')->nullable()->index();
            $table->dateTime('transaction_at')->nullable();
            $table->text('remarks')->nullable();
            $table->string('raw_payload_hash')->nullable()->index();
            $table->timestamps();

            $table->unique(['channel', 'payment_reference', 'installment_id'], 'payment_settlements_unique_payment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlements');
    }
};
