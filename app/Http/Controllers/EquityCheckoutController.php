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
        abort_unless(app()->environment('local') || config('services.cybersource.checkout_enabled') === true, 404);
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
            'expires_at' => $expiresAt, 'state' => 'ready', 'flow' => 'sale-v1',
        ], now()->addDays(30));

        return response()->json([
            'success' => true, 'checkout_id' => $id, 'reference' => $reference,
            'amount' => $amount, 'currency' => $validated['currency'],
            'capture_context' => $context['capture_context'],
            'client_library' => $context['client_library'],
            'client_library_integrity' => $context['client_library_integrity'],
            'expires_at' => $expiresAt, 'target_origin' => $origin,
            'environment' => config('services.cybersource.environment') === 'api.cybersource.com' ? 'production' : 'test',
            'flow' => 'sale-v1',
        ])->header('Cache-Control', 'no-store');
    }

    public function authorizeToken(Request $request, EquityCheckoutService $service)
    {
        // Old clients must refresh rather than accidentally process an unauthenticated sale.
        return response()->json(['success' => false, 'message' => 'Checkout has been upgraded. Reload the checkout page.'], 409);
    }

    public function begin(Request $request, EquityCheckoutService $service)
    {
        abort_unless(app()->environment('local') || config('services.cybersource.checkout_enabled') === true, 404);
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
            abort_unless(($checkout['flow'] ?? '') === 'sale-v1', 409, 'Prepare a new checkout.');
            abort_unless($checkout['state'] === 'ready', 409,
                'The payment outcome is unconfirmed. Check Business Center before making another payment.');
            abort_if($checkout['expires_at'] <= time(), 410, 'Checkout expired. Prepare a new checkout.');
            try {
                $service->verifyToken($validated['transient_token'], $checkout['token_key']);
            } catch (Throwable $exception) {
                return response()->json(['success' => false,
                    'message' => 'The payment token is invalid or expired. Prepare a new checkout.'], 422);
            }
            // Only the browser's complete() call processes funds. This endpoint never charges.
            // A second begin is rejected, including after a timeout or lost response.
            $checkout['state'] = 'submitted';
            Cache::put($key, $checkout, now()->addDays(30));
            return response()->json(['proceed' => true])->header('Cache-Control', 'no-store');
        } finally {
            $lock->release();
        }
    }

    public function status(Request $request, EquityCheckoutService $service)
    {
        abort_unless(app()->environment('local') || config('services.cybersource.checkout_enabled') === true, 404);
        $validated = $request->validate([
            'checkout_id' => ['required', 'uuid'],
            'payment_id' => ['nullable', 'string', 'regex:/^[0-9]{1,40}$/'],
        ]);
        $key = 'equity-checkout:'.$validated['checkout_id'];
        $lock = Cache::lock($key.':lock', 120);
        abort_unless($lock->get(), 409, 'Payment status is being checked.');
        try {
            $checkout = Cache::get($key);
            abort_unless(is_array($checkout), 410, 'Checkout record unavailable. Check Business Center using your reference.');
            abort_unless(($checkout['flow'] ?? '') === 'sale-v1' && $checkout['state'] === 'submitted', 409, 'Payment has not been submitted.');
            $paymentId = $checkout['payment_id'] ?? $validated['payment_id'] ?? null;
            $unknown = ['success' => false, 'paid' => false, 'verified' => false,
                'payment_status' => 'UNKNOWN', 'reference' => $checkout['reference'],
                'message' => 'Payment outcome unconfirmed. Check Business Center before paying again.'];
            if (!$paymentId) {
                return response()->json($unknown)->header('Cache-Control', 'no-store');
            }
            try {
                $result = $service->paymentStatus($paymentId, $checkout);
                // Persist only IDs whose merchant, reference, currency and amount were verified.
                $checkout['payment_id'] = $paymentId;
                $checkout['result'] = $result;
                Cache::put($key, $checkout, now()->addDays(30));
            } catch (Throwable $exception) {
                // Reporting can lag payment processing. Status checks never retry a charge.
                $result = $unknown;
            }
            return response()->json($result)->header('Cache-Control', 'no-store');
        } finally {
            $lock->release();
        }
    }
}
