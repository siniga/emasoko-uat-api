<?php

//get pending mandate 

use App\Models\DebitOrder;

if (!function_exists('get_pending_debit_orders')) {
    function get_pending_debit_orders()
    {
        $debit_orders = DebitOrder::where(['approved' => 0]);

        if ($debit_orders->get()->isNotEmpty()) {
            return $debit_orders->count();
        } else {
            return null;
        }
    }
}
