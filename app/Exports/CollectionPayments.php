<?php

namespace App\Exports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CollectionPayments implements FromArray, WithHeadings, ShouldAutoSize
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
            'Periodicity',
            'Payment Channel',
            'Account Name',
            'Account Number',
            'Customer Address',
            'Expected Amount',
            'Paid Amount',
            'Current Balance',
            'Outstanding Amount',
            'Payment Status',
            'Payment Date',
            'Collection Date',
            'Debit Start Date',
            'Debit End Date',
            'Payment Remarks',
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
            AfterSheet::class => function (AfterSheet $event) {

                $cellRange = 'A1:R1'; // Header row

                $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => [
                            'rgb' => 'FFFFFF', // white text (optional)
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '1F4E78', // dark blue background
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ]);
            },
        ];
    }
}
