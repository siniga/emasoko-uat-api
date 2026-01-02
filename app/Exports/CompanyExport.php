<?php

namespace App\Exports;

use App\Models\Partner;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;

class CompanyExport implements FromQuery, WithHeadings, ShouldAutoSize, WithEvents, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function query()
    {
        $requests = $this->data;

        //companies
        $companies = Partner::query();

        $companies = $companies->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

        //return
        return $companies;
    }

    //headings
    public function headings(): array
    {
        return [
            'Business Name',
            'TIN Number',
            'Category',
            'Year of Incorporation',
            'No. of staff',
            'Company Email',
            'Company Phone',
            'Company Website',
            'Region',
            'Physical Address',
            'Created Date',
            'Status'
        ];
    }

    //map
    public function map($companies): array
    {
        $status = "Failed";

        if($companies->approved == 0)
            $status = "Pending Review";
        else if($companies->approved == 1)
            $status = "Approved";
        else if ($companies->approved == 99)
            $status = "Failed";

        return [
            $companies->business_name ?? '',
            $companies->tin_number ?? 'NIL',
            $companies->partnerCategory->name ?? '',
            $companies->year_of_incorporation ?? '',
            $companies->number_of_staff ?? '',
            $companies->email ?? '',
            $companies->phone ?? '',
            $companies->website ?? '',
            $companies->region->name ?? '',
            $companies->physical_address ?? '',
            $companies->created_at,
            $status,
        ];
    }

    //events
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:L1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
