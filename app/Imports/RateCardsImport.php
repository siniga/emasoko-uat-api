<?php

namespace App\Imports;

use App\Models\MediaOption;
use App\Models\MediaProgramSlot;
use Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Validator;

class RateCardsImport implements ToCollection, WithStartRow
{
    protected $media_program_id;
    protected $day_of_week;

    //constructor
    public function __construct($media_program_id, $day_of_week)
    {
        $this->media_program_id = $media_program_id;
        $this->day_of_week = $day_of_week;
    }


    public function collection(Collection $rows)
    {
        // Validator::make($collection->toArray(), [
        //     '*.name' => 'required',
        //     '*.region' => 'required',
        //     '*.category' => 'required',
        //     '*.type_of_input' => 'required',
        // ])->validate();

        foreach ($rows as $row) {
            //time range
            $time_range = explode(' - ', $row[0]);

            //media option
            $media_option = MediaOption::whereRaw('lower(name) = ?', strtolower($row[3]))->first();

            //insert or update
            $rate_card = MediaProgramSlot::firstOrNew(
                [
                    'media_program_id' => $this->media_program_id,
                    'day_of_week' => $this->day_of_week,
                    'start_time' => date('H:i', strtotime($time_range[0])),
                    'end_time' => date('H:i', strtotime($time_range[1])),
                    'time_category' => $row[1],
                    'media_option_id' => $media_option->id ?? null
                ]
            );
            $rate_card->available_slot = $row[4];
            $rate_card->time_per_slot = $row[5];
            $rate_card->cost = floatval(str_replace(",", "", $row[6]));
            $rate_card->created_by = Auth::user()->id;
            $rate_card->updated_by = Auth::user()->id;
            $rate_card->save();
        }
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }
}
