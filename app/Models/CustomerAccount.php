<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerAccount extends Model
{
    use HasFactory;
    protected $guarded = [];

    //filter condition
    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        //debit orders
        $customer_accounts = static::orderBy('customer_accounts.created_at', 'DESC');

        // if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
        //     $arr_partners = [];

        //     // foreach ($user->partners as $val)
        //     //     array_push($arr_partners, $val->partner_id);


        //     //$customer_accounts = $customer_accounts->whereIn('debit_orders.partner_id', $arr_partners);
        // }

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

    public function scopeWithFilters($query, $request)
    {
        $start_date        = $request['start_at'] ?? null;
        $end_date          = $request['end_at'] ?? null;
        $account_type_id   = $request['account_type_id'] ?? null;
        $account_id        = $request['account_id'] ?? null;
        $status            = $request['status'] ?? null;
        $keyword           = $request['keyword'] ?? null;

        return $query->when($start_date, function ($query) use ($start_date, $end_date) {
            if ($start_date != null || $end_date != null) {
                if ($start_date != null && $end_date != null)
                    $query->whereBetween('created_at', [$start_date, $end_date]);

                else if ($start_date != null)
                    $query->where('created_at', '>=', $start_date);

                else if ($end_date != null)
                    $query->where('created_at', '<=', $end_date);
            }
        })

            ->when($account_type_id, function ($query) use ($account_type_id) {
                $query->where('account_type_id', $account_type_id);
            })

            ->when($account_id, function ($query) use ($account_id) {
                $query->where('account_id', $account_id);
            })

            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(DB::raw('LOWER(account_name)'), 'like', "%" . strtolower($keyword) . "%")
                    ->orWhere(DB::raw('LOWER(account_number)'), 'like', "%" . strtolower($keyword) . "%");
            });
    }
}
