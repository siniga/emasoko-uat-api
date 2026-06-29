<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Classes\BearerToken;
use App\Classes\Checksum;
use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Carbon\Carbon;
use Exception;

class BankApiService
{
    private $auth;
    private $tigoAuth;
    private $crdbAuth;
    private $deductionService;

    public function __construct()
    {
        $this->auth = new Authentication();
        $this->deductionService = new DeductionService();
    }

    //corporate registration
    public function corporateRegistrationRequest($data, ?PartnerAccount $partnerAccount = null)
    {
        //declare code
        $http_response_code = 200;

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("Token : {$AUTH_BEARER_TOKEN}");

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateRegistration",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== CorporateRegistration Request == ');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the corporate
        $partner = Partner::where('reference', '=', $payload['data']['corporateReference'])->first();

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CorporateRegistration Response ==');
        Log::debug($response);

        if ($response) {
            if (strtolower($response['status']) == "success") {
                $http_response_code = 201; //created
                $remarks = $response['data']['response'][0]['responseDescription'];
            } else if (strtolower($response['status']) == "fail") {
                $http_response_code = 404; //failed
                $remarks = $response['data']['response'][0]['responseDescription'];
            }

            //updated partner account
            $partner_account = $partnerAccount
                ?? PartnerAccount::where(['partner_id' => $partner->id, 'channel' => "NMB"])->firstOrFail();
            $partner_account->remarks = $remarks;
            if ($http_response_code === 201) {
                $partner_account->send_verification = 2;
                $partner_account->rejected_at = null;
            } else {
                $partner_account->send_verification = 0;
                $partner_account->rejected_at = Carbon::now();
            }
            $partner_account->save();

            $partner->refreshLifecycleStatus()->save();

            return [$http_response_code, $remarks];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from NMB endpoint.'];
        }
    }

