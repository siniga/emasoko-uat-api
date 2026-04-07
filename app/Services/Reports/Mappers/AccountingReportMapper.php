<?php

namespace App\Services\Reports\Mappers;

use Illuminate\Support\Collection;

class AccountingReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Service Provider',
            'Mandate Reference',
            'Customer Name',
            'Account Number',
            'Payment Channel',
            'Due Date',
            'Effective Date',
            'Expected Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Collection Status',
            'Entry Classification',
            'Remarks',
        ];
    }

    public function map(mixed $record): array
    {
        return [
            strtoupper((string) ($record['provider_name'] ?? '')),
            $record['mandate_reference'] ?? '',
            strtoupper((string) ($record['customer_name'] ?? '')),
            $record['account_number'] ?? '',
            strtoupper((string) ($record['payment_channel'] ?? '')),
            $record['due_date'] ?? '',
            $record['effective_date'] ?? '',
            number_format((float) ($record['expected_amount'] ?? 0), 2),
            number_format((float) ($record['paid_amount'] ?? 0), 2),
            number_format((float) ($record['outstanding_amount'] ?? 0), 2),
            $record['collection_status'] ?? '',
            $record['entry_classification'] ?? '',
            $record['remarks'] ?? '',
        ];
    }

    public function summary(Collection $records): array
    {
        return [
            ['label' => 'Lines', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => 'Expected', 'value' => number_format($records->sum('expected_amount'), 2), 'tone' => 'primary'],
            ['label' => 'Paid', 'value' => number_format($records->sum('paid_amount'), 2), 'tone' => 'success'],
            ['label' => 'Outstanding', 'value' => number_format($records->sum('outstanding_amount'), 2), 'tone' => 'warning'],
        ];
    }
}
