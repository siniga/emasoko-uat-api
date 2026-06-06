<?php

namespace App\Services\Reports\Mappers;

use App\Models\Installment;
use Illuminate\Support\Collection;

class OverdueInstallmentReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Service Provider',
            'Mandate Reference',
            'Account Name',
            'Account Number',
            'Payment Channel',
            'Due Date',
            'Days Overdue',
            'Installment Amount',
            'Outstanding Amount',
            'Status',
        ];
    }

    public function map(mixed $record): array
    {
        /** @var Installment $record */
        $daysOverdue = $record->payment_date
            ? now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($record->payment_date)->startOfDay())
            : 0;

        return [
            strtoupper($record->debitOrder->partner->business_name ?? ''),
            $record->debitOrder->reference ?? '',
            strtoupper($record->debitOrder->customerAccount->account_name ?? ''),
            $record->debitOrder->customerAccount->account_number ?? '',
            $record->debitOrder->channel ?? '',
            $record->payment_date ? \Carbon\Carbon::parse($record->payment_date)->format('d-m-Y') : '',
            $daysOverdue,
            $record->installment_amount ?? 0,
            $record->outstanding_amount ?? 0,
            strtoupper((string) $record->status),
        ];
    }

    public function summary(Collection $records): array
    {
        return [
            ['label' => 'Overdue Items', 'value' => number_format($records->count()), 'tone' => 'danger'],
            ['label' => 'Overdue Amount', 'value' => number_format($records->sum('outstanding_amount'), 2), 'tone' => 'warning'],
            ['label' => 'Providers Affected', 'value' => number_format($records->pluck('debitOrder.partner_id')->filter()->unique()->count()), 'tone' => 'primary'],
            ['label' => 'Mandates Affected', 'value' => number_format($records->pluck('debit_order_id')->filter()->unique()->count()), 'tone' => 'info'],
        ];
    }
}
