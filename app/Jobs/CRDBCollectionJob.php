<?php

namespace App\Jobs;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Services\DeductionService;
use App\Services\ExternalCallbackService;
use App\Services\SettlementPostingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CRDBCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected DeductionService $deductionService;
    protected SettlementPostingService $settlementPostingService;
    protected ExternalCallbackService $externalCallbackService;
    protected DebitOrder $debitOrder;
    private array $postData;

    /**
     * Create a new job instance.
     */
    public function __construct(DebitOrder $debitOrder, array $postData)
    {
        $this->debitOrder = $debitOrder;
        $this->postData = $postData;
    }

    /**
     * Execute the job.
     */
    public function handle(
        DeductionService $deductionService,
        SettlementPostingService $settlementPostingService,
        ExternalCallbackService $externalCallbackService
    ): void {
        $this->deductionService = $deductionService;
        $this->settlementPostingService = $settlementPostingService;
        $this->externalCallbackService = $externalCallbackService;

        //payment responses
        $transferResponse = $this->postData['data']['transferResponse'];
        $collectionDate = $this->postData['data']['collectionDate'];

        if (strtolower($transferResponse) == "success") {
            // get success data
            $phoneNumber = $this->postData['data']['phoneNumber'];
            $accountNumber = $this->postData['data']['accountNumber'];
            $amount = $this->postData['data']['amount'];
            $transactionReference = $this->postData['data']['transactionReference'];
            $transactionDateTime = $this->postData['data']['transactionDateTime'];

            if (!PaymentSettlement::where('channel', 'CRDB')
                ->where(function ($query) use ($transactionReference) {
                    $query->where('external_reference', $transactionReference)
                        ->orWhere('payment_reference', $transactionReference);
                })
                ->exists()) {
                $this->postCrdbSuccessfulPayment(
                    $this->debitOrder,
                    (float) $amount,
                    $transactionReference,
                    $transactionDateTime ?: $collectionDate,
                    $collectionDate,
                    strtoupper((string) $transferResponse),
                    md5(json_encode($this->postData))
                );
            } else {
                Log::info('CRDB payment status skipped: transaction reference already settled.', [
                    'debit_order_id' => $this->debitOrder->id,
                    'mandate_reference' => $this->debitOrder->reference,
                    'crdb_reference' => $this->postData['data']['reference'] ?? null,
                    'transaction_reference' => $transactionReference,
                ]);
            }
        } else if (strtolower($transferResponse) == "insufficient_amount") {
            // select installment for this month
            $installments = Installment::where('debit_order_id', "=", $this->debitOrder->id)
                ->whereMonth('payment_date', "=", date('m', strtotime($collectionDate)))
                ->whereYear('payment_date', "=", date('Y', strtotime($collectionDate)));

            if ($installments->exists()) {
                $installment = $installments->first();
                $paymentReference = "CRDB-FAILED-{$this->debitOrder->id}-" . date('Ym', strtotime($collectionDate));
                $existingSettlement = PaymentSettlement::where([
                    'channel' => 'CRDB',
                    'payment_reference' => $paymentReference,
                    'installment_id' => $installment->id,
                ])->first();

                $settlement = $this->settlementPostingService->post([
                    'channel' => 'CRDB',
                    'debit_order' => $this->debitOrder,
                    'installment' => $installment,
                    'amount' => 0,
                    'raw_status' => strtoupper((string) $transferResponse),
                    'normalized_status' => PaymentSettlement::STATUS_FUNDSCHECK,
                    'remarks' => 'Insufficient funds',
                    'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                    'transaction_at' => $collectionDate,
                    'payment_reference' => $paymentReference,
                    'raw_payload_hash' => md5(json_encode($this->postData)),
                ]);

                $this->dispatchCrdbCollectionCallback($settlement, !$existingSettlement
                    || strtoupper((string) $existingSettlement->normalized_status) !== PaymentSettlement::STATUS_FUNDSCHECK);

                Log::debug("== update installment : insufficient amount ===");
            }
        }
    }

    protected function postCrdbSuccessfulPayment(
        DebitOrder $debitOrder,
        float $paymentAmount,
        string $transactionReference,
        string $transactionAt,
        string $collectionDate,
        string $rawStatus,
        string $rawPayloadHash
    ): void {
        $remainingAmount = $paymentAmount;
        $sequence = 1;

        while ($remainingAmount > 0) {
            $installment = $this->nextPayableCrdbInstallment($debitOrder, $collectionDate);

            if (!$installment) {
                $this->settlementPostingService->postUnallocated([
                    'channel' => 'CRDB',
                    'debit_order' => $debitOrder,
                    'amount' => $remainingAmount,
                    'raw_status' => $rawStatus,
                    'normalized_status' => PaymentSettlement::STATUS_UNALLOCATED,
                    'settlement_type' => 'OVERPAYMENT_UNALLOCATED',
                    'remarks' => 'Excess deduction held for reconciliation',
                    'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                    'transaction_at' => $transactionAt,
                    'payment_reference' => $transactionReference . '-EXCESS',
                    'external_reference' => $transactionReference,
                    'raw_payload_hash' => $rawPayloadHash,
                ]);

                return;
            }

            $openingBalance = (float) ($installment->outstanding_amount ?? $installment->installment_amount ?? 0);

            if ($openingBalance <= 0) {
                $installment->status = 'CLOSED';
                $installment->save();
                continue;
            }

            $amountToApply = min($remainingAmount, $openingBalance);
            $remainingAmount -= $amountToApply;
            $normalizedStatus = $amountToApply < $openingBalance
                ? PaymentSettlement::STATUS_PARTIAL
                : PaymentSettlement::STATUS_SUCCESS;

            $settlement = $this->settlementPostingService->post([
                'channel' => 'CRDB',
                'debit_order' => $debitOrder,
                'installment' => $installment,
                'amount' => $amountToApply,
                'raw_status' => $rawStatus,
                'normalized_status' => $normalizedStatus,
                'remarks' => $normalizedStatus === PaymentSettlement::STATUS_PARTIAL ? 'Partial amount paid' : 'Amount paid',
                'effective_payment_date' => date('Y-m-d', strtotime($collectionDate)),
                'transaction_at' => $transactionAt,
                'payment_reference' => $sequence === 1 ? $transactionReference : "{$transactionReference}-PART-{$sequence}",
                'external_reference' => $transactionReference,
                'raw_payload_hash' => $rawPayloadHash,
            ]);

            $this->dispatchCrdbCollectionCallback($settlement);

            $sequence++;
        }
    }

    protected function nextPayableCrdbInstallment(DebitOrder $debitOrder, string $collectionDate): ?Installment
    {
        $debitOrder->refresh();

        if ((float) ($debitOrder->outstanding_amount ?? 0) <= 0) {
            return null;
        }

        $installment = Installment::where('debit_order_id', $debitOrder->id)
            ->whereNotIn('status', ['CLOSED'])
            ->where('outstanding_amount', '>', 0)
            ->orderByRaw("CASE WHEN status = 'PARTIAL_PAID' THEN 0 ELSE 1 END")
            ->orderBy('payment_date')
            ->orderBy('installment_order')
            ->first();

        if ($installment) {
            return $installment;
        }

        $installmentAmount = $this->deductionService->getInstallmentAmount($debitOrder);
        $lastInstallmentOrder = $this->deductionService->getLastInstallmentOrder($debitOrder);

        $installment = new Installment();
        $installment->debit_order_id = $debitOrder->id;
        $installment->installment_order = $lastInstallmentOrder + 1;
        $installment->installment_amount = $installmentAmount;
        $installment->current_balance = 0;
        $installment->outstanding_amount = $installmentAmount;
        $installment->payment_date = date('Y-m-d', strtotime($collectionDate));
        $installment->status = 'OPEN';
        $installment->remarks = 'CRDB amount paid';
        $installment->save();

        return $installment;
    }

    protected function dispatchCrdbCollectionCallback(PaymentSettlement $settlement, bool $shouldDispatch = true): void
    {
        if (!$shouldDispatch) {
            Log::info('External CRDB collection callback skipped: settlement status did not change.', [
                'settlement_id' => $settlement->id,
                'installment_id' => $settlement->installment_id,
                'payment_reference' => $settlement->payment_reference,
                'normalized_status' => $settlement->normalized_status,
            ]);
            return;
        }

        if (!$settlement->installment_id) {
            Log::warning('External CRDB collection callback skipped: settlement has no installment.', [
                'settlement_id' => $settlement->id,
                'payment_reference' => $settlement->payment_reference,
                'normalized_status' => $settlement->normalized_status,
            ]);
            return;
        }

        $settlement->loadMissing('installment.debitOrder.partner');

        if (!$settlement->installment) {
            Log::warning('External CRDB collection callback skipped: settlement installment could not be loaded.', [
                'settlement_id' => $settlement->id,
                'installment_id' => $settlement->installment_id,
                'payment_reference' => $settlement->payment_reference,
            ]);
            return;
        }

        $eventType = match (strtoupper((string) $settlement->normalized_status)) {
            PaymentSettlement::STATUS_SUCCESS => 'COLLECTION_PAID',
            PaymentSettlement::STATUS_PARTIAL => 'COLLECTION_PARTIAL_PAID',
            PaymentSettlement::STATUS_FUNDSCHECK => 'COLLECTION_FUNDSCHECK',
            PaymentSettlement::STATUS_UNALLOCATED => 'COLLECTION_UNALLOCATED',
            default => 'COLLECTION_FAILED',
        };

        $this->externalCallbackService->dispatchCollectionStatus($settlement->installment, $eventType, [
            'channel' => $settlement->channel,
            'settlement_reference' => $settlement->payment_reference,
            'external_reference' => $settlement->external_reference,
            'bank_status' => $settlement->raw_status,
            'settlement_status' => $settlement->normalized_status,
            'paid_amount' => (float) $settlement->paid_amount,
            'transaction_at' => $settlement->transaction_at
                ? Carbon::parse($settlement->transaction_at)->toIso8601String()
                : null,
        ]);
    }
}
