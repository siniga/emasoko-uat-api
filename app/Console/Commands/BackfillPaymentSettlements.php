<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillPaymentSettlements extends Command
{
    private const LEGACY_BALANCE_ROUNDING_TOLERANCE = 1.00;

    protected $signature = 'payments:backfill-settlements
                            {--apply : Persist settlements and recalculated balances}
                            {--debit-order= : Process only one debit order ID}
                            {--chunk=200 : Number of debit orders processed per chunk}';

    protected $description = 'Backfill historical payment settlements and recalculate mandate balances';

    private int $legacyBalancesImported = 0;
    private int $debitOrdersRecalculated = 0;
    private int $unresolvedDebitOrders = 0;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $chunkSize = max((int) $this->option('chunk'), 1);
        $debitOrderId = $this->option('debit-order');

        $this->components->info($apply
            ? 'Applying historical settlement backfill.'
            : 'Dry run only. No records or balances will be changed.');

        DebitOrder::query()
            ->when($debitOrderId, fn ($query) => $query->whereKey($debitOrderId))
            ->where(function ($query) {
                $query->where('current_balance', '>', 0)
                    ->orWhereHas('installments', fn ($installments) => $installments->where('current_balance', '>', 0));
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $debitOrders) use ($apply) {
                foreach ($debitOrders as $debitOrder) {
                    $this->processDebitOrder($debitOrder, $apply);
                }
            });

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Legacy installment balances imported', $this->legacyBalancesImported],
                ['Debit orders recalculated', $this->debitOrdersRecalculated],
                ['Debit orders requiring review', $this->unresolvedDebitOrders],
            ]
        );

        if (!$apply) {
            $this->components->warn('Run again with --apply after reviewing this summary.');
        }

        return $this->unresolvedDebitOrders > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processDebitOrder(DebitOrder $debitOrder, bool $apply): void
    {
        $runner = function () use ($debitOrder, $apply) {
            $debitOrder = DebitOrder::query()
                ->when($apply, fn ($query) => $query->lockForUpdate())
                ->findOrFail($debitOrder->id);

            $installments = Installment::query()
                ->where('debit_order_id', $debitOrder->id)
                ->when($apply, fn ($query) => $query->lockForUpdate())
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();

            $plannedByInstallment = [];
            $this->importLegacyInstallmentBalances($debitOrder, $installments, $plannedByInstallment, $apply);

            $ledgerPaid = (float) PaymentSettlement::query()
                ->where('debit_order_id', $debitOrder->id)
                ->whereIn('normalized_status', $this->successfulStatuses())
                ->sum('paid_amount') + array_sum($plannedByInstallment);

            if ($ledgerPaid + self::LEGACY_BALANCE_ROUNDING_TOLERANCE < (float) $debitOrder->current_balance) {
                $this->unresolvedDebitOrders++;
                $this->components->warn(
                    "Skipping debit order {$debitOrder->id}: ledger {$ledgerPaid} is below stored paid balance {$debitOrder->current_balance}."
                );

                return;
            }

            if (!$apply) {
                $this->debitOrdersRecalculated++;

                return;
            }

            $this->recalculateInstallments($installments);
            $this->recalculateDebitOrder($debitOrder);
            $this->debitOrdersRecalculated++;
        };

        $apply ? DB::transaction($runner) : $runner();
    }

    private function importLegacyInstallmentBalances(
        DebitOrder $debitOrder,
        Collection $installments,
        array &$plannedByInstallment,
        bool $apply
    ): void {
        foreach ($installments as $installment) {
            $settled = (float) PaymentSettlement::query()
                ->where('installment_id', $installment->id)
                ->whereIn('normalized_status', $this->successfulStatuses())
                ->sum('paid_amount');
            $settled += $plannedByInstallment[$installment->id] ?? 0;

            $missingAmount = round((float) $installment->current_balance - $settled, 2);

            if ($missingAmount <= 0) {
                continue;
            }

            $reference = "LEGACY-INSTALLMENT-{$installment->id}";
            $alreadyImported = PaymentSettlement::query()
                ->where('installment_id', $installment->id)
                ->where('payment_reference', $reference)
                ->exists();

            if ($alreadyImported) {
                continue;
            }

            $this->legacyBalancesImported++;

            if ($apply) {
                PaymentSettlement::create($this->settlementAttributes(
                    $debitOrder,
                    $installment,
                    $reference,
                    $missingAmount,
                    $installment->effective_payment_date ?: $installment->payment_date,
                    'LEGACY_INSTALLMENT_BALANCE'
                ));
            } else {
                $plannedByInstallment[$installment->id] = ($plannedByInstallment[$installment->id] ?? 0) + $missingAmount;
            }
        }
    }

    private function settlementAttributes(
        DebitOrder $debitOrder,
        Installment $installment,
        string $paymentReference,
        float $amount,
        $paymentDate,
        string $type
    ): array {
        $paymentDate = Carbon::parse($paymentDate ?: $installment->payment_date)->toDateString();

        return [
            'channel' => strtoupper((string) ($debitOrder->channel ?: 'SYSTEM')),
            'debit_order_id' => $debitOrder->id,
            'installment_id' => $installment->id,
            'mandate_reference' => $debitOrder->reference,
            'payment_reference' => $paymentReference,
            'raw_status' => PaymentSettlement::STATUS_SUCCESS,
            'normalized_status' => PaymentSettlement::STATUS_SUCCESS,
            'settlement_type' => $type,
            'applied_to_balances' => true,
            'reconciliation_status' => 'POSTED',
            'expected_amount' => (float) $installment->installment_amount,
            'paid_amount' => $amount,
            'effective_payment_date' => $paymentDate,
            'transaction_at' => Carbon::parse($paymentDate)->startOfDay(),
            'remarks' => 'Historical payment backfill',
        ];
    }

    private function recalculateInstallments(Collection $installments): void
    {
        foreach ($installments as $installment) {
            $paid = (float) PaymentSettlement::query()
                ->where('installment_id', $installment->id)
                ->whereIn('normalized_status', $this->successfulStatuses())
                ->sum('paid_amount');

            $installment->current_balance = $paid;
            $installment->outstanding_amount = max((float) $installment->installment_amount - $paid, 0);

            if ($paid > 0) {
                $installment->status = $installment->outstanding_amount <= 0 ? 'CLOSED' : 'PARTIAL_PAID';
            }

            $installment->save();
        }
    }

    private function recalculateDebitOrder(DebitOrder $debitOrder): void
    {
        $paid = (float) Installment::query()
            ->where('debit_order_id', $debitOrder->id)
            ->sum('current_balance');

        $debitOrder->current_balance = $paid;
        $debitOrder->paid_amount = $paid;
        $debitOrder->outstanding_amount = max((float) $debitOrder->total_amount - $paid, 0);
        $debitOrder->contract_status = $debitOrder->outstanding_amount <= 0 ? 'CLOSED' : 'OPEN';
        $debitOrder->save();
    }

    private function successfulStatuses(): array
    {
        return [
            PaymentSettlement::STATUS_SUCCESS,
            PaymentSettlement::STATUS_PARTIAL,
        ];
    }
}
