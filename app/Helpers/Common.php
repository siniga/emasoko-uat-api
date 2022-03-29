<?php

use App\Models\Partner;

if (!function_exists('calculate_end_date')) {
    function calculate_end_date($start_at, $periodicity)
    {
        $date = strtotime($start_at);

        if ($periodicity == 'DAILY')
            $date = strtotime("+1 year", $date);

        echo date('Y/m/d', $date);
    }
}


//cast mobile number => remove 0 
if (!function_exists('cast_phone')) {
    function cast_phone($phone)
    {
        if (preg_match("~^0\d+$~", $phone)) {
            return substr($phone, 1);
        } else {
            return $phone;
        }
    }
}


//complete registration notification
if (!function_exists('get_company_notification')) {
    function get_company_notification()
    {
        $companies = Partner::where(function ($query) {
            $query->where('status', '=', 1)
                ->orWhere('status', '=', 2);
        });

        if ($companies->get()->isNotEmpty()) {
            return $companies->count();
        } else {
            return null;
        }
    }
}
