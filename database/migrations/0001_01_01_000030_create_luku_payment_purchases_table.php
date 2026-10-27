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
CREATE TABLE `luku_payment_purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) DEFAULT NULL,
  `partner_id` char(36) DEFAULT NULL,
  `subscriber_id` char(36) DEFAULT NULL,
  `meter_number` varchar(255) NOT NULL,
  `payer_name` varchar(255) NOT NULL,
  `payer_mobile_number` varchar(30) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'TZS',
  `callback_url` varchar(255) DEFAULT NULL,
  `request_id` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `response_code` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `debit_order_id` bigint(20) unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `luku_payment_purchases_uuid_unique` (`uuid`),
  KEY `luku_payment_purchases_meter_number_index` (`meter_number`),
  KEY `luku_payment_purchases_request_id_index` (`request_id`),
  KEY `luku_payment_purchases_status_index` (`status`),
  KEY `luku_payment_purchases_created_by_index` (`created_by`),
  KEY `luku_payment_purchases_debit_order_id_index` (`debit_order_id`),
  KEY `luku_payment_purchases_partner_id_index` (`partner_id`),
  KEY `luku_payment_purchases_subscriber_id_index` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('luku_payment_purchases');
    }
};
