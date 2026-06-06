<?php

namespace App\Services\Reports\Mappers;

use Illuminate\Support\Collection;

class CrbReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Service Provider',
            'Customer Name',
            'ID Number',
            'Phone',
            'Account Number',
            'Mandate Reference',
            'Facility Status',
            'Total Exposure',
            'Paid Amount',
            'Outstanding Amount',
            'Installment Amount',
            'Start Date',
            'End Date',
            'Overdue Installments',
            'Max Days Past Due',
            'CRB Classification',
            'Customer Address',
        ];
    }

    public function map(mixed $record): array
    {
        return [
            strtoupper((string) ($record['provider_name'] ?? '')),
            strtoupper((string) ($record['customer_name'] ?? '')),
            $record['id_number'] ?? '',
            $record['phone'] ?? '',
            $record['account_number'] ?? '',
            $record['mandate_reference'] ?? '',
            $record['facility_status'] ?? '',
            number_format((float) ($record['total_exposure'] ?? 0), 2),
            number_format((float) ($record['paid_amount'] ?? 0), 2),
            number_format((float) ($record['outstanding_amount'] ?? 0), 2),
            number_format((float) ($record['installment_amount'] ?? 0), 2),
            $record['start_date'] ?? '',
            $record['end_date'] ?? '',
            number_format((int) ($record['overdue_installments'] ?? 0)),
            number_format((int) ($record['max_days_past_due'] ?? 0)),
            $record['crb_classification'] ?? '',
            $record['customer_address'] ?? '',
        ];
    }

    public function summary(Collection $records): array
    {
        return [
            ['label' => 'Accounts', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => 'Total Exposure', 'value' => number_format($records->sum('total_exposure'), 2), 'tone' => 'primary'],
            ['label' => 'Outstanding', 'value' => number_format($records->sum('outstanding_amount'), 2), 'tone' => 'warning'],
            ['label' => 'Delinquent Accounts', 'value' => number_format($records->where('max_days_past_due', '>', 0)->count()), 'tone' => 'danger'],
        ];
    }
}
