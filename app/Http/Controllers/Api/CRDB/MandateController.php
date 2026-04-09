<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Services\DeductionService;
use App\Services\ExternalCallbackService;
use App\Services\SettlementPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MandateController extends Controller
{
    private $auth;

    public function __construct(
        protected DeductionService $deductionService,
        protected SettlementPostingService $settlementPostingService,
        protected ExternalCallbackService $externalCallbackService
    )
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
            $debitOrder = DebitOrder::where('otp_reference', '=', $request['data']['reference']);

            if ($debitOrder->exists()) {
                $debitOrder = $debitOrder->first();

                //approved data
                $approved = $request['data']['status'];

                if (strtolower($approved) == "approved") {
                    $debitOrder->approved = 1;
                    $debitOrder->status = 240;
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
                    $debitOrder->remarks = "Mandate approved";
                    $debitOrder->save();

                    // create installments
                    $this->deductionService->createInstallment($debitOrder);

                    //Log response
                    Log::debug("== CRDB Mandate Status Approved ==");
                } else {
                    // Failed debit orders
                    $debitOrder->status = 500;
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
                    $debitOrder->remarks = "Failed to approve mandate.";
                    $debitOrder->save();

                    //Log response
                    Log::debug("== Failed to approve CRDB Mandate ==");
                }

                $debitOrder->refresh();
                $this->externalCallbackService->dispatchMandateStatus(
                    $debitOrder,
                    strtolower($approved) == "approved" ? 'MANDATE_ACTIVE' : 'MANDATE_FAILED'
                );
            }
        } else if ($callback_type == "PAYMENT_STATUS") {
            // mandate reference
            $mandateReference = $request['data']['reference'];

            //query debit order by reference id
            $debitOrder = DebitOrder::where('otp_reference', '=', $mandateReference);

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

                    if (!PaymentSettlement::where([
                        'channel' => 'CRDB',
                        'payment_reference' => $transactionReference,
                    ])->exists()) {
                        $installments = Installment::where('debit_order_id', "=", $debitOrder->id)
                            ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                            ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

                        if ($installments->exists()) {
                            $installment = $installments->first();
                        } else {
                            $installment_amount = $this->deductionService->getInstallmentAmount($debitOrder);
                            $last_installment_order = $this->deductionService->getLastInstallmentOrder($debitOrder);

                            $installment = new Installment();
                            $installment->debit_order_id = $debitOrder->id;
                            $installment->installment_order = $last_installment_order + 1;
                            $installment->installment_amount = $installment_amount;
                            $installment->current_balance = 0;
                            $installment->outstanding_amount = $installment_amount;
                            $installment->payment_date = date('Y-m-d', strtotime($collectionDate));
                            $installment->remarks = "CRDB amount paid";
                            $installment->save();
                        }

                        $openingBalance = (float) ($installment->outstanding_amount ?? 0);
                        $paymentAmount = (float) $amount;
                        $rawPayloadHash = md5(json_encode($request->all()));

                        if ($openingBalance <= 0) {
                            $this->settlementPostingService->postUnallocated([
                                'channel' => 'CRDB',
                                'debit_order' => $debitOrder,
                                'installment' => $installment,
                                'amount' => $paymentAmount,
                                'raw_status' => strtoupper((string) $transferResponse),
                                'normalized_status' => PaymentSettlement::STATUS_UNALLOCATED,
                                'settlement_type' => 'OVERPAYMENT_UNALLOCATED',
                                'remarks' => 'Duplicate or extra deduction after installment already covered',
                                'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                                'transaction_at' => $transactionDateTime ?: $collectionDate,
                                'payment_reference' => $transactionReference,
                                'external_reference' => $transactionReference,
                                'raw_payload_hash' => $rawPayloadHash,
                            ]);
                        } elseif ($paymentAmount > $openingBalance) {
                            $this->settlementPostingService->post([
                                'channel' => 'CRDB',
                                'debit_order' => $debitOrder,
                                'installment' => $installment,
                                'amount' => $openingBalance,
                                'raw_status' => strtoupper((string) $transferResponse),
                                'normalized_status' => PaymentSettlement::STATUS_SUCCESS,
                                'remarks' => 'Applied capped amount from CRDB payment',
                                'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                                'transaction_at' => $transactionDateTime ?: $collectionDate,
                                'payment_reference' => $transactionReference,
                                'external_reference' => $transactionReference,
                                'raw_payload_hash' => $rawPayloadHash,
                            ]);

                            $this->settlementPostingService->postUnallocated([
                                'channel' => 'CRDB',
                                'debit_order' => $debitOrder,
                                'installment' => $installment,
                                'amount' => $paymentAmount - $openingBalance,
                                'raw_status' => strtoupper((string) $transferResponse),
                                'normalized_status' => PaymentSettlement::STATUS_UNALLOCATED,
                                'settlement_type' => 'OVERPAYMENT_UNALLOCATED',
                                'remarks' => 'Excess deduction held for reconciliation',
                                'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                                'transaction_at' => $transactionDateTime ?: $collectionDate,
                                'payment_reference' => $transactionReference . '-EXCESS',
                                'external_reference' => $transactionReference,
                                'raw_payload_hash' => $rawPayloadHash,
                            ]);
                        } else {
                            $normalizedStatus = $paymentAmount > 0 && $paymentAmount < $openingBalance
                                ? PaymentSettlement::STATUS_PARTIAL
                                : PaymentSettlement::STATUS_SUCCESS;

                            $this->settlementPostingService->post([
                                'channel' => 'CRDB',
                                'debit_order' => $debitOrder,
                                'installment' => $installment,
                                'amount' => $paymentAmount,
                                'raw_status' => strtoupper((string) $transferResponse),
                                'normalized_status' => $normalizedStatus,
                                'remarks' => 'Amount paid',
                                'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                                'transaction_at' => $transactionDateTime ?: $collectionDate,
                                'payment_reference' => $transactionReference,
                                'external_reference' => $transactionReference,
                                'raw_payload_hash' => $rawPayloadHash,
                            ]);
                        }
                    }
                } else if (strtolower($transferResponse) == "insufficient_amount") {
                    // select installment for this month
                    $installments = Installment::where('debit_order_id', "=", $debitOrder->id)
                        ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                        ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

                    if ($installments->exists()) {
                        $installment = $installments->first();

                        $this->settlementPostingService->post([
                            'channel' => 'CRDB',
                            'debit_order' => $debitOrder,
                            'installment' => $installment,
                            'amount' => 0,
                            'raw_status' => strtoupper((string) $transferResponse),
                            'normalized_status' => PaymentSettlement::STATUS_FAILED,
                            'remarks' => 'Insufficient funds',
                            'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                            'transaction_at' => $collectionDate,
                            'payment_reference' => "CRDB-FAILED-{$debitOrder->id}-" . date('Ym', strtotime($collectionDate)),
                            'raw_payload_hash' => md5(json_encode($request->all())),
                        ]);

                        Log::debug("== update installment : insufficient amount ===");
                        Log::debug($installment->toArray());
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
