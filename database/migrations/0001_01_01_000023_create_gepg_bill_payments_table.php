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
CREATE TABLE `gepg_bill_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `debit_order_id` bigint(20) unsigned NOT NULL,
  `payment_settlement_id` bigint(20) unsigned DEFAULT NULL,
  `mandate_reference` varchar(255) DEFAULT NULL,
  `control_number` varchar(255) NOT NULL,
  `trigger` varchar(50) NOT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `request_id` varchar(255) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'TZS',
  `status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `response_code` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `request_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_payload`)),
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gepg_bill_payments_idempotency_key_unique` (`idempotency_key`),
  KEY `gepg_bill_payments_debit_order_id_index` (`debit_order_id`),
  KEY `gepg_bill_payments_payment_settlement_id_index` (`payment_settlement_id`),
  KEY `gepg_bill_payments_mandate_reference_index` (`mandate_reference`),
  KEY `gepg_bill_payments_control_number_index` (`control_number`),
  KEY `gepg_bill_payments_trigger_index` (`trigger`),
  KEY `gepg_bill_payments_request_id_index` (`request_id`),
  KEY `gepg_bill_payments_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('gepg_bill_payments');
    }
};
