<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerAccount extends Model
{
    use HasFactory;
    protected $guarded = [];

    function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, "currency_id");
    }
}
