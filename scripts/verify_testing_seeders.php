<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Database\Seeders\Testing\TestingDataPlan;
use Database\Seeders\TestingDatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI!=='cli') throw new RuntimeException('CLI only.');
set_time_limit(0);
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e) { fwrite(STDERR,'Seeder verification failed: '.$e->getMessage().PHP_EOL); exit(1); });
$sourceName = DB::getDefaultConnection();
$source = DB::connection();
$config = $source->getConfig();
if ($source->getDriverName()!=='mysql' || !in_array($config['host'],['127.0.0.1','localhost','::1'],true)) throw new RuntimeException('A local MySQL/MariaDB reference server is required.');
$quote = fn($s)=>'`'.str_replace('`','``',$s).'`';
$snapshot = function($db) use ($quote) {
    $snapshot = [];
    foreach ($db->select("SHOW FULL TABLES WHERE Table_type='BASE TABLE'") as $table) {
        $name = array_values((array)$table)[0];
        $rows = array_map(fn($r)=>json_encode((array)$r),$db->select('SELECT * FROM '.$quote($name)));
        sort($rows,SORT_STRING);
        $snapshot[$name] = ['count'=>count($rows),'sha256'=>hash('sha256',implode("\n",$rows))];
    }
    ksort($snapshot);
    return $snapshot;
};
$beforeSource = $snapshot($source);
$database = 'portal_seeder_check_'.bin2hex(random_bytes(6));
$alias = 'seeder_verification';
$environment = app()->environment();
$created = false;
$attachments = [public_path('assets/uploads/docs/emakato-testing-sample.txt'),public_path('assets/uploads/pdf/emakato-testing-sample.txt')];
$existingAttachments = array_fill_keys(array_filter($attachments,'is_file'),true);
$assert = function($test,$message) { if (!$test) throw new RuntimeException($message); };
$run = function($command,$options=[]) use ($alias) {
    $exit = Artisan::call($command,['--database'=>$alias,'--force'=>true]+$options);
    if ($exit!==0) throw new RuntimeException($command.' failed: '.Artisan::output());
};
$seed = fn()=> $run('db:seed',['--class'=>TestingDatabaseSeeder::class]);
$report = [];
try {
    $assert((int)$source->selectOne('SELECT COUNT(*) AS n FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?',[$database])->n===0,'Temporary database exists.');
    $source->statement('CREATE DATABASE '.$quote($database).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config()->set('database.connections.'.$alias,array_merge($config,['name'=>$alias,'database'=>$database,'url'=>null,'prefix'=>'']));
    DB::purge($alias);
    DB::setDefaultConnection($alias);
    Schema::clearResolvedInstance('db.schema');
    $db = DB::connection($alias);
    $assert($db->getName()===$alias && $db->getDatabaseName()===$database,'Database isolation failed.');
    config()->set('passport.storage.database.connection',$alias);
    config()->set('passport.connection',$alias);
    config()->set('testing-data.rows',30);
    $password = bin2hex(random_bytes(12)).'Test!';
    config()->set('testing-data.password',$password);
    config()->set('testing-data.enabled',true);
    app()->instance('env','testing');
    echo "Creating current migrated schema on an isolated database...\n";
    $run('migrate');
    $tableMap = json_decode(file_get_contents(database_path('seeders/data/testing-table-map.json')),true,512,JSON_THROW_ON_ERROR);
    $mapped = array_keys($tableMap['tables']);
    $expectedMap = array_merge(TestingDataPlan::tables(),array_keys(TestingDataPlan::RUNTIME),['migrations']);
    $assert(!array_diff($mapped,$expectedMap) && !array_diff($expectedMap,$mapped),'The documented table map does not match executable seeder coverage.');
    foreach (TestingDataPlan::GROUPS as $group=>$tables) foreach ($tables as $table) {
        $assert($tableMap['tables'][$table]['seeder']===$group,'Incorrect documented seeder for '.$table);
    }
    $empty = $snapshot($db);
    echo "Checking production/opt-in guards and transactional rollback...\n";
    app()->instance('env','production');
    $blocked = false;
    try { $seed(); } catch (RuntimeException $e) { $blocked = str_contains($e->getMessage(),'Production'); }
    $assert($blocked && $snapshot($db)===$empty,'Production guard failed or wrote data.');
    app()->instance('env','testing');
    config()->set('testing-data.enabled',false);
    $blocked = false;
    try { $seed(); } catch (RuntimeException $e) { $blocked = str_contains($e->getMessage(),'TEST_DATA_ENABLED'); }
    $assert($blocked && $snapshot($db)===$empty,'Opt-in guard failed or wrote data.');
    config()->set('testing-data.enabled',true);
    $db->table('roles')->insert(['id'=>2,'name'=>'Conflicting role','description'=>'Existing row']);
    $collision = $snapshot($db);
    $blocked = false;
    try { $seed(); } catch (RuntimeException $e) { $blocked = str_contains($e->getMessage(),'collision'); }
    $assert($blocked && $snapshot($db)===$collision,'Canonical ID collision was not rolled back completely.');
    $db->table('roles')->where('id',2)->delete();

    Http::preventStrayRequests();
    Http::fake();
    Queue::fake();
    Bus::fake();
    Mail::fake();
    echo "Seeding every mapped application table...\n";
    $seed();
    $counts = [];
    foreach (TestingDataPlan::tables() as $table) {
        $counts[$table] = $db->table($table)->count();
        $assert($counts[$table]>0,'No fixtures inserted for '.$table);
    }
    foreach (TestingDataPlan::RUNTIME as $table=>$reason) $assert($db->table($table)->count()===0,'Runtime table should be empty: '.$table);
    Http::assertNothingSent(); Queue::assertNothingPushed(); Bus::assertNothingDispatched(); Mail::assertNothingSent(); Mail::assertNothingQueued();
    $foreignKeys = $db->select('SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL',[$database]);
    foreach ($foreignKeys as $fk) {
        $orphans = $db->selectOne('SELECT COUNT(*) AS n FROM '.$quote($fk->TABLE_NAME).' c LEFT JOIN '.$quote($fk->REFERENCED_TABLE_NAME).' p ON c.'.$quote($fk->COLUMN_NAME).'=p.'.$quote($fk->REFERENCED_COLUMN_NAME).' WHERE c.'.$quote($fk->COLUMN_NAME).' IS NOT NULL AND p.'.$quote($fk->REFERENCED_COLUMN_NAME).' IS NULL')->n;
        $assert((int)$orphans===0,'Broken physical FK in '.$fk->TABLE_NAME);
    }
    foreach ([
        ['customer_accounts','customer_id','customers','id'],['customer_accounts','account_id','accounts','id'],
        ['customer_partners','customer_id','customers','id'],['customer_partners','partner_id','partners','id'],
        ['partner_accounts','partner_id','partners','id'],['partner_accounts','partner_uuid','partners','uuid'],
        ['partner_staff','partner_id','partners','id'],['partner_documents','partner_uuid','partners','uuid'],
        ['partner_users','user_id','users','id'],['partner_users','partner_id','partners','id'],
        ['debit_orders','partner_id','partners','id'],['debit_orders','customer_account_id','customer_accounts','id'],
        ['debit_orders','subscriber_id','subscribers','id'],['debit_orders','bank_sponsor_partner_account_id','partner_accounts','id'],
        ['debit_orders','luku_payment_purchase_id','luku_payment_purchases','id'],
        ['installments','debit_order_id','debit_orders','id'],['collection_debit_orders','installment_id','installments','id'],
        ['collection_debit_orders','collection_batch_id','collection_batches','id'],['collection_debit_orders','debit_order_id','debit_orders','id'],
        ['payment_settlements','debit_order_id','debit_orders','id'],['payment_settlements','installment_id','installments','id'],
        ['payment_settlements','collection_debit_order_id','collection_debit_orders','id'],
        ['installment_payments','installment_id','installments','id'],['installment_payments','mandate_reference','debit_orders','reference'],
        ['subscribers','partner_id','partners','id'],['subscriber_users','subscriber_id','subscribers','id'],
        ['subscriber_mandate_requests','debit_order_id','debit_orders','id'],['luku_payment_purchases','debit_order_id','debit_orders','id'],
        ['gepg_bill_payments','payment_settlement_id','payment_settlements','id'],
        ['payment_settlement_backfill_archives','payment_settlement_id','payment_settlements','id'],
        ['notifications','notifiable_id','users','id'],['audit_logs','auditable_id','debit_orders','id'],
    ] as [$child,$column,$parent,$pk]) {
        // Legacy UUID/text columns have different collations; compare identifier bytes.
        $orphans = $db->selectOne('SELECT COUNT(*) AS n FROM '.$quote($child).' c LEFT JOIN '.$quote($parent).' p ON BINARY c.'.$quote($column).'=BINARY p.'.$quote($pk).' WHERE c.'.$quote($column).' IS NOT NULL AND p.'.$quote($pk).' IS NULL')->n;
        $assert((int)$orphans===0,'Broken application relation: '.$child.'.'.$column);
    }
    foreach ($db->table('debit_orders')->get() as $mandate) {
        $paid = (float)$db->table('payment_settlements')->where('debit_order_id',$mandate->id)->where('applied_to_balances',1)->where('reconciliation_status','POSTED')->sum('paid_amount');
        $assert(abs($paid-(float)$mandate->paid_amount)<0.005,'Mandate paid amount differs from settlements.');
        $assert(abs((float)$mandate->total_amount-$paid-(float)$mandate->outstanding_amount)<0.005,'Mandate balance does not reconcile.');
    }
    $views = array_keys(json_decode(file_get_contents(database_path('schema/baseline.json')),true)['views']);
    foreach ($views as $view) $assert((int)$db->selectOne('SELECT COUNT(*) AS n FROM '.$quote($view))->n>0,'Reporting view has no test rows: '.$view);
    $admin = \App\Models\User::where('email','admin@testing.emakato.invalid')->firstOrFail();
    $assert($admin->active===1 || (int)$admin->active===1,'Admin is not active.');
    $assert($admin->hasRole('admin') && Hash::check($password,$admin->password),'Test account password or role is incorrect.');
    $assert($admin->otp_password===base64_encode($password),'Legacy web OTP credentials do not match.');
    $operator = \App\Models\User::where('email','operator@testing.emakato.invalid')->firstOrFail();
    $assert($operator->partners()->where('authorize',1)->count()===30,'Operator is missing provider access.');
    $assert(\App\Models\DebitOrder::eligibleForCollection()->count()>0,'No active mandates are available for workflow tests.');
    $assert(\App\Models\PaymentSettlement::reportable()->count()>0,'No settlements are reportable.');
    foreach ($attachments as $file) $assert(is_file($file),'A testing attachment is missing.');

    echo "Checking repeat runs preserve existing records and edits...\n";
    $db->table('customers')->insert(['email'=>'existing@example.invalid','fullname'=>'Existing testing record','created_by'=>$admin->id]);
    $db->table('partners')->where('reference','TEST-CO-0001')->update(['business_name'=>'Edited testing provider']);
    $beforeRepeat = $snapshot($db);
    $seed();
    $assert($snapshot($db)===$beforeRepeat,'Repeated seeding modified or duplicated existing rows.');
    $clock = \Carbon\Carbon::now()->addMonthNoOverflow();
    \Carbon\CarbonImmutable::setTestNow($clock);
    try { $seed(); } finally { \Carbon\CarbonImmutable::setTestNow(); }
    $assert($snapshot($db)===$beforeRepeat,'A later-month seed run duplicated history.');
    Http::assertNothingSent(); Queue::assertNothingPushed(); Bus::assertNothingDispatched(); Mail::assertNothingSent();
    echo "Checking real API authentication and the web OTP entry path with fake transports...\n";
    $keyOptions = ['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA];
    if (is_file('C:/xampp/apache/conf/openssl.cnf')) $keyOptions['config'] = 'C:/xampp/apache/conf/openssl.cnf';
    $key = openssl_pkey_new($keyOptions);
    $assert($key!==false,'Cannot generate disposable Passport keys.');
    $assert(openssl_pkey_export($key,$privateKey,null,$keyOptions),'Cannot export disposable Passport key.');
    $publicKey = openssl_pkey_get_details($key)['key'];
    $personal = $db->table('oauth_clients')->where('name','Emakato Testing personal')->first();
    config()->set('passport.private_key',$privateKey);
    config()->set('passport.public_key',$publicKey);
    config()->set('passport.personal_access_client',['id'=>$personal->id,'secret'=>$personal->secret]);
    config()->set('session.driver','array');
    config()->set('cache.default','array');
    \Illuminate\Support\Facades\Cache::setDefaultDriver('array');
    \Illuminate\Support\Facades\Auth::forgetGuards();
    foreach ([\Laravel\Passport\ClientRepository::class,\Laravel\Passport\PersonalAccessTokenFactory::class,\League\OAuth2\Server\AuthorizationServer::class] as $service) app()->forgetInstance($service);
    $response = app(\App\Http\Controllers\Api\ExternalApp\AuthController::class)->token(\Illuminate\Http\Request::create('/api/v1/token','POST',['partnerId'=>'test.api','password'=>$password]));
    $payload = $response->getData(true);
    $assert($response->getStatusCode()===200 && !empty($payload['data']['token']),'Seeded API account cannot obtain a Passport token.');
    $assert(count($payload['data']['corporate_references'])===30,'API account is missing linked providers.');
    \Illuminate\Support\Facades\Auth::logout();
    $response = app(\App\Http\Controllers\Api\MobileApp\AuthController::class)->login(\Illuminate\Http\Request::create('/api/mobile/v1/login','POST',['email'=>$admin->email,'password'=>$password]));
    $assert($response->getStatusCode()===200 && !empty($response->getData(true)['data']['token']),'Seeded mobile login cannot obtain a token.');
    \Illuminate\Support\Facades\Auth::logout();
    $request = \Illuminate\Http\Request::create('/otp/generate','POST',['email'=>$admin->email,'password'=>$password]);
    $session = app('session')->driver('array'); $session->start();
    $request->setLaravelSession($session);
    app()->instance('request',$request);
    app('redirect')->setSession($session);
    $response = app(\App\Http\Controllers\Auth\AuthOtpController::class)->generate($request);
    $assert($response->getStatusCode()===302 && str_contains($response->getTargetUrl(),'/otp/verification/'),'Web OTP credentials were not accepted.');
    $assert($db->table('user_otps')->where('user_id',$admin->id)->where('expire_at','>',now())->exists(),'Web OTP was not generated.');
    $otp = $db->table('user_otps')->where('user_id',$admin->id)->where('expire_at','>',now())->orderByDesc('id')->first();
    $otpRequest = \Illuminate\Http\Request::create('/otp/login','POST',['user_id'=>$admin->id,'otp'=>$otp->otp]);
    $otpRequest->setLaravelSession($session);
    $response = app(\App\Http\Controllers\Auth\AuthOtpController::class)->loginWithOtp($otpRequest);
    $assert($response->getStatusCode()===302 && str_contains($response->getTargetUrl(),'/dashboard'),'Web OTP verification did not reach the dashboard.');
    $assert((int)\Illuminate\Support\Facades\Auth::id()===(int)$admin->id,'Web OTP did not authenticate the seeded admin.');
    $report = ['application_tables_mapped'=>count(TestingDataPlan::tables())+count(TestingDataPlan::RUNTIME),'populated_tables'=>count(TestingDataPlan::tables()),'runtime_tables_intentionally_empty'=>array_keys(TestingDataPlan::RUNTIME),'physical_foreign_keys_checked'=>count($foreignKeys),'reporting_views_checked'=>count($views),'seeded_rows'=>array_sum($counts),'workflow_rows'=>30,'production_and_opt_in_guards'=>'passed','collision_rollback'=>'passed','relationships_and_balances'=>'passed','repeat_run_preserves_edits'=>'passed','later_month_repeat'=>'passed','api_mobile_and_web_otp_authentication'=>'passed with fake SMS/mail transports and disposable Passport keys','seeding_outbound_side_effects'=>0,'table_counts'=>$counts];
} finally {
    DB::purge($alias);
    DB::setDefaultConnection($sourceName);
    Schema::clearResolvedInstance('db.schema');
    app()->instance('env',$environment);
    if ($created && preg_match('/^portal_seeder_check_[a-f0-9]{12}$/D',$database) && $database!==$source->getDatabaseName()) $source->statement('DROP DATABASE '.$quote($database));
    $assert($snapshot($source)===$beforeSource,'Source database was changed during verification.');
    foreach ($attachments as $file) if (!isset($existingAttachments[$file]) && is_file($file)) unlink($file);
}
$report['source_database_unchanged'] = true;
$report['temporary_database_removed'] = true;
$directory = storage_path('app/database-backups');
if (!is_dir($directory)) mkdir($directory,0700,true);
$path = $directory.'/seeder-verification-'.gmdate('Ymd-His').'.json';
file_put_contents($path,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(array_diff_key($report,['table_counts'=>true]),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\nReport: ".$path."\n";
