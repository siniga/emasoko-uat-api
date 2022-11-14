<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }
}
