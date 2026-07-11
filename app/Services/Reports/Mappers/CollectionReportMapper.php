<?php

namespace App\Services\Reports\Mappers;

use App\Models\Installment;
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
        /** @var Installment $record */
        $status = match ($record->status) {
            'OPEN' => 'PENDING COLLECTION',
            'CLOSED' => 'PAID',
            'PARTIAL_PAID' => 'PARTIAL PAID',
            'FAILED' => 'FAILED',
            'FUNDSCHECK' => 'FUND CHECK',
            default => strtoupper(str_replace('_', ' ', (string) $record->status)),
        };

        $collectedAmount = $record->current_balance ?? 0;

        return [
            strtoupper($record->debitOrder->partner->business_name ?? ''),
            $record->debitOrder->reference ?? '',
            $record->debitOrder->periodicity ?? '',
            $record->debitOrder->channel ?? '',
            strtoupper($record->debitOrder->customerAccount->account_name ?? ''),
            $record->debitOrder->customerAccount->account_number ?? '',
            $record->debitOrder->customerAccount->customer->address ?? '',
            $record->installment_amount ?? 0,
            $this->actual ? $collectedAmount : ($record->last_paid_amount ?? 0),
            $collectedAmount,
            $record->outstanding_amount ?? 0,
            $status,
            $record->payment_date ? \Carbon\Carbon::parse($record->payment_date)->format('d-m-Y') : '',
            $record->effective_payment_date ? \Carbon\Carbon::parse($record->effective_payment_date)->format('d-m-Y') : '',
            $record->debitOrder->start_date ? \Carbon\Carbon::parse($record->debitOrder->start_date)->format('d-m-Y') : '',
            $record->debitOrder->end_date ? \Carbon\Carbon::parse($record->debitOrder->end_date)->format('d-m-Y') : '',
            $record->remarks,
        ];
    }

    public function summary(Collection $records): array
    {
        $expected = $records->sum('installment_amount');
        $paid = $records->sum('current_balance');
        $outstanding = $records->sum('outstanding_amount');

        return [
            ['label' => 'Installments', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => $this->actual ? 'Collected Amount' : 'Expected Amount', 'value' => number_format($this->actual ? $paid : $expected, 2), 'tone' => $this->actual ? 'success' : 'primary'],
            ['label' => 'Outstanding', 'value' => number_format($outstanding, 2), 'tone' => 'warning'],
            ['label' => 'Paid Amount', 'value' => number_format($paid, 2), 'tone' => 'info'],
        ];
    }
}
