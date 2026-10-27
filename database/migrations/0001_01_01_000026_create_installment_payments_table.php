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
CREATE TABLE `installment_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mandate_reference` varchar(20) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `installment_id` int(11) DEFAULT NULL,
  `amount` float NOT NULL,
  `payment_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};
