<?php

namespace App\Models;

use App\Models\Traits\DocumentNumberTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory, DocumentNumberTrait;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->reference = Partner::document_number("reference", "CO", 6, true, false);
            $model->short_code = strtoupper(substr($model->business_name, 0, 3));
            $model->client_code = strtoupper(substr($model->business_name, 0, 2));
        });
    }

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

    function partnerApprovals()
    {
        return $this->hasMany(PartnerApproval::class, 'partner_id');
    }

    function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    function verifiedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    function createdBy()
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
