<?php

namespace App\Http\Controllers;

use CyberSource\Api\UnifiedCheckoutCaptureContextApi;
use CyberSource\ApiClient;
use CyberSource\ApiException;
use CyberSource\Authentication\Core\MerchantConfiguration;
use CyberSource\Configuration;
use CyberSource\ObjectSerializer;
use CyberSource\Api\PaymentsApi;
use CyberSource\Model as CybsModel;
use CyberSource\Model\GenerateUnifiedCheckoutCaptureContextRequest;
use CyberSource\Model\Upv1capturecontextsCaptureMandate;
use CyberSource\Model\Upv1capturecontextsOrderInformation;
use CyberSource\Model\Upv1capturecontextsOrderInformationAmountDetails;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class CybersourceTestController extends Controller
{
    public function captureContext(Request $request)
    {
        // This temporary endpoint is only available from the local application.
        abort_unless(app()->environment('local'), 404);

        $settings = config('services.cybersource');

        abort_unless(
            ($settings['environment'] ?? '') === 'api.cybersource.com',
            400,
            'This endpoint requires the Cybersource production environment.'
        );

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        ]);

        foreach (['merchant_id', 'key_id', 'secret_key'] as $key) {
            abort_if(
                empty($settings[$key]),
                500,
                "Missing Cybersource setting: {$key}"
            );
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

        $client = new ApiClient($configuration, $merchant);
        $api = new UnifiedCheckoutCaptureContextApi($client);

        $amount = new Upv1capturecontextsOrderInformationAmountDetails([
            'totalAmount' => (string) $validated['amount'],
            'currency' => $validated['currency'],
        ]);

        $order = new Upv1capturecontextsOrderInformation([
            'amountDetails' => $amount,
        ]);

        $captureMandate = new Upv1capturecontextsCaptureMandate([
            'billingType' => 'FULL',
            'requestEmail' => true,
            'requestPhone' => false,
            'requestShipping' => false,
        ]);

        $payload = new GenerateUnifiedCheckoutCaptureContextRequest([
            'clientVersion' => '0.23',
            'targetOrigins' => ['https://localhost:3000'],
            'allowedCardNetworks' => ['VISA', 'MASTERCARD'],
            'allowedPaymentTypes' => ['PANENTRY'],
            'country' => 'TZ',
            'locale' => 'en_US',
            'captureMandate' => $captureMandate,
            'orderInformation' => $order,
        ]);

        try {
            [$captureContext, $statusCode] =
                $api->generateUnifiedCheckoutCaptureContext($payload);

            return response()->json([
                'success' => true,
                'upstream_status' => $statusCode,
                'capture_context' => $captureContext,
            ])->header('Cache-Control', 'no-store');
            } catch (ApiException $exception) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'upstream_status' => $exception->getCode(),
                    'details' => $exception->getResponseBody(),
                ], 502);
        }

        
    }

    public function authorizePayment(Request $request)
   {
    abort_unless(app()->environment('local'), 404);

    $settings = config('services.cybersource');

    abort_unless(
        ($settings['environment'] ?? '') === 'api.cybersource.com',
        400,
        'This endpoint requires the Cybersource production environment.'
    );

    $validated = $request->validate([
        'amount' => ['required', 'numeric', 'gt:0', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
        'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        'card' => ['required', 'array:number,expirationMonth,expirationYear,securityCode'],
        'card.number' => ['required', 'string', 'regex:/^[0-9]{13,19}$/'],
        'card.expirationMonth' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
        'card.expirationYear' => ['required', 'string', 'regex:/^[0-9]{4}$/'],
        'card.securityCode' => ['sometimes', 'required', 'string', 'regex:/^[0-9]{3,4}$/'],
        'billTo' => ['required', 'array:firstName,lastName,address1,address2,locality,administrativeArea,postalCode,country,email,phoneNumber'],
        'billTo.firstName' => ['required', 'string', 'max:60'],
        'billTo.lastName' => ['required', 'string', 'max:60'],
        'billTo.address1' => ['required', 'string', 'max:60'],
        'billTo.address2' => ['nullable', 'string', 'max:60'],
        'billTo.locality' => ['required', 'string', 'max:50'],
        'billTo.administrativeArea' => ['nullable', 'string', 'max:50'],
        'billTo.postalCode' => ['nullable', 'string', 'max:10'],
        'billTo.country' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
        'billTo.email' => ['required', 'string', 'email', 'max:254'],
        'billTo.phoneNumber' => ['nullable', 'string', 'max:15'],
    ]);

    foreach (['merchant_id', 'key_id', 'secret_key'] as $key) {
        abort_if(
            empty($settings[$key]),
            500,
            "Missing Cybersource setting: {$key}"
        );
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

    $client = new ApiClient($configuration, $merchant);
    $api = new PaymentsApi($client);

    $reference = 'TEST-' . Str::uuid();

    $payload = new CybsModel\CreatePaymentRequest([
        'clientReferenceInformation' =>
            new CybsModel\Ptsv2paymentsClientReferenceInformation([
                'code' => $reference,
            ]),

        'processingInformation' =>
            new CybsModel\Ptsv2paymentsProcessingInformation([
                'capture' => false,
            ]),

        // Card details supplied by the caller for production authorization.
        'paymentInformation' =>
            new CybsModel\Ptsv2paymentsPaymentInformation([
                'card' => new CybsModel\Ptsv2paymentsPaymentInformationCard($validated['card']),
            ]),

        'orderInformation' =>
            new CybsModel\Ptsv2paymentsOrderInformation([
                'amountDetails' =>
                    new CybsModel\Ptsv2paymentsOrderInformationAmountDetails([
                        'totalAmount' => (string) $validated['amount'],
                        'currency' => $validated['currency'],
                    ]),

                'billTo' =>
                    new CybsModel\Ptsv2paymentsOrderInformationBillTo($validated['billTo']),
            ]),
    ]);

    try {
        
        [$payment, $httpStatus] = $api->createPayment($payload);

        return response()->json([
            'success' => $payment->getStatus() === 'AUTHORIZED',
            'upstream_status' => $httpStatus,
            'reference' => $reference,
            'payment_id' => $payment->getId(),
            'payment_status' => $payment->getStatus(),
            'error_information' => ObjectSerializer::sanitizeForSerialization($payment->getErrorInformation()),
            'risk_information' => ObjectSerializer::sanitizeForSerialization($payment->getRiskInformation()),
            'processor_information' => ObjectSerializer::sanitizeForSerialization($payment->getProcessorInformation()),
        ])->header('Cache-Control', 'no-store');
    } catch (ApiException $exception) {
        return response()->json([
            'success' => false,
            'reference' => $reference,
            'message' => $exception->getMessage(),
            'upstream_status' => $exception->getCode(),
            'details' => $exception->getResponseBody(),
        ], 502);
    }
   }
}
