<?php

namespace App\Models;

use App\Models\Traits\DocumentNumberTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Partner extends Model
{
    use HasFactory, DocumentNumberTrait;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->reference = Partner::document_number("reference", "CO", 4, true, false);
            $model->short_code = strtoupper(substr($model->business_name, 0, 3));
            $model->client_code = strtoupper(substr($model->business_name, 0, 2));
        });
    }

    //where condition
    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        $partners = static::orderBy('partners.created_at', 'DESC');

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
        return $this->hasMany(DebitOrder::class, 'partner_id');
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
        $status            = $request['status'] ?? null;
        $category_id       = $request['category_id'] ?? null;
        $region_id         = $request['region_id'] ?? null;
        $keyword           = $request['keyword'] ?? null;

        return $query->when($start_date, function ($query) use ($start_date, $end_date) {
            if ($start_date != null || $end_date != null) {
                if ($start_date != null && $end_date != null)
                    $query->whereBetween("created_at", [$start_date, $end_date]);

                else if ($start_date != null)
                    $query->where("created_at", '>=', $start_date);

                else if ($end_date != null)
                    $query->where("created_at", '<=', $end_date);
            }
        })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($category_id, function ($query) use ($category_id) {
                $query->where('partner_category_id', $category_id);
            })
            ->when($region_id, function ($query) use ($region_id) {
                $query->where('region_id', $region_id);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->orWhere('business_name', 'LIKE', "%$keyword%")
                    ->orWhere('tin_number', 'LIKE', "%$keyword%");
            })
            ->when(request()->account_status, function ($query) {
                $query->whereHas('partnerAccounts', function ($query) {
                    if (request()->account_status == 1) {
                        $query->whereNotNull("verified_at");
                    }
                    if (request()->account_status == 2) {
                        $query->whereNotNull("rejected_at");
                    }
                });
            });
    }
}
