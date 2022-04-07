<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAccount extends Model
{
    use HasFactory;
    protected $guarded = [];

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
