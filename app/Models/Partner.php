<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;
    protected $guarded = [];

    function partnerAuthorizeUser()
    {
        return $this->hasOne(PartnerUser::class, 'partner_id')->where('authorize', '=', 1);
    }

    function partnerUsers()
    {
        return $this->hasMany(PartnerUser::class, 'partner_id');
    }

    function partnerCategory()
    {
        return $this->belongsTo(PartnerCategory::class, 'partner_category_id');
    }

    function partnerDocuments()
    {
        return $this->hasMany(PartnerDocument::class, 'partner_id');
    }

    function partnerAccounts()
    {
        return $this->hasMany(PartnerAccount::class, 'partner_id');
    }

    function partnerStaff()
    {
        return $this->hasMany(PartnerStaff::class, 'partner_id');
    }

    function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