    //corporate status
    public function corporateStatusRequest($data)
    {
        //responce code
        $is_verified = 0;
        $http_status_code = 200;
        $status = 'pending_approval';
        $remarks = "";

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== Corporate Status Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the corporate
        $partner = Partner::where('reference', '=', $payload['data']['corporateReference'])->first();

        //NMB partner account
        $nmb_partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'channel' => "NMB"])->first();

        //Decode the response 
        $response = json_decode($response, true);

        //Logging response
        Log::debug('== CorporateStatus Response  ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $response_code = $response['data']['response'][0]['responseCode'];
            $remarks = $response['data']['response'][0]['responseDescription'];

            // check for response code
            if ($response_code == 240) {
                $is_verified = 1;
                $status = 'active';
            } else if ($response_code == 249) {
                $status = 'not_active';
            } else {
                $http_status_code = 500;
                $status = 'not_active';
            }
        } else if (strtolower($response['status']) == "fail") {
            $http_status_code = 500;
            $remarks = $response['data']['response'][0]['responseDescription'];
        }

        // update partner
        $partner->status = $status;
        $partner->http_response_code = $response['data']['response'][0]['responseCode'];
        $partner->remarks = $response['data']['response'][0]['responseDescription'];
        $partner->save();

        //update partner account
        $nmb_partner_account->is_verified = $is_verified;
        $nmb_partner_account->verified_at = $is_verified ? Carbon::now() : null;
        $nmb_partner_account->rejected_at = $http_status_code === 500 ? Carbon::now() : null;
        $nmb_partner_account->send_verification = $is_verified ? 0 : 2;
        $nmb_partner_account->remarks = $remarks;
        $nmb_partner_account->save();

        $partner->refreshLifecycleStatus()->save();

        return [$http_status_code, $remarks];
    }

    //mandate registartion
    public function mandateRegistrationRequest($data)
    {
        //responce code
        $http_status_code = 200;
        $remarks = "";

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN
        ];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateRegistration",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== MandateRegistration Request == ');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update debit response
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        $debit_order->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateRegistration Response ==');
        Log::debug($response);

        // Check if response is null or not
        if ($response) {
            // Separate status code
            if (strtolower($response['status']) == "success") {
                $http_status_code = 201;
                $remarks = $response['data']['response'][0]['responseDescription'];

                $debit_order->status = 201; //created
                $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_AWAITING_OTP;
                $debit_order->otp_reference = $response['data']['otpCaseReference'];
            } else if (strtolower($response['status']) == "fail") {
                $http_status_code = 500;
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update debit order status
                $debit_order->status = 500;
                $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
            }

            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();

            return [$http_status_code, $remarks];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from NMB endpoint.'];
        }
    }

    //crdb mandate registration
    public function crdbMandateRegistrationRequest($data)
    {
        //responce code
        $http_status_code = 200;
        $remarks = "";

        //update the debit order
        $debitOrder = DebitOrder::where('reference', '=', $data['mandateReferenceNumber'])->first();
        if (!$debitOrder) {
            Log::warning('Debit order not found for mandateReferenceNumber', [
                'mandateReferenceNumber' => $this->data['mandateReferenceNumber'] ?? null
            ]);
            // Bail early; nothing to update
            return [500, 'Debit order not found for mandateReferenceNumber.'];
        }

        //authentication
        $this->crdbAuth = new Checksum();

        //token
        $bearerToken = $this->crdbAuth->generateToken();
        Log::debug("== CRDB Bearer Token ==");
        Log::debug($bearerToken);

        //headers
        $headers = [
            'Channel' => config('services.crdb.channel_id'),
            'AppId' => config('services.crdb.app_id'),
            'AppVersion' => '1.0.0',
            "RequestId" => $data['requestId'],
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        //Payload
        Log::debug("== CRDB Payload ==");
        Log::debug($data['payload']);

        //API URL
        $API_URL = config('services.crdb.api_url');

        //push payload => NMB Direct Debits
        $httpResponse = Http::withHeaders($headers)->post($API_URL, $data['payload']);

        Log::debug('== Raw Response Body ==');
        Log::debug($httpResponse->body());

        //http response
        $raw = $httpResponse->body();

        // strip UTF-8 BOM + trim
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw ?? ''));

        // remove trailing commas before } or ] (JSON5-style but not valid JSON)
        $sanitized = preg_replace('/,\s*(\}|\])/m', '$1', $raw);

        // now decode with error reporting
        $response = json_decode($sanitized, true);

        if (!is_array($response)) {
            $debitOrder->status = 403;
            $debitOrder->http_response_code = $httpResponse->status();
            $debitOrder->remarks = 'HTTP error or invalid JSON from gateway';
            $debitOrder->save();

            Log::error('MandateRegistration failed: HTTP error or invalid JSON', [
                'json_error' => json_last_error_msg(),
                'sample'     => Str::limit($raw, 800),
            ]);
            return [500, 'MandateRegistration failed: HTTP error or invalid JSON'];
        }

        // safely read values
        $statusCode = data_get($response, 'ResponseHeader.StatusCode');
        $statusDesc = data_get($response, 'ResponseHeader.StatusDesc');
        $responseId = data_get($response, 'ResponseHeader.RequestId');
        $reference  = data_get($response, 'ResponseBody.Reference');
        Log::debug('Parsed header', compact('statusCode', 'statusDesc', 'reference'));

        // Handle business logic only if StatusCode is present
        if ($statusCode === 1000) {
            $debitOrder->status = 201;  // success
            if ($reference) {
                $debitOrder->request_id = $responseId;
                $debitOrder->crdb_reference = $reference;
                $debitOrder->remarks = preg_replace('/[^A-Za-z0-9\-]/', '', $statusDesc) ?: 'No StatusDesc in response';
            }

            //update http_status_code and remarks
            $http_status_code = 201;
            $remarks = preg_replace('/[^A-Za-z0-9\-]/', '', $statusDesc) ?: 'No StatusDesc in response';
        } elseif ($statusCode === 1001) {
            // get error metadata
            $errorMetadata = data_get($response, 'ResponseHeader.ErrorMetadata', []);

            //set update params
            $debitOrder->status = 403;
            $debitOrder->remarks = $errorMetadata ?: 'No StatusDesc in response';

            //update http_status_code and remarks
            $http_status_code = 500;
            $remarks = $errorMetadata ?: 'No StatusDesc in response';
        } else {
            // Unknown/other code: record it for investigation
            Log::warning('MandateRegistration returned unexpected StatusCode', [
                'StatusCode' => $statusCode,
                'response'    => $response,
            ]);

            //update http_status_code and remarks
            $http_status_code = 500;
            $remarks = 'MandateRegistration returned unexpected StatusCode';
        }

        // Persist shared fields (guard nulls)
        $debitOrder->http_response_code = $statusCode ?? $httpResponse->status();
        $debitOrder->save();

        return [$http_status_code, $remarks];
    }

    //yas mandate registration
    public function yasMandateRegistrationRequest($data, $debitOrder)
    {
        //responce code
        $response_code = 200;
        $redirect_url = "";
        $remarks = "";

        //request id
        $requestId = Str::uuid()->toString();

        //authentication
        $this->tigoAuth = new BearerToken($debitOrder);

        //token
        $bearerToken = $this->tigoAuth->generate();
        Log::debug("== Bearer Token ==");
        Log::debug($bearerToken);

        //headers
        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Idempotency-Key' => $requestId
        ];

        //API URL
        $API_URL = config('services.jumena.payment_session_url');

        //push payload => JUMENA Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $data);

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== YAS MandateRegistration Response ==');
        Log::debug($response);

        if ($response) {
            // Separate status code
            if (strtoupper($response['status']) == "PENDING") {
                $response_code = 201;
                $remarks = "Mandate created";
                $redirect_url = $response['redirect_url'];

                $debitOrder->status = 201;
                $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_AWAITING_OTP;
                $debitOrder->request_id = $response['session_id'];
                $debitOrder->remarks = 'Payment session created';
                $debitOrder->save();
            } else {
                $response_code = 500;
                $remarks = 'Unexpected status: ' . $response['status'];

                $debitOrder->status = 500;
                $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
                $debitOrder->remarks = $remarks;
                $debitOrder->save();
            }

            return [$response_code, $remarks, $redirect_url];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from Jumena endpoint.'];
        }
    }

    //mandate cancellation 
    public function mandateStatusRequest($data)
    {
        //responce code
        $response_code = 200;
        $remarks = "";

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Mandate Status Request');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the debit order
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        //$debit_order->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateStatus Response  ==');
        Log::debug($response);

        // Check if response is null or not
        if ($response) {
            // Separate status code
            if (strtolower($response['status']) == "success") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update status
                $debit_order->status = $response['data']['response'][0]['responseCode'];

                //update debit order => approved
                if ($response_code == 240) {
                    $debit_order->approved = 1;
                    $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
                } else {
                    $debit_order->approved = 0;
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update status and status
                $debit_order->status = 500;
                $debit_order->approved = 0;
                $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
            }

            $debit_order->http_response_code = $response_code;
            $debit_order->remarks = $remarks;
            $debit_order->save();

            return [$response_code, $remarks];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from NMB endpoint.'];
        }
    }

    //mandate cancellation 
    public function mandateCancellationRequest($data)
    {
        //responce code
        $response_code = 200;
        $remarks = "";

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateCancellation",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== Mandate Cancellation Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the debit order
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();

        // //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateCancellation Response  ==');
        Log::debug($response);

        // Check if response is null or not
        if ($response) {
            // Separate status code
            if (strtolower($response['status']) == "success") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update debit order => approved
                if ($response_code == 201) {
                    $debit_order->cancelled = 1; // Mark as cancelled
                    $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];
            }

            $debit_order->remarks = $remarks;
            $debit_order->save();

            return [$response_code, $remarks];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from NMB endpoint.'];
        }
    }

    public function crdbMandateCancellationRequest(array $data): array
    {
        $debitOrder = DebitOrder::where('reference', '=', $data['mandateReferenceNumber'])->first();

        if (!$debitOrder) {
            Log::warning('Debit order not found for CRDB mandate cancellation', [
                'mandateReferenceNumber' => $data['mandateReferenceNumber'] ?? null,
            ]);

            return [404, 'Mandate not found.', null];
        }

        $authBearerToken = $this->crdbAuth?->generateToken() ?? (new Checksum())->generateToken();

        $headers = [
            'Channel' => config('services.crdb.channel_id'),
            'AppId' => config('services.crdb.app_id'),
            'AppVersion' => '1.0.0',
            'RequestId' => $data['requestId'],
            'Authorization' => 'Bearer ' . $authBearerToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        Log::debug('== CRDB Mandate Cancellation Request ==');
        Log::debug($data['payload']);

        $httpResponse = Http::withHeaders($headers)->post(config('services.crdb.api_url'), $data['payload']);
        $raw = $httpResponse->body();
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw ?? ''));
        $sanitized = preg_replace('/,\s*(\}|\])/m', '$1', $raw);
        $response = json_decode($sanitized, true);

        Log::debug('== CRDB Mandate Cancellation Response ==');
        Log::debug($response ?: $raw);

        if (!is_array($response)) {
            Log::error('CRDB mandate cancellation failed: invalid JSON response', [
                'json_error' => json_last_error_msg(),
                'sample' => Str::limit($raw, 800),
                'http_status' => $httpResponse->status(),
            ]);

            return [$httpResponse->status() ?: 500, 'Invalid response from CRDB endpoint.', null];
        }

        $statusCode = data_get($response, 'ResponseHeader.StatusCode');
        $statusCode = is_numeric($statusCode) ? (int) $statusCode : $statusCode;
        $statusDesc = data_get($response, 'ResponseHeader.StatusDesc') ?: 'CRDB mandate cancellation response received.';
        $isAlreadyCancelled = $statusCode === 1001 && data_get($response, 'ErrorMetadata.400') !== null;

        if ($statusCode === 1000 || $isAlreadyCancelled) {
            $debitOrder->cancelled = 1;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;
            $debitOrder->remarks = $statusDesc ?: 'Mandate cancelled successfully.';
            $debitOrder->http_response_code = $statusCode;
            $debitOrder->save();

            return [201, $debitOrder->remarks, $statusCode];
        }

        $debitOrder->http_response_code = $statusCode ?? $httpResponse->status();
        $debitOrder->remarks = $statusDesc;
        $debitOrder->save();

        return [$httpResponse->successful() ? 400 : ($httpResponse->status() ?: 500), $statusDesc, $statusCode];
    }

    //create Otp 
    public function createOtpRequest($data)
    {
        //responce code
        $response_code = 200;
        $remarks = "";

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPCreate",
            "status" => "Create",
            "data" => $data
        ];

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //create or update the otp request
        $otp_request = OtpRequest::firstOrNew(
            [
                'reference' => $payload['data']['caseReference'],
                'action' => 'create'
            ]
        );
        //$otp_request->http_response_json = $response;
        $otp_request->save();

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== OtpCreate Response  ==');
        Log::debug($response);

        // Check if response is null or not
        if ($response) {
            // Separate status code
            if (strtolower($response['status']) == "success") {
                $response_code = 201;
                $remarks = $response['data']['response'][0]['responseDescription'];
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];
            }

            return [$response_code, $remarks];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from NMB endpoint.'];
        }
    }

    //verify OTP
    public function verifyOtpRequest($data, $reference)
    {
        //responce code
        $response_code = 200;
        $remarks = "";

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPVerify",
            "status" => "Verify",
            "data" => $data
        ];

        //Create Payload
        Log::debug('OTPVerify Request');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //create or update the otp request
        $otp_request = OtpRequest::firstOrNew(
            [
                'reference' => $reference,
                'action' => 'verify'
            ]
        );
        //$otp_request->http_response_json = $response;
        $otp_request->save();

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== OtpVerify Response  ==');
        Log::debug($response);

        //debit order
        $debit_order = DebitOrder::where('reference', '=', $reference)->first();

        // Check if response is null or not
        if ($response) {
            // Separate status code
            if (strtolower($response['status']) == "success") {
                $response_code = 202;
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update debit order status
                $debit_order->status = 202; //otp verified
                $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_VERIFIED;
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update debit order status
                $debit_order->status = 500;
                $debit_order->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
            }

            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();

            return [$response_code, $remarks];
        } else {
            Log::debug("No response from NMB end");
            return [500, 'No response from NMB endpoint.'];
        }
    }

    //create collection deduction
    public function collectionCreate($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CollectionCreate",
            "status" => "Create",
            "data" => $data
        ];

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $payload['data']['collectionReference'])->first();
        //$collection_batch->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionCreate Response ==');
        Log::debug($response);

        if ($response) {
            if (strtolower($response['status']) == "success" || strtolower($response['status']) == "fail") {
                $collection_batch->status = $response['data']['response'][0]['responseCode'];
                $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
                $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
                $collection_batch->pushed = 1;
                $collection_batch->save();
            } else {
                Log::debug('== CollectionCreate Response not success ==');
            }
        }

        return 200;
    }

    //create collection deduction status
    public function collectionStatus($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CollectionStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== CollectionStatus Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = config('services.nmb.api_url');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //collection reference
        $collection_reference = $payload['data']['collectionReference'];

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $collection_reference)->first();

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionStatus Response ==');
        Log::debug($response);

        if ($response) {
            if (strtolower($response['status']) == "success") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update batch status
                $collection_batch->status = $response_code;
                $collection_batch->http_response_code = $response_code;
                $collection_batch->remarks = $remarks;
                $collection_batch->processed = 1;

                //check for batch processing or batch complete
                if ($response_code == 230) {
                    //1. Batch Complete
                    //1.1: read csv file
                    $data = base64_to_content($response['data']['file']['fileData']);

                    Log::debug("== Complete Collection CSV ==");
                    Log::debug($data);

                    //update installments
                    $response = $this->deductionService->updateInstallments($collection_batch, $data);

                    //3. process batch
                    $collection_batch->success = $response[0];
                    $collection_batch->failed = $response[1];
                    $collection_batch->save();
                } else if ($response_code == 235) {
                    //1. Batch Complete
                    //1.1: read csv file
                    $data = base64_to_content($response['data']['file']['fileData']);

                    Log::debug("== Failed Collection CSV ==");
                    Log::debug($data);

                    //update installments
                    $response = $this->deductionService->updateInstallments($collection_batch, $data);

                    //3. process batch
                    $collection_batch->success = $response[0];
                    $collection_batch->failed = $response[1];
                    $collection_batch->save();
                } else if ($response_code == 239) {
                    //3.Batch Processing
                    //3.1: read csv file
                    $data = base64_to_content($response['data']['file']['fileData']);
                    //$collection_batch->response_csv = $data;

                    Log::debug("== Processing Collection CSV ==");
                    Log::debug($data);

                    //update installments
                    $response = $this->deductionService->updateInstallments($collection_batch, $data);

                    //3. process batch
                    $collection_batch->success = $response[0];
                    $collection_batch->failed = $response[1];
                    $collection_batch->save();
                }
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update collection status
                $collection_batch->status = 501;
                $collection_batch->http_response_code = $response_code;
                $collection_batch->remarks = $remarks;

                if ($response_code == 235 || $response_code == 404)
                    $collection_batch->processed = 1; //batch processed and failed
            }
        }

        return 200;
    }
}
