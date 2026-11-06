<?php

namespace Tests\Feature;

use App\Services\EquityCheckoutService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class EquityCheckoutTest extends TestCase
{
    private string $checkoutId = '0d8036eb-c8e1-42f7-8b6a-2e8d356029fe';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn () => 'local');
        config(['cache.default' => 'array', 'services.cybersource.checkout_enabled' => false, 'services.cybersource.checkout_origin' => 'https://localhost:3000']);
    }

    private function checkout(array $overrides = []): array
    {
        $checkout = array_merge([
            'reference' => 'EQUITY-REFERENCE', 'amount' => '1000.00', 'currency' => 'TZS',
            'token_key' => ['kty' => 'RSA'], 'expires_at' => time() + 900, 'state' => 'ready',
            'flow' => 'sale-v1',
        ], $overrides);
        Cache::put('equity-checkout:'.$this->checkoutId, $checkout, 3600);

        return $checkout;
    }

    private function authorize(array $extra = [])
    {
        return $this->postJson('/api/cybersource/checkout/begin', array_merge([
            'checkout_id' => $this->checkoutId, 'transient_token' => 'header.payload.signature',
        ], $extra));
    }

    public function test_context_stores_amount_and_key_without_authorizing(): void
    {
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('captureContext')->once()->with('1000.00', 'TZS', Mockery::type('string'))
            ->andReturn([
                'capture_context' => 'context', 'token_key' => ['kty' => 'RSA'], 'expires_at' => time() + 900,
                'client_library' => 'https://up.cybersource.com/uc/library.js', 'client_library_integrity' => 'sha256-test',
            ]);
        $this->app->instance(EquityCheckoutService::class, $service);
        $response = $this->postJson('/api/cybersource/capture-context', ['amount' => '1000', 'currency' => 'TZS'])
            ->assertOk()->assertJsonPath('amount', '1000.00')->assertJsonMissingPath('token_key');
        $saved = Cache::get('equity-checkout:'.$response->json('checkout_id'));
        $this->assertSame('1000.00', $saved['amount']);
        $this->assertSame('ready', $saved['state']);
    }

    public function test_begin_verifies_token_and_prevents_duplicate_submissions(): void
    {
        $checkout = $this->checkout();
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('verifyToken')->once()->with('header.payload.signature', $checkout['token_key']);
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize(['amount' => '0.01', 'currency' => 'USD'])->assertOk()
            ->assertJsonPath('proceed', true);
        $saved = Cache::get('equity-checkout:'.$this->checkoutId);
        $this->assertSame('1000.00', $saved['amount']);
        $this->assertSame('TZS', $saved['currency']);
        $this->assertSame('submitted', $saved['state']);
        $this->authorize()->assertStatus(409);
    }

    public function test_invalid_token_is_rejected_before_upstream_payment(): void
    {
        $this->checkout();
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('verifyToken')->once()->andThrow(new \UnexpectedValueException('Invalid signature'));
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize()->assertUnprocessable();
        $this->assertSame('ready', Cache::get('equity-checkout:'.$this->checkoutId)['state']);
    }

    public function test_expired_checkout_cannot_authorize(): void
    {
        $this->checkout(['expires_at' => time() - 1]);
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldNotReceive('verifyToken');
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize()->assertStatus(410);
    }

    public function test_status_network_failure_never_marks_paid_or_allows_another_begin(): void
    {
        $this->checkout(['state' => 'submitted']);
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldNotReceive('verifyToken');
        $service->shouldReceive('paymentStatus')->once()->andThrow(new \RuntimeException('Timeout'));
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->postJson('/api/cybersource/checkout/status', ['checkout_id' => $this->checkoutId, 'payment_id' => '123'])
            ->assertOk()->assertJsonPath('payment_status', 'UNKNOWN')->assertJsonPath('paid', false);
        $this->authorize()->assertStatus(409);
        $this->assertArrayNotHasKey('payment_id', Cache::get('equity-checkout:'.$this->checkoutId));
    }

    public function test_hosted_checkout_requires_explicit_enable_setting(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.cybersource.checkout_enabled' => true]);
        $this->postJson('/api/cybersource/capture-context', [])->assertUnprocessable();
        $this->postJson('/api/cybersource/checkout/begin', [])->assertUnprocessable();
    }

    public function test_status_uses_verified_saved_id_and_checks_again_after_session_expiry(): void
    {
        $checkout = $this->checkout(['state' => 'submitted', 'expires_at' => time() - 1]);
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('paymentStatus')->once()->with('123', $checkout)
            ->andReturn(['paid' => false, 'verified' => true, 'payment_id' => '123', 'payment_status' => 'PENDING']);
        $service->shouldReceive('paymentStatus')->once()->with('123', Mockery::type('array'))
            ->andReturn(['paid' => true, 'verified' => true, 'payment_id' => '123', 'payment_status' => 'SETTLED']);
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->postJson('/api/cybersource/checkout/status', ['checkout_id' => $this->checkoutId, 'payment_id' => '123', 'paid' => true])
            ->assertOk()->assertJsonPath('paid', false);
        $this->postJson('/api/cybersource/checkout/status', ['checkout_id' => $this->checkoutId, 'payment_id' => '456'])
            ->assertOk()->assertJsonPath('paid', true);
    }

    public function test_old_authorization_client_is_rejected_without_processing_funds(): void
    {
        $this->postJson('/api/cybersource/authorize', [])->assertStatus(409);
    }

    public function test_short_and_legacy_urls_use_the_session_controller(): void
    {
        foreach ([
            '/api/cybersource/capture-context' => 'context',
            '/api/cybersource/authorize' => 'authorizeToken',
            '/api/cybersource/checkout/context' => 'context',
            '/api/cybersource/checkout/authorize' => 'authorizeToken',
            '/api/cybersource/checkout/begin' => 'begin',
            '/api/cybersource/checkout/status' => 'status',
        ] as $url => $action) {
            $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create($url, 'POST'));
            $this->assertSame(\App\Http\Controllers\EquityCheckoutController::class.'@'.$action, $route->getActionName());
            $this->assertContains('throttle:10,1', $route->gatherMiddleware());
        }
    }
    public function test_endpoints_are_unavailable_outside_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->postJson('/api/cybersource/capture-context', [])->assertNotFound();
        $this->authorize()->assertNotFound();
    }
}
