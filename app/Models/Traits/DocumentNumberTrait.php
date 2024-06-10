<?php

namespace App\Models\Traits;

use Carbon\Carbon;

trait DocumentNumberTrait
{
    public function scopeDocument_Number($query, $column = "transaction_number", $initial = "PC", $total_count = 5, $isTime = true, $isDash = true, $startAt = 1, $appendUnderscore = false, $except = null)
    {
        $date = Carbon::now()->format('YmdHis'); // Get current date
        $initial = $initial; // Concat current date with initial value
        $initial .=  $isTime ?  ($isDash ? "-" : "") . $date : ""; // Concat current date with initial value
        $query->where($column, 'like', "%$initial%")->withoutGlobalScopes(); // Query all value match initial-today
        if ($except) {
            $query->where("id", "!=", $except);
        }
        $total_document_today = (string)($query->count() + $startAt); // Count results and add 1
        $count = strlen($total_document_today); // Get number of string retuned
        $i = 0;
        /**
         * Reverse number of string read from right to left if is 123 can read 321
         */
        $stack = '';
        for ($i = $count; $i > 0; $i--) {
            $stack .= $total_document_today[$i - 1];
        }
        /**
         * Append zero to remaining part if total character (total_count) is 6 for 321 will append 000 to be 321000
         */
        for ($i; $i < $total_count; $i++) {
            $stack .= "0";
        }
        /**
         * Reverse string back to normal mean read string from left to right eg. 321000 to 000123
         */
        $stack2 = '';
        for ($i = $total_count; $i > 0; $i--) {
            $stack2 .= $stack[$i - 1];
        }
        /**
         * Concantinate number with initials
         */
        $dash = $isDash ? "-" : "";
        if (!$total_document_today) {
            $stack2 = "";
        }
        if ($appendUnderscore) {
            $stack2 = "";
            for ($i = 0; $i < $total_document_today; $i++) {
                $stack2 .= "_";
            }
        }
        return $initial . $dash . $stack2;
    }
}
