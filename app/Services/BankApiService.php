<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Classes\BearerToken;
use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Exception;

class BankApiService
{
    private $auth;
    private $tigoAuth;
    private $deduction_service;

    public function __construct()
    {
        $this->auth = new Authentication();
        $this->deduction_service = new DeductionService();
    }

    //corporate registration
    public function corporateRegistrationRequest($data)
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
        $API_URL = env('API_URL');

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
            $partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'channel' => "NMB"])->first();
            $partner_account->remarks = $remarks;
            $partner_account->save();

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
            "targetService" => "CorporateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== Corporate Status Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

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
                $response_code = 202;
            } else {
                $response_code = 203;
            }
        } else if (strtolower($response['status']) == "fail") {
            $response_code = 500;
            $remarks = $response['data']['response'][0]['responseDescription'];
        }

        // update partner
        $partner->status = $response_code;
        $partner->http_response_code = $response['data']['response'][0]['responseCode'];
        $partner->remarks = $response['data']['response'][0]['responseDescription'];
        $partner->save();

        //update partner account
        $nmb_partner_account->is_verified = $is_verified;
        $nmb_partner_account->remarks = $remarks;
        $nmb_partner_account->save();

        return [$response_code, $remarks];
    }

    //mandate registartion
    public function mandateRegistrationRequest($data)
    {
        //responce code
        $response_code = 200;
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
        $API_URL = env('API_URL');

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
                $response_code = 201;
                $remarks = $response['data']['response'][0]['responseDescription'];

                $debit_order->status = 201; //created
                $debit_order->otp_reference = $response['data']['otpCaseReference'];
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update debit order status
                $debit_order->status = 500;
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
        $API_URL = env("JUMENA_PAYMENT_SESSION_URL");

        //push payload => NMB Direct Debits
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
                $debitOrder->request_id = $response['session_id'];
                $debitOrder->remarks = 'Payment session created';
                $debitOrder->save();
            } else {
                $response_code = 500;
                $remarks = 'Unexpected status: ' . $response['status'];

                $debitOrder->status = 500;
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
        $API_URL = env('API_URL');

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
                if ($response_code == 240)
                    $debit_order->approved = 1;
                else
                    $debit_order->approved = 0;
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update status and status
                $debit_order->status = 500;
                $debit_order->approved = 0;
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
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the debit order
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        //$debit_order->http_response_status_json = $response;

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
                    $debit_order->deleted = 1; // Mark as deleted
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
        $API_URL = env('API_URL');

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
        $API_URL = env('API_URL');

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
            } else if (strtolower($response['status']) == "fail") {
                $response_code = $response['data']['response'][0]['responseCode'];
                $remarks = $response['data']['response'][0]['responseDescription'];

                //update debit order status
                $debit_order->status = 500;
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
        $API_URL = env('API_URL');

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
        $API_URL = env('API_URL');

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
                    $response = $this->deduction_service->updateInstallments($data);

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
                    $response = $this->deduction_service->updateInstallments($data);

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
                    $response = $this->deduction_service->updateInstallments($data);

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
