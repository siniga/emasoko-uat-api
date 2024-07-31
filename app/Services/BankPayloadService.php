<?php

namespace App\Services;
use App\Models\Partner;
use App\Models\PartnerAccount;


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
            "corporateLimit" => 500000000,
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

    //create corporateStatus Payload
    // public function corporateStatusPayload(Partner $partner, PartnerAccount $partnerAccount)
    // {

    // }

}
