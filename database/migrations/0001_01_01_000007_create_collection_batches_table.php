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
CREATE TABLE `collection_batches` (
  `id` char(36) NOT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `bank_sponsor_partner_id` int(11) DEFAULT NULL,
  `bank_sponsor_source` varchar(50) DEFAULT NULL,
  `collectionReference` varchar(20) DEFAULT NULL,
  `corporateReference` varchar(255) NOT NULL,
  `selectedPostingDate` varchar(255) DEFAULT NULL,
  `collectionDate` date DEFAULT NULL,
  `createdBy` varchar(255) DEFAULT NULL,
  `verifiedBy` varchar(255) DEFAULT NULL,
  `createdDate` timestamp NULL DEFAULT NULL,
  `verifyDate` timestamp NULL DEFAULT NULL,
  `pushed` tinyint(4) DEFAULT 0,
  `processed` tinyint(4) NOT NULL DEFAULT 0,
  `response` mediumtext DEFAULT NULL,
  `lifecycle_status` varchar(255) DEFAULT NULL,
  `last_pushed_at` timestamp NULL DEFAULT NULL,
  `last_status_checked_at` timestamp NULL DEFAULT NULL,
  `push_correlation_id` varchar(255) DEFAULT NULL,
  `status_payload_hash` varchar(255) DEFAULT NULL,
  `recheckStatusOn` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` int(11) DEFAULT 100,
  `failed` int(11) DEFAULT 0,
  `http_response_code` int(11) DEFAULT NULL,
  `http_response_json` text DEFAULT NULL,
  `http_response_status_json` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `number_of_collections` int(11) DEFAULT 0,
  `number_of_success` int(11) DEFAULT NULL,
  `number_of_failed` int(11) DEFAULT NULL,
  `request_csv` longtext DEFAULT NULL,
  `response_csv` longtext DEFAULT NULL,
  `installment_ids` text DEFAULT NULL,
  `deleted` int(11) DEFAULT 0,
  `recreated` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `collection_batches_bank_sponsor_partner_id_index` (`bank_sponsor_partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_batches');
    }
};
