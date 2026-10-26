<?php

namespace App\Http\Controllers;

use App\Services\EquityCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class EquityCheckoutController extends Controller
{
    public function context(Request $request, EquityCheckoutService $service)
    {
        abort_unless(app()->environment('local'), 404);
        $validated = $request->validate([
            'amount' => ['required', 'string', 'regex:/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/', 'numeric', 'gt:0'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        ]);
        $origin = config('services.cybersource.checkout_origin');
        abort_unless(is_string($origin) && preg_match('#^https://[^/]+$#', $origin), 503,
            'Set CYBERSOURCE_CHECKOUT_ORIGIN to the HTTPS origin of the checkout page, without a path.');
        $id = (string) Str::uuid();
        $reference = 'EQUITY-'.Str::uuid();
        $amount = number_format((float) $validated['amount'], 2, '.', '');

        try {
            $context = $service->captureContext($amount, $validated['currency'], $reference);
        } catch (Throwable $exception) {
            // Do not expose upstream request headers, credentials or JWTs in errors.
            return response()->json([
                'success' => false,
                'message' => 'Could not prepare checkout. Check merchant credentials, Unified Checkout enablement and connectivity.',
            ], 502)->header('Cache-Control', 'no-store');
        }
        $expiresAt = min($context['expires_at'], time() + 900);
        Cache::put('equity-checkout:'.$id, [
            'reference' => $reference, 'amount' => $amount,
            'currency' => $validated['currency'], 'token_key' => $context['token_key'],
            'expires_at' => $expiresAt, 'state' => 'ready',
        ], now()->addDay());

        return response()->json([
            'success' => true, 'checkout_id' => $id, 'reference' => $reference,
            'amount' => $amount, 'currency' => $validated['currency'],
            'capture_context' => $context['capture_context'],
            'client_library' => $context['client_library'],
            'client_library_integrity' => $context['client_library_integrity'],
            'expires_at' => $expiresAt, 'target_origin' => $origin,
            'environment' => 'production',
        ])->header('Cache-Control', 'no-store');
    }

    public function authorizeToken(Request $request, EquityCheckoutService $service)
    {
        abort_unless(app()->environment('local'), 404);
        $validated = $request->validate([
            'checkout_id' => ['required', 'uuid'],
            'transient_token' => ['required', 'string', 'max:30000', 'regex:/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/'],
        ]);
        $key = 'equity-checkout:'.$validated['checkout_id'];
        $lock = Cache::lock($key.':lock', 120);
        abort_unless($lock->get(), 409, 'This payment is already being processed. Do not submit again.');
        try {
            $checkout = Cache::get($key);
            abort_unless(is_array($checkout), 410, 'Checkout expired. Prepare a new checkout.');
            if (isset($checkout['result'])) {
                return response()->json($checkout['result'])->header('Cache-Control', 'no-store');
            }
            abort_unless($checkout['state'] === 'ready', 409,
                'The payment outcome is unconfirmed. Check Business Center before making another payment.');
            abort_if($checkout['expires_at'] <= time(), 410, 'Checkout expired. Prepare a new checkout.');
            try {
                $service->verifyToken($validated['transient_token'], $checkout['token_key']);
            } catch (Throwable $exception) {
                return response()->json(['success' => false,
                    'message' => 'The payment token is invalid or expired. Prepare a new checkout.'], 422);
            }
            // Persist before calling upstream: timeouts must not trigger another authorization.
            $checkout['state'] = 'submitted';
            Cache::put($key, $checkout, now()->addDay());
            try {
                $result = $service->authorize($validated['transient_token'], $checkout);
            } catch (Throwable $exception) {
                $result = [
                    'success' => false, 'payment_status' => 'UNKNOWN',
                    'reference' => $checkout['reference'],
                    'message' => 'The payment outcome is unconfirmed. Check this reference in Business Center before paying again.',
                ];
            }
            $checkout['result'] = $result;
            $checkout['state'] = 'finished';
            Cache::put($key, $checkout, now()->addDay());

            return response()->json($result)->header('Cache-Control', 'no-store');
        } finally {
            $lock->release();
        }
    }
}
