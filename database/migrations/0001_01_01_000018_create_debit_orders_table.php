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
CREATE TABLE `debit_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` text DEFAULT NULL,
  `reference` varchar(20) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `bank_sponsor_partner_id` int(11) DEFAULT NULL,
  `bank_sponsor_partner_account_id` int(11) DEFAULT NULL,
  `bank_sponsor_source` varchar(50) DEFAULT NULL,
  `subscriber_id` char(36) DEFAULT NULL,
  `mandate_source` varchar(255) DEFAULT NULL,
  `control_number` varchar(255) DEFAULT NULL,
  `control_number_amount` decimal(18,2) DEFAULT NULL,
  `charge_amount` decimal(18,2) DEFAULT NULL,
  `luku_meter_number` varchar(255) DEFAULT NULL,
  `luku_payment_purchase_id` bigint(20) unsigned DEFAULT NULL,
  `settlement_trigger` varchar(50) DEFAULT NULL,
  `customer_account_id` int(11) NOT NULL,
  `total_amount` float NOT NULL,
  `paid_amount` float NOT NULL DEFAULT 0,
  `outstanding_amount` float NOT NULL DEFAULT 0,
  `current_balance` float DEFAULT 0,
  `debit_type` varchar(20) DEFAULT NULL,
  `installment_amount` float DEFAULT NULL,
  `min_installment_amount` float DEFAULT 0,
  `max_installment_amount` float DEFAULT 0,
  `installment_number` int(11) NOT NULL,
  `paid_installment` int(11) DEFAULT 0,
  `failed_installment` int(11) DEFAULT 0,
  `periodicity` enum('ANNUALLY','MONTHLY','WEEKLY','DAILY') NOT NULL DEFAULT 'MONTHLY',
  `deduction_end_date` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `aggrement_evidence` varchar(255) DEFAULT NULL,
  `contract_status` varchar(20) DEFAULT 'OPEN',
  `status` varchar(20) DEFAULT '100',
  `otp_code` varchar(11) DEFAULT NULL,
  `approved` varchar(25) NOT NULL DEFAULT '0',
  `approved_by` int(11) DEFAULT NULL,
  `approver_at` datetime DEFAULT NULL,
  `approve_code` varchar(15) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cancellationStartedBy` bigint(20) unsigned DEFAULT NULL,
  `cancellationApprovedBy` bigint(20) unsigned DEFAULT NULL,
  `cancellationStartedAt` timestamp NULL DEFAULT NULL,
  `cancellationApprovedAt` timestamp NULL DEFAULT NULL,
  `next_debit_date` date DEFAULT NULL,
  `http_response_code` int(11) DEFAULT NULL,
  `http_response_json` text DEFAULT NULL,
  `http_response_status_json` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled` int(11) DEFAULT 0,
  `closed` int(11) DEFAULT 0,
  `archived` int(11) DEFAULT 0,
  `deleted` int(11) DEFAULT 0,
  `otp_reference` varchar(50) DEFAULT NULL,
  `channel` varchar(15) DEFAULT 'NMB',
  `crdb_reference` varchar(10) DEFAULT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `lifecycle_status` varchar(50) DEFAULT 'draft',
  PRIMARY KEY (`id`),
  KEY `debit_orders_cancellationstartedby_foreign` (`cancellationStartedBy`),
  KEY `debit_orders_cancellationapprovedby_foreign` (`cancellationApprovedBy`),
  KEY `debit_orders_luku_meter_number_index` (`luku_meter_number`),
  KEY `debit_orders_luku_payment_purchase_id_index` (`luku_payment_purchase_id`),
  KEY `debit_orders_bank_sponsor_partner_id_index` (`bank_sponsor_partner_id`),
  KEY `debit_orders_bank_sponsor_partner_account_id_index` (`bank_sponsor_partner_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_orders');
    }
};
