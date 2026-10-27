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
CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `amount` float NOT NULL,
  `reference` varchar(50) NOT NULL,
  `msisdn` varchar(20) NOT NULL,
  `status` varchar(50) NOT NULL,
  `transid` varchar(50) NOT NULL,
  `insight_reference` varchar(64) NOT NULL,
  `remarks` varchar(128) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
