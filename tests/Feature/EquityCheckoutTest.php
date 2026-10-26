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
        config(['cache.default' => 'array', 'services.cybersource.checkout_origin' => 'https://localhost:3000']);
    }

    private function checkout(array $overrides = []): array
    {
        $checkout = array_merge([
            'reference' => 'EQUITY-REFERENCE', 'amount' => '1000.00', 'currency' => 'TZS',
            'token_key' => ['kty' => 'RSA'], 'expires_at' => time() + 900, 'state' => 'ready',
        ], $overrides);
        Cache::put('equity-checkout:'.$this->checkoutId, $checkout, 3600);

        return $checkout;
    }

    private function authorize(array $extra = [])
    {
        return $this->postJson('/api/cybersource/checkout/authorize', array_merge([
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
        $response = $this->postJson('/api/cybersource/checkout/context', ['amount' => '1000', 'currency' => 'TZS'])
            ->assertOk()->assertJsonPath('amount', '1000.00')->assertJsonMissingPath('token_key');
        $saved = Cache::get('equity-checkout:'.$response->json('checkout_id'));
        $this->assertSame('1000.00', $saved['amount']);
        $this->assertSame('ready', $saved['state']);
    }

    public function test_authorization_uses_saved_amount_and_duplicate_requests_return_same_result(): void
    {
        $checkout = $this->checkout();
        $result = ['success' => false, 'payment_status' => 'AUTHORIZED_RISK_DECLINED',
            'payment_id' => 'payment-1', 'authorization_hold_possible' => true];
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('verifyToken')->once()->with('header.payload.signature', $checkout['token_key']);
        $service->shouldReceive('authorize')->once()->with('header.payload.signature', array_merge($checkout, ['state' => 'submitted']))->andReturn($result);
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize(['amount' => '0.01', 'currency' => 'USD'])->assertOk()
            ->assertJsonPath('success', false)->assertJsonPath('authorization_hold_possible', true);
        $this->authorize()->assertOk()->assertJsonPath('payment_id', 'payment-1');
    }

    public function test_invalid_token_is_rejected_before_upstream_payment(): void
    {
        $this->checkout();
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('verifyToken')->once()->andThrow(new \UnexpectedValueException('Invalid signature'));
        $service->shouldNotReceive('authorize');
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize()->assertUnprocessable();
        $this->assertSame('ready', Cache::get('equity-checkout:'.$this->checkoutId)['state']);
    }

    public function test_expired_checkout_cannot_authorize(): void
    {
        $this->checkout(['expires_at' => time() - 1]);
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldNotReceive('verifyToken');
        $service->shouldNotReceive('authorize');
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize()->assertStatus(410);
    }

    public function test_network_failure_is_not_retried(): void
    {
        $this->checkout();
        $service = Mockery::mock(EquityCheckoutService::class);
        $service->shouldReceive('verifyToken')->once();
        $service->shouldReceive('authorize')->once()->andThrow(new \RuntimeException('Timeout'));
        $this->app->instance(EquityCheckoutService::class, $service);
        $this->authorize()->assertOk()->assertJsonPath('payment_status', 'UNKNOWN');
        $this->authorize()->assertOk()->assertJsonPath('payment_status', 'UNKNOWN');
    }

    public function test_endpoints_are_unavailable_outside_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->postJson('/api/cybersource/checkout/context', [])->assertNotFound();
        $this->authorize()->assertNotFound();
    }
}
