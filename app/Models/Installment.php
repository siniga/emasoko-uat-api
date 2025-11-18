<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Installment extends Model
{
    use HasFactory;
    protected $guarded = [];

    //filter condition
    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::user()->id);

        //installments
        $installments = static::query();

        //filter installments
        $installments = $installments->whereHas('debitOrder');

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => $user->id])->first();

            if ($company_user) {
                $installments = $installments->whereHas('debitOrder',  function ($query) use ($company_user) {
                    $query->whereHas('partner', function ($query) use ($company_user) {
                        $query->where('partners.company_id', '=', $company_user->company_id);
                    });
                });
            }
        } else if ($user->hasRole('Company_Approver')) {
            $arr_partners = [];

            //partners
            $partners = $user->partners->where('authorize', '=', 1);

            foreach ($partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners) {
                $installments = $installments->whereHas('debitOrder', function ($query) use ($arr_partners) {
                    $query->whereIn('debit_orders.partner_id', $arr_partners);
                });
            }
        } else if ($user->hasRole('Company_User')) {
            $installments = $installments->whereHas('debitOrder', function ($query) use ($user) {
                $query->where('debit_orders.created_by', '=', $user->id);
            });
        }

        return $installments;
    }

    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, "debit_order_id");
    }

    public static function getCreated(Partner $partner, $current_date)
    {
        //installments
        $installments = static::query();

        $installments = $installments->whereHas('debitOrder',  function ($query) use ($partner) {
            $query->where('debit_orders.approved', '=', 1)
                ->where('debit_orders.status', '=', 240)
                ->where('contract_status', '=', 'OPEN')
                ->where('deleted', '=', 0)
                ->whereHas('partner', function ($query) use ($partner) {
                    $query->where('partners.id', '=', $partner->id);
                });
        })->where('installments.status', '=', 'OPEN')
            ->where('installments.created', '=', 0)
            ->whereDate('installments.payment_date', '=', $current_date);

        return $installments;
    }

    // Get today's statistics
    public static function todayStats($date = null)
    {
        $date = $date ?? now()->toDateString(); // Default to today's date: 'Y-m-d'

        return self::filterCondition()
            ->select([
                DB::raw("COALESCE(SUM(CASE 
                WHEN DATE(payment_date) = '{$date}' 
                THEN installment_amount ELSE 0 END), 0) as total_amount"),

                DB::raw("COALESCE(SUM(CASE 
                WHEN DATE(effective_payment_date) = '{$date}' 
                THEN current_balance ELSE 0 END), 0) as paid_amount")
            ])
            ->whereHas('debitOrder', function ($query) {
                $query->where([
                    'debit_orders.approved' => 1,
                ])->whereHas('partner', function ($q) {
                    $q->where('partners.approved', 1);
                });
            })
            ->first();
    }

    // Get this month's statistics
    public static function thisMonthStats($month = null, $year = null, $paid = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return self::filterCondition()
            ->select([
                DB::raw("COALESCE(SUM(CASE 
                WHEN MONTH(payment_date) = {$month} AND YEAR(payment_date) = {$year} 
                THEN installment_amount ELSE 0 END), 0) as total_amount"),

                DB::raw("COALESCE(SUM(CASE 
                WHEN MONTH(effective_payment_date) = {$month} AND YEAR(effective_payment_date) = {$year} AND status='CLOSED'
                THEN current_balance ELSE 0 END), 0) as paid_amount")
            ])
            ->whereHas('debitOrder', function ($query) {
                $query->where([
                    'debit_orders.approved' => 1
                ])->whereHas('partner', function ($q) {
                    $q->where('partners.approved', 1);
                });
            })
            ->first();
    }

    // Get today's collection statistics for a specific partner
    public static function todayCollectionStats($partnerId, $date = null, $status = null, $deleted = null, $paid = null)
    {
        $date = $date ?? now()->toDateString(); // Default to today's date: 'Y-m-d'

        return self::select([
            DB::raw("COALESCE(SUM(CASE
            WHEN DATE(payment_date) = '{$date}'
            THEN 1 ELSE 0 END), 0) as number_of_collection"),

            DB::raw("COALESCE(SUM(CASE
            WHEN DATE(payment_date) = '{$date}'
            THEN installment_amount ELSE 0 END), 0) as total_amount"),

            DB::raw("COALESCE(SUM(CASE
            WHEN DATE(payment_date) = '{$date}'
            THEN outstanding_amount ELSE 0 END), 0) as outstanding_amount"),

            DB::raw("COALESCE(SUM(CASE
            WHEN DATE(effective_payment_date) = '{$date}' AND status='CLOSED'
            THEN current_balance ELSE 0 END), 0) as paid_amount")
        ])
            ->whereHas('debitOrder', function ($query) use ($partnerId, $status, $deleted) {
                $query->where('debit_orders.approved', 1)
                    ->when($partnerId, function ($q) use ($partnerId) {
                        $q->where('debit_orders.partner_id', $partnerId);
                    })
                    ->when($status, function ($q) use ($status) {
                        $q->where('debit_orders.status', $status);
                    })
                    ->when($deleted, function ($q) use ($deleted) {
                        $q->where('debit_orders.deleted', $deleted);
                    });
            })->first();
    }

    // Get monthly collection statistics for a specific partner
    public static function monthlyCollectionStats($partnerId, $month = null, $year = null, $status = null, $deleted = null, $paid = null)
    {
        $month = $month ?? now()->month;
        $year  = $year ?? now()->year;

        return self::select([
            DB::raw("COALESCE(SUM(CASE
            WHEN MONTH(payment_date) = {$month} AND YEAR(payment_date) = {$year}
            THEN 1 ELSE 0 END), 0) as number_of_collection"),

            DB::raw("COALESCE(SUM(CASE
            WHEN MONTH(payment_date) = {$month} AND YEAR(payment_date) = {$year}
            THEN installment_amount ELSE 0 END), 0) as total_amount"),

            DB::raw("COALESCE(SUM(CASE
            WHEN MONTH(payment_date) = {$month} AND YEAR(payment_date) = {$year}
            THEN outstanding_amount ELSE 0 END), 0) as outstanding_amount"),

            DB::raw("COALESCE(SUM(CASE
            WHEN MONTH(effective_payment_date) = {$month} AND YEAR(effective_payment_date) = {$year} AND status='CLOSED'
            THEN current_balance ELSE 0 END), 0) as paid_amount")
        ])
        ->whereHas('debitOrder', function ($query) use ($partnerId, $status, $deleted) {
                $query->where('debit_orders.approved', 1)
                    ->when($partnerId, function ($q) use ($partnerId) {
                        $q->where('debit_orders.partner_id', $partnerId);
                    })
                    ->when($status, function ($q) use ($status) {
                        $q->where('debit_orders.status', $status);
                    })
                    ->when($deleted, function ($q) use ($deleted) {
                        $q->where('debit_orders.deleted', $deleted);
                    });
            })->first();
    }
}
