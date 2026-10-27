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
CREATE TABLE `subscribers` (
  `id` char(36) NOT NULL,
  `partner_id` char(36) NOT NULL,
  `subscriber_reference` varchar(255) NOT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `identity_type` varchar(255) DEFAULT NULL,
  `identity_number` varchar(255) DEFAULT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'GEPG',
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `account_type_id` bigint(20) unsigned DEFAULT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `bank_code` varchar(255) DEFAULT NULL,
  `branch_code` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `registration_status` varchar(255) DEFAULT NULL,
  `registration_source` varchar(255) NOT NULL DEFAULT 'web',
  `registration_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`registration_payload`)),
  `registration_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`registration_response`)),
  `registration_remarks` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscribers_partner_phone_unique` (`partner_id`,`phone`,`deleted`),
  UNIQUE KEY `subscribers_partner_account_unique` (`partner_id`,`account_number`,`deleted`),
  UNIQUE KEY `subscribers_subscriber_reference_unique` (`subscriber_reference`),
  UNIQUE KEY `subscribers_partner_identity_unique` (`partner_id`,`identity_number`,`deleted`),
  UNIQUE KEY `subscribers_partner_external_unique` (`partner_id`,`external_reference`,`deleted`),
  KEY `subscribers_partner_id_status_index` (`partner_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
