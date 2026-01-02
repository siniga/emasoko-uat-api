<?php

namespace App\Models;

use App\Models\Traits\DocumentNumberTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Partner extends Model
{
    use HasFactory, DocumentNumberTrait;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            //create short code
            $short_code = strtoupper(Str::random(3));

            $model->reference = Partner::document_number("reference", "CO", 4, true, false);
            $model->short_code = $short_code;
            $model->client_code = substr($short_code, 0, 2);
        });
    }

    //where condition
    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        //Partners
        $partners = static::orderBy('partners.business_name', 'ASC');

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => $user->id])->first();

            if ($company_user) {
                $partners = $partners->where('partners.company_id', '=', $company_user->company_id);
            }
        } else if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $arr_partners = [];

            $user_partners = $user->partners->where('authorize', '=', 1);
            foreach ($user_partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners)
                $partners = $partners->whereIn('partners.id', $arr_partners);
        }

        return $partners;
    }

    public function partnerAuthorizeUser()
    {
        return $this->hasOne(PartnerUser::class, 'partner_id')->where('authorize', '=', 1);
    }

    public function users()
    {
        return $this->hasMany(PartnerUser::class, 'partner_id');
    }

    public function partnerCategory()
    {
        return $this->belongsTo(PartnerCategory::class, 'partner_category_id');
    }

    public function partnerDocuments()
    {
        return $this->hasMany(PartnerDocument::class, 'partner_id');
    }

    public function partnerAccounts()
    {
        return $this->hasMany(PartnerAccount::class, 'partner_id');
    }

    public function partnerStaff()
    {
        return $this->hasMany(PartnerStaff::class, 'partner_id');
    }

    public function partnerApprovals()
    {
        return $this->hasMany(PartnerApproval::class, 'partner_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function debitOrders()
    {
        return $this->hasMany(DebitOrder::class, 'partner_id')->orderBy('business_name', 'ASC');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeWithFilters($query, $request)
    {
        $start_date        = $request['start_at'] ?? null;
        $end_date          = $request['end_at'] ?? null;
        $approved          = $request['approved'] ?? null;
        $is_verified       = $request['is_verified'] ?? null;
        $category_id       = $request['category_id'] ?? null;
        $region_id         = $request['region_id'] ?? null;
        $keyword           = $request['keyword'] ?? null;

        Log::debug("approved: $approved");

        return $query
            ->when($start_date || $end_date, function ($q) use ($start_date, $end_date) {
                if ($start_date && $end_date) {
                    $q->whereBetween('created_at', [$start_date, $end_date]);
                } elseif ($start_date) {
                    $q->whereDate('created_at', '>=', $start_date);
                } elseif ($end_date) {
                    $q->whereDate('created_at', '<=', $end_date);
                }
            })
            ->when(!is_null($approved) && $approved !== '', function ($q) use ($approved) {
                $q->where('approved', (int) $approved);
            })
            ->when(!is_null($category_id) && $category_id !== '', function ($q) use ($category_id) {
                $q->where('partner_category_id', $category_id);
            })
            ->when(!is_null($region_id) && $region_id !== '', function ($q) use ($region_id) {
                $q->where('region_id', $region_id);
            })
            ->when(!is_null($keyword) && $keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('business_name', 'LIKE', "%{$keyword}%")
                        ->orWhere('tin_number', 'LIKE', "%{$keyword}%");
                });
            })
            ->when(!is_null($is_verified) && $is_verified !== '', function ($q) use ($is_verified) {
                $q->whereHas('partnerAccounts', function ($qq) use ($is_verified) {
                    $qq->where('is_verified', (int) $is_verified);
                });
            });
    }
}
