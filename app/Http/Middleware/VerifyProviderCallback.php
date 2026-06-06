<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyProviderCallback
{
    public function handle(Request $request, Closure $next, string $provider)
    {
        $enforced = (bool) config('services.callbacks.enforce_signatures', true);
        if (!$enforced) {
            return $next($request);
        }

        $providerConfig = config("services.callbacks.providers.$provider", []);
        $secret = $providerConfig['secret'] ?? null;
        $headerName = $providerConfig['header'] ?? 'X-Signature';

        if (!$secret) {
            return response()->json([
                'success' => false,
                'message' => "Callback signature secret is not configured for provider [$provider].",
            ], 500);
        }

        $signature = $request->header($headerName) ?: $request->header('X-Signature');
        if (!$signature) {
            return response()->json([
                'success' => false,
                'message' => 'Missing callback signature.',
            ], 401);
        }

        // Accept either raw hex or "sha256=<hex>" format.
        $signature = preg_replace('/^sha256=/i', '', trim($signature));
        $expected = hash_hmac('sha256', (string) $request->getContent(), (string) $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid callback signature.',
            ], 401);
        }

        return $next($request);
    }
}
