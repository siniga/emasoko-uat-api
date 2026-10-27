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
CREATE TABLE `partner_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `partner_uuid` text NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(25) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `response_sms` mediumtext DEFAULT NULL,
  `send_verification` tinyint(4) NOT NULL DEFAULT 0,
  `collection_account_source` varchar(255) NOT NULL DEFAULT 'provider',
  `mapped_partner_account_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `branch_code` varchar(10) DEFAULT NULL,
  `currency_id` varchar(255) DEFAULT NULL,
  `customer_code` varchar(30) DEFAULT NULL,
  `corporate_limit` float DEFAULT NULL,
  `channel` varchar(15) DEFAULT 'NMB',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_accounts');
    }
};
