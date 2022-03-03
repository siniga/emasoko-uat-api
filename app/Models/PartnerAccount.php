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
}
