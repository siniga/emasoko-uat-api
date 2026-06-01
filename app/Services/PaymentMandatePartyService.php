<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Support\Str;

class PaymentMandatePartyService
{
    public function upsertCustomerAccount(array $attributes, int $userId, ?string $customerAccountUuid = null): CustomerAccount
    {
        $accountTypeId = (int) $attributes['account_type_id'];
        $accountId = (int) $attributes['account_id'];
        $accountName = $attributes['account_name'] ?? null;
        $accountNumber = $attributes['account_number'] ?? null;
        $secondaryPhone = $attributes['secondary_phone'] ?? null;
        $email = $attributes['email'] ?? null;
        $idNumber = $attributes['id_number'] ?? null;
        $address = $attributes['address'] ?? null;
        $phone = casting_phone($attributes['phone']);
        $customerAccountUuid = $customerAccountUuid ?: (string) Str::uuid();

        $bankCode = null;
        if ($accountTypeId === 1 && $accountNumber) {
            $bankCode = substr($accountNumber, 0, 3);
        }

        $customer = Customer::firstOrNew(['phone' => $phone]);
        $customer->fullname = $accountName ? strtoupper($accountName) : ($customer->fullname ?? null);
        $customer->email = $email;
        $customer->id_number = $idNumber;
        $customer->address = $address;
        if ($customer->created_by === null) {
            $customer->created_by = $userId;
        }
        $customer->updated_by = $userId;
        $customer->save();

        $customerAccount = CustomerAccount::firstOrNew(['uuid' => $customerAccountUuid]);
        $customerAccount->uuid = $customerAccountUuid;
        $customerAccount->account_type_id = $accountTypeId;
        $customerAccount->account_id = $accountId;
        $customerAccount->phone = $phone;
        $customerAccount->secondary_phone = $secondaryPhone;
        $customerAccount->account_name = $accountName;
        $customerAccount->account_number = $accountNumber;
        $customerAccount->bank_code = $bankCode;
        $customerAccount->customer_id = $customer->id;
        if ($customerAccount->created_by === null) {
            $customerAccount->created_by = $userId;
        }
        $customerAccount->updated_by = $userId;
        $customerAccount->save();

        return $customerAccount;
    }
}
