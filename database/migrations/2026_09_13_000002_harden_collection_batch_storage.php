<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table("collection_batches")
            ->select("collectionReference")
            ->whereNotNull("collectionReference")
            ->groupBy("collectionReference")
            ->havingRaw("COUNT(*) > 1")
            ->first();

        if ($duplicate) {
            throw new \RuntimeException(
                "Duplicate collection reference {$duplicate->collectionReference} must be resolved before this migration can run."
            );
        }

        Schema::table("collection_batches", function (Blueprint $table) {
            if (!Schema::hasIndex("collection_batches", "collection_batches_reference_unique")) {
                $table->unique("collectionReference", "collection_batches_reference_unique");
            }
            if (!Schema::hasIndex("collection_batches", "collection_batches_partner_created_index")) {
                $table->index(["partner_id", "created_at"], "collection_batches_partner_created_index");
            }
            if (!Schema::hasIndex("collection_batches", "collection_batches_collection_date_index")) {
                $table->index("collectionDate", "collection_batches_collection_date_index");
            }
            if (!Schema::hasIndex("collection_batches", "collection_batches_lifecycle_index")) {
                $table->index("lifecycle_status", "collection_batches_lifecycle_index");
            }
        });

        Schema::table("collection_debit_orders", function (Blueprint $table) {
            if (!Schema::hasIndex("collection_debit_orders", "collection_lines_batch_index")) {
                $table->index("collection_batch_id", "collection_lines_batch_index");
            }
            if (!Schema::hasIndex("collection_debit_orders", "collection_lines_mandate_index")) {
                $table->index("debit_order_id", "collection_lines_mandate_index");
            }
            if (!Schema::hasIndex("collection_debit_orders", "collection_lines_payment_month_index")) {
                $table->index("payment_month", "collection_lines_payment_month_index");
            }
        });
    }

    public function down(): void
    {
        Schema::table("collection_debit_orders", function (Blueprint $table) {
            foreach (["collection_lines_batch_index", "collection_lines_mandate_index", "collection_lines_payment_month_index"] as $index) {
                if (Schema::hasIndex("collection_debit_orders", $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table("collection_batches", function (Blueprint $table) {
            foreach (["collection_batches_partner_created_index", "collection_batches_collection_date_index", "collection_batches_lifecycle_index"] as $index) {
                if (Schema::hasIndex("collection_batches", $index)) {
                    $table->dropIndex($index);
                }
            }
            if (Schema::hasIndex("collection_batches", "collection_batches_reference_unique")) {
                $table->dropUnique("collection_batches_reference_unique");
            }
        });
    }
};
