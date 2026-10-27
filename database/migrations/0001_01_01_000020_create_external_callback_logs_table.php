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
CREATE TABLE `external_callback_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` char(36) NOT NULL,
  `partner_id` bigint(20) unsigned DEFAULT NULL,
  `debit_order_id` bigint(20) unsigned DEFAULT NULL,
  `callback_type` varchar(50) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `callback_url` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `last_response_code` int(11) DEFAULT NULL,
  `response_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_body`)),
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_callback_logs_event_id_unique` (`event_id`),
  KEY `external_callback_logs_partner_id_callback_type_index` (`partner_id`,`callback_type`),
  KEY `external_callback_logs_debit_order_id_callback_type_index` (`debit_order_id`,`callback_type`),
  KEY `external_callback_logs_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('external_callback_logs');
    }
};
