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
CREATE TABLE `app_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cur_customer_code` int(11) DEFAULT NULL,
  `next_customer_code` varchar(15) DEFAULT NULL,
  `cur_collection_reference` bigint(20) DEFAULT NULL,
  `next_collection_reference` bigint(20) DEFAULT NULL,
  `cur_mandate_reference` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_variables');
    }
};
