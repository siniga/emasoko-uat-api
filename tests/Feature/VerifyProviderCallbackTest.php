<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class VerifyProviderCallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/test-callback', fn () => response()->json(['ok' => true]))
            ->middleware(['api', 'verify.callback:crdb']);
    }

    public function test_rejects_missing_signature_header(): void
    {
        config()->set('services.callbacks.enforce_signatures', true);
        config()->set('services.callbacks.providers.crdb.secret', 'test-secret');
        config()->set('services.callbacks.providers.crdb.header', 'X-Signature');

        $response = $this->postJson('/test-callback', ['status' => 'ok']);

        $response->assertStatus(401);
    }

    public function test_rejects_invalid_signature(): void
    {
        config()->set('services.callbacks.enforce_signatures', true);
        config()->set('services.callbacks.providers.crdb.secret', 'test-secret');
        config()->set('services.callbacks.providers.crdb.header', 'X-Signature');

        $response = $this->withHeaders(['X-Signature' => 'invalid-signature'])
            ->postJson('/test-callback', ['status' => 'ok']);

        $response->assertStatus(401);
    }

    public function test_accepts_valid_signature(): void
    {
        config()->set('services.callbacks.enforce_signatures', true);
        config()->set('services.callbacks.providers.crdb.secret', 'test-secret');
        config()->set('services.callbacks.providers.crdb.header', 'X-Signature');

        $payload = json_encode(['status' => 'ok']);
        $signature = hash_hmac('sha256', $payload, 'test-secret');

        $response = $this->call(
            'POST',
            '/test-callback',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertOk()
            ->assertJson(['ok' => true]);
    }
}
