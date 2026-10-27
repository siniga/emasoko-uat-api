<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Partner;
use App\Models\Wallet;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Tests\TestCase;

class WalletContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Passport::actingAs supplies test identities; no production signing keys are needed.
        $this->app->instance(ResourceServer::class, Mockery::mock(ResourceServer::class));

        // Use a dedicated in-memory connection; never migrate or reset the portal database.
        config()->set('database.connections.wallet_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('wallet_test');

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('business_name');
            $table->string('reference');
            $table->string('short_code')->nullable();
            $table->string('client_code')->nullable();
            $table->timestamps();
        });
        Schema::create('partner_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('authorize');
        });

        // SQLite uses the archived Blueprint definition; MariaDB has a separate baseline verifier.
        (require database_path('migrations/archive/2026_10_22_000001_create_wallets_table.php'))->up();
    }

    public function test_guests_cannot_read_wallet_context(): void
    {
        $this->getJson('/api/v1/wallet/context')->assertUnauthorized();
    }

    public function test_context_only_returns_the_users_authorised_businesses(): void
    {
        foreach ([1, 2, 3] as $id) {
            DB::table('partners')->insert([
                'id' => $id, 'uuid' => "business-$id",
                'business_name' => "Business $id", 'reference' => "CO-$id",
            ]);
        }
        DB::table('partner_users')->insert([
            ['user_id' => 10, 'partner_id' => 1, 'authorize' => 1],
            ['user_id' => 10, 'partner_id' => 2, 'authorize' => 0],
            ['user_id' => 11, 'partner_id' => 3, 'authorize' => 1],
        ]);
        Passport::actingAs((new User())->forceFill(['id' => 10, 'name' => 'Wallet user', 'active' => 1]));

        $this->getJson('/api/v1/wallet/context?partner_id=3')
            ->assertOk()
            ->assertJsonPath('data.currency', 'TZS')
            ->assertJsonPath('data.user.id', 10)
            ->assertJsonCount(1, 'data.businesses')
            ->assertJsonPath('data.businesses.0.id', 1)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_users_without_authorised_businesses_are_blocked(): void
    {
        Passport::actingAs((new User())->forceFill(['id' => 10, 'active' => 1]));

        $this->getJson('/api/v1/wallet/context')->assertForbidden();
    }

    public function test_inactive_users_are_blocked_even_with_a_token(): void
    {
        Passport::actingAs((new User())->forceFill(['id' => 10, 'active' => 0]));

        $this->getJson('/api/v1/wallet/context')->assertForbidden();
    }

    public function test_wallet_accounts_require_authentication(): void
    {
        $this->getJson('/api/v1/wallet/account')->assertUnauthorized();
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_wallet_is_available_by_default_and_repeated_requests_reuse_it(): void
    {
        $this->linkBusiness(1);
        $this->signIn();
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-22 12:00:00', 'Africa/Nairobi'));

        $first = $this->getJson('/api/v1/wallet/account?currency=USD&status=FROZEN');
        $first->assertOk()
            ->assertJsonPath('data.wallet.partner_id', 1)
            ->assertJsonPath('data.wallet.currency', 'TZS')
            ->assertJsonPath('data.wallet.status', 'ACTIVE')
            ->assertJsonPath('data.wallet.created_at', '2026-10-22T09:00:00+00:00')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertTrue(\Illuminate\Support\Str::isUuid($first->json('data.wallet.id')));

        $this->getJson('/api/v1/wallet/account')->assertOk()
            ->assertJsonPath('data.wallet.id', $first->json('data.wallet.id'));
        $this->assertDatabaseCount('wallets', 1);
        $this->assertDatabaseHas('wallets', ['created_at' => '2026-10-22 09:00:00']);
    }

    public function test_wallet_creation_endpoint_is_removed(): void
    {
        $this->linkBusiness(1);
        $this->signIn();

        $this->postJson('/api/v1/wallet/account')->assertStatus(405);
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_wallet_can_be_fetched_by_another_authorised_user_of_the_same_business(): void
    {
        $this->linkBusiness(1);
        $this->signIn();
        $id = $this->getJson('/api/v1/wallet/account')->assertOk()->json('data.wallet.id');
        DB::table('partner_users')->insert(['partner_id' => 1, 'user_id' => 11, 'authorize' => 1]);
        $this->signIn(11);

        $this->getJson('/api/v1/wallet/account')->assertOk()->assertJsonPath('data.wallet.id', $id);
    }

    public function test_unauthorised_businesses_cannot_be_read_or_initialised(): void
    {
        $this->linkBusiness(1);
        $this->linkBusiness(2, 11);
        $this->signIn(11);
        $this->getJson('/api/v1/wallet/account')->assertOk();
        $this->signIn();

        $this->getJson('/api/v1/wallet/account?partner_id=2')->assertForbidden();
        $this->assertDatabaseCount('wallets', 1);
    }

    public function test_revoked_membership_and_inactive_users_cannot_access_or_provision_wallets(): void
    {
        $this->linkBusiness(1);
        $this->signIn();
        DB::table('partner_users')->update(['authorize' => 0]);
        $this->getJson('/api/v1/wallet/account?partner_id=1')->assertForbidden();
        DB::table('partner_users')->update(['authorize' => 1]);
        Passport::actingAs((new User())->forceFill(['id' => 10, 'active' => 0]));
        $this->getJson('/api/v1/wallet/account')->assertForbidden();
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_users_with_multiple_businesses_must_choose_an_authorised_business(): void
    {
        $this->linkBusiness(1);
        $this->linkBusiness(2);
        $this->signIn();

        $this->getJson('/api/v1/wallet/account')->assertUnprocessable();
        $this->assertDatabaseCount('wallets', 0);
        $this->getJson('/api/v1/wallet/account?partner_id=2')->assertOk()
            ->assertJsonPath('data.wallet.partner_id', 2);
    }

    public function test_invalid_business_identifiers_are_rejected(): void
    {
        $this->signIn();
        foreach (['not-an-id', 0, -1, ['id' => 1]] as $partnerId) {
            $this->getJson('/api/v1/wallet/account?' . http_build_query(['partner_id' => $partnerId]))->assertUnprocessable();
        }
        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_existing_frozen_wallet_is_not_reset_by_account_request(): void
    {
        $this->linkBusiness(1);
        $this->signIn();
        $this->getJson('/api/v1/wallet/account')->assertOk();
        DB::table('wallets')->update(['status' => 'FROZEN']);

        $this->getJson('/api/v1/wallet/account')->assertOk()->assertJsonPath('data.wallet.status', 'FROZEN');
    }

    public function test_database_prevents_a_second_wallet_for_a_business(): void
    {
        $this->linkBusiness(1);
        $this->signIn();
        $this->getJson('/api/v1/wallet/account')->assertOk();
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        DB::table('wallets')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(), 'partner_id' => 1,
            'currency' => 'TZS', 'status' => 'ACTIVE',
        ]);
    }

    public function test_database_prevents_deleting_a_business_with_a_wallet(): void
    {
        $this->linkBusiness(1);
        $this->signIn();
        $this->getJson('/api/v1/wallet/account')->assertOk();
        $this->assertSame(1, Wallet::first()->partner->id);
        $this->assertNotNull(\App\Models\Partner::find(1)->wallet);
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('partners')->where('id', 1)->delete();
    }

    public function test_new_business_gets_a_wallet_without_an_api_request(): void
    {
        $partner = Partner::create(['uuid' => 'new-business', 'business_name' => 'New business']);

        $this->assertDatabaseCount('wallets', 1);
        $this->assertSame('TZS', $partner->wallet->currency);
        $this->assertSame('ACTIVE', $partner->wallet->status);
        $this->assertSame($partner->wallet->id, Wallet::forPartner($partner->id)->id);
    }

    public function test_backfill_provisions_all_existing_businesses_and_preserves_existing_wallets(): void
    {
        $this->linkBusiness(1);
        $this->linkBusiness(2);
        $existing = Wallet::forPartner(1);
        $existing->update(['status' => 'FROZEN']);
        $original = $existing->fresh()->getAttributes();

        $migration = require database_path('migrations/archive/2026_10_22_000002_provision_default_business_wallets.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('wallets', 2);
        $this->assertSame($original, $existing->fresh()->getAttributes());
        $this->assertDatabaseHas('wallets', ['partner_id' => 2, 'currency' => 'TZS', 'status' => 'ACTIVE']);
        $migration->down();
        $this->assertDatabaseCount('wallets', 2);
    }

    private function signIn(int $userId = 10): void
    {
        Passport::actingAs((new User())->forceFill(['id' => $userId, 'name' => 'Wallet user', 'active' => 1]));
    }

    private function linkBusiness(int $partnerId, int $userId = 10): void
    {
        DB::table('partners')->insert([
            'id' => $partnerId, 'uuid' => "business-$partnerId",
            'business_name' => "Business $partnerId", 'reference' => "CO-$partnerId",
        ]);
        DB::table('partner_users')->insert(['partner_id' => $partnerId, 'user_id' => $userId, 'authorize' => 1]);
    }
}
