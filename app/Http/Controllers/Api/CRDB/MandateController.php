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
use Carbon\Carbon;
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
            $data = $request->input('data', []);
            $mandateReference = $data['reference'] ?? null;
            $approved = $data['status'] ?? null;

            if (!$mandateReference || !$approved) {
                Log::warning('Invalid CRDB request status callback payload.', [
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid callback payload',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            //query debit order by reference id
            $debitOrder = DebitOrder::where('crdb_reference', '=', $mandateReference);

            if ($debitOrder->exists()) {
                $debitOrder = $debitOrder->first();
                $previousLifecycleStatus = $debitOrder->resolved_lifecycle_status;
                $remarks = $data['message']
                    ?? $data['statusDescription']
                    ?? $data['statusDesc']
                    ?? null;

                if (strtolower($approved) == "approved") {
                    $debitOrder->approved = 1;
                    $debitOrder->status = 240;
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
                    $debitOrder->remarks = $remarks ?: "Mandate approved";
                    $debitOrder->save();

                    if ($previousLifecycleStatus !== DebitOrder::LIFECYCLE_ACTIVE) {
                        $this->deductionService->createInstallment($debitOrder);
                    }

                    //Log response
                    Log::debug("== CRDB Mandate Status Approved ==");
                } else {
                    // Failed debit orders
                    $debitOrder->approved = 2;
                    $debitOrder->status = 500;
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
                    $debitOrder->remarks = $remarks ?: "Failed to approve mandate.";
                    $debitOrder->save();

                    //Log response
                    Log::debug("== Failed to approve CRDB Mandate ==");
                }

                $debitOrder->refresh();

                if ($previousLifecycleStatus !== $debitOrder->resolved_lifecycle_status) {
                    $this->externalCallbackService->dispatchMandateStatus(
                        $debitOrder,
                        strtolower($approved) == "approved" ? 'MANDATE_ACTIVE' : 'MANDATE_FAILED'
                    );
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

                    if (!PaymentSettlement::where('channel', 'CRDB')
                        ->where(function ($query) use ($transactionReference) {
                            $query->where('external_reference', $transactionReference)
                                ->orWhere('payment_reference', $transactionReference);
                        })
                        ->exists()) {
                        $this->postCrdbSuccessfulPayment(
                            $debitOrder,
                            (float) $amount,
                            $transactionReference,
                            $transactionDateTime ?: $collectionDate,
                            $collectionDate,
                            strtoupper((string) $transferResponse),
                            md5(json_encode($request->all()))
                        );
                    } else {
                        Log::info('CRDB payment status skipped: transaction reference already settled.', [
                            'debit_order_id' => $debitOrder->id,
                            'mandate_reference' => $debitOrder->reference,
                            'crdb_reference' => $mandateReference,
                            'transaction_reference' => $transactionReference,
                        ]);
                    }
                } else if (strtolower($transferResponse) == "insufficient_amount") {
                    // select installment for this month
                    $installments = Installment::where('debit_order_id', "=", $debitOrder->id)
                        ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                        ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

                    if ($installments->exists()) {
                        $installment = $installments->first();
                        $paymentReference = "CRDB-FAILED-{$debitOrder->id}-" . date('Ym', strtotime($collectionDate));
                        $existingSettlement = PaymentSettlement::where([
                            'channel' => 'CRDB',
                            'payment_reference' => $paymentReference,
                            'installment_id' => $installment->id,
                        ])->first();

                        $settlement = $this->settlementPostingService->post([
                            'channel' => 'CRDB',
                            'debit_order' => $debitOrder,
                            'installment' => $installment,
                            'amount' => 0,
                            'raw_status' => strtoupper((string) $transferResponse),
                            'normalized_status' => PaymentSettlement::STATUS_FUNDSCHECK,
                            'remarks' => 'Insufficient funds',
                            'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                            'transaction_at' => $collectionDate,
                            'payment_reference' => $paymentReference,
                            'raw_payload_hash' => md5(json_encode($request->all())),
                        ]);

                        $this->dispatchCrdbCollectionCallback($settlement, !$existingSettlement
                            || strtoupper((string) $existingSettlement->normalized_status) !== PaymentSettlement::STATUS_FUNDSCHECK);

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

    protected function postCrdbSuccessfulPayment(
        DebitOrder $debitOrder,
        float $paymentAmount,
        string $transactionReference,
        string $transactionAt,
        string $collectionDate,
        string $rawStatus,
        string $rawPayloadHash
    ): void {
        $remainingAmount = $paymentAmount;
        $sequence = 1;

        while ($remainingAmount > 0) {
            $installment = $this->nextPayableCrdbInstallment($debitOrder, $collectionDate);

            if (!$installment) {
                $this->settlementPostingService->postUnallocated([
                    'channel' => 'CRDB',
                    'debit_order' => $debitOrder,
                    'amount' => $remainingAmount,
                    'raw_status' => $rawStatus,
                    'normalized_status' => PaymentSettlement::STATUS_UNALLOCATED,
                    'settlement_type' => 'OVERPAYMENT_UNALLOCATED',
                    'remarks' => 'Excess deduction held for reconciliation',
                    'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                    'transaction_at' => $transactionAt,
                    'payment_reference' => $transactionReference . '-EXCESS',
                    'external_reference' => $transactionReference,
                    'raw_payload_hash' => $rawPayloadHash,
                ]);

                return;
            }

            $openingBalance = (float) ($installment->outstanding_amount ?? $installment->installment_amount ?? 0);

            if ($openingBalance <= 0) {
                $installment->status = 'CLOSED';
                $installment->save();
                continue;
            }

            $amountToApply = min($remainingAmount, $openingBalance);
            $remainingAmount -= $amountToApply;
            $normalizedStatus = $amountToApply < $openingBalance
                ? PaymentSettlement::STATUS_PARTIAL
                : PaymentSettlement::STATUS_SUCCESS;

            $settlement = $this->settlementPostingService->post([
                'channel' => 'CRDB',
                'debit_order' => $debitOrder,
                'installment' => $installment,
                'amount' => $amountToApply,
                'raw_status' => $rawStatus,
                'normalized_status' => $normalizedStatus,
                'remarks' => $normalizedStatus === PaymentSettlement::STATUS_PARTIAL ? 'Partial amount paid' : 'Amount paid',
                'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                'transaction_at' => $transactionAt,
                'payment_reference' => $sequence === 1 ? $transactionReference : "{$transactionReference}-PART-{$sequence}",
                'external_reference' => $transactionReference,
                'raw_payload_hash' => $rawPayloadHash,
            ]);

            $this->dispatchCrdbCollectionCallback($settlement);

            $sequence++;
        }
    }

    protected function nextPayableCrdbInstallment(DebitOrder $debitOrder, string $collectionDate): ?Installment
    {
        $debitOrder->refresh();

        if ((float) ($debitOrder->outstanding_amount ?? 0) <= 0) {
            return null;
        }

        $installment = Installment::where('debit_order_id', $debitOrder->id)
            ->whereNotIn('status', ['CLOSED'])
            ->where('outstanding_amount', '>', 0)
            ->orderByRaw("CASE WHEN status = 'PARTIAL_PAID' THEN 0 ELSE 1 END")
            ->orderBy('payment_date')
            ->orderBy('installment_order')
            ->first();

        if ($installment) {
            return $installment;
        }

        $installmentAmount = $this->deductionService->getInstallmentAmount($debitOrder);
        $lastInstallmentOrder = $this->deductionService->getLastInstallmentOrder($debitOrder);

        $installment = new Installment();
        $installment->debit_order_id = $debitOrder->id;
        $installment->installment_order = $lastInstallmentOrder + 1;
        $installment->installment_amount = $installmentAmount;
        $installment->current_balance = 0;
        $installment->outstanding_amount = $installmentAmount;
        $installment->payment_date = date('Y-m-d', strtotime($collectionDate));
        $installment->status = 'OPEN';
        $installment->remarks = 'CRDB amount paid';
        $installment->save();

        return $installment;
    }

    protected function dispatchCrdbCollectionCallback(PaymentSettlement $settlement, bool $shouldDispatch = true): void
    {
        if (!$shouldDispatch) {
            Log::info('External CRDB collection callback skipped: settlement status did not change.', [
                'settlement_id' => $settlement->id,
                'installment_id' => $settlement->installment_id,
                'payment_reference' => $settlement->payment_reference,
                'normalized_status' => $settlement->normalized_status,
            ]);
            return;
        }

        if (!$settlement->installment_id) {
            Log::warning('External CRDB collection callback skipped: settlement has no installment.', [
                'settlement_id' => $settlement->id,
                'payment_reference' => $settlement->payment_reference,
                'normalized_status' => $settlement->normalized_status,
            ]);
            return;
        }

        $settlement->loadMissing('installment.debitOrder.partner');

        if (!$settlement->installment) {
            Log::warning('External CRDB collection callback skipped: settlement installment could not be loaded.', [
                'settlement_id' => $settlement->id,
                'installment_id' => $settlement->installment_id,
                'payment_reference' => $settlement->payment_reference,
            ]);
            return;
        }

        $eventType = match (strtoupper((string) $settlement->normalized_status)) {
            PaymentSettlement::STATUS_SUCCESS => 'COLLECTION_PAID',
            PaymentSettlement::STATUS_PARTIAL => 'COLLECTION_PARTIAL_PAID',
            PaymentSettlement::STATUS_FUNDSCHECK => 'COLLECTION_FUNDSCHECK',
            PaymentSettlement::STATUS_UNALLOCATED => 'COLLECTION_UNALLOCATED',
            default => 'COLLECTION_FAILED',
        };

        $this->externalCallbackService->dispatchCollectionStatus($settlement->installment, $eventType, [
            'channel' => $settlement->channel,
            'settlement_reference' => $settlement->payment_reference,
            'external_reference' => $settlement->external_reference,
            'bank_status' => $settlement->raw_status,
            'settlement_status' => $settlement->normalized_status,
            'paid_amount' => (float) $settlement->paid_amount,
            'transaction_at' => $settlement->transaction_at
                ? Carbon::parse($settlement->transaction_at)->toIso8601String()
                : null,
        ]);
    }
}
