<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAddColumnInDebitOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debit_orders', function (Blueprint $table) {
            $table->bigInteger("cancellationStartedBy")->unsigned()->nullable();
            $table->bigInteger("cancellationApprovedBy")->unsigned()->nullable();
            $table->timestamp("cancellationStartedAt")->nullable();
            $table->timestamp("cancellationApprovedAt")->nullable();
            $table->foreign("cancellationStartedBy")->references("id")->on("users");
            $table->foreign("cancellationApprovedBy")->references("id")->on("users");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debit_orders', function (Blueprint $table) {
            //
        });
    }
}
