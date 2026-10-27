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
CREATE TABLE `payment_settlement_backfill_archives` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_id` char(36) NOT NULL,
  `payment_settlement_id` bigint(20) unsigned NOT NULL,
  `debit_order_id` bigint(20) unsigned NOT NULL,
  `settlement_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settlement_payload`)),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `restored_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_settlement_backfill_archive_unique` (`run_id`,`payment_settlement_id`),
  KEY `payment_settlement_backfill_archives_run_id_index` (`run_id`),
  KEY `payment_settlement_backfill_archives_payment_settlement_id_index` (`payment_settlement_id`),
  KEY `payment_settlement_backfill_archives_debit_order_id_index` (`debit_order_id`),
  KEY `payment_settlement_backfill_archives_restored_at_index` (`restored_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlement_backfill_archives');
    }
};
