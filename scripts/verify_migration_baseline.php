<?php

// Integration verification against a disposable database on the configured local server.
// Never runs migrations, deletes, or changes schema on the configured project database.
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Support\SchemaBaseline;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI !== 'cli') throw new RuntimeException('CLI only.');
set_time_limit(0);
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $exception) {
    fwrite(STDERR, 'Migration verification failed: '.$exception->getMessage().PHP_EOL);
    exit(1);
});
$sourceName = DB::getDefaultConnection();
$source = DB::connection($sourceName);
$sourceConfig = $source->getConfig();
if ($source->getDriverName() !== 'mysql' || !in_array($sourceConfig['host'], ['127.0.0.1','localhost','::1'], true)) {
    throw new RuntimeException('Verification requires the configured local MySQL/MariaDB server.');
}
$sourceDatabase = $source->getDatabaseName();
$database = 'portal_migration_check_' . bin2hex(random_bytes(6));
$alias = 'migration_verification';
$created = false;
$manifest = SchemaBaseline::manifest();
$report = ['source_database'=>$sourceDatabase, 'verification_database'=>$database];
$quote = fn($name) => '`' . str_replace('`', '``', $name) . '`';
$assert = function($condition,$message) { if (!$condition) throw new RuntimeException($message); };
$run = function($command,$parameters=[]) use ($alias) {
    $exit = Artisan::call($command, ['--database'=>$alias] + $parameters);
    if ($exit !== 0) throw new RuntimeException($command . ' failed: ' . Artisan::output());
    return Artisan::output();
};
$rowCounts = function($connection) use ($manifest,$quote) {
    $counts = [];
    foreach (array_keys($manifest['tables']) as $table) $counts[$table] = (int)$connection->selectOne('SELECT COUNT(*) AS n FROM '.$quote($table))->n;
    return $counts;
};
$sourceBefore = $rowCounts($source);
$sourceHistoryBefore = $source->table('migrations')->orderBy('id')->get()->toJson();
try {
    $assert((int)$source->selectOne('SELECT COUNT(*) AS n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?',[$database])->n === 0,'Verification database already exists.');
    $source->statement('CREATE DATABASE '.$quote($database).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config()->set('database.connections.'.$alias, array_merge($sourceConfig,['database'=>$database,'url'=>null,'prefix'=>'','name'=>$alias]));
    DB::purge($alias);
    DB::setDefaultConnection($alias);
    Schema::clearResolvedInstance('db.schema');
    $connection = DB::connection($alias);
    $assert($connection->getName() === $alias && $connection->getDatabaseName() === $database,'Verification connection is not isolated.');
    $paths = array_map(fn($name) => 'database/migrations/'.$name.'.php',$manifest['migrations']);

    echo "Migrating the captured baseline on a disposable database...\n";
    $run('migrate',['--path'=>$paths,'--force'=>true]);
    foreach (['tables'=>'TABLE','views'=>'VIEW'] as $section=>$kind) foreach ($manifest[$section] as $name=>$expected) {
        $result = array_values((array)$connection->selectOne('SHOW CREATE '.$kind.' '.$quote($name)));
        $assert(SchemaBaseline::fingerprint($result[1],$database) === $expected['sha256'],'Schema mismatch: '.$name);
    }
    $report['matching_baseline_tables'] = count($manifest['tables']);
    $report['matching_baseline_views'] = count($manifest['views']);
    $report['baseline_foreign_keys'] = (int)$connection->selectOne('SELECT COUNT(*) AS n FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=?',[$database])->n;
    $assert($report['baseline_foreign_keys'] === 9,'Expected all nine captured foreign keys.');
    $connection->beginTransaction();
    try {
        $rejected = false;
        try { $connection->statement('INSERT INTO role_user (user_id, role_id) VALUES (2147483647,2147483647)'); }
        catch (\Illuminate\Database\QueryException $e) { $rejected = (string)$e->getCode() === '23000'; }
        $assert($rejected,'Foreign key enforcement did not reject an invalid relationship.');
    } finally { $connection->rollBack(); }

    echo "Checking existing-schema adoption, drift rejection, and idempotence...\n";
    $connection->table('migrations')->whereIn('migration',$manifest['migrations'])->delete();
    $connection->table('migrations')->insert(['migration'=>'legacy_verification_marker','batch'=>1]);
    $run('schema:baseline');
    $assert($connection->table('migrations')->count() === 1,'Preview changed migration history.');
    $connection->statement('ALTER TABLE accounts ADD verification_only_column INT NULL');
    $exit = Artisan::call('schema:baseline',['--database'=>$alias,'--apply'=>true]);
    $assert($exit !== 0 && $connection->table('migrations')->count() === 1,'Schema drift was not rejected without writes.');
    $connection->statement('ALTER TABLE accounts DROP COLUMN verification_only_column');
    $beforeAdoption = $rowCounts($connection);
    $run('schema:baseline',['--apply'=>true]);
    $assert($connection->table('migrations')->count() === count($manifest['migrations'])+1,'Baseline registration incomplete.');
    $assert($beforeAdoption === $rowCounts($connection),'Adoption changed application data.');
    $run('schema:baseline',['--apply'=>true]);
    $assert($connection->table('migrations')->count() === count($manifest['migrations'])+1,'Repeated adoption duplicated history.');
    $connection->table('migrations')->where('migration','legacy_verification_marker')->delete();
    $report['adoption_preview_apply_drift_and_repeat'] = 'passed';

    echo "Applying remaining upgrades and verifying rollback...\n";
    $run('migrate',['--force'=>true]);
    foreach (['system_health_checks','collection_integrity_issues','collection_batch_push_attempts'] as $table) {
        $assert($connection->getSchemaBuilder()->hasTable($table),'Missing upgrade table: '.$table);
    }
    $assert($connection->getSchemaBuilder()->hasColumn('payment_settlements','reverses_settlement_id'),'Missing settlement upgrade.');
    $report['remaining_upgrades'] = count($manifest['remaining_upgrades']);
    $report['final_table_count'] = count($connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));
    $report['final_view_count'] = count($connection->select("SHOW FULL TABLES WHERE Table_type = 'VIEW'"));
    $run('migrate:reset',['--force'=>true]);
    $assert(count($connection->select('SHOW FULL TABLES')) === 1,'Rollback left project tables or views behind.');

    echo "Checking a full clean migration run and a repeated run...\n";
    $run('migrate',['--force'=>true]);
    $historyCount = $connection->table('migrations')->count();
    $run('migrate',['--force'=>true]);
    $assert($connection->table('migrations')->count() === $historyCount,'Repeated migrate changed history.');
    // Laravel's fresh command leaves views in place unless --drop-views is supplied.
    $run('migrate:fresh',['--force'=>true]);
    $assert($connection->table('migrations')->count() === $historyCount,'Fresh migration history differs.');
    $run('migrate:reset',['--force'=>true]);
    $assert(count($connection->select('SHOW FULL TABLES')) === 1,'Full-run rollback left project objects behind.');
    $report['clean_migrate_repeat_fresh_and_rollback'] = 'passed';
} finally {
    DB::purge($alias);
    DB::setDefaultConnection($sourceName);
    Schema::clearResolvedInstance('db.schema');
    // Only remove the random database created by this invocation.
    if ($created && preg_match('/^portal_migration_check_[a-f0-9]{12}$/D',$database) && $database !== $sourceDatabase) {
        $source->statement('DROP DATABASE '.$quote($database));
    }
    $assert($sourceBefore === $rowCounts($source),'Source database row counts changed during verification.');
    $assert($sourceHistoryBefore === $source->table('migrations')->orderBy('id')->get()->toJson(),'Source migration history changed during verification.');
}
$report['source_application_rows_unchanged'] = array_sum($sourceBefore);
$report['temporary_database_removed'] = true;
$directory = storage_path('app/database-backups');
if (!is_dir($directory)) mkdir($directory,0700,true);
$path = $directory . '/migration-verification-' . gmdate('Ymd-His') . '.json';
file_put_contents($path,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\nReport: ".$path."\n";
