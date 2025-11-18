<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamp = false;


    function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
