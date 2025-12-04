<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Jobs\CRDBMandateCancellationJob;
use App\Jobs\CRDBMandateRegistrationJob;
use App\Jobs\MandateCancellationJob;
use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Exception;


class MandateActionService
{
    private $auth;
    private $bankApi;
    private $payloadService;

    public function __construct()
    {
        $this->auth = new Authentication();
        $this->bankApi = new BankApiService();
        $this->payloadService = new BankPayloadService();
    }

    public function registerMandate($debitOrder)
    {
        if ($debitOrder->channel == "CRDB") {
            //requestId
            $requestId = Str::uuid()->toString();

            //create payload
            $payload = $this->payloadService->CRDBMandateRegistrationPayload($debitOrder, $requestId);

            //create data
            $data['payload'] = $payload;
            $data['requestId'] = $requestId;
            $data['mandateReferenceNumber'] = $debitOrder->reference;

            //Create Request to CRDB
            dispatch(new CRDBMandateRegistrationJob($data));

            //return response
            return response()->json([
                'success' => true,
                'response_code' => 201,
                'remarks' => "Mandate registration request sent to CRDB. Go to customer SimBanking and approve mandate."
            ]);
        } else if ($debitOrder->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->mandateRegistrationPayload($debitOrder);

            //arr response
            $arrResponse = $this->bankApi->mandateRegistrationRequest($payload);

            if ($arrResponse) {
                $success = true;
                $response_code = $arrResponse[0];
                $remarks = $arrResponse[1];

                return response()->json([
                    'success' => $success,
                    'response_code' => $response_code,
                    'remarks' => $remarks
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'response_code' => 500,
                    'remarks' => 'Unable to register mandate'
                ]);
            }
        }
    }


    public function cancelMandate($debitOrder)
    {
        if ($debitOrder->channel == "CRDB") {
            //requestId
            $requestId = $debitOrder->crdb_request_id;

            //create payload
            $payload = $this->payloadService->CRDBMandateCancellationPayload($debitOrder, $requestId);

            //create data
            $data['payload'] = $payload;
            $data['requestId'] = $requestId;
            $data['mandateReferenceNumber'] = $debitOrder->reference;

            //Create Request to CRDB
            dispatch(new CRDBMandateCancellationJob($data));
        } else if ($debitOrder->channel == "NMB") {
            //cancel mandate
            Log::debug("== NMB Cancel Mandate Payload ==");

            //create payload
            $payload = $this->payloadService->mandateCancellationPayload($debitOrder);

            //background job
            dispatch(new MandateCancellationJob($payload));
        }
    }
}
