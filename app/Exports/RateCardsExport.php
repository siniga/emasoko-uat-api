<?php

namespace App\Exports;

use App\Models\MediaProgram;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;

class RateCardsExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $media_program_id;

    public function __construct($media_program_id)
    {
        $this->media_program_id = $media_program_id;
    }

    public function array(): array
    {
        //query
        $media_program = MediaProgram::findOrFail($this->media_program_id);

        //range
        $range = range(strtotime($media_program->start_time), strtotime($media_program->end_time), 30 * 60);

        $arr_rate_card = [];
        foreach ($range as $time) {
            $start = $time;
            $end = strtotime(date('H:i', $time) . '+29 minutes');

            $time = date('Hi', $start);
            if (($time >= "0600") && ($time <= "1200")) {
                $time_category = "Morning";
            } elseif (($time >= "1201") && ($time <= "1600")) {
                $time_category = "Afternoon";
            } elseif (($time >= "1601") && ($time <= "2100")) {
                $time_category = "Evening";
            } elseif (($time >= "2101") && ($time <= "0400")) {
                $time_category = "Night";
            } else {
                $time_category = "Early Morning";
            }

            //push in array
            $arr_rate_card[] = [
                'time_range' => date('h:i A', $start) . ' - ' . date('h:i A', $end),
                'time_category' => $time_category,
                'program_name' => $media_program->program_name
            ];
        }

        //return
        return $arr_rate_card;
    }

    //headings
    public function headings(): array
    {
        return [
            'Time Range',
            'Time Categorization',
            'Program Name',
            'Media Options (Sponsorship, Mention, Interviews, Spots, Live Streaming) ',
            'Available Slots (1 - 6)',
            'Time Per available Slot(In seconds)',
            'Cost (Inclusive of All Taxes)'
        ];
    }

    //events
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $cellRange = 'A1:G1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setBold(true);
            },
        ];
    }
}
