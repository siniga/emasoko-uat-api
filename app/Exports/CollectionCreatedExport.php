<?php

namespace App\Exports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class CollectionCreatedExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\FromArray
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    //headings
    public function headings(): array
    {
        return [
            'Mandate Reference',
            'Account Name',
            'Account Number',
            'Collection Amount',
            'Collection Date',
            'Debit End Date',
        ];
    }

    public function array(): array
    {
        Log::debug("== collection ==");
        Log::debug($this->data);
        return $this->data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:F1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
