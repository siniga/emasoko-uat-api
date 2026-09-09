<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service', 30);
            $table->string('endpoint_key', 60);
            $table->string('endpoint_label', 100);
            $table->string('check_type', 30)->default('tcp_tls');
            $table->string('status', 20);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('error_category', 30)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['endpoint_key', 'checked_at']);
            $table->index('checked_at');
            $table->index(['service', 'status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_checks');
    }
};
