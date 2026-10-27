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
CREATE TABLE `customer_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` text DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `account_number` varchar(55) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `bank_code` varchar(5) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `secondary_phone` varchar(20) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'partial',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};
