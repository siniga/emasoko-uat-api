<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Installment extends Model
{
    use HasFactory;
    protected $guarded = [];

    //filter condition
    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        //installments
        $installments = static::query();

        if ($user->hasRole('Company_Approver')) {
            $arr_partners = [];

            //partners
            $partners = $user->partners->where('authorize', '=', 1);

            foreach ($partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners) {
                $installments = $installments->whereHas('debitOrder', function ($query) use ($arr_partners) {
                    $query->whereIn('debit_orders.partner_id', $arr_partners)
                        ->where('debit_orders.approved', '=', 1)
                        ->where('debit_orders.status', '=', 240);
                });
            }
        } else if ($user->hasRole('Company_User')) {
            $installments = $installments->whereHas('debitOrder', function ($query) use ($user) {
                $query->where('debit_orders.created_by', '=', $user->id)
                    ->where('debit_orders.approved', '=', 1)
                    ->where('debit_orders.status', '=', 240);
            });
        } else {
            $installments = $installments->whereHas('debitOrder', function ($query) {
                $query->where('debit_orders.approved', '=', 1)
                    ->where('debit_orders.status', '=', 240);
            });
        }

        return $installments;
    }

    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, "debit_order_id");
    }
}
