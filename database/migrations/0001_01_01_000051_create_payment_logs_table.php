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
CREATE TABLE `payment_logs` (
  `id` int(11) NOT NULL,
  `msisdn` varchar(15) NOT NULL,
  `amount` int(11) NOT NULL,
  `end_date` date DEFAULT NULL,
  `reference` varchar(45) NOT NULL,
  `spid` int(11) NOT NULL DEFAULT 259911,
  `request_id` varchar(45) DEFAULT NULL,
  `service_id` int(11) NOT NULL DEFAULT 1,
  `callback` varchar(100) DEFAULT NULL,
  `ipg_reference` varchar(45) DEFAULT NULL,
  `status` enum('INITIATED','SUCCESS','FAIL','ERROR') NOT NULL DEFAULT 'INITIATED',
  `remarks` varchar(65) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
