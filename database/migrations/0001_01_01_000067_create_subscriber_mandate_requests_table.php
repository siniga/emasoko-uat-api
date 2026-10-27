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
CREATE TABLE `subscriber_mandate_requests` (
  `id` char(36) NOT NULL,
  `subscriber_id` char(36) DEFAULT NULL,
  `partner_id` char(36) NOT NULL,
  `debit_order_id` bigint(20) unsigned DEFAULT NULL,
  `control_number` varchar(255) NOT NULL,
  `control_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `charge_percent` decimal(8,4) NOT NULL DEFAULT 2.0000,
  `charge_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'TZS',
  `settlement_trigger` varchar(50) NOT NULL DEFAULT 'success_deduction',
  `status` varchar(255) NOT NULL DEFAULT 'verified',
  `verification_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_payload`)),
  `verification_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_response`)),
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriber_mandate_requests_partner_id_subscriber_id_index` (`partner_id`,`subscriber_id`),
  KEY `subscriber_mandate_requests_control_number_status_index` (`control_number`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_mandate_requests');
    }
};
