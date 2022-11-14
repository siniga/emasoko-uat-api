<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CustomerAccount extends Model
{
    use HasFactory;
    protected $guarded = [];

    //where condition
    public static function where_cond()
    {
        $user_id = Auth::user()->id; //user id

        //customers
        $customer_accounts = static::orderBy('customer_accounts.created_at', 'DESC');

        if (Auth::user()->hasRole('company_approver')) {
            $partner_user = PartnerUser::where(['user_id' => $user_id])->first();

            if ($partner_user) {
                $customer_accounts = $customer_accounts->where('customer_accounts.created_by', '=', $partner_user->partner_id);
            }
        }

        return $customer_accounts;
    }

    function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    function debitOrders()
    {
        return $this->hasMany(DebitOrder::class, 'customer_account_id');
    }
}
