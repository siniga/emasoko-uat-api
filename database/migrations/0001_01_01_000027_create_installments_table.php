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
CREATE TABLE `installments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `debit_order_id` int(11) NOT NULL,
  `installment_order` int(11) NOT NULL,
  `installment_amount` float DEFAULT NULL,
  `min_installment_amount` float DEFAULT NULL,
  `max_installment_amount` float DEFAULT NULL,
  `current_balance` float DEFAULT NULL,
  `outstanding_amount` float DEFAULT NULL,
  `payment_type` varchar(10) DEFAULT NULL,
  `payment_reference` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `effective_payment_date` date DEFAULT NULL,
  `last_paid_amount` float DEFAULT NULL,
  `due_days` int(11) DEFAULT NULL,
  `past_due_amount` float DEFAULT NULL,
  `status` varchar(20) DEFAULT 'OPEN',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pushed` int(11) DEFAULT 0,
  `created` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
