<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Existing partners.id is a signed INT, not Laravel's default BIGINT.
            $table->integer('partner_id')->unique();
            $table->enum('currency', ['TZS'])->default('TZS');
            $table->enum('status', ['ACTIVE', 'FROZEN'])->default('ACTIVE');
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
