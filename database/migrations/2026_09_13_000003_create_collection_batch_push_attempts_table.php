<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("collection_batch_push_attempts", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("collection_batch_id")->index();
            $table->uuid("correlation_id")->index();
            $table->string("status", 32)->index();
            $table->unsignedSmallInteger("http_status")->nullable();
            $table->string("response_code", 32)->nullable();
            $table->text("message")->nullable();
            $table->timestamps();

            $table->foreign("collection_batch_id")
                ->references("id")
                ->on("collection_batches")
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("collection_batch_push_attempts");
    }
};
