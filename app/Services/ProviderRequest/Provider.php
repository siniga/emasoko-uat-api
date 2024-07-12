<?php

namespace App\Services\ProviderRequest;

use App\Models\CollectionBatch;
use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Exception;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Http\Response;

abstract class Provider
{
    ## corporateRegistration
    abstract public function corporateRegistration(Request $request, PartnerAccount $partnerAccount);
    abstract public function corporateRegistrationStatus(Request $request, PartnerAccount $partnerAccount);

    ## mandateRegistration
    abstract public function mandateRegistration(Request $request, DebitOrder $debitOrder);
    abstract public function mandateOTPRegeneration(Request $request, DebitOrder $debitOrder);
    abstract public function mandateRegistrationStatus(Request $request, DebitOrder $debitOrder);
    abstract public function mandateStatusAndValidation(Request $request, DebitOrder $debitOrder);

    ## mandate Cancellation
    abstract public function mandateCancellation(Request $request, DebitOrder $debitOrder);
    abstract public function mandateOTPCancellationRegeneration(Request $request, DebitOrder $debitOrder);
    abstract public function mandateCancellationStatus(Request $request, DebitOrder $debitOrder);
    abstract public function mandateCancellationStatusAndValidation(Request $request, DebitOrder $debitOrder);

    ## Collection Processing
    abstract public function collectionFileReceptionAndValidation(Request $request, CollectionBatch $batch);
    abstract public function collectionStatus(Request $request, CollectionBatch $batch);

    ## Collection Accounting Response 
    abstract public function mandateAccountingResponse(Request $request);
    abstract public function mandateCheckerBypass(Request $request);

    public static function sendRequest($url, $request = [], $method = "POST", $headers = ['accept' => 'application/json'])
    {
        try {
            $client = new Client();
            $response = $client->request($method, $url, [
                'headers' => $headers,
                'form_params' => $request
            ]);
            $resultData =  $response->getBody();
            if (!empty($resultData)) {
                $results = json_decode($resultData, TRUE);
                if ($results['status'] = '200') {
                    return response()->json([
                        'status' => true,
                        'message' => '',
                        'data' => $results
                    ], Response::HTTP_OK);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Not valid response',
                        'data' => []
                    ], Response::HTTP_BAD_REQUEST);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Empty Response',
                    'data' => []
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
