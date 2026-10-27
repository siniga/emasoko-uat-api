<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Baseline: preserve the captured MariaDB column types, defaults, indexes and checks.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE `wallets` (
  `id` char(36) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `currency` enum('TZS') NOT NULL DEFAULT 'TZS',
  `status` enum('ACTIVE','FROZEN') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallets_partner_id_unique` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
