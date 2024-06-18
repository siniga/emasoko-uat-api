<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BackLog;
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
    protected $account;
    public function __construct(Account $account)
    {
        $this->account = $account;
        $this->targetProvider = [
            "nmb-bank" => new NmbRequest(),
            "vodacom" => new VodacomRequest(),
            "crdb-bank" => new CrdbRequest(),
        ][$account->slug ?? null] ?? null;
    }

    public function corporateRegistration(Request $request, PartnerAccount $partnerAccount)
    {
        $action = "corporateRegistration";
        if ($this->targetProvider) {
            $response = $this->targetProvider->corporateRegistration(request: $request, partnerAccount: $partnerAccount);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $partnerAccount->id,
                'loggable_type' => PartnerAccount::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $partnerAccount->id,
            'loggable_type' => PartnerAccount::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function corporateRegistrationStatus(Request $request, PartnerAccount $partnerAccount)
    {
        $action = "corporateRegistrationStatus";
        if ($this->targetProvider) {
            $response = $this->targetProvider->corporateRegistrationStatus(request: $request, partnerAccount: $partnerAccount);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $partnerAccount->id,
                'loggable_type' => PartnerAccount::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $partnerAccount->id,
            'loggable_type' => PartnerAccount::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateRegistration(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateRegistration";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateRegistration(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateRegistrationStatus(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateRegistrationStatus";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateRegistrationStatus(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateOTPRegeneration(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateOTPRegeneration";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateOTPRegeneration(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateStatusAndValidation";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateStatusAndValidation(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }


    public function mandateCancellation(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateCancellation";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateCancellation(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateCancellationStatus(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateCancellationStatus";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateCancellationStatus(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateOTPCancellationRegeneration(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateOTPCancellationRegeneration";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateOTPCancellationRegeneration(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function mandateCancellationStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        $action = "mandateCancellationStatusAndValidation";
        if ($this->targetProvider) {
            $response = $this->targetProvider->mandateCancellationStatusAndValidation(request: $request, debitOrder: $debitOrder);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $debitOrder->id,
                'loggable_type' => DebitOrder::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $debitOrder->id,
            'loggable_type' => DebitOrder::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function collectionFileReceptionAndValidation(Request $request, CollectionBatch $batch)
    {
        $action = "collectionFileReceptionAndValidation";
        if ($this->targetProvider) {
            $response = $this->targetProvider->collectionFileReceptionAndValidation(request: $request, batch: $batch);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $batch->id,
                'loggable_type' => CollectionBatch::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $batch->id,
            'loggable_type' => CollectionBatch::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }

    public function collectionStatus(Request $request, CollectionBatch $batch)
    {
        $action = "collectionStatus";
        if ($this->targetProvider) {
            $response = $this->targetProvider->collectionStatus(request: $request, batch: $batch);
            BackLog::create([
                'action' => $action,
                'source' => $this->account->id,
                'status' => $response->status,
                'message' => $response->message,
                'loggable_id' => $batch->id,
                'loggable_type' => CollectionBatch::class,
            ]);
            return $response;
        }
        BackLog::create([
            'action' => $action,
            'source' => $this->account->id,
            'status' => false,
            'message' => 'Not valid response',
            'loggable_id' => $batch->id,
            'loggable_type' => CollectionBatch::class,
        ]);
        return response()->json([
            'status' => false,
            'message' => 'Not valid response',
            'data' => []
        ], Response::HTTP_BAD_REQUEST)->getData();
    }
}
