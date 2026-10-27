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
CREATE TABLE `partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `partner_category_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `year_of_incorporation` year(4) NOT NULL,
  `tin_number` varchar(45) NOT NULL,
  `number_of_staff` int(11) DEFAULT 0,
  `region_id` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(40) NOT NULL,
  `website` varchar(100) DEFAULT NULL,
  `postal_address` text DEFAULT NULL,
  `physical_address` text NOT NULL,
  `complete` int(11) NOT NULL DEFAULT 0,
  `approved` int(11) DEFAULT 0,
  `status` varchar(50) NOT NULL DEFAULT '0',
  `lifecycle_status` varchar(255) DEFAULT NULL,
  `registration_source` varchar(20) NOT NULL DEFAULT 'officer',
  `next_step` text DEFAULT NULL,
  `otp_token` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reference` varchar(255) DEFAULT NULL,
  `short_code` varchar(5) DEFAULT NULL,
  `client_code` varchar(5) DEFAULT NULL,
  `http_response_code` int(11) DEFAULT NULL,
  `http_response_json` text DEFAULT NULL,
  `http_response_status_json` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `callback_url` varchar(255) DEFAULT NULL,
  `callback_secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`,`partner_category_id`),
  KEY `partners_lifecycle_status_index` (`lifecycle_status`),
  KEY `partners_registration_source_index` (`registration_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
