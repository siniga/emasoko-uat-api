<?php

namespace App\Services\Reports\Mappers;

use Illuminate\Support\Collection;

class DelinquentAgeAnalysisReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Service Provider',
            'Mandate Reference',
            'Customer Name',
            'ID Number',
            'Account Number',
            'Due Date',
            'Days Past Due',
            'Age Bucket',
            'Risk Highlight',
            'Installment Amount',
            'Outstanding Amount',
            'Status',
            'Customer Address',
        ];
    }

    public function map(mixed $record): array
    {
        return [
            strtoupper((string) ($record['provider_name'] ?? '')),
            $record['mandate_reference'] ?? '',
            strtoupper((string) ($record['customer_name'] ?? '')),
            $record['id_number'] ?? '',
            $record['account_number'] ?? '',
            $record['due_date'] ?? '',
            number_format((int) ($record['days_past_due'] ?? 0)),
            $record['age_bucket'] ?? '',
            $record['risk_highlight'] ?? '',
            number_format((float) ($record['installment_amount'] ?? 0), 2),
            number_format((float) ($record['outstanding_amount'] ?? 0), 2),
            $record['status'] ?? '',
            $record['customer_address'] ?? '',
        ];
    }

    public function summary(Collection $records): array
    {
        return [
            ['label' => 'Delinquent Items', 'value' => number_format($records->count()), 'tone' => 'danger'],
            ['label' => 'Outstanding', 'value' => number_format($records->sum('outstanding_amount'), 2), 'tone' => 'warning'],
            ['label' => '31+ Days', 'value' => number_format($records->where('days_past_due', '>', 30)->count()), 'tone' => 'info'],
            ['label' => '91+ Days', 'value' => number_format($records->where('days_past_due', '>', 90)->count()), 'tone' => 'danger'],
        ];
    }
}
