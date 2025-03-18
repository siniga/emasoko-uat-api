<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CollectionBatch extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $incrementing = false;

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function getCsvsAttribute()
    {
        $debits = $this->debits;
        $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO; AMOUNT\n";
        foreach ($debits as $debit) {
            $debitOrder = $debit->debitOrder;
            $customerAccount = $debitOrder->customerAccount;
            $csv .= "{$debit->id};16;NMB;{$customerAccount->account_name};{$customerAccount->account_name};{$customerAccount->account_number};{$debitOrder->installment_amount}\n";
        }
        return $csv;
    }

    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        $batches = static::orderBy('collection_batches.created_at', 'DESC');

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => $user->id])->first();

            if ($company_user) {
                $batches = $batches->whereHas('partner', function ($query) use ($company_user) {
                    $query->where('partners.company_id', '=', $company_user->company_id);
                });
            }
        } else if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $arr_partners = [];

            $partners = $user->partners->where('authorize', '=', 1);
            foreach ($partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners)
                $batches = $batches->whereIn('collection_batches.partner_id', $arr_partners);
        } 

        //filter where deleted = 0
        $batches = $batches->where('collection_batches.deleted', '=', 0);

        return $batches;
    }

    public function debits()
    {
        return $this->hasMany(CollectionDebitOrder::class, "collection_batch_id");
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, "createdBy");
    }

    public function verifiedUser()
    {
        return $this->belongsTo(User::class, "verifiedBy");
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, "partner_id");
    }

    public function scopeWithFilters($query, $request)
    {
        $start_date        = $request['start_at'] ?? null;
        $end_date          = $request['end_at'] ?? null;
        $partner_id        = $request['partner_id'] ?? null;

        return $query->when($start_date, function ($query) use ($start_date, $end_date) {
            if ($start_date != null || $end_date != null) {
                if ($start_date != null && $end_date != null)
                    $query->whereBetween('collection_batches.created_at', [$start_date, $end_date]);

                else if ($start_date != null)
                    $query->where('collection_batches.created_at', '>=', $start_date);

                else if ($end_date != null)
                    $query->where('collection_batches.created_at', '<=', $end_date);
            }
        })
            ->when($partner_id, function ($query) use ($partner_id) {
                $query->where('collection_batches.partner_id', $partner_id);
            });
    }
}
