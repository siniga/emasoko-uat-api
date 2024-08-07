<?php

namespace App\Services\ProviderRequest;

use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\PartnerAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NmbRequest extends BankMnoProcedure
{

    public function __construct()
    {
        parent::__construct();
        $this->corporateRegistrationUrl = 'http://127.0.0.1:8001/responses/corporateRegistration/responseSuccess.json';
        $this->corporateRegistrationStatusUrl = 'http://127.0.0.1:8001/responses/corporateStatus/responseSuccess.json';

        $this->mandateRegistrationUrl = 'http://127.0.0.1:8001/responses/mandateRegistration/responseSuccess.json';
        $this->mandateRegistrationStatusUrl = 'http://127.0.0.1:8001/responses/mandateStatus/responseSuccess.json';
        $this->mandateOTPRegenerationUrl = 'http://127.0.0.1:8001/responses/otpCreate/responseSuccess.json';
        $this->mandateStatusAndValidationUrl = 'http://127.0.0.1:8001/responses/otpVerify/responseSuccess.json';

        $this->mandateCancellationUrl = 'http://127.0.0.1:8001/responses/mandateCancellation/responseSuccess.json';
        $this->mandateOTPCancellationRegenerationUrl = 'http://127.0.0.1:8001/responses/otpCreate/responseSuccess.json';
        $this->mandateCancellationStatusUrl = 'http://127.0.0.1:8001/responses/mandateStatus/responseSuccess.json'; // NOT EXISTS
        $this->mandateCancellationStatusAndValidationUrl = 'http://127.0.0.1:8001/responses/otpVerify/responseSuccess.json';

        $this->collectionFileReceptionAndValidationUrl = 'http://127.0.0.1:8001/responses/collectionsCreate/responseSuccess.json';
        $this->collectionStatusUrl = 'http://127.0.0.1:8001/responses/collectionsStatus/responseSuccessComplete.json';

        // $this->mandateAccountingResponseUrl = asset('response');
        // $this->mandateCheckerBypassUrl = asset('responses/ok.json');
    }

    public static function userNameFormat($user)
    {
        return str_replace(" ", ".", trim(ucwords($user->name ?? null)));
    }

    ## corporateRegistration
    public function corporateRegistration(Request $request, PartnerAccount $partnerAccount)
    {
        $partner =  $partnerAccount->partner;
        $myRequest = new Request();
        $currency = $partnerAccount->currency;
        $region = $partner->region ?? null;
        $myRequest->merge([
            "CorporateReference" => $partner->reference,
            "name" => $partner->business_name,
            "corporateShortCode" => $partner->short_code,
            "clientCode" => $partner->short_code, // What the difference 
            "customerCode" => "000000822", // What is this
            "accountName" => $partnerAccount->account_name,
            "primaryAccount" => $partnerAccount->account_number,
            "branchSortCode" => $partnerAccount->sort_code,
            "currency" => $currency->short_code ?? null,
            "accountStatus" => null,
            "address1" => $region->name ?? null,
            "address2" => $partner->physical_address ?? null,
            "address3" => $partner->postal_address ?? null,
            "phoneNumber" => $partner->phone ?? null,
            "email" => $partner->email ?? null,
            "secondaryMobile" => $partner->phone2 ?? null,
            "secondaryEmail" => $partner->email2 ?? null,
            "suffix" => "Large",
            "deductionsActive" => "false",
            "description" => "",
            "bookedBalance" => 500000,
            "creditLimit" => 0,
            "debitLimit" => 0,
            "coporateLimit" => 500000000,
            "isOverallLimit" => true,
            "defaultAmount" => 500,
            "defaultPercentage" => 0,
            "createdBy" => static::userNameFormat($partner->createdBy ?? null),
            "verifiedBy" => static::userNameFormat($partner->verifiedBy ?? null),
            "createdDate" => $partner->created_at,
            "verifyDate" => $partner->updated_at
        ]);
        $response = $this->sendRequest($this->corporateRegistrationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "201") {
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
    public function corporateRegistrationStatus(Request $request, PartnerAccount $partnerAccount)
    {
        $partner =  $partnerAccount->partner;
        $myRequest = new Request();
        $myRequest->merge([
            "CorporateReference" => $partner->reference,
        ]);
        $response = $this->sendRequest($this->corporateRegistrationStatusUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "240") {
            $partnerAccount->is_verified = 1;
            $partnerAccount->verified_at = Carbon::now();
            $partnerAccount->rejected_at = null;
            $partnerAccount->response_sms = $response->data->data->response[0]->responseDescription;
            $partnerAccount->send_verification = 0;
            $partnerAccount->save();
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        } else if ($data->response[0]->responseCode == "249") {
            $partnerAccount->is_verified = 0;
            $partnerAccount->verified_at = null;
            $partnerAccount->rejected_at = null;
            $partnerAccount->response_sms = $data->response[0]->responseDescription;
            $partnerAccount->send_verification = 2;
            $partnerAccount->save();
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        $partnerAccount->is_verified = 0;
        $partnerAccount->verified_at = null;
        $partnerAccount->rejected_at = Carbon::now();
        $partnerAccount->response_sms = $response->data->data->response[0]->responseDescription;
        $partnerAccount->send_verification = 0;
        $partnerAccount->save();
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }


    ## mandateRegistration
    public function mandateRegistration(Request $request, DebitOrder $debitOrder)
    {
        $customerAccount = $debitOrder->customerAccount;
        $customer = $customerAccount->customer;
        $partner = $debitOrder->partner;
        $partnerAccount = $debitOrder->partnerAccount;
        if (!$partner) {
            return response()->json([
                'status' => false,
                'message' => 'The debit order is not associated with any partner.',
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        if (!$partnerAccount) {
            return response()->json([
                'status' => false,
                'message' => 'Partner has not configured the NBC account.',
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        $myRequest = new Request();
        $myRequest->merge([
            "mandateReferenceNumber" => $debitOrder->uuid,
            "mandateStartDate" => Carbon::parse($debitOrder->start_date)->format('Y-m-d H:i:s'),
            "mandateEndDate" => Carbon::parse($debitOrder->end_date)->format('Y-m-d H:i:s'),
            "directDebitType" => $debitOrder->debit_type,
            "debitAccountHolderName" => $customerAccount->account_name,
            "debitAccountNumber" => $customerAccount->account_number,
            "debitAccountBankCode" => $partnerAccount->sort_code, // What is this
            "debitAccountHolderAddress1" => $customer->address,
            "debitAccountHolderAddress2" => null,
            "debitAccountHolderAddress3" => null,
            "corporateReference" => $partner->reference,
            "creditAccountNumber" => $partnerAccount->account_number,
            "creditAccountName" => $partnerAccount->account_name,
            "creditAccountShortName" => $partner->short_code, // thisi
            "beneficiaryBank" => "NMB",
            "accountStatus" => "Account Is Valid",
            "creditAccountAddressLine1" => $partner->region->name ?? null,
            "creditAccountAddressLine2" => $partner->physical_address ?? null,
            "amount" => $debitOrder->total_amount,
            "maxAmount" => $debitOrder->max_installment_amount,
            "minAmount" =>  $debitOrder->min_installment_amount,
            "createdBy" => static::userNameFormat($debitOrder->userCreated),
            "verifiedBy" => static::userNameFormat(auth()->user()),
            "createdDate" => $debitOrder->created_at,
            "verifyDate" => Carbon::now()->format('Y-m-d H:i:s'),
            "file" => [
                "fileName" => "Evidance Of Aggrement",
                "fileType" => "pdf",
                "fileData" => pdf_to_base64(public_path('assets/uploads/docs/' . $debitOrder->evidence_of_aggrement))
            ]
        ]);
        $response = $this->sendRequest($this->mandateRegistrationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "201") {
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
    public function mandateRegistrationStatus(Request $request, DebitOrder $debitOrder)
    {
        $myRequest = new Request();
        $myRequest->merge([
            "mandateReferenceNumber" => $debitOrder->uuid,
        ]);
        $response = $this->sendRequest($this->mandateRegistrationStatusUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "240") {
            $debitOrder->status = "APPROVED";
            $debitOrder->save();
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
    public function mandateOTPRegeneration(Request $request, DebitOrder $debitOrder)
    {
        $customerAccount = $debitOrder->customerAccount;
        $myRequest = new Request();
        $myRequest->merge([
            "caseReference" => $debitOrder->uuid,
            "accountNumber" => $customerAccount->account_number,
            "processName" => "EMakato Mandate Registration",
            "makerName" => static::userNameFormat(auth()->user()),
        ]);
        $response = $this->sendRequest($this->mandateOTPRegenerationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "240") {
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
    public function mandateStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        $myRequest = new Request();
        $myRequest->merge([
            "caseReference" => $debitOrder->uuid,
            "processName" => $request->processName,
            "otpPin" => $request->otpPin,
        ]);
        $response = $this->sendRequest($this->mandateStatusAndValidationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "210") {
            $debitOrder->status = "verified";
            $debitOrder->save();
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => [
                    'debit_order' => $debitOrder
                ]
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    ## mandateCancellation
    public function mandateCancellation(Request $request, DebitOrder $debitOrder)
    {
        $partner = $debitOrder->partner;
        $cancellationApprovedBy = $debitOrder->cancellationApprovedBy;
        $cancellationStartedBy = $debitOrder->cancellationStartedBy;
        $myRequest = new Request();
        $myRequest->merge([
            "mandateReferenceNumber" => $debitOrder->uuid,
            "corporateReference" => $partner->reference,
            "cancelReason" => $request->reason,
            "createdBy" => static::userNameFormat($cancellationStartedBy),
            "verifiedBy" => static::userNameFormat($cancellationApprovedBy),
            "createdDate" => $$debitOrder->cancellationStartedAt,
            "verifyDate" => $$debitOrder->cancellationApprovedAt,
        ]);
        $response = $this->sendRequest($this->mandateCancellationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "201") {
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateCancellationStatus(Request $request, DebitOrder $debitOrder)
    {
        $myRequest = new Request();
        $myRequest->merge([
            "mandateReferenceNumber" => $debitOrder->uuid, // cancelation status link
        ]);
        $response = $this->sendRequest($this->mandateRegistrationStatusUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "240") {
            $debitOrder->status = "cancelled";
            $debitOrder->save();
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
    public function mandateOTPCancellationRegeneration(Request $request, DebitOrder $debitOrder)
    {
        $customerAccount = $debitOrder->customerAccount;
        $cancellationApprovedBy = $debitOrder->cancellationApprovedBy;
        $myRequest = new Request();
        $myRequest->merge([
            "caseReference" => $debitOrder->uuid,
            "accountNumber" => $customerAccount->account_number,
            "processName" => "EMakato Mandate Cancellation", // Process Name
            "makerName" => static::userNameFormat($cancellationApprovedBy),
        ]);
        $response = $this->sendRequest($this->mandateOTPRegenerationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "240") {
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateCancellationStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        $myRequest = new Request();
        $myRequest->merge([
            "caseReference" => $debitOrder->uuid,
            "processName" => "MandateCancellation", // Process Name
            "otpPin" => $request->otpPin,
        ]);
        $response = $this->sendRequest($this->mandateStatusAndValidationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($data->response[0]->responseCode == "210") {
            return response()->json([
                'status' => true,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        return response()->json([
            'status' => false,
            'message' => $data->response[0]->responseDescription,
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    ## collectionFileReceptionAndValidation
    public function collectionFileReceptionAndValidation(Request $request, CollectionBatch $batch)
    {
        $myRequest = new Request();
        $myRequest->merge([
            "collectionReference" => $batch->id,
            "corporateReference" => $batch->corporateReference,
            "selectedPostingDate" => $batch->selectedPostingDate,
            "createdBy" => static::userNameFormat($batch->createdUser),
            "verifiedBy" => static::userNameFormat($batch->createdUser),
            "createdDate" => $batch->createdDate,
            "verifyDate" => $batch->verifyDate,
            "file" => [
                "fileName" => "Payment Collection $batch->id",
                "fileType" => "CSV",
                "fileData" => content_to_base64($batch->csvs)
            ]
        ]);
        $response = $this->sendRequest($this->collectionFileReceptionAndValidationUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($response->status) {
            if ($data->response[0]->responseCode == "201") {
                $batch->batch_processed = 0;
                $batch->response = $data->response[0]->responseDescription;
                $batch->save();
                return response()->json([
                    'status' => true,
                    'message' => $data->response[0]->responseDescription,
                    'data' => []
                ], Response::HTTP_BAD_REQUEST)->getData();
            }
            $batch->batch_processed = 0;
            $batch->response = $data->response[0]->responseDescription;
            $batch->save();
            return response()->json([
                'status' => false,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        $batch->batch_processed = 0;
        $batch->response = 'Error on registration Occured!!';
        $batch->save();
        return response()->json([
            'status' => false,
            'message' => 'Error Occured!!',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
    
    public function collectionStatus(Request $request, CollectionBatch $batch)
    {
        $myRequest = new Request();
        $myRequest->merge([
            "collectionReference" => $batch->id,
        ]);
        $response = $this->sendRequest($this->collectionStatusUrl, $myRequest->all(), 'POST');
        $response = $response->getData();
        $data = $response->data->data;
        if ($response->status) {
            if ($data->response[0]->responseCode == "230") {
                $batchDebits = base64_csv_to_array($data->file->fileData, ';');
                foreach ($batchDebits as $batchDebit) {
                    $serialNumber = $batchDebit['SERIAL No.'];
                    $status = $batchDebit['Status'];
                    CollectionDebitOrder::where("id", $serialNumber)->update(['status' => strtolower($status)]);
                }
                $batch->batch_processed = 1;
                $batch->response = $data->response[0]->responseDescription;
                $batch->save();
                return response()->json([
                    'status' => true,
                    'message' => $data->response[0]->responseDescription,
                    'data' => [
                        $data->file
                    ]
                ], Response::HTTP_BAD_REQUEST)->getData();
            }
            $batch->batch_processed = 0;
            $batch->response = $data->response[0]->responseDescription;
            $batch->save();
            return response()->json([
                'status' => false,
                'message' => $data->response[0]->responseDescription,
                'data' => []
            ], Response::HTTP_BAD_REQUEST)->getData();
        }
        $batch->batch_processed = 0;
        $batch->response = 'Error Occured!!';
        $batch->save();
        return response()->json([
            'status' => false,
            'message' => 'Error Occured!!',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
}
