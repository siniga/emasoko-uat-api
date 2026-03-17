<?php

if (!function_exists('mask_account_number')) {
    function mask_account_number($value, int $visibleStart = 3, int $visibleEnd = 3, string $mask = '*')
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $length = strlen($value);

        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat($mask, max($length, 1));
        }

        $start = substr($value, 0, $visibleStart);
        $end = substr($value, -$visibleEnd);
        $maskedLength = $length - ($visibleStart + $visibleEnd);

        return $start . str_repeat($mask, $maskedLength) . $end;
    }
}

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

if (!function_exists('casting_phone')) {
    function casting_phone($phone)
    {
        // remove spaces just in case
        $phone = trim($phone);

        // Starts with 0 (e.g. 0712345678 → 255712345678)
        if (preg_match('~^0\d+$~', $phone)) {
            return '255' . substr($phone, 1);
        }

        // Starts with + (e.g. +255712345678 → 255712345678)
        if (preg_match('~^\+\d+$~', $phone)) {
            return substr($phone, 1);
        }

        // Already normalized or unknown format
        return $phone;
    }
}


if (!function_exists('reverse_casting_phone')) {
    function reverse_casting_phone($phone)
    {
        if (preg_match("~^255\d+$~", $phone)) {
            return '0' . substr($phone, 3);
        } else if (preg_match("~^+\d+$~", $phone)) {
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
        $companies = Partner::filterCondition()->where(function ($query) {
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


if (!function_exists('pdf_to_base64')) {
    function pdf_to_base64($path)
    {
        $pdfFilePath = $path;
        if (file_exists($pdfFilePath)) {
            $pdfFileContents = file_get_contents($pdfFilePath);
            $base64EncodedPdf = base64_encode($pdfFileContents);
            return  $base64EncodedPdf;
        }
        return false;
    }
}

if (!function_exists('content_to_base64')) {
    function content_to_base64($contents)
    {
        return base64_encode($contents);
    }
}

if (!function_exists('base64_to_content')) {
    function base64_to_content($base64)
    {
        return base64_decode($base64);
    }
}

if (!function_exists('base64_to_csv_to_array')) {
    function base64_csv_to_array($base64, $delimiter = ",", $newLine = "\n")
    {
        $csv_data = base64_to_content($base64);
        $lines = explode($newLine, trim($csv_data));
        $headers = array_map('trim', str_getcsv(array_shift($lines), $delimiter));
        $data = [];
        foreach ($lines as $line) {
            $row = array_map('trim', str_getcsv($line, $delimiter));
            $data[] = array_combine($headers, $row);
        }
        return $data;
    }
}
