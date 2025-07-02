<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeductionService
{
    public function __construct() {}

    //get mandate reference
    public function nextCollectionDate($original_date)
    {
        //original date
        $original = Carbon::parse($original_date);

        // Get current month and year
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Adjust year if original was in December and we're now in January
        $newYear = ($original->month === 12 && $currentMonth === 1) ? $original->year + 1 : $original->year;

        // Try to build new date with current month, original day and adjusted year
        try {
            $newDate = Carbon::create($newYear, $currentMonth, $original->day);
        } catch (\Exception $e) {
            // Day is invalid for the current month (e.g., Feb 30)
            $newDate = Carbon::create($newYear, $currentMonth, 1)->endOfMonth();
        }

        return $newDate;
    }

    //check for duplicate

    //check if mandate end date pass

    //check if collected this month
}
