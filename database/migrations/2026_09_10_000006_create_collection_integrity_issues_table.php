<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_integrity_issues', function (Blueprint $table) {
            $table->id();
            $table->uuid('audit_run_id')->index();
            $table->string('fingerprint', 64)->unique();
            $table->string('issue_type', 60)->index();
            $table->string('severity', 20)->default('ERROR')->index();
            $table->unsignedBigInteger('settlement_id')->nullable()->index();
            $table->unsignedBigInteger('debit_order_id')->nullable()->index();
            $table->unsignedBigInteger('installment_id')->nullable()->index();
            $table->string('mandate_reference')->nullable()->index();
            $table->string('payment_reference')->nullable()->index();
            $table->decimal('amount', 18, 2)->default(0);
            $table->date('event_date')->nullable()->index();
            $table->text('description');
            $table->json('context')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('last_detected_at');
            $table->timestamp('resolved_at')->nullable()->index();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(
                ['resolved_at', 'event_date', 'severity'],
                'collection_integrity_open_period_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_integrity_issues');
    }
};
