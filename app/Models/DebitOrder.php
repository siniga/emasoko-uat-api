<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DebitOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    //where condition
    public static function where_cond()
    {
        $user_id = Auth::user()->id; //user id

        //customers
        $debit_orders = static::orderBy('debit_orders.id', 'DESC');

        // if (Auth::user()->hasRole('company_approver')) {
        //     $partner_user = PartnerUser::where(['user_id' => $user_id])->first();

        //     if ($partner_user) {
        //         $debit_orders = $debit_orders->where('debit_orders.partner_id', '=', $partner_user->partner_id);
        //     }
        // }

        return $debit_orders;
    }

    function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    function loanPurpose()
    {
        return $this->belongsTo(LoanPurpose::class, 'loan_purpose_id');
    }
}
