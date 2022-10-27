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
    protected $start_at, $end_at, $category_id, $region_id, $status, $keyword;

    public function __construct($start_at, $end_at, $category_id, $region_id, $status, $keyword)
    {
        $this->start_at = $start_at;
        $this->end_at = $end_at;
        $this->category_id = $category_id;
        $this->region_id = $region_id;
        $this->status = $status;
        $this->keyword = $keyword;
    }

    public function query()
    {
        //companies
        $companies = Partner::query();

        //start data and end date
        if ($this->start_at != null && $this->end_at != null) {
            $start_at = date('Y-m-d', strtotime($this->start_at));
            $end_at = date('Y-m-d', strtotime($this->end_at));

            $companies = $companies->whereBetween('created_at', [$start_at, $end_at]);
        }

        //category
        if ($this->category_id != null) {
            $companies = $companies->where('partner_category_id', $this->category_id);
        }

        //region
        if ($this->region_id != null) {
            $companies = $companies->where('region_id', $this->region_id);
        }

        //status
        if ($this->status != null)
            $companies = $companies->where('status', $this->status);

        //keyword
        if ($this->keyword != null) {
            $keyword = $this->keyword;

            //query
            $companies = $companies->where(function ($query) use ($keyword) {
                $query->orWhere('business_name', 'LIKE', "%$keyword%")
                    ->orWhere('tin_number', 'LIKE', "%$keyword%");
            });
        }


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
            'Created Date'
        ];
    }

    //map
    public function map($companies): array
    {
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
        ];
    }

    //events
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:K1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
