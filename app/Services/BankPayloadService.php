<?php

namespace App\Services;

use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Carbon\Carbon;

class BankPayloadService
{
    public function __construct()
    {
    }

    public static function userNameFormat($user)
    {
        return str_replace(" ", ".", trim(ucwords($user->name ?? null)));
    }

    //create corporateRegistration Payload
    public function corporateRegistrationPayload(Partner $partner, PartnerAccount $partnerAccount)
    {
        //partner information
        $currency = $partnerAccount->currency;
        $region = $partner->region ?? null;

        $payload = [
            "corporateReference" => $partner->reference,
            "name" => $partner->business_name,
            "corporateShortCode" => $partner->short_code,
            "clientCode" => $partner->client_code,
            "customerCode" => $partnerAccount->customer_code,
            "accountName" => $partnerAccount->account_name,
            "primaryAccount" => $partnerAccount->account_number,
            "branchSortCode" => $partnerAccount->branch_code,
            "currency" => $currency->short_code ?? null,
            "accountStatus" => null,
            "address1" => $region->name ?? null,
            "address2" => $partner->physical_address ?? null,
            "address3" => $region->name ?? null,
            "phoneNumber" => $partner->phone ?? null,
            "email" => $partner->email ?? null,
            "secondaryMobile" => $partner->phone ?? null,
            "secondaryEmail" => $partner->email ?? null,
            "suffix" => "Large",
            "deductionsActive" => "false",
            "description" => "Valid corporate account",
            "bookedBalance" => 0,
            "creditLimit" => 0,
            "debitLimit" => 0,
            "corporateLimit" => $partnerAccount->corporate_limit,
            "isOverallLimit" => true,
            "defaultAmount" => 500,
            "defaultPercentage" => 0,
            "createdBy" => static::userNameFormat($partner->createdBy ?? null),
            "verifiedBy" => static::userNameFormat($partner->verifiedBy ?? null),
            "createdDate" => date("Y-m-d H:i:s", strtotime($partner->created_at)),
            "verifyDate" => date("Y-m-d H:i:s", strtotime($partner->updated_at))
        ];

        return $payload;
    }

    //corporate status payload
    public function corporateStatusPayload(Partner $partner)
    {
        $payload = [
            "corporateReference" =>  $partner->reference
        ];

        return $payload;
    }

    //create mandate registration Payload
    public function mandateRegistrationPayload(DebitOrder $debitOrder)
    {
        $customerAccount = $debitOrder->customerAccount;
        $customer = $customerAccount->customer;
        $partner = $debitOrder->partner;
        $partnerAccount = $debitOrder->partnerAccount;

        //create payload
        $payload = [
            "mandateReferenceNumber" => $debitOrder->reference,
            "mandateStartDate" => Carbon::parse($debitOrder->start_date)->format('Y-m-d\TH:i:s.v'),
            "mandateEndDate" => Carbon::parse($debitOrder->end_date)->format('Y-m-d\TH:i:s.v'),
            "directDebitType" => strtoupper($debitOrder->periodicity),
            "debitAccountHolderName" => strtoupper($customerAccount->account_name),
            "debitAccountNumber" => $customerAccount->account_number,
            "debitAccountBankCode" => $customerAccount->bank_code,
            "debitAccountHolderAddress1" => $customer->address,
            "debitAccountHolderAddress2" => $customer->address,
            "debitAccountHolderAddress3" => $customer->address,
            "corporateReference" => $partner->reference,
            "creditAccountNumber" => $partnerAccount->account_number,
            "creditAccountName" => $partnerAccount->account_name,
            "creditAccountShortName" => $partner->short_code,
            "beneficiaryBank" => "NMB",
            "accountStatus" => "Account Is Valid",
            "creditAccountAddressLine1" => $partner->region->name ?? null,
            "creditAccountAddressLine2" => $partner->physical_address ?? null,
            "amount" => $debitOrder->total_amount,
            "maxAmount" => $debitOrder->max_installment_amount,
            "minAmount" =>  $debitOrder->min_installment_amount,
            "createdBy" => static::userNameFormat($debitOrder->userCreated),
            "verifiedBy" => static::userNameFormat(auth()->user()),
            "createdDate" => Carbon::parse($debitOrder->created_at)->format('Y-m-d H:i:s'),
            "verifyDate" => Carbon::parse($debitOrder->created_at)->format('Y-m-d H:i:s'),
            "file" => [
                "fileName" => "Evidence Of Aggrement",
                "fileType" => "pdf",
                "fileData" => pdf_to_base64(public_path('assets/uploads/docs/pdf/'. $debitOrder->evidence_of_aggrement))
            ]
        ];

        return $payload;
    }

    //mandate status Payload
    public function mandateStatusPayload(DebitOrder $debitOrder)
    {
        //create payload
        $payload = [
            "mandateReferenceNumber" => $debitOrder->reference
        ];

        return $payload;
    }

    //create mandate cancellation Payload
    public function mandateCancellationPayload(DebitOrder $debitOrder)
    {
        $partner = $debitOrder->partner;

        //create payload
        $payload = [
            "mandateReferenceNumber" => $debitOrder->reference,
            "corporateReference" => $partner->reference,
            "cancelReason" => $debitOrder->cancellation_reason,
            "createdBy" => static::userNameFormat($debitOrder->cancellationStartedBy),
            "verifiedBy" => static::userNameFormat($debitOrder->cancellationApprovedBy),
            "createdDate" => $debitOrder->cancellationStartedAt,
            "verifyDate" => $debitOrder->cancellationApprovedAt
        ];

        return $payload;
    }

    //create otp payload
    public function createOtpPayload(OtpRequest $otpRequest)
    {
        $payload = [
            "caseReference" => $otpRequest->reference,
            "accountNumber" => $otpRequest->account_number,
            "processName" => "EMakato Mandate Registration",
            "makerName" => static::userNameFormat($otpRequest->created_by)
        ];

        return $payload;
    }

    //verify otp payload
    public function verifyOtpPayload(OtpRequest $otpRequest, $otpPin)
    {
        $payload = [
            "caseReference" => $otpRequest->reference,
            "processName" => "EMakato Mandate Registration",
            "otpPin" => $otpPin
        ];

        return $payload;
    }

    //create collection  payload
    public function collectionPayload(CollectionBatch $batch, $csv)
    {
        $payload = [
            "collectionReference" => $batch->collectionReference,
            "corporateReference" => $batch->corporateReference,
            "selectedPostingDate" => $batch->selectedPostingDate,
            "createdBy" => static::userNameFormat($batch->createdUser),
            "verifiedBy" => static::userNameFormat($batch->createdUser),
            "createdDate" => Carbon::parse($batch->createdDate)->format('Y-m-d H:i:s'),
            "verifyDate" => Carbon::parse($batch->verifyDate)->format('Y-m-d H:i:s'),
            "file" => [
                "fileName" => "Payment_Collection_$batch->id",
                "fileType" => "CSV",
                "fileData" => content_to_base64($csv)
            ]
        ];

        return $payload;
    }

    // create collection status payload
    public function collectionStatusPayload(CollectionBatch $batch)
    {
        $payload = [
            "collectionReference" => $batch->collectionReference
        ];

        return $payload;
    }
}
