<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_callback_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('debit_order_id')->nullable();
            $table->string('callback_type', 50);
            $table->string('event_type', 100)->nullable();
            $table->string('callback_url');
            $table->json('payload');
            $table->string('status', 20)->default('PENDING');
            $table->unsignedInteger('attempts')->default(0);
            $table->integer('last_response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'callback_type']);
            $table->index(['debit_order_id', 'callback_type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_callback_logs');
    }
};
