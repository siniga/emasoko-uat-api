<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebitOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    //where condition
    public static function where_cond()
    {
        $user = User::findOrFail(Auth::user()->id);

        //customers
        $debit_orders = static::orderBy('debit_orders.created_at', 'DESC');

        if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $partner_user = PartnerUser::where(['user_id' => $user->id])->first();

            if ($partner_user) {
                $debit_orders = $debit_orders->where('debit_orders.partner_id', '=', $partner_user->partner_id);
            }
        }

        return $debit_orders;
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 'active');
    }

    function installments()
    {
        return $this->hasMany(Installment::class, 'debit_order_id');
    }

    function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    function loanPurpose()
    {
        return $this->belongsTo(LoanPurpose::class, 'loan_purpose_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, "partner_id");
    }

    public function userCreated()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }

    public function cancellationStarted()
    {
        return $this->belongsTo(User::class, "cancellationStartedBy", "id");
    }

    public function cancellationApproved()
    {
        return $this->belongsTo(User::class, "cancellationApprovedBy", "id");
    }

    public function getPartnerAccountAttribute()
    {
        return PartnerAccount::toBase()
            ->where("partner_id", $this->partner->id)
            ->where('account_id', $this->customerAccount->account_id)
            ->first();
    }

    public function scopeWithFilters($query, $request)
    {
        $start_date        = $request['start_at'] ?? null;
        $end_date          = $request['end_at'] ?? null;
        $account_type_id   = $request['account_type_id'] ?? null;
        $account_id        = $request['account_id'] ?? null;
        $status            = $request['status'] ?? null;
        $keyword           = $request['keyword'] ?? null;

        return $query->when($start_date, function ($query) use ($start_date, $end_date) {
            if ($start_date != null || $end_date != null) {
                if ($start_date != null && $end_date != null)
                    $query->whereBetween('debit_orders.created_at', [$start_date, $end_date]);

                else if ($start_date != null)
                    $query->where('debit_orders.created_at', '>=', $start_date);

                else if ($end_date != null)
                    $query->where('debit_orders.created_at', '<=', $end_date);
            }
        })
            ->when($status, function ($query) use ($status) {
                $query->where('debit_orders.approved', $status);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->whereHas("customerAccount", function ($q) use ($keyword) {
                    $q->where(DB::raw('LOWER(account_name)'), 'like', "%".strtolower($keyword)."%")
                        ->orWhere(DB::raw('LOWER(account_number)'), 'like', "%".strtolower($keyword)."%");
                });
            });
    }
}
