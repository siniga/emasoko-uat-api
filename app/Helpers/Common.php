<?php


//media types function
if (!function_exists('calculate_end_date')) {
    function calculate_end_date($start_at, $periodicity)
    {
        $date = strtotime($start_at);

        if($periodicity == 'DAILY')
            $date = strtotime("+1 year", $date);

        echo date('Y/m/d', $date);
    }
}







