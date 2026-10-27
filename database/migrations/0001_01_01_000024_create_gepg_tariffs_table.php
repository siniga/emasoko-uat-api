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
CREATE TABLE `gepg_tariffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lower_amount` decimal(18,2) NOT NULL,
  `upper_amount` decimal(18,2) DEFAULT NULL,
  `tariff_amount` decimal(18,2) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gepg_tariffs_lower_amount_upper_amount_index` (`lower_amount`,`upper_amount`),
  KEY `gepg_tariffs_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('gepg_tariffs');
    }
};
