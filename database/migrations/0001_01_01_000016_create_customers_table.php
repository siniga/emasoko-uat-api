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
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(15) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `id_type` varchar(10) DEFAULT 'NIN',
  `id_number` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'partial',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `added_by` (`created_by`),
  KEY `id_type` (`id_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
