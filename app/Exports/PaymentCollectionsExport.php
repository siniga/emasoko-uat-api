<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentCollectionsExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, ShouldAutoSize
{
    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Service Provider',
            'Channel',
            'Mandate Type',
            'Mandate Reference',
            'Payment Reference',
            'Raw Status',
            'Payment Status',
            'Expected Amount',
            'Paid Amount',
            'Collection Date',
            'Remarks',
        ];
    }

    public function map($collection): array
    {
        return [
            $collection->partner_name,
            $collection->channel,
            $collection->mandate_type,
            $collection->mandate_reference,
            $collection->collection_payment_reference,
            $collection->raw_status,
            $collection->collection_status,
            (float) $collection->expected_amount,
            (float) $collection->paid_amount,
            $collection->effective_payment_date,
            $collection->collection_remarks,
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
