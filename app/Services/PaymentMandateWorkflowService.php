<?php

namespace App\Services;

use App\Jobs\PollNmbMandateStatusJob;
use App\Models\CustomerAccount;
use App\Models\CustomerPartner;
use App\Models\DebitOrder;
use App\Models\DebitOrderRequest;
use App\Models\Installment;
use App\Models\OtpRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentMandateWorkflowService
{
    public function __construct(
        protected DeductionService $deductionService,
        protected BankPayloadService $payloadService,
        protected BankApiService $bankApi,
        protected MandateActionService $mandateActionService,
        protected ExternalCallbackService $externalCallbackService
    ) {}

    public function createDraft(array $attributes, int $userId): DebitOrder
    {
        return DB::transaction(function () use ($attributes, $userId) {
            $loanAmount = $this->normalizeAmount($attributes['loan_amount'] ?? 0);
            $installmentAmount = $this->normalizeAmount($attributes['installment_amount'] ?? 0);
            $minInstallmentAmount = $this->normalizeAmount($attributes['min_installment_amount'] ?? 0);
            $maxInstallmentAmount = $this->normalizeAmount($attributes['max_installment_amount'] ?? 0);

            $partnerId = $attributes['partner_id'] ?? $attributes['provider_id'] ?? null;
            $customerAccountUuid = $attributes['customer_account_uuid'];
            $customerAccount = CustomerAccount::where('uuid', '=', $customerAccountUuid)->firstOrFail();
            $debitOrderUuid = $attributes['debit_order_uuid'] ?? (string) Str::uuid();

            $debitOrderQuery = DebitOrder::where(['uuid' => $debitOrderUuid]);

            if ($debitOrderQuery->exists()) {
                $debitOrder = $debitOrderQuery->first();
            } else {
                $debitOrder = DebitOrder::firstOrNew([
                    'customer_account_id' => $customerAccount->id,
                    'partner_id' => $partnerId,
                    'approved' => 0,
                ]);
            }

            $debitOrder->uuid = $debitOrderUuid;
            $debitOrder->total_amount = $loanAmount;
            $debitOrder->outstanding_amount = $loanAmount;
            if ($debitOrder->created_by === null) {
                $debitOrder->created_by = $userId;
            }
            $debitOrder->reference = Carbon::now()->format('dmY') . $this->deductionService->getMandateReference();
            $debitOrder->debit_type = $attributes['debit_type'];
            $debitOrder->installment_amount = $installmentAmount;
            $debitOrder->min_installment_amount = $minInstallmentAmount;
            $debitOrder->max_installment_amount = $maxInstallmentAmount;
            $debitOrder->periodicity = $attributes['periodicity'];
            $debitOrder->installment_number = $attributes['number_of_installment'];
            $debitOrder->start_date = $attributes['start_date'];
            $debitOrder->next_debit_date = $attributes['start_date'];
            $debitOrder->deduction_end_date = 'totalLoanCovered';
            $debitOrder->otp_code = rand(10000, 99999);
            $debitOrder->approved = 0;
            $debitOrder->channel = $customerAccount->account->slug;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_DRAFT;
            $debitOrder->save();

            $debitOrder->end_date = $this->deductionService->calculateEndDate($debitOrder);
            $debitOrder->save();

            $customerPartner = CustomerPartner::firstOrNew([
                'customer_id' => $customerAccount->customer->id,
                'partner_id' => $partnerId,
            ]);
            $customerPartner->created_by = $userId;
            $customerPartner->save();

            $debitOrder->aggrement_evidence = $this->createEvidence($debitOrder);
            $debitOrder->status = 100;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_DRAFT;
            $debitOrder->save();

            return $debitOrder;
        });
    }

    public function saveDraft(string $uuid): DebitOrder
    {
        $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();
        $debitOrder->status = 100;
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_DRAFT;
        $debitOrder->save();

        return $debitOrder;
    }

    public function submit(string $uuid): array
    {
        $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();
        $debitOrder->status = 200;
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_SUBMITTED;
        $debitOrder->save();

        $jsonResponse = $this->mandateActionService->registerMandate($debitOrder);
        $response = $jsonResponse->getData(true);

        Log::debug('== Mandate response data ==');
        Log::debug($response);

        if (!$response['success'] || (int) $response['response_code'] >= 400) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
        } elseif ((int) $response['response_code'] === 240) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
        } elseif ((int) $response['response_code'] === 202) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_VERIFIED;
        } elseif ((int) $response['response_code'] === 201) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_AWAITING_OTP;
        } else {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_SUBMITTED;
        }
        $debitOrder->save();
        $debitOrder->refresh();

        if ($response['success'] && $debitOrder->channel !== 'NMB') {
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_SUBMITTED');
        } elseif (!$response['success']) {
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_FAILED');
        }

        return [
            'success' => $response['success'],
            'channel' => $debitOrder->channel,
            'response_code' => $response['response_code'],
            'success_msg' => $response['remarks'],
            'redirect_url' => $response['redirect_url'] ?? null,
            'lifecycle_status' => $debitOrder->fresh()->lifecycle_status,
        ];
    }

    public function resendOtp(string $uuid, int $userId): array
    {
        $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();
        $otpRequest = new OtpRequest();
        $otpRequest->reference = $debitOrder->reference;
        $otpRequest->account_name = $debitOrder->customerAccount->account_name;
        $otpRequest->account_number = $debitOrder->customerAccount->account_number;
        $otpRequest->action = 'create';
        $otpRequest->created_by = $userId;
        $otpRequest->save();

        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_AWAITING_OTP;
        $debitOrder->save();

        return $this->sendOtpRequest($debitOrder, $otpRequest);
    }

    public function verifyOtp(string $uuid, string $otpPin, int $userId): array
    {
        $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();
        $otpRequest = new OtpRequest();
        $otpRequest->reference = $debitOrder->reference;
        $otpRequest->account_number = $debitOrder->customerAccount->account_number;
        $otpRequest->action = 'verify';
        $otpRequest->created_by = $userId;
        $otpRequest->save();

        $success = true;
        $responseCode = 200;
        $remarks = '';

        if ($debitOrder->channel === 'NMB') {
            $payload = $this->payloadService->verifyOtpPayload($otpRequest, $otpPin);
            $arrResponse = $this->bankApi->verifyOtpRequest($payload, $debitOrder->reference);

            if ($arrResponse) {
                $responseCode = $arrResponse[0];
                $remarks = $arrResponse[1];
            } else {
                $success = false;
                $remarks = 'API system error, contact eMakato support team.';
                $responseCode = 500;
            }
        }

        $debitOrder->refresh();
        if ($success && $responseCode === 202) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_VERIFIED;
            if ($debitOrder->channel === 'NMB') {
                PollNmbMandateStatusJob::dispatch($debitOrder->id)->delay(now()->addSeconds(30));
            }
        } elseif (!$success || $responseCode >= 400) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
        }
        $debitOrder->save();
        $debitOrder->refresh();

        if ($success && $responseCode === 202 && $debitOrder->channel !== 'NMB') {
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_VERIFIED');
        } elseif (!$success || $responseCode >= 400) {
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_FAILED');
        }

        return [
            'success' => $success,
            'channel' => $debitOrder->channel,
            'response_code' => $responseCode,
            'success_msg' => $remarks,
            'lifecycle_status' => $debitOrder->fresh()->lifecycle_status,
        ];
    }

    public function fetchStatus(string $uuid): array
    {
        $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();

        $success = true;
        $responseCode = 200;
        $remarks = $debitOrder->remarks ?? '';

        if ($debitOrder->channel === 'NMB') {
            $payload = $this->payloadService->mandateStatusPayload($debitOrder);
            $arrResponse = $this->bankApi->mandateStatusRequest($payload);

            if ($arrResponse) {
                $responseCode = $arrResponse[0];
                $remarks = $arrResponse[1];

                if ($responseCode == 240) {
                    $this->activate($debitOrder);
                } elseif ($responseCode == 202) {
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_VERIFIED;
                    $debitOrder->save();
                } elseif ($responseCode == 201) {
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_AWAITING_OTP;
                    $debitOrder->save();
                }
            } else {
                $success = false;
                $remarks = 'API system error, contact eMakato support team.';
                $responseCode = 500;
            }
        }

        if (!$success || $responseCode >= 400) {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
            $debitOrder->save();
        }

        return [
            'success' =>$success,
            'channel' => $debitOrder->channel,
            'response_code' => $responseCode,
            'success_msg' => $remarks,
            'lifecycle_status' => $debitOrder->fresh()->lifecycle_status,
        ];
    }

    public function activate(DebitOrder $debitOrder): DebitOrder
    {
        $this->deductionService->createInstallment($debitOrder);
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
        $debitOrder->save();
        $debitOrder->refresh();

        if ($debitOrder->channel !== 'NMB') {
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_ACTIVE');
        }

        return $debitOrder->fresh();
    }

    public function requestCancellation(DebitOrder $debitOrder, int $userId, ?string $reason = null, bool $immediate = false): DebitOrder|DebitOrderRequest
    {
        $this->ensureCancellationCanBeRequested($debitOrder);

        if ($immediate) {
            $debitOrder->cancellation_reason = $reason;
            $debitOrder->cancellationStartedBy = $userId;
            $debitOrder->cancellationStartedAt = now();
            $debitOrder->cancellationApprovedBy = $userId;
            $debitOrder->cancellationApprovedAt = now();
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED;
            $debitOrder->save();
            $debitOrder->refresh();
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_CANCELLATION_REQUESTED');

            $this->mandateActionService->cancelMandate($debitOrder);

            return $debitOrder;
        }

        $request = $this->createRequest($debitOrder, 'CANCELLATION', $userId);
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED;
        $debitOrder->save();
        $debitOrder->refresh();
        $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_CANCELLATION_REQUESTED');

        return $request;
    }

    private function ensureCancellationCanBeRequested(DebitOrder $debitOrder): void
    {
        $lifecycleStatus = $debitOrder->resolved_lifecycle_status;
        $hasPendingRequest = DebitOrderRequest::query()
            ->where('debit_order_id', $debitOrder->id)
            ->where('request_type', 'CANCELLATION')
            ->where('status', 'PENDING')
            ->exists();

        if ($lifecycleStatus === DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED || $hasPendingRequest) {
            throw ValidationException::withMessages([
                'mandate' => 'Mandate cancellation has already been requested.',
            ]);
        }

        if ($lifecycleStatus === DebitOrder::LIFECYCLE_CANCELLED || (int) $debitOrder->cancelled === 1) {
            throw ValidationException::withMessages([
                'mandate' => 'Mandate is already cancelled.',
            ]);
        }

        $canRequest = in_array($lifecycleStatus, [
            DebitOrder::LIFECYCLE_SUBMITTED,
            DebitOrder::LIFECYCLE_AWAITING_OTP,
            DebitOrder::LIFECYCLE_VERIFIED,
            DebitOrder::LIFECYCLE_ACTIVE,
        ], true);
        $canRetryFailedCancellation = $lifecycleStatus === DebitOrder::LIFECYCLE_FAILED
            && !empty($debitOrder->cancellationStartedAt);

        if (!$canRequest && !$canRetryFailedCancellation) {
            throw ValidationException::withMessages([
                'mandate' => 'Mandate is not eligible for cancellation.',
            ]);
        }
    }

    public function cancel(DebitOrder $debitOrder, int $userId, string $reason): array
    {
        $this->ensureCancellationCanBeRequested($debitOrder);

        $debitOrder->cancellation_reason = $reason;
        $debitOrder->cancellationStartedBy = $userId;
        $debitOrder->cancellationStartedAt = now();
        $debitOrder->cancellationApprovedBy = $userId;
        $debitOrder->cancellationApprovedAt = now();
        $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED;
        $debitOrder->save();
        $debitOrder->refresh();

        $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_CANCELLATION_REQUESTED');

        $response = $this->mandateActionService->cancelMandate($debitOrder);
        $debitOrder->refresh();

        if ($response['success']) {
            if ($debitOrder->lifecycle_status !== DebitOrder::LIFECYCLE_CANCELLED) {
                $debitOrder->cancelled = 1;
                $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;
                $debitOrder->remarks = $response['remarks'];
                $debitOrder->save();
                $debitOrder->refresh();
            }

            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_CANCELLED');
        } else {
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
            $debitOrder->remarks = $response['remarks'];
            $debitOrder->save();
            $debitOrder->refresh();
        }

        return [
            'success' => $response['success'],
            'channel' => $debitOrder->channel,
            'response_code' => $response['response_code'],
            'provider_response_code' => $response['provider_response_code'] ?? null,
            'success_msg' => $response['remarks'],
            'lifecycle_status' => $debitOrder->lifecycle_status,
        ];
    }

    public function approveCancellation(DebitOrderRequest $debitOrderRequest, int $userId): DebitOrderRequest
    {
        $debitOrderRequest->status = 'APPROVED';
        $debitOrderRequest->approved_by = $userId;
        $debitOrderRequest->approved_at = Carbon::now();
        $debitOrderRequest->save();

        $debitOrder = $debitOrderRequest->debitOrder;
        if ($debitOrder) {
            $debitOrder->cancelled = 1;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLED;
            $debitOrder->save();
            $debitOrder->refresh();
            $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_CANCELLED');
            $this->mandateActionService->cancelMandate($debitOrder);
        }

        return $debitOrderRequest;
    }

    public function requestDeletion(DebitOrder $debitOrder, int $userId): DebitOrderRequest
    {
        return $this->createRequest($debitOrder, 'DELETION', $userId);
    }

    public function approveDeletion(DebitOrderRequest $debitOrderRequest, int $userId): DebitOrderRequest
    {
        $debitOrderRequest->status = 'APPROVED';
        $debitOrderRequest->approved_by = $userId;
        $debitOrderRequest->approved_at = Carbon::now();
        $debitOrderRequest->save();

        $debitOrder = $debitOrderRequest->debitOrder;
        if ($debitOrder) {
            $debitOrder->deleted = 1;
            $debitOrder->save();
            $this->mandateActionService->cancelMandate($debitOrder);
        }

        return $debitOrderRequest;
    }

    public function requestClosing(DebitOrder $debitOrder, int $userId): DebitOrderRequest
    {
        return $this->createRequest($debitOrder, 'CLOSING', $userId);
    }

    public function approveClosing(DebitOrderRequest $debitOrderRequest, int $userId): DebitOrderRequest
    {
        $debitOrderRequest->status = 'APPROVED';
        $debitOrderRequest->approved_by = $userId;
        $debitOrderRequest->approved_at = Carbon::now();
        $debitOrderRequest->save();

        $debitOrder = $debitOrderRequest->debitOrder;
        if ($debitOrder) {
            $debitOrder->closed = 1;
            $debitOrder->contract_status = 'CLOSED';
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CLOSED;
            $debitOrder->save();
            $this->mandateActionService->cancelMandate($debitOrder);
        }

        return $debitOrderRequest;
    }

    public function canReopenClosedMandate(DebitOrder $debitOrder): bool
    {
        $isClosed = $debitOrder->resolved_lifecycle_status === DebitOrder::LIFECYCLE_CLOSED
            || (int) $debitOrder->closed === 1
            || strtoupper((string) $debitOrder->contract_status) === 'CLOSED';

        return $isClosed
            && (int) $debitOrder->cancelled === 0
            && (int) $debitOrder->deleted === 0
            && (int) $debitOrder->archived === 0
            && (float) $debitOrder->outstanding_amount > 0
            && $debitOrder->installments()
                ->where('status', '!=', 'CLOSED')
                ->where('outstanding_amount', '>', 0)
                ->exists();
    }

    public function reopenClosedMandate(DebitOrder $debitOrder): DebitOrder
    {
        $debitOrder = DB::transaction(function () use ($debitOrder) {
            $debitOrder = DebitOrder::query()
                ->whereKey($debitOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$this->canReopenClosedMandate($debitOrder)) {
                throw ValidationException::withMessages([
                    'mandate' => 'This mandate is not eligible to be reopened.',
                ]);
            }

            $nextInstallment = $debitOrder->installments()
                ->where('status', '!=', 'CLOSED')
                ->where('outstanding_amount', '>', 0)
                ->orderBy('payment_date')
                ->firstOrFail();
            $nextDebitDate = Carbon::parse($nextInstallment->payment_date);

            $debitOrder->closed = 0;
            $debitOrder->contract_status = 'OPEN';
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
            $debitOrder->status = 240;
            $debitOrder->next_debit_date = $nextDebitDate->isPast()
                ? now()->toDateString()
                : $nextDebitDate->toDateString();
            $debitOrder->save();

            return $debitOrder->fresh();
        });

        $this->externalCallbackService->dispatchMandateStatus($debitOrder, 'MANDATE_ACTIVE', [
            'reopened_by_admin' => true,
        ]);

        return $debitOrder;
    }

    public function archive(DebitOrder $debitOrder, int $userId): DebitOrderRequest
    {
        return $this->createRequest($debitOrder, 'ARCHIVE', $userId);
    }

    public function approveArchive(DebitOrderRequest $debitOrderRequest, int $userId): DebitOrderRequest
    {
        $debitOrderRequest->status = 'APPROVED';
        $debitOrderRequest->approved_by = $userId;
        $debitOrderRequest->approved_at = Carbon::now();
        $debitOrderRequest->save();

        $debitOrder = $debitOrderRequest->debitOrder;
        if ($debitOrder) {
            $debitOrder->archived = 1;
            $debitOrder->deleted = 1;
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ARCHIVED;
            $debitOrder->save();
        }

        return $debitOrderRequest;
    }

    private function sendOtpRequest(DebitOrder $debitOrder, OtpRequest $otpRequest): array
    {
        $success = true;
        $responseCode = 200;
        $remarks = '';

        if ($debitOrder->channel === 'NMB') {
            $payload = $this->payloadService->createOtpPayload($otpRequest);
            $arrResponse = $this->bankApi->createOtpRequest($payload);

            if ($arrResponse) {
                $responseCode = $arrResponse[0];
                $remarks = $arrResponse[1];
            } else {
                $success = false;
                $remarks = 'API system error, contact eMakato support team.';
                $responseCode = 500;
            }
        }

        return [
            'success' => $success,
            'channel' => $debitOrder->channel,
            'response_code' => $responseCode,
            'success_msg' => $remarks,
            'lifecycle_status' => $debitOrder->fresh()->lifecycle_status,
        ];
    }

    private function createRequest(DebitOrder $debitOrder, string $requestType, int $userId): DebitOrderRequest
    {
        $request = new DebitOrderRequest();
        $request->debit_order_id = $debitOrder->id;
        $request->request_type = $requestType;
        $request->requested_by = $userId;
        $request->status = 'PENDING';
        $request->requested_at = Carbon::now();
        $request->save();

        return $request;
    }

    private function normalizeAmount($value): float
    {
        return floatval(str_replace(',', '', (string) $value));
    }

    private function createEvidence(DebitOrder $debitOrder): string
    {
        view()->share('debit_order', $debitOrder);

        $pdf = Pdf::loadView('payment_mandate.pdf.evidence');
        $filename = "{$debitOrder->reference}.pdf";

        $pdf->save('assets/uploads/pdf/' . $filename);

        return $filename;
    }
}
