<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    //get account type from account category
    function account_types($id)
    {
        $accounts = Account::where('account_type_id', '=', $id)->where(['active' => 1])->get();

        if ($accounts) {
            echo '<option value="">Select</option>';
            foreach ($accounts as $val) {
                echo '<option value="' . $val->id . '">' . $val->name . '</option>';
            }
        } else {
            echo '<option value="">Select</option>';
        }
    }
}
