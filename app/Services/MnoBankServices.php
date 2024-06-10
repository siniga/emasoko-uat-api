<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Services\ProviderRequest\BankMnoProcedure;
use App\Services\ProviderRequest\CrdbRequest;
use App\Services\ProviderRequest\NmbRequest;
use App\Services\ProviderRequest\Provider;
use App\Services\ProviderRequest\VodacomRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MnoBankServices extends BankMnoProcedure
{
    public function __construct(Account $account)
    {
        $this->targetProvider = [
            "nmb-bank" => new NmbRequest(),
            "vodacom" => new VodacomRequest(),
            "crdb-bank" => new CrdbRequest(),
        ][$account->slug ?? null] ?? null;
    }

    public function corporateRegistration(Request $request, PartnerAccount $partnerAccount)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->corporateRegistration(request: $request, partnerAccount: $partnerAccount);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function corporateRegistrationStatus(Request $request, PartnerAccount $partnerAccount)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->corporateRegistrationStatus(request: $request, partnerAccount: $partnerAccount);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateRegistration(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateRegistration(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateRegistrationStatus(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateRegistrationStatus(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateOTPRegeneration(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateOTPRegeneration(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateStatusAndValidation(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }


    public function mandateCancellation(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateCancellation(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateCancellationStatus(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateCancellationStatus(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateOTPCancellationRegeneration(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateOTPCancellationRegeneration(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateCancellationStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->mandateCancellationStatusAndValidation(request: $request, debitOrder: $debitOrder);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function collectionFileReceptionAndValidation(Request $request, CollectionBatch $batch)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->collectionFileReceptionAndValidation(request: $request, batch: $batch);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function collectionStatus(Request $request, CollectionBatch $batch)
    {
        if ($this->targetProvider) {
            return $this->targetProvider->collectionStatus(request: $request, batch: $batch);
        }
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
}
