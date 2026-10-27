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
CREATE TABLE `collection_debit_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collection_batch_id` varchar(255) NOT NULL,
  `debit_order_id` bigint(20) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `installment_id` bigint(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `payment_at` datetime DEFAULT NULL,
  `payment_month` varchar(7) DEFAULT NULL,
  `account_month_unique_key` varchar(80) DEFAULT NULL,
  `pushed_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `bank_status` varchar(255) DEFAULT NULL,
  `bank_message` text DEFAULT NULL,
  `response_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `collection_debit_orders_installment_payment_unique` (`installment_id`,`payment_at`),
  UNIQUE KEY `collection_debit_orders_account_month_unique` (`account_month_unique_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_debit_orders');
    }
};
