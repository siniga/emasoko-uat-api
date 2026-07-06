<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Models\DebitOrder;
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

    public function registerMandate(DebitOrder $debitOrder)
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

            //arr response
            $arrResponse = $this->bankApi->crdbMandateRegistrationRequest($data);

            if ($arrResponse) {
                $success = true;
                $http_status_code = $arrResponse[0];
                $remarks = $arrResponse[1];

                if ($http_status_code == 201) {
                    return response()->json([
                        'success' => true,
                        'response_code' => 201,
                        'remarks' => $remarks
                    ]);
                } else if ($http_status_code == 500) {
                    return response()->json([
                        'success' => true,
                        'response_code' => 500,
                        'remarks' => $remarks
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'response_code' => 500,
                    'remarks' => 'Unable to register mandate'
                ]);
            }
        } else if ($debitOrder->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->mandateRegistrationPayload($debitOrder);

            //arr response
            $arrResponse = $this->bankApi->mandateRegistrationRequest($payload);

            if ($arrResponse) {
                $success = true;
                $http_status_code = $arrResponse[0];
                $remarks = $arrResponse[1];

                if ($http_status_code == 201) {
                    return response()->json([
                        'success' => true,
                        'response_code' => 201,
                        'remarks' => $remarks
                    ]);
                } else if ($http_status_code == 500) {
                    return response()->json([
                        'success' => true,
                        'response_code' => 500,
                        'remarks' => $remarks
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'response_code' => 500,
                    'remarks' => 'Unable to register mandate'
                ]);
            }
        } else if ($debitOrder->channel == "YAS") {
            //create payload
            $payload = $this->payloadService->yasMandateRegistrationPayload($debitOrder);

            //arr response
            $arrResponse = $this->bankApi->yasMandateRegistrationRequest($payload, $debitOrder);

            if ($arrResponse) {
                $success = true;
                $response_code = $arrResponse[0];
                $remarks = $arrResponse[1];
                $redirect_url = $arrResponse[2] ?? null;

                return response()->json([
                    'success' => $success,
                    'response_code' => $response_code,
                    'remarks' => $remarks,
                    'redirect_url' => $redirect_url
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

    public function cancelMandate(DebitOrder $debitOrder)
    {
        if ($debitOrder->channel == "CRDB") {
            //requestId
            $requestId = Str::uuid()->toString();
            $debitOrder->request_id = $requestId;
            $debitOrder->save();

            //create payload
            $payload = $this->payloadService->CRDBMandateCancellationPayload($debitOrder, $requestId);

            //create data
            $data['payload'] = $payload;
            $data['requestId'] = $requestId;
            $data['mandateReferenceNumber'] = $debitOrder->reference;

            $arrResponse = $this->bankApi->crdbMandateCancellationRequest($data);

            return [
                'success' => (int) $arrResponse[0] < 400,
                'response_code' => $arrResponse[0],
                'remarks' => $arrResponse[1],
                'provider_response_code' => $arrResponse[2] ?? null,
            ];
        } else if ($debitOrder->channel == "NMB") {
            //cancel mandate
            Log::debug("== NMB Cancel Mandate Payload ==");

            //create payload
            $payload = $this->payloadService->mandateCancellationPayload($debitOrder);

            $arrResponse = $this->bankApi->mandateCancellationRequest($payload);

            return [
                'success' => (int) $arrResponse[0] < 400,
                'response_code' => $arrResponse[0],
                'remarks' => $arrResponse[1],
                'provider_response_code' => $arrResponse[0],
            ];
        }

        return [
            'success' => false,
            'response_code' => 400,
            'remarks' => 'Mandate cancellation is not supported for this payment channel.',
            'provider_response_code' => null,
        ];
    }
}
