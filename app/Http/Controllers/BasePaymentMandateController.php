<?php

namespace App\Http\Controllers;

use App\Classes\Messaging;
use App\Models\DebitOrder;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use App\Services\MandateActionService;
use App\Services\PaymentMandateQueryService;
use App\Services\PaymentMandatePartyService;
use App\Services\PaymentMandateWorkflowService;

abstract class BasePaymentMandateController extends Controller
{
    public function __construct(
        protected MandateActionService $mandateActionService,
        protected DeductionService $deductionService,
        protected BankPayloadService $payloadService,
        protected Messaging $messaging,
        protected BankApiService $bankApi,
        protected PaymentMandateWorkflowService $paymentMandateWorkflowService,
        protected PaymentMandateQueryService $paymentMandateQueryService,
        protected PaymentMandatePartyService $paymentMandatePartyService
    ) {
        $this->middleware('auth');
    }
}
