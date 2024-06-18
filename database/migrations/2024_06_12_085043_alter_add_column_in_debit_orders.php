<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAddColumnInDebitOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('back_logs', function (Blueprint $table) {
            $table->id();
            $table->string("action");
            $table->string("source");
            $table->string("status");
            $table->mediumText("message");
            $table->nullableUuidMorphs("loggable");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("back_logs");
    }
}
