<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
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
        $http_code = 200;

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


        try {
            //response
            $response = Http::withHeaders($headers)->post($API_URL, $payload);

            if ($response) {
                //update the corporate
                $partner = Partner::where('reference', '=', $payload['data']['corporateReference'])->first();
                $partner->http_response_json = $response;

                //decode the response 
                $response = json_decode($response, true);

                //logging response
                Log::debug('== CorporateRegistration Response ==');
                Log::debug($response);

                if ($response['status'] == "Success") {
                    $partner->status = 201; //created

                    //http code
                    $http_code = 201;
                } else if ($response['status'] == "Fail") {
                    $http_code = 403; //Failed

                    if ($response['data']['response'][0]['responseCode'] != 410)
                        $partner->status = 403;
                }

                $partner->http_response_code = $response['data']['response'][0]['responseCode'];
                $partner->remarks = $response['data']['response'][0]['responseDescription'];
                $partner->save();

                return response()->json(['error' => false, 'http_code' => $http_code, 'error_msg' => $response['data']['response'][0]['responseDescription']]);
            } else {
                return response()->json(['error' => true, 'http_code' => 500, 'error_msg' => 'No any response.']);
            }
        } catch (Exception $e) {
            return response()->json(['error' => true, 'http_code' => 500, 'error_msg' => '']);
        }
    }

    //corporate status
    public function corporateStatusRequest($data)
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
        $partner->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CorporateStatus Response  ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $response_code = 202; //response code
            $remarks = $response['data']['response'][0]['responseDescription'];

            $partner->status = 202;
            $partner->http_response_code = $response['data']['response'][0]['responseCode'];
            $partner->remarks = $response['data']['response'][0]['responseDescription'];
            $partner->save();

            //TODO: update partner account
            $nmb_partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'account_type_id' => 1, 'account_id' => 1])->first();
            $nmb_partner_account->status = 1;
            $nmb_partner_account->save();
        } else if (strtolower($response['status']) == "fail") {
            $response_code = 500; //response code
            $remarks = $response['data']['response'][0]['responseDescription'];

            $partner->status = 500;
            $partner->http_response_code = $response['data']['response'][0]['responseCode'];
            $partner->remarks = $response['data']['response'][0]['responseDescription'];
            $partner->save();
        }

        return [$response_code, $remarks];
    }

    //mandate registartion
    public function mandateRegistrationRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

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
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
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
        $debit_order->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateStatus Response  ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $response_code = $response['data']['response'][0]['responseCode'];
            $remarks = $response['data']['response'][0]['responseDescription'];

            //update debit order
            $debit_order->status = $response['data']['response'][0]['responseCode'];
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];

            //update debit order => approved
            if ($response_code == 240)
                $debit_order->approved = 1;
            else if ($response_code == 249 || $response_code == 430)
                $debit_order->approved = 0;
            else
                $debit_order->approved = 2;
        } else if (strtolower($response['status']) == "fail") {
            $response_code = $response['data']['response'][0]['responseCode'];
            $remarks = $response['data']['response'][0]['responseDescription'];

            //update debit order
            $debit_order->status = 500;
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->approved = 2;
        }
        $debit_order->save();

        return [$response_code, $remarks];
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
        $debit_order->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateCancellation Response  ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $response_code = 405; //response code
            $remarks = $response['data']['response'][0]['responseDescription'];

            $debit_order->deleted = 1;
            $debit_order->status = 405;
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();
        } else if (strtolower($response['status']) == "fail") {
            $response_code = 500; //response code
            $remarks = $response['data']['response'][0]['responseDescription'];

            $debit_order->deleted = 0;
            $debit_order->status = 500;
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();
        }

        return [$response_code, $remarks];
    }

    //create Otp 
    public function createOtpRequest($data)
    {
        //responce code
        $response_code = 200;

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

        //Create Payload
        Log::debug('OTPCreate Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the otp request
        $otp_request = OtpRequest::where(['reference' => $payload['data']['caseReference'], 'account_number' => $payload['data']['accountNumber'], 'action' => 'create']);

        if ($otp_request->count() > 0) {
            $otp_request = $otp_request->first();
            $otp_request->http_response_json = $response;
            $otp_request->save();
        }

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== OtpCreate Response  ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $response_code = 202;
        } else if (strtolower($response['status']) == "fail") {
            $response_code = $response['data']['response'][0]['responseCode'];
            $status_code = $response['data']['response'][0]['responseCode'];

            //debit order
            $debit_order = DebitOrder::where('reference', '=', $payload['data']['caseReference'])->first();

            //Update mandate status based on response
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];

            if ($status_code == 404) {
                $debit_order->status = 404;
                $debit_order->approved = 2;
            } else {
                $debit_order->status = 500;
            }
            $debit_order->save();
        }

        return $response_code;
    }

    //verify OTP
    public function verifyOtpRequest($data, $account_number, $reference)
    {
        //responce code
        $response_code = 200;

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

        //update the otp request
        $otp_request = OtpRequest::where(['reference' => $reference, 'account_number' => $account_number, 'action' => 'verify']);

        if ($otp_request->count() > 0) {
            $otp_request = $otp_request->first();
            $otp_request->http_response_json = $response;
            $otp_request->save();
        }

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== OtpVerify Response  ==');
        Log::debug($response);

        //debit order
        $debit_order = DebitOrder::where('reference', '=', $reference)->first();

        if (strtolower($response['status']) == "success") {
            $response_code = 202;

            //Update mandate status based on response
            $debit_order->status = 202;
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();
        } else if (strtolower($response['status']) == "fail") {
            $response_code = $response['data']['response'][0]['responseCode'];
            $status_code = $response['data']['response'][0]['responseCode'];

            //Update mandate status based on response
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];

            if ($status_code == 404) {
                $debit_order->status = 404;
                $debit_order->approved = 2;
            } else if ($status_code == 500) {
                $debit_order->status = 500;
            } else {
                $debit_order->status = 430;
            }
            $debit_order->save();
        }

        return $response_code;
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

        //Create Payload
        Log::debug('== CollectionCreate Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $payload['data']['collectionReference'])->first();
        $collection_batch->http_response_json = $response;

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
        $collection_batch->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionStatus Response ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $batch_status = $response['data']['response'][0]['responseCode'];

            //update batch status
            $collection_batch->status = $response['data']['response'][0]['responseCode'];
            $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
            $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
            $collection_batch->processed = 1;

            //check for batch processing or batch complete
            if ($batch_status == 230) {
                //1. Batch Complete
                //1.1: read csv file
                $data = base64_to_content($response['data']['file']['fileData']);
                $collection_batch->response_csv = $data;

                Log::debug("== Collection CSV ==");
                Log::debug($data);

                $response = $this->updateInstallments($data, $collection_reference);

                //3. process batch
                $collection_batch->success = $response[0];
                $collection_batch->failed = $response[1];
                $collection_batch->save();
            } else if ($batch_status == 239) {
                //3.Batch Processing
                //3.1: read csv file
                $data = base64_to_content($response['data']['file']['fileData']);
                $collection_batch->response_csv = $data;

                Log::debug("== Collection CSV ==");
                Log::debug($data);

                $response = $this->updateInstallments($data, $collection_reference);

                //3. process batch
                $collection_batch->success = $response[0];
                $collection_batch->failed = $response[1];
                $collection_batch->save();
            }
        } else if (strtolower($response['status']) == "fail") {
            $collection_batch->status = $response['data']['response'][0]['responseCode'];
            $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
            $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
            $collection_batch->processed = 1;
            $collection_batch->save();
        }

        return 200;
    }

    protected function updateInstallments($data, $collection_reference)
    {
        //Convert the data into an array of rows
        $rows = explode("\n", $data);

        //remove empty rows
        $rows = array_filter($rows);

        //count success and failed installments
        $success_installments = 0;
        $failed_installments = 0;

        //2. process installment
        foreach ($rows as $index => $row) {
            // Skip the first row as it contains headers
            if ($index === 0) {
                continue;
            }

            // Split the row into individual fields using semicolon as delimiter
            $fields = explode(";", $row);

            //get installment from id
            $installment = Installment::where('id', '=', $fields[0])->first();

            if ($installment) {
                //installment amount
                $installment_amount = $installment->installment_amount;

                //payment date
                $payment_date = $installment->payment_date;

                //status and paid amount
                $message = strtolower($fields[8]);
                $status = strtolower($fields[7]);
                $amount = floatval($fields[6]);

                //check for status
                if ($status == "fail") {
                    //updated status of installment
                    $installment->status = "FAILED";
                    $installment->save();

                    //increment failed count
                    $failed_installments += 1;

                    if ($message == 'insufficient funds') {
                        //send sms to FAILED mandate
                        $phone = $installment->debitOrder->customerAccount->phone;
                        $message = "Makato ya tarehe {$payment_date} ya kiasi cha shilingi {$installment_amount} yameshindikana. Tafadhali weka pesa kwenye akaunti yako ya benki kwa ajili ya makato.";

                        //send sms
                        $data = ["phone" => $phone, "message" => $message];
                        dispatch(new SendSMSJob($data));
                    }
                } else if ($status == "success") {
                    //updated status of installment
                    $installment->status = "SUCCESS";
                    $installment->last_paid_amount = $amount;
                    $installment->save();

                    //increment success count
                    $success_installments += 1;

                    //process calculation for installment and debit order.
                    $this->deduction_service->processInstallment($installment, $amount, $collection_reference);
                } else {
                    //updated status of installment
                    $installment->status = $status;
                    $installment->save();
                }
            }
        }

        return [$success_installments, $failed_installments];
    }
}
