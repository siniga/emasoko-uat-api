<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Jobs\CRDBMandateCancellationJob;
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
    private $payloadService;

    public function __construct()
    {
        $this->auth = new Authentication();
        $this->payloadService = new BankPayloadService();
    }

    public function registerMandate($debitOrder) {}


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
