<?php

namespace App\Services\ProviderRequest;

use App\Models\CollectionBatch;
use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BankMnoProcedure extends Provider
{
    public $targetProvider;

    public $corporateRegistrationUrl;
    public $corporateRegistrationStatusUrl;

    public $mandateRegistrationUrl;
    public $mandateRegistrationStatusUrl;
    public $mandateOTPRegenerationUrl;
    public $mandateStatusAndValidationUrl;

    public $mandateCancellationUrl;
    public $mandateOTPCancellationRegenerationUrl;
    public $mandateCancellationStatusUrl;
    public $mandateCancellationStatusAndValidationUrl;

    public $collectionFileReceptionAndValidationUrl;
    public $collectionStatusUrl;

    public $mandateAccountingResponseUrl;
    public $mandateCheckerBypassUrl;

    public function __construct()
    {
        $this->corporateRegistrationUrl = asset('responses/ok.json');
        $this->corporateRegistrationStatusUrl = asset('responses/ok.json');

        $this->mandateRegistrationUrl = asset('responses/ok.json');
        $this->mandateRegistrationStatusUrl = asset('responses/ok.json');
        $this->mandateOTPRegenerationUrl = asset('responses/ok.json');
        $this->mandateStatusAndValidationUrl = asset('responses/ok.json');

        $this->mandateCancellationUrl = asset('responses/ok.json');
        $this->mandateOTPCancellationRegenerationUrl = asset('responses/ok.json');
        $this->mandateCancellationStatusUrl = asset('responses/ok.json');
        $this->mandateCancellationStatusAndValidationUrl = asset('responses/ok.json');

        $this->collectionFileReceptionAndValidationUrl = asset('responses/ok.json');
        $this->collectionStatusUrl = asset('responses/ok.json');

        $this->mandateAccountingResponseUrl = asset('responses/ok.json');
        $this->mandateCheckerBypassUrl = asset('responses/ok.json');
    }

    ## corporateRegistration
    public function corporateRegistration(Request $request, PartnerAccount $partnerAccount)
    {
        $response = $this->sendRequest($this->corporateRegistrationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function corporateRegistrationStatus(Request $request, PartnerAccount $partnerAccount)
    {
        $response = $this->sendRequest($this->corporateRegistrationStatusUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    ## mandateRegistration
    public function mandateRegistration(Request $request, DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateRegistrationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function mandateRegistrationStatus(Request $request, DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateRegistrationStatusUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function mandateOTPRegeneration(Request $request, DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateOTPRegenerationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function mandateStatusAndValidation(Request $request, DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateStatusAndValidationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    ## mandate Cancellation
    public function mandateCancellation(Request $request,  DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateCancellationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function mandateOTPCancellationRegeneration(Request $request,  DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateOTPCancellationRegenerationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function mandateCancellationStatus(Request $request,  DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateCancellationStatusUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    public function mandateCancellationStatusAndValidation(Request $request,  DebitOrder $debitOrder)
    {
        $response = $this->sendRequest($this->mandateCancellationStatusAndValidationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    ## Collection Processing
    public function collectionFileReceptionAndValidation(Request $request, CollectionBatch $batch)
    {
        $response = $this->sendRequest($this->collectionFileReceptionAndValidationUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }
    public function collectionStatus(Request $request, CollectionBatch $batch)
    {
        $response = $this->sendRequest($this->collectionStatusUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }

    ## Collection AccountingResponse 
    public function mandateAccountingResponse(Request $request)
    {
        $response = $this->sendRequest($this->mandateAccountingResponseUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }
    public function mandateCheckerBypass(Request $request)
    {
        $response = $this->sendRequest($this->mandateCheckerBypassUrl, $request->all(), 'POST');
        $data = $response->getData();
        return $data;
    }
}
