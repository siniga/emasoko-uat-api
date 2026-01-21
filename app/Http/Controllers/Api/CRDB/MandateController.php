<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Services\DeductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MandateController extends Controller
{
    private $auth;

    public function __construct(protected DeductionService $deductionService)
    {
        $this->auth = new Checksum();
    }

    //push mandate to CRDB
    public function create(Request $request)
    {
        //predefined variables
        $debitAccount = $request->debitAccount;
        $phone = $request->phone;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $totalAmount = $request->totalAmount;
        $installmentAmount = $request->installmentAmount;
        $periodicity = $request->periodicity;
        $frequency = $request->frequency;

        //validate mandatory fields
        $partnerId = env('CRDB_PARTNER_ID');
        $requestId = Str::uuid()->toString();

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;

        //create checksum
        $checksum = $this->auth->generateChecksum($data);
        Log::debug("== checksum ==");
        Log::debug($checksum);

        //token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== AUTH Token ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //headers
        $headers = [
            'Channel' => env('CRDB_CHANNEL_ID'),
            'AppId' => env('CRDB_APP_ID'),
            'AppVersion' => '1.0.0',
            "RequestId" => $requestId,
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        Log::debug("== Headers ==");
        Log::debug($headers);

        //create payload
        $payload = [
            "PartnerId" =>  $partnerId,
            "RequestId" => $requestId,
            'Checksum' => $checksum,
            "PhoneNumber" =>  $phone,
            "DebitAccount" =>  $debitAccount,
            "TotalAmount" =>  $totalAmount,
            "InstallmentAmount" => $installmentAmount,
            "StartDate" => $startDate,
            "EndDate" => $endDate,
            "FrequencyType" => $periodicity,
            "Frequency" => $frequency,
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== CRDB Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = env('CRDB_API_URL');
        Log::debug("== CRDB API URL ==");
        Log::debug($API_URL);

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== CRDB MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //cancellation
    public function cancel(Request $request)
    {
        //get data
        $requestId = $request->requestId;
        $debitAccount = $request->debitAccount;
        $phone = $request->phone;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $totalAmount = $request->totalAmount;
        $installmentAmount = $request->installmentAmount;
        $periodicity = $request->periodicity;
        $frequency = $request->frequency;

        //validate mandatory fields
        $partnerId = env('CRDB_PARTNER_ID');

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;

        //create checksum
        $checksum = $this->auth->generateChecksum($data);

        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== AUTH Token ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //headers
        $headers = [
            'Channel' => env('CRDB_CHANNEL_ID'),
            'AppId' => env('CRDB_APP_ID'),
            'AppVersion' => '1.0.0',
            "RequestId" => $requestId,
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        Log::debug("== Headers ==");
        Log::debug($headers);

        //create payload
        $payload = [
            "PartnerId" =>  $partnerId,
            "RequestId" => $requestId,
            'Checksum' => $checksum,
            "PhoneNumber" =>  $phone,
            "DebitAccount" =>  $debitAccount,
            "TotalAmount" =>  $totalAmount,
            "InstallmentAmount" => $installmentAmount,
            "StartDate" => $startDate,
            "EndDate" => $endDate,
            "FrequencyType" => $periodicity,
            "Frequency" => $frequency,
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== CRDB Cancellation Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = env('CRDB_API_URL');
        Log::debug("== CRDB API URL ==");
        Log::debug($API_URL);

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== CRDB Cancellation Response ==');
        Log::debug($response);

        return $response;
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== CRDB Callback Response ==');
        Log::debug($request->all());

        // check for valid callback type
        $callback_type = $request->input('callbackType');

        if ($callback_type == "REQUEST_STATUS") {
            // mandate reference
            $mandateReference = $request['data']['reference'];

            //query debit order by reference id
            $debitOrder = DebitOrder::where('crdb_reference', '=', $request['data']['reference']);

            if ($debitOrder->exists()) {
                $debitOrder = $debitOrder->first();

                //approved data
                $approved = $request['data']['status'];

                if (strtolower($approved) == "approved") {
                    $debitOrder->approved = 1;
                    $debitOrder->status = 240;
                    $debitOrder->remarks = "Mandate approved";
                    $debitOrder->save();

                    // create installments
                    $this->deductionService->createInstallment($debitOrder);

                    //Log response
                    Log::debug("== CRDB Mandate Status Approved ==");
                } else {
                    // Failed debit orders
                    $debitOrder->status = 500;
                    $debitOrder->remarks = "Failed to approve mandate.";
                    $debitOrder->save();

                    //Log response
                    Log::debug("== Failed to approve CRDB Mandate ==");
                }
            }
        } else if ($callback_type == "PAYMENT_STATUS") {
            // mandate reference
            $mandateReference = $request['data']['reference'];

            //query debit order by reference id
            $debitOrder = DebitOrder::where('crdb_reference', '=', $mandateReference);

            if ($debitOrder->exists()) {
                $debitOrder = $debitOrder->first();

                //payment responses
                $transferResponse = $request['data']['transferResponse'];
                $collectionDate = $request['data']['collectionDate'];

                if (strtolower($transferResponse) == "success") {
                    // get success data
                    $phoneNumber = $request['data']['phoneNumber'];
                    $accountNumber = $request['data']['accountNumber'];
                    $amount = $request['data']['amount'];
                    $transactionReference = $request['data']['transactionReference'];
                    $transactionDateTime = $request['data']['transactionDateTime'];

                    // check if payment reference exists in installments payment
                    $installment_payments = InstallmentPayment::where([
                        'mandate_reference' => $debitOrder->reference,
                        'payment_reference' => $transactionReference
                    ]);

                    if (!$installment_payments->exists()) {
                        // create installment payment
                        $installment_payment = new InstallmentPayment();
                        $installment_payment->mandate_reference = $debitOrder->reference;
                        $installment_payment->payment_reference = $transactionReference;
                        $installment_payment->amount = $amount;
                        $installment_payment->payment_at = date('Y-m-d', strtotime($collectionDate));
                        $installment_payment->save();

                        // check if installment  exists with status == "OPEN" or "PARTIAL_PAID"
                        $installments = Installment::where('debit_order_id', "=", $debitOrder->id)
                            ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                            ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

                        if ($installments->exists()) {
                            // get installment
                            $installment = $installments->first();

                            //if status is OPEN or FAILED
                            if ($installment->status == "OPEN" || $installment->status == "FAILED") {
                                //updated status of installment
                                $installment->last_paid_amount = $amount;
                                $installment->payment_reference = $transactionReference;
                                $installment->effective_payment_date = date('Y-m-d', strtotime($collectionDate));
                                $installment->remarks = "Amount paid";
                                $installment->save();

                                // process installment
                                $this->deductionService->processInstallment($installment);
                            } else if ($installment->status == "PARTIAL_PAID") {
                                //1.0 calculate outstanding amount
                                $outstanding_amount = ($installment->installment_amount - $installment->last_paid_amount);
                                //create function for partial payment

                            }
                        } else {
                            //1.0 calculate installment amount
                            $installment_amount = $this->deductionService->getInstallmentAmount($debitOrder);
                            $last_installment_order = $this->deductionService->getLastInstallmentOrder($debitOrder);

                            //1.0 create new installment with payment details
                            $new_installment = new Installment();
                            $new_installment->debit_order_id = $debitOrder->id;
                            $new_installment->installment_order = $last_installment_order + 1;
                            $new_installment->installment_amount = $installment_amount;
                            $new_installment->payment_date = date('Y-m-d', strtotime($collectionDate));
                            $new_installment->last_paid_amount = $amount;
                            $new_installment->payment_reference = $transactionReference;
                            $new_installment->effective_payment_date = date('Y-m-d', strtotime($collectionDate));
                            $new_installment->remarks = "Amount paid";
                            $new_installment->save();

                            //2.0 then process payment installment
                            $this->deductionService->processInstallment($new_installment);
                        }
                    }
                } else if (strtolower($transferResponse) == "insufficient_amount") {
                    // select installment for this month
                    $installments = Installment::where('debit_order_id', "=", $debitOrder->id)
                        ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                        ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

                    if ($installments->exists()) {
                        $installment = $installments->first();
                        $installment->status = "FAILED";
                        $installment->remarks = "Insufficient funds";
                        $installment->save();
                    }
                }
            }
        }

        // response
        return response()->json([
            'status' => true,
            'message' => "Callback received",
        ], 200);
    }
}
