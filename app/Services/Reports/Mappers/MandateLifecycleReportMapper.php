<?php

namespace App\Services\Reports\Mappers;

use Illuminate\Support\Collection;

class MandateLifecycleReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Lifecycle Status',
            'Mandates',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
        ];
    }

    public function map(mixed $record): array
    {
        return [
            strtoupper(str_replace('_', ' ', $record['lifecycle_status'] ?? 'unknown')),
            number_format((int) ($record['mandates'] ?? 0)),
            number_format((float) ($record['total_amount'] ?? 0), 2),
            number_format((float) ($record['paid_amount'] ?? 0), 2),
            number_format((float) ($record['outstanding_amount'] ?? 0), 2),
        ];
    }

    public function summary(Collection $records): array
    {
        return [
            ['label' => 'Lifecycle Buckets', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => 'Mandates', 'value' => number_format($records->sum('mandates')), 'tone' => 'primary'],
            ['label' => 'Total Amount', 'value' => number_format($records->sum('total_amount'), 2), 'tone' => 'success'],
            ['label' => 'Outstanding', 'value' => number_format($records->sum('outstanding_amount'), 2), 'tone' => 'warning'],
        ];
    }
}
