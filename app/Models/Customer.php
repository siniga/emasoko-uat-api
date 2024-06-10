<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    use HasFactory;
    protected $guarded = [];

    
    public function getFullNameAttribute()
    {
        return $this->first_name . " " . $this->middle_name . " " . $this->last_name;
    }

    //where condition
    public static function where_cond()
    {
        $user_id = Auth::user()->id; //user id

        //customers
        $customers = static::orderBy('customers.created_at', 'DESC');

        if (Auth::user()->hasRole('company_approver')) {
            $partner_user = PartnerUser::where(['user_id' => $user_id])->first();

            if ($partner_user) {
                $customers = $customers->where('customers.partner_id', '=', $partner_user->partner_id);
            }
        }

        return $customers;
    }

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
        return $this->hasMany(CustomerAccount::class, 'customer_id');
    }
}
