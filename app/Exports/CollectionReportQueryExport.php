<?php

namespace App\Exports;

use App\Services\Reports\Mappers\CollectionReportMapper;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CollectionReportQueryExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    protected CollectionReportMapper $mapper;

    public function __construct(
        protected Builder $query,
        bool $actual
    ) {
        $this->mapper = new CollectionReportMapper($actual);
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return $this->mapper->headings();
    }

    public function map($record): array
    {
        return $this->mapper->map($record);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:Q1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '153567'],
                    ],
                ]);
            },
        ];
    }
}
