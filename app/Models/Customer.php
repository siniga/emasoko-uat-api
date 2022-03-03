<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $guarded = [];

    function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class, 'marital_status_id');
    }

    function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    function occupation()
    {
        return $this->belongsTo(Occupation::class, 'occupation_id');
    }

    function customerAccount()
    {
        return $this->hasOne(CustomerAccount::class, 'customer_id');
    }
}
