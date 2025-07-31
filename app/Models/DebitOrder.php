<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebitOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    //filter condition
    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        //debit orders
        $debit_orders = static::query()->where('deleted', '=', 0);

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => $user->id])->first();

            if ($company_user) {
                $debit_orders = $debit_orders->whereHas('partner', function ($query) use ($company_user) {
                    $query->where('partners.company_id', '=', $company_user->company_id);
                });
            }
        } else if ($user->hasRole('Company_Approver')) {
            $arr_partners = [];

            $partners = $user->partners->where('authorize', '=', 1);
            foreach ($partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners)
                $debit_orders = $debit_orders->whereIn('debit_orders.partner_id', $arr_partners);
        } else if ($user->hasRole('Company_User')) {
            $debit_orders = $debit_orders->where('debit_orders.created_by', '=', $user->id);
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

    public function installments()
    {
        return $this->hasMany(Installment::class, 'debit_order_id', 'id');
    }

    public function collectionDebitOrders()
    {
        return $this->hasMany(CollectionDebitOrder::class, 'debit_order_id', 'id');
    }

    public function debitOrderRequests()
    {
        return $this->hasMany(DebitOrderRequest::class, 'debit_order_id', 'id');
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id', 'id');
    }

    public function loanPurpose()
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

    public static function mandateApprovalStats()
    {
        return self::filterCondition()
            ->select([
                'debit_orders.approved',
                DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'),
                DB::raw('COUNT(id) as number_of_mandate'),
            ])
            ->whereIn('debit_orders.approved', [0, 1, 2])
            ->whereHas('partner', function ($q) {
                $q->where('partners.status', 202);
            })
            ->groupBy('debit_orders.approved')
            ->orderBy('debit_orders.approved')
            ->toBase()->get();
    }

    public function scopeWithFilters($query, $request)
    {
        $start_date        = $request['start_at'] ?? null;
        $end_date          = $request['end_at'] ?? null;
        $account_type_id   = $request['account_type_id'] ?? null;
        $account_id        = $request['account_id'] ?? null;
        $partner_id        = $request['partner_id'] ?? null;
        $status            = $request['status'] ?? null;
        $keyword           = $request['keyword'] ?? null;

        return $query->when($start_date, function ($query) use ($start_date, $end_date) {
            if ($start_date != null || $end_date != null) {
                //format dates
                $start_at = Carbon::createFromFormat('Y-m-d', $start_date)->startOfDay();
                $end_at = Carbon::createFromFormat('Y-m-d', $end_date)->endOfDay();

                if ($start_date != null && $end_date != null)
                    $query->whereBetween('debit_orders.created_at', [$start_at, $end_at]);

                else if ($start_date != null)
                    $query->where('debit_orders.created_at', '>=', $start_at);

                else if ($end_date != null)
                    $query->where('debit_orders.created_at', '<=', $end_at);
            }
        })
            ->when($partner_id, function ($query) use ($partner_id) {
                $query->where('debit_orders.partner_id', '=', $partner_id);
            })
            ->when($status, function ($query) use ($status) {
                switch ($status) {
                    case 'pending':
                        $approved = 0;
                        break;
                    case 'approved':
                        $approved = 1;
                        break;
                    case 'failed':
                        $approved = 2;
                        break;
                    default:
                        $approved = 0;
                }

                $query->where('debit_orders.approved', $approved);
            })
            ->when($account_type_id, function ($query) use ($account_type_id) {
                $query->whereHas("customerAccount", function ($q) use ($account_type_id) {
                    $q->where('account_type_id', $account_type_id);
                });
            })
            ->when($account_id, function ($query) use ($account_id) {
                $query->whereHas("customerAccount", function ($q) use ($account_id) {
                    $q->where('account_id', $account_id);
                });
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->whereHas("customerAccount", function ($q) use ($keyword) {
                    $q->where(DB::raw('LOWER(account_name)'), 'like', "%" . strtolower($keyword) . "%")
                        ->orWhere(DB::raw('LOWER(account_number)'), 'like', "%" . strtolower($keyword) . "%");
                });
            });
    }
}
