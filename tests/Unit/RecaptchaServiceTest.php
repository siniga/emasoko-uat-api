<?php

namespace Tests\Unit;

use App\Services\RecaptchaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaServiceTest extends TestCase
{
    public function test_it_verifies_token_with_google_siteverify_endpoint(): void
    {
        config()->set('services.recaptcha.site_key', 'site-key');
        config()->set('services.recaptcha.secret_key', 'secret-key');
        config()->set('services.recaptcha.verify_url', 'https://www.google.com/recaptcha/api/siteverify');

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
            ]),
        ]);

        $verified = app(RecaptchaService::class)->verify('response-token', '127.0.0.1');

        $this->assertTrue($verified);
        Http::assertSent(fn ($request) => $request['secret'] === 'secret-key'
            && $request['response'] === 'response-token'
            && $request['remoteip'] === '127.0.0.1');
    }

    public function test_it_fails_closed_when_keys_are_not_configured(): void
    {
        config()->set('services.recaptcha.site_key', null);
        config()->set('services.recaptcha.secret_key', null);
        Http::fake();

        $this->assertFalse(app(RecaptchaService::class)->verify('response-token'));
        Http::assertNothingSent();
    }
}
