<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Baseline: preserve the captured MariaDB column types, defaults, indexes and checks.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || DB::connection()->getTablePrefix() !== '') {
            throw new \RuntimeException('The baseline requires MySQL/MariaDB with an empty table prefix.');
        }
        $existing = array_intersect(Schema::getTableListing(), array_keys(\App\Support\SchemaBaseline::manifest()['tables']));
        if ($existing) {
            throw new \RuntimeException('Existing project tables detected. Run php artisan schema:baseline --apply before migrating.');
        }

        DB::statement(<<<'SQL'
CREATE TABLE `account_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `description` varchar(55) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
