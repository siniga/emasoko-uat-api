<?php

namespace App\Exports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DebitOrders implements FromArray, WithHeadings, ShouldAutoSize
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
            'Service Provider',
            'Mandate Reference',
            'Mandate Status',
            'Account Name',
            'Account Number',
            'Debit Type',
            'Periodicity',
            'Number of Installment',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Deduction Amount',
            'Deduction Start Date',
            'Deduction End Date',
            'Contract Status',
            'Remarks',
            'Created Date',
            'Created By',

        ];
    }

    public function array(): array
    {
        Log::debug("== debit orders ==");
        Log::debug($this->data);
        return $this->data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:R1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
