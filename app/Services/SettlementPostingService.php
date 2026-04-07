<?php

namespace App\Services;

use App\Jobs\CRDBMandateCancellationJob;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\PaymentSettlement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SettlementPostingService
{
    public function post(array $payload): PaymentSettlement
    {
        return DB::transaction(function () use ($payload) {
            /** @var Installment|null $installment */
            $installment = $payload['installment'] ?? null;
            $debitOrder = $payload['debit_order'] ?? $installment?->debitOrder;
            if (!$debitOrder) {
                throw new \InvalidArgumentException('A debit order is required for settlement posting.');
            }
            $batch = $payload['batch'] ?? null;
            $line = $payload['line'] ?? null;
            $amount = (float) ($payload['amount'] ?? 0);
            $normalizedStatus = strtoupper((string) ($payload['normalized_status'] ?? 'FAILED'));
            $rawStatus = strtoupper((string) ($payload['raw_status'] ?? $normalizedStatus));
            $remarks = $payload['remarks'] ?? null;
            $effectiveDate = !empty($payload['effective_payment_date'])
                ? Carbon::parse($payload['effective_payment_date'])->toDateString()
                : ($installment?->effective_payment_date ?: now()->toDateString());
            $applyToBalances = (bool) ($payload['apply_to_balances'] ?? true);

            $openingInstallmentBalance = (float) ($installment->outstanding_amount ?? $installment->installment_amount ?? 0);
            $openingDebitOrderBalance = (float) ($debitOrder->outstanding_amount ?? 0);

            $paymentReference = (string) ($payload['payment_reference'] ?? $this->defaultPaymentReference($batch, $installment));
            $externalReference = $payload['external_reference'] ?? null;
            $transactionAt = !empty($payload['transaction_at']) ? Carbon::parse($payload['transaction_at']) : now();
            $sourceChannel = strtoupper((string) ($payload['channel'] ?? $debitOrder->channel ?? 'SYSTEM'));
            $rawPayloadHash = $payload['raw_payload_hash'] ?? null;

            $existing = PaymentSettlement::where([
                'channel' => $sourceChannel,
                'payment_reference' => $paymentReference,
                'installment_id' => $installment?->id,
            ])->first();

            if ($existing) {
                return $existing;
            }

            $settlement = new PaymentSettlement();

            $settlement->collection_batch_id = $batch?->id;
            $settlement->collection_debit_order_id = $line?->id;
            $settlement->debit_order_id = $debitOrder->id;
            $settlement->installment_id = $installment?->id;
            $settlement->mandate_reference = $debitOrder->reference;
            $settlement->payment_reference = $paymentReference;
            $settlement->external_reference = $externalReference;
            $settlement->raw_status = $rawStatus;
            $settlement->normalized_status = $normalizedStatus;
            $settlement->settlement_type = $payload['settlement_type'] ?? null;
            $settlement->applied_to_balances = $applyToBalances;
            $settlement->reconciliation_status = $applyToBalances ? 'POSTED' : 'PENDING';
            $settlement->opening_installment_balance = $openingInstallmentBalance;
            $settlement->opening_debit_order_balance = $openingDebitOrderBalance;
            $settlement->expected_amount = (float) ($payload['expected_amount'] ?? $installment?->installment_amount ?? 0);
            $settlement->paid_amount = $amount;
            $settlement->effective_payment_date = $effectiveDate;
            $settlement->transaction_at = $transactionAt;
            $settlement->remarks = $remarks;
            $settlement->raw_payload_hash = $rawPayloadHash;

            if ($applyToBalances && in_array($normalizedStatus, [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL], true)) {
                $this->storeInstallmentPayment($debitOrder->reference, $paymentReference, $amount, $effectiveDate);
            }

            $settlement->save();

            if (!$applyToBalances || !$installment) {
                $settlement->closing_installment_balance = $openingInstallmentBalance;
                $settlement->closing_debit_order_balance = $openingDebitOrderBalance;
                $settlement->settlement_type = $settlement->settlement_type ?: 'UNALLOCATED_PAYMENT';
                $settlement->save();

                return $settlement;
            }

            $this->recalculateInstallment($installment, $settlement, $normalizedStatus, $amount, $effectiveDate, $remarks, $paymentReference);
            $this->recalculateDebitOrder($debitOrder, $installment);

            if ($line) {
                $line->status = $normalizedStatus;
                $line->bank_status = $rawStatus;
                $line->bank_message = $remarks;
                $line->response_hash = $rawPayloadHash;
                $line->processed_at = now();
                $line->save();
            }

            $settlement->closing_installment_balance = (float) $installment->outstanding_amount;
            $settlement->closing_debit_order_balance = (float) $debitOrder->outstanding_amount;
            $settlement->settlement_type = $settlement->settlement_type ?: $this->settlementType($settlement, $installment);
            $settlement->save();

            return $settlement;
        });
    }

    public function postUnallocated(array $payload): PaymentSettlement
    {
        $payload['apply_to_balances'] = false;
        $payload['normalized_status'] = $payload['normalized_status'] ?? PaymentSettlement::STATUS_UNALLOCATED;
        $payload['settlement_type'] = $payload['settlement_type'] ?? 'UNALLOCATED_PAYMENT';

        return $this->post($payload);
    }

    protected function storeInstallmentPayment(string $mandateReference, string $paymentReference, float $amount, string $effectiveDate): void
    {
        $payment = InstallmentPayment::firstOrNew([
            'mandate_reference' => $mandateReference,
            'payment_reference' => $paymentReference,
        ]);

        $payment->amount = $amount;
        $payment->payment_at = $effectiveDate;
        $payment->save();
    }

    protected function recalculateInstallment(
        Installment $installment,
        PaymentSettlement $settlement,
        string $normalizedStatus,
        float $amount,
        string $effectiveDate,
        ?string $remarks,
        string $paymentReference
    ): void {
        $successfulPaid = (float) PaymentSettlement::query()
            ->where('installment_id', $installment->id)
            ->whereIn('normalized_status', [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL])
            ->sum('paid_amount');

        $installment->current_balance = $successfulPaid;
        $installment->outstanding_amount = max((float) $installment->installment_amount - $successfulPaid, 0);
        $installment->last_paid_amount = $amount;
        $installment->payment_reference = $paymentReference;
        $installment->effective_payment_date = $effectiveDate;
        $installment->remarks = $remarks;

        if (in_array($normalizedStatus, [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL], true)) {
            $installment->status = $installment->outstanding_amount <= 0 ? 'CLOSED' : 'PARTIAL_PAID';
        } else {
            $installment->status = $successfulPaid > 0 ? 'PARTIAL_PAID' : $normalizedStatus;
        }

        $installment->save();
    }

    protected function recalculateDebitOrder(DebitOrder $debitOrder, Installment $installment): void
    {
        $paidAmount = (float) PaymentSettlement::query()
            ->where('debit_order_id', $debitOrder->id)
            ->whereIn('normalized_status', [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL])
            ->sum('paid_amount');

        $debitOrder->current_balance = $paidAmount;
        $debitOrder->paid_amount = $paidAmount;
        $debitOrder->outstanding_amount = max((float) $debitOrder->total_amount - $paidAmount, 0);

        if ($debitOrder->outstanding_amount <= 0) {
            $debitOrder->contract_status = 'CLOSED';
        } else {
            $debitOrder->contract_status = 'OPEN';
        }

        $debitOrder->save();

        if ($debitOrder->outstanding_amount <= 0) {
            $this->autoCancelSettledMandate($debitOrder);
        }

        if ($installment->status === 'CLOSED' && !empty($debitOrder->next_debit_date)) {
            app(DeductionService::class)->updateNextDebitDate($debitOrder);
        }
    }

    protected function settlementType(PaymentSettlement $settlement, Installment $installment): string
    {
        if (!in_array($settlement->normalized_status, [PaymentSettlement::STATUS_SUCCESS, PaymentSettlement::STATUS_PARTIAL], true)) {
            return 'FAILED_COLLECTION';
        }

        return $installment->status === 'CLOSED' ? 'FULL_SETTLEMENT' : 'PARTIAL_SETTLEMENT';
    }

    protected function defaultPaymentReference(?CollectionBatch $batch, ?Installment $installment): string
    {
        if ($batch && $installment) {
            return "COL-{$batch->collectionReference}-{$installment->id}";
        }

        if ($installment) {
            return "SET-{$installment->debit_order_id}-{$installment->id}-" . now()->format('YmdHis');
        }

        return "SET-" . now()->format('YmdHis');
    }

    protected function autoCancelSettledMandate(DebitOrder $debitOrder): void
    {
        if (strtoupper((string) $debitOrder->channel) !== 'CRDB') {
            return;
        }

        if ((int) ($debitOrder->cancelled ?? 0) === 1) {
            return;
        }

        if (in_array((string) $debitOrder->lifecycle_status, [
            DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED,
            DebitOrder::LIFECYCLE_CANCELLED,
        ], true)) {
            return;
        }

        if ($debitOrder->outstanding_amount > 0) {
            return;
        }

        try {
            $requestId = (string) Str::uuid();
            $debitOrder->cancellation_reason = $debitOrder->cancellation_reason ?: 'Automatic cancellation after full settlement';
            $debitOrder->cancellationStartedBy = $debitOrder->cancellationStartedBy ?: $debitOrder->created_by;
            $debitOrder->cancellationStartedAt = $debitOrder->cancellationStartedAt ?: now();
            $debitOrder->cancellationApprovedBy = $debitOrder->cancellationApprovedBy ?: $debitOrder->created_by;
            $debitOrder->cancellationApprovedAt = $debitOrder->cancellationApprovedAt ?: now();
            $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED;
            $debitOrder->remarks = 'Automatic CRDB cancellation queued after full settlement';
            $debitOrder->save();

            $payload = app(BankPayloadService::class)->CRDBMandateCancellationPayload($debitOrder, $requestId);

            dispatch(new CRDBMandateCancellationJob([
                'payload' => $payload,
                'requestId' => $requestId,
                'mandateReferenceNumber' => $debitOrder->reference,
            ]));
        } catch (\Throwable $e) {
            Log::error('Failed to queue automatic CRDB cancellation after settlement', [
                'debit_order_id' => $debitOrder->id,
                'reference' => $debitOrder->reference,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
