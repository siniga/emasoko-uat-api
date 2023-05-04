<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPartner extends Model
{
    use HasFactory;
    protected $guarded = [];

    function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
