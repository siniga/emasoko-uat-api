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
CREATE TABLE `payment_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(20) NOT NULL DEFAULT 'NMB',
  `collection_batch_id` char(36) DEFAULT NULL,
  `collection_debit_order_id` bigint(20) unsigned DEFAULT NULL,
  `debit_order_id` bigint(20) unsigned NOT NULL,
  `installment_id` bigint(20) unsigned DEFAULT NULL,
  `mandate_reference` varchar(255) NOT NULL,
  `payment_reference` varchar(255) NOT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `raw_status` varchar(50) DEFAULT NULL,
  `normalized_status` varchar(50) NOT NULL,
  `settlement_type` varchar(50) DEFAULT NULL,
  `applied_to_balances` tinyint(1) NOT NULL DEFAULT 1,
  `reconciliation_status` varchar(30) NOT NULL DEFAULT 'PENDING',
  `backfill_run_id` char(36) DEFAULT NULL,
  `expected_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `opening_installment_balance` decimal(18,2) DEFAULT NULL,
  `closing_installment_balance` decimal(18,2) DEFAULT NULL,
  `opening_debit_order_balance` decimal(18,2) DEFAULT NULL,
  `closing_debit_order_balance` decimal(18,2) DEFAULT NULL,
  `effective_payment_date` date DEFAULT NULL,
  `transaction_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `raw_payload_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_settlements_unique_payment` (`channel`,`payment_reference`,`installment_id`),
  KEY `payment_settlements_channel_index` (`channel`),
  KEY `payment_settlements_collection_batch_id_index` (`collection_batch_id`),
  KEY `payment_settlements_collection_debit_order_id_index` (`collection_debit_order_id`),
  KEY `payment_settlements_debit_order_id_index` (`debit_order_id`),
  KEY `payment_settlements_installment_id_index` (`installment_id`),
  KEY `payment_settlements_mandate_reference_index` (`mandate_reference`),
  KEY `payment_settlements_payment_reference_index` (`payment_reference`),
  KEY `payment_settlements_external_reference_index` (`external_reference`),
  KEY `payment_settlements_normalized_status_index` (`normalized_status`),
  KEY `payment_settlements_effective_payment_date_index` (`effective_payment_date`),
  KEY `payment_settlements_raw_payload_hash_index` (`raw_payload_hash`),
  KEY `payment_settlements_backfill_run_id_index` (`backfill_run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlements');
    }
};
