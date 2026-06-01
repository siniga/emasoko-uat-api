<?php

namespace App\Services\Reports\Mappers;

use App\Models\DebitOrder;
use Illuminate\Support\Collection;

class MandateReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Service Provider',
            'Mandate Reference',
            'Mandate Status',
            'Account Type',
            'Account Name',
            'Account Number',
            'Customer Address',
            'Debit Type',
            'Periodicity',
            'Number of Installment',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Deduction Amount',
            'Deduction Start Date',
            'Deduction End Date',
            'Contract Status',
            'Created Date',
            'Created By',
            'Remarks',
        ];
    }

    public function map(mixed $record): array
    {
        /** @var DebitOrder $record */
        $consentStatus = match ((int) $record->approved) {
            1 => 'APPROVED',
            2 => 'FAILED',
            9 => 'CANCELLED',
            default => 'PENDING',
        };

        return [
            strtoupper($record->partner->business_name ?? ''),
            $record->reference,
            $consentStatus,
            strtoupper($record->customerAccount->account->name ?? ''),
            strtoupper($record->customerAccount->account_name ?? ''),
            $record->customerAccount->account_number ?? '',
            $record->customerAccount->customer->address ?? '',
            $record->debit_type,
            $record->periodicity,
            $record->installment_number,
            number_format($record->total_amount),
            number_format($record->current_balance),
            number_format($record->outstanding_amount),
            $record->installment_amount == 0 ? number_format($record->max_installment_amount) : number_format($record->installment_amount),
            $record->start_date,
            $record->end_date,
            $record->contract_status,
            $record->created_at,
            strtoupper($record->userCreated->name ?? ''),
            $record->remarks,
        ];
    }

    public function summary(Collection $records): array
    {
        return [
            ['label' => 'Mandates', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => 'Total Amount', 'value' => number_format($records->sum('total_amount'), 2), 'tone' => 'primary'],
            ['label' => 'Paid Amount', 'value' => number_format($records->sum('current_balance'), 2), 'tone' => 'success'],
            ['label' => 'Outstanding', 'value' => number_format($records->sum('outstanding_amount'), 2), 'tone' => 'warning'],
        ];
    }
}
