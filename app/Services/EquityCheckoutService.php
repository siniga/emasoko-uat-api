<?php

namespace App\Services;

use CyberSource\Api\TransactionDetailsApi;
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
        abort_unless(in_array($settings['environment'] ?? '', ['api.cybersource.com', 'apitest.cybersource.com'], true), 400,
            'Unsupported Cybersource environment.');
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
        $payload = $this->captureContextRequest($amount, $currency, $reference);
        [$context] = (new UnifiedCheckoutCaptureContextApi($this->client()))
            ->generateUnifiedCheckoutCaptureContext($payload);

        // Verify before trusting the script URL or the embedded transient-token key.
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $context)[0]));
        if (($header->alg ?? '') !== 'RS256' || empty($header->kid)) {
            throw new UnexpectedValueException('Unexpected capture-context signature.');
        }
        $host = config('services.cybersource.environment');
        $jwk = Http::acceptJson()->connectTimeout(5)->timeout(15)
            ->get('https://'.$host.'/flex/v2/public-keys/'.rawurlencode($header->kid))
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

    public function captureContextRequest(string $amount, string $currency, string $reference): Model\GenerateUnifiedCheckoutCaptureContextRequest
    {
        return new Model\GenerateUnifiedCheckoutCaptureContextRequest([
            'clientVersion' => '0.28',
            'targetOrigins' => [config('services.cybersource.checkout_origin')],
            'allowedCardNetworks' => ['VISA', 'MASTERCARD'],
            'allowedPaymentTypes' => ['PANENTRY'],
            'country' => 'TZ',
            'locale' => 'en_US',
            'completeMandate' => new Model\Upv1capturecontextsCompleteMandate([
                'type' => 'CAPTURE', 'consumerAuthentication' => true,
            ]),
            'data' => new Model\Upv1capturecontextsData([
                // The API rejects data combined with legacy top-level orderInformation.
                'orderInformation' => new Model\Upv1capturecontextsDataOrderInformation([
                    'amountDetails' => new Model\Upv1capturecontextsDataOrderInformationAmountDetails([
                        'totalAmount' => $amount, 'currency' => $currency,
                    ]),
                ]),
                'clientReferenceInformation' => new Model\Upv1capturecontextsDataClientReferenceInformation([
                    'code' => $reference,
                ]),
            ]),
            'captureMandate' => new Model\Upv1capturecontextsCaptureMandate([
                'billingType' => 'FULL', 'requestEmail' => true,
                'requestPhone' => false, 'requestShipping' => false,
            ]),
        ]);
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

    public function paymentStatus(string $paymentId, array $checkout): array
    {
        [$payment] = (new TransactionDetailsApi($this->client()))->getTransaction($paymentId);
        $data = json_decode(json_encode(ObjectSerializer::sanitizeForSerialization($payment)), true);
        return $this->verifiedResult($paymentId, $data, $checkout);
    }

    public function verifiedResult(string $paymentId, array $data, array $checkout): array
    {
        $amount = $data['orderInformation']['amountDetails'] ?? [];
        if (($data['id'] ?? '') !== $paymentId
            || ($data['merchantId'] ?? '') !== config('services.cybersource.merchant_id')
            || ($data['clientReferenceInformation']['code'] ?? '') !== $checkout['reference']
            || ($amount['currency'] ?? '') !== $checkout['currency']
            || !isset($amount['totalAmount']) || !is_numeric($amount['totalAmount'])
            || number_format((float) $amount['totalAmount'], 2, '.', '') !== $checkout['amount']) {
            throw new UnexpectedValueException('Transaction does not match checkout.');
        }
        $status = strtoupper($data['applicationInformation']['status'] ?? $data['status'] ?? 'UNKNOWN');
        // Authorization or a successful HTTP response is never evidence of settlement.
        $paid = $status === 'SETTLED';

        return [
            'success' => $paid,
            'verified' => true,
            'paid' => $paid,
            'reference' => $checkout['reference'],
            'amount' => $checkout['amount'],
            'currency' => $checkout['currency'],
            'payment_id' => $paymentId,
            'payment_status' => $status,
            'message' => $paid ? 'Payment settled.' : 'Payment status verified. Pending or authorized payments are not yet confirmed as collected.',
            'error_information' => $data['errorInformation'] ?? null,
        ];
    }
}
