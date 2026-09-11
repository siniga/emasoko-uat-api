<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settlements', function (Blueprint $table) {
            $table->uuid('backfill_run_id')
                ->nullable()
                ->after('reconciliation_status')
                ->index();
        });

        Schema::table('payment_settlement_backfill_archives', function (Blueprint $table) {
            $table->timestamp('restored_at')->nullable()->after('archived_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('payment_settlement_backfill_archives', function (Blueprint $table) {
            $table->dropColumn('restored_at');
        });

        Schema::table('payment_settlements', function (Blueprint $table) {
            $table->dropColumn('backfill_run_id');
        });
    }
};
