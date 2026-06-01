<?php

namespace App\Services\Reports\Mappers;

use Illuminate\Support\Collection;

abstract class ReportMapper
{
    abstract public function headings(): array;

    abstract public function map(mixed $record): array;

    public function rows(Collection $records): array
    {
        return $records->map(fn ($record) => $this->map($record))->all();
    }

    public function previewRows(Collection $records, int $limit = 100): array
    {
        return $records->take($limit)->map(fn ($record) => $this->map($record))->all();
    }

    public function summary(Collection $records): array
    {
        return [];
    }
}
