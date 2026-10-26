<?php

namespace Tests\Unit;

use App\Classes\Checksum;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChecksumTest extends TestCase
{
    public function test_missing_token_url_is_rejected_without_a_request(): void
    {
        config(['services.crdb.token_url' => null]);
        Http::fake();

        try {
            (new Checksum())->generateToken();
            $this->fail('Missing configuration must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('CRDB_TOKEN_URL', $exception->errors()['crdb'][0]);
        }

        Http::assertNothingSent();
    }

    public function test_successful_authentication_returns_the_token(): void
    {
        $this->configureAuthentication();
        Http::fake(['https://crdb.example.test/token' => Http::response(['access_token' => 'test-token'])]);

        $this->assertSame('test-token', (new Checksum())->generateToken());
    }

    public function test_response_without_a_token_is_rejected(): void
    {
        $this->configureAuthentication();
        Http::fake(['https://crdb.example.test/token' => Http::response([])]);
        $this->expectException(ValidationException::class);

        (new Checksum())->generateToken();
    }

    private function configureAuthentication(): void
    {
        config([
            'services.crdb.token_url' => 'https://crdb.example.test/token',
            'services.crdb.consumer_key' => 'test-key',
            'services.crdb.consumer_secret' => 'test-secret',
        ]);
    }
}
