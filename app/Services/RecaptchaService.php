<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RecaptchaService
{
    public function isConfigured(): bool
    {
        return filled(config('services.recaptcha.site_key'))
            && filled(config('services.recaptcha.secret_key'));
    }

    public function verify(string $token, ?string $ipAddress = null): bool
    {
        if (!$this->isConfigured() || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(config('services.recaptcha.verify_url'), [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]);

            return $response->successful()
                && $response->json('success') === true;
        } catch (Throwable) {
            return false;
        }
    }
}
