<?php

namespace App\Services\Reports\Mappers;

use App\Models\Installment;
use App\Models\PaymentSettlement;
use Illuminate\Support\Collection;

class CollectionReportMapper extends ReportMapper
{
    public function __construct(protected bool $actual = false)
    {
    }

    public function headings(): array
    {
        return [
            'Service Provider',
            'Mandate Reference',
            'Periodicity',
            'Payment Channel',
            'Account Name',
            'Account Number',
            'Customer Address',
            'Expected Amount',
            $this->actual ? 'Collected Amount' : 'Last Paid Amount',
            'Current Balance',
            'Outstanding Amount',
            'Payment Status',
            'Payment Date',
            'Collection Date',
            'Debit Start Date',
            'Debit End Date',
            'Payment Remarks',
        ];
    }

    public function map(mixed $record): array
    {
        $installment = $this->actual ? $record->installment : $record;
        $debitOrder = $record->debitOrder;
        $rawStatus = $this->actual ? $record->normalized_status : $record->status;
        $status = match ($rawStatus) {
            'OPEN' => 'PENDING COLLECTION',
            'CLOSED', PaymentSettlement::STATUS_SUCCESS => 'PAID',
            PaymentSettlement::STATUS_REVERSED => 'REVERSED',
            'PARTIAL_PAID', PaymentSettlement::STATUS_PARTIAL => 'PARTIAL PAID',
            'FAILED' => 'FAILED',
            'FUNDSCHECK' => 'FUND CHECK',
            default => strtoupper(str_replace('_', ' ', (string) $rawStatus)),
        };

        $expectedAmount = $this->actual
            ? (float) ($installment?->installment_amount ?? $record->expected_amount ?? 0)
            : (float) ($record->installment_amount ?? 0);
        $collectedAmount = $this->actual
            ? (float) ($record->paid_amount ?? 0)
            : min(max((float) ($record->ledger_paid_amount ?? 0), 0), $expectedAmount);
        $currentBalance = $this->actual
            ? min(max((float) ($record->ledger_paid_amount ?? 0), 0), $expectedAmount)
            : $collectedAmount;
        $outstandingAmount = max($expectedAmount - $currentBalance, 0);

        return [
            strtoupper($debitOrder->partner->business_name ?? ''),
            $debitOrder->reference ?? '',
            $debitOrder->periodicity ?? '',
            $debitOrder->channel ?? '',
            strtoupper($debitOrder->customerAccount->account_name ?? ''),
            $debitOrder->customerAccount->account_number ?? '',
            $debitOrder->customerAccount->customer->address ?? '',
            $expectedAmount,
            $this->actual ? $collectedAmount : ($record->last_paid_amount ?? 0),
            $currentBalance,
            $outstandingAmount,
            $status,
            $installment?->payment_date ? \Carbon\Carbon::parse($installment->payment_date)->format('d-m-Y') : '',
            $record->effective_payment_date ? \Carbon\Carbon::parse($record->effective_payment_date)->format('d-m-Y') : '',
            $debitOrder->start_date ? \Carbon\Carbon::parse($debitOrder->start_date)->format('d-m-Y') : '',
            $debitOrder->end_date ? \Carbon\Carbon::parse($debitOrder->end_date)->format('d-m-Y') : '',
            $record->remarks,
        ];
    }

    public function summary(Collection $records): array
    {
        $expected = $records->sum(fn ($record) => $this->actual
            ? (float) ($record->expected_amount ?? $record->installment?->installment_amount ?? 0)
            : (float) $record->installment_amount);
        $paid = $records->sum(fn ($record) => $this->actual
            ? (float) $record->paid_amount
            : min(max((float) ($record->ledger_paid_amount ?? 0), 0), (float) $record->installment_amount));
        $outstanding = max($expected - $paid, 0);

        return [
            ['label' => $this->actual ? 'Payment Events' : 'Installments', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => $this->actual ? 'Collected Amount' : 'Expected Amount', 'value' => number_format($this->actual ? $paid : $expected, 2), 'tone' => $this->actual ? 'success' : 'primary'],
            ['label' => 'Outstanding', 'value' => number_format($outstanding, 2), 'tone' => 'warning'],
            ['label' => 'Paid Amount', 'value' => number_format($paid, 2), 'tone' => 'info'],
        ];
    }
}
