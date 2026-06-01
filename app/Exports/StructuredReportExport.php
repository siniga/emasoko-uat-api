<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StructuredReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected array $headings,
        protected array $rows
    ) {
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        $endColumn = chr(64 + count($this->headings));

        return [
            AfterSheet::class => function (AfterSheet $event) use ($endColumn) {
                $cellRange = "A1:{$endColumn}1";

                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
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
