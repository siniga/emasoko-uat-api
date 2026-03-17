<?php

namespace App\Services\Reports\Mappers;

use Illuminate\Support\Collection;

class PartnerPerformanceReportMapper extends ReportMapper
{
    public function headings(): array
    {
        return [
            'Service Provider',
            'Expected Amount',
            'Collected Amount',
            'Outstanding Amount',
            'Installments',
            'Collection Rate %',
        ];
    }

    public function map(mixed $record): array
    {
        $expected = (float) ($record['expected_amount'] ?? 0);
        $collected = (float) ($record['collected_amount'] ?? 0);
        $rate = $expected > 0 ? round(($collected / $expected) * 100, 2) : 0;

        return [
            strtoupper($record['partner_name'] ?? ''),
            number_format($expected, 2),
            number_format($collected, 2),
            number_format((float) ($record['outstanding_amount'] ?? 0), 2),
            number_format((int) ($record['installments'] ?? 0)),
            number_format($rate, 2),
        ];
    }

    public function summary(Collection $records): array
    {
        $expected = $records->sum('expected_amount');
        $collected = $records->sum('collected_amount');
        $rate = $expected > 0 ? round(($collected / $expected) * 100, 2) : 0;

        return [
            ['label' => 'Providers', 'value' => number_format($records->count()), 'tone' => 'primary'],
            ['label' => 'Expected', 'value' => number_format($expected, 2), 'tone' => 'primary'],
            ['label' => 'Collected', 'value' => number_format($collected, 2), 'tone' => 'success'],
            ['label' => 'Collection Rate %', 'value' => number_format($rate, 2), 'tone' => 'info'],
        ];
    }
}
