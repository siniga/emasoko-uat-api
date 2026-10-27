<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('message_id', 30);
            $table->text('message')->nullable();
            $table->unsignedSmallInteger('number_of_sms')->nullable();
            $table->string('phone', 20);
            $table->string('sender', 11);
            $table->enum('status', ['PENDING', 'SENT', 'DELIVERED', 'FAILED'])->default('PENDING');
            $table->boolean('schedule')->default(false);
            $table->timestamp('schedule_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('response')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
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
        Schema::dropIfExists('sms_logs');
    }
}
