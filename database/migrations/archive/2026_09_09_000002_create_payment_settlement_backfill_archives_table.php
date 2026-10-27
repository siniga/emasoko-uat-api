<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settlement_backfill_archives', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id')->index();
            $table->unsignedBigInteger('payment_settlement_id')->index();
            $table->unsignedBigInteger('debit_order_id')->index();
            $table->json('settlement_payload');
            $table->timestamp('archived_at');

            $table->unique(
                ['run_id', 'payment_settlement_id'],
                'payment_settlement_backfill_archive_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlement_backfill_archives');
    }
};
