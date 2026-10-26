<?php

namespace App\Services;

use CyberSource\Api\PaymentsApi;
use CyberSource\Api\UnifiedCheckoutCaptureContextApi;
use CyberSource\ApiClient;
use CyberSource\Authentication\Core\MerchantConfiguration;
use CyberSource\Configuration;
use CyberSource\Model as Model;
use CyberSource\ObjectSerializer;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class EquityCheckoutService
{
    private function client(): ApiClient
    {
        $settings = config('services.cybersource');
        abort_unless(($settings['environment'] ?? '') === 'api.cybersource.com', 400,
            'Equity Checkout requires the configured Cybersource production environment.');
        foreach (['merchant_id', 'key_id', 'secret_key'] as $key) {
            abort_if(empty($settings[$key]), 503, "Missing Cybersource setting: {$key}");
        }
        $merchant = new MerchantConfiguration();
        $merchant->setAuthenticationType('JWT');
        $merchant->setJwtKeyType('SHARED_SECRET');
        $merchant->setMerchantID($settings['merchant_id']);
        $merchant->setApiKeyID($settings['key_id']);
        $merchant->setSecretKey($settings['secret_key']);
        $merchant->setRunEnvironment($settings['environment']);
        $configuration = new Configuration();
        $configuration->setHost($settings['environment']);

        return new ApiClient($configuration, $merchant);
    }

    public function captureContext(string $amount, string $currency, string $reference): array
    {
        $payload = new Model\GenerateUnifiedCheckoutCaptureContextRequest([
            // Keep the Accept SDK flow used by the existing integration.
            'clientVersion' => '0.23',
            'targetOrigins' => [config('services.cybersource.checkout_origin')],
            'allowedCardNetworks' => ['VISA', 'MASTERCARD'],
            'allowedPaymentTypes' => ['PANENTRY'],
            'country' => 'TZ',
            'locale' => 'en_US',
            'captureMandate' => new Model\Upv1capturecontextsCaptureMandate([
                'billingType' => 'FULL', 'requestEmail' => true,
                'requestPhone' => false, 'requestShipping' => false,
            ]),
            'orderInformation' => new Model\Upv1capturecontextsOrderInformation([
                'amountDetails' => new Model\Upv1capturecontextsOrderInformationAmountDetails([
                    'totalAmount' => $amount, 'currency' => $currency,
                ]),
            ]),
        ]);
        [$context] = (new UnifiedCheckoutCaptureContextApi($this->client()))
            ->generateUnifiedCheckoutCaptureContext($payload);

        // Verify before trusting the script URL or the embedded transient-token key.
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $context)[0]));
        if (($header->alg ?? '') !== 'RS256' || empty($header->kid)) {
            throw new UnexpectedValueException('Unexpected capture-context signature.');
        }
        $jwk = Http::acceptJson()->connectTimeout(5)->timeout(15)
            ->get('https://api.cybersource.com/flex/v2/public-keys/'.rawurlencode($header->kid))
            ->throw()->json();
        $claims = JWT::decode($context, JWK::parseKey($jwk, 'RS256'));
        if (($claims->iss ?? '') !== 'Flex API' || ($claims->exp ?? 0) <= time()) {
            throw new UnexpectedValueException('Invalid capture-context claims.');
        }
        $tokenKey = json_decode(json_encode($claims->flx->jwk ?? null), true);
        $data = $claims->ctx[0]->data ?? null;
        if (!is_array($tokenKey) || empty($data->clientLibrary) || empty($data->clientLibraryIntegrity)) {
            throw new UnexpectedValueException('Incomplete capture context.');
        }

        return [
            'capture_context' => $context,
            'token_key' => $tokenKey,
            'expires_at' => (int) $claims->exp,
            'client_library' => $data->clientLibrary,
            'client_library_integrity' => $data->clientLibraryIntegrity,
        ];
    }

    public function verifyToken(string $token, array $key): void
    {
        // Decode verifies the signature, expiry and not-before/issued-at claims.
        $claims = JWT::decode($token, JWK::parseKey($key, 'RS256'));
        if (!str_starts_with($claims->iss ?? '', 'Flex/') || empty($claims->jti)
            || ($claims->exp ?? 0) <= time()) {
            throw new UnexpectedValueException('Invalid transient-token claims.');
        }
    }

    public function authorize(string $token, array $checkout): array
    {
        $payload = new Model\CreatePaymentRequest([
            'clientReferenceInformation' => new Model\Ptsv2paymentsClientReferenceInformation([
                'code' => $checkout['reference'],
            ]),
            'processingInformation' => new Model\Ptsv2paymentsProcessingInformation([
                'capture' => false, 'commerceIndicator' => 'internet',
            ]),
            'tokenInformation' => new Model\Ptsv2paymentsTokenInformation([
                'transientTokenJwt' => $token,
            ]),
            'orderInformation' => new Model\Ptsv2paymentsOrderInformation([
                'amountDetails' => new Model\Ptsv2paymentsOrderInformationAmountDetails([
                    // Use the amount saved when checkout began, never a second client amount.
                    'totalAmount' => $checkout['amount'], 'currency' => $checkout['currency'],
                ]),
            ]),
        ]);
        [$payment, $status] = (new PaymentsApi($this->client()))->createPayment($payload);
        $paymentStatus = $payment->getStatus();

        return [
            'success' => $paymentStatus === 'AUTHORIZED',
            'upstream_status' => $status,
            'reference' => $checkout['reference'],
            'amount' => $checkout['amount'],
            'currency' => $checkout['currency'],
            'payment_id' => $payment->getId(),
            'payment_status' => $paymentStatus,
            'authorization_hold_possible' => in_array($paymentStatus,
                ['AUTHORIZED', 'PARTIAL_AUTHORIZED', 'AUTHORIZED_RISK_DECLINED', 'AUTHORIZED_PENDING_REVIEW'], true),
            'error_information' => ObjectSerializer::sanitizeForSerialization($payment->getErrorInformation()),
        ];
    }
}
