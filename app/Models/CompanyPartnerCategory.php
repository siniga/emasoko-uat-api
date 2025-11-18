<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyPartnerCategory extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamp = false;


    function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    function partnerCategory()
    {
        return $this->belongsTo(PartnerCategory::class, 'partner_category_id');
    }
}
