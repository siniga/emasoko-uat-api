<?php

namespace App\Console\Commands;

use App\Support\SchemaBaseline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdoptSchemaBaseline extends Command
{
    protected $signature = 'schema:baseline
        {--database= : Database connection to inspect}
        {--apply : Register the verified baseline in the migrations table}';

    protected $description = 'Verify an existing MySQL schema and adopt its rebuilt migrations without changing application data';

    public function handle(): int
    {
        $connection = DB::connection($this->option('database') ?: null);
        if ($connection->getDriverName() !== 'mysql' || $connection->getTablePrefix() !== '') {
            $this->error('This baseline requires MySQL/MariaDB with an empty table prefix.');
            return self::FAILURE;
        }

        if (!$connection->getSchemaBuilder()->hasTable('migrations')) {
            $this->error('No migration history found. Use php artisan migrate for a new database.');
            return self::FAILURE;
        }

        $manifest = SchemaBaseline::manifest();
        $recorded = $connection->table('migrations')->pluck('migration')->all();
        $pending = array_values(array_diff($manifest['migrations'], $recorded));
        if (!$pending) {
            $this->info('The schema baseline is already registered.');
            return self::SUCCESS;
        }

        $errors = [];
        foreach (['tables' => 'TABLE', 'views' => 'VIEW'] as $section => $kind) {
            foreach ($manifest[$section] as $name => $expected) {
                $identifier = '`' . str_replace('`', '``', $name) . '`';
                try {
                    $result = (array) $connection->selectOne('SHOW CREATE ' . $kind . ' ' . $identifier);
                    $ddl = array_values($result)[1];
                    if (SchemaBaseline::fingerprint($ddl, $connection->getDatabaseName()) !== $expected['sha256']) {
                        $errors[] = $name . ': schema differs from the baseline';
                    }
                } catch (\Throwable $e) {
                    $errors[] = $name . ': definition is unavailable';
                }
            }
        }

        if ($errors) {
            foreach ($errors as $error) $this->error($error);
            $this->error('Nothing was registered. Resolve schema differences before adopting the baseline.');
            return self::FAILURE;
        }

        $this->info(sprintf('Verified %d tables and %d views; %d baseline migrations to register.', count($manifest['tables']), count($manifest['views']), count($pending)));
        if (!$this->option('apply')) {
            $this->line('Preview only. Add --apply to register these migrations without running their DDL.');
            return self::SUCCESS;
        }

        $connection->transaction(function () use ($connection, $pending) {
            foreach ($pending as $migration) {
                // Batch zero keeps the adopted baseline below subsequent upgrades.
                $connection->table('migrations')->insert(['migration' => $migration, 'batch' => 0]);
            }
        });

        $this->info('Baseline registered. Application data and schema were unchanged.');
        $this->line('Run php artisan migrate to apply the remaining upgrades.');
        return self::SUCCESS;
    }
}
