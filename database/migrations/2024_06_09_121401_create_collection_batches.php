<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionBatches extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */

    public function up()
    {
        Schema::create('collection_batches', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("corporateReference");
            $table->string("selectedPostingDate")->nullable();
            $table->string("createdBy")->nullable();
            $table->string("verifiedBy")->nullable();
            $table->timestamp("createdDate")->nullable();
            $table->timestamp("verifyDate")->nullable();
            $table->tinyInteger("batch_processed")->default(0);
            $table->mediumText("response")->nullable();
            $table->timestamp("recheckStatusOn")->nullable();
            $table->timestamps();
        });

        Schema::create('collection_debit_orders', function (Blueprint $table) {
            $table->id();
            $table->string("collection_batch_id");
            $table->foreignId("debit_order_id");
            $table->string("status")->nullable();
            $table->timestamps();
        });

        Schema::table('debit_orders', function (Blueprint $table) {
            if (!Schema::hasColumn("debit_orders", "next_debit_date")) {
                $table->timestamp("next_debit_date")->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collection_debit_orders');
        Schema::dropIfExists('collection_batches');
    }
}
