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

    public const LIFECYCLE_DRAFT = 'draft';
    public const LIFECYCLE_SUBMITTED = 'submitted';
    public const LIFECYCLE_AWAITING_OTP = 'awaiting_otp';
    public const LIFECYCLE_VERIFIED = 'verified';
    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_CANCELLATION_REQUESTED = 'cancellation_requested';
    public const LIFECYCLE_CANCELLED = 'cancelled';
    public const LIFECYCLE_CLOSED = 'closed';
    public const LIFECYCLE_ARCHIVED = 'archived';
    public const LIFECYCLE_FAILED = 'failed';

    public const SUMMARY_PENDING = 'pending';
    public const SUMMARY_APPROVED = 'approved';
    public const SUMMARY_FAILED = 'failed';

    //filter condition
    public static function filterCondition(bool $includeDeleted = false)
    {
        $user = User::findOrFail(Auth::user()->id);

        //debit orders
        $debit_orders = static::query()
            ->when(!$includeDeleted, fn ($query) => $query->where('deleted', 0));

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => $user->id])->first();

            if ($company_user) {
                $debit_orders = $debit_orders->whereHas('partner', function ($query) use ($company_user) {
                    $query->where('partners.company_id', '=', $company_user->company_id);
                });
            } else {
                $debit_orders->whereRaw('1 = 0');
            }
        } else if ($user->hasRole('Company_Approver')) {
            $arr_partners = [];

            $partners = $user->partners->where('authorize', '=', 1);
            foreach ($partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners) {
                $debit_orders = $debit_orders
                    ->whereIn('debit_orders.partner_id', $arr_partners);
            } else {
                $debit_orders->whereRaw('1 = 0');
            }
        } else if ($user->hasRole('Company_User')) {
            $debit_orders = $debit_orders
                ->where('debit_orders.created_by', '=', $user->id);
        }

        return $debit_orders;
    }

    public function scopeCancelled($query)
    {
        return $query->where('lifecycle_status', 'cancelled');
    }

    public function scopeActive($query)
    {
        return $query->where('lifecycle_status', '=', 'active');
    }

    public function scopeEligibleForCollection($query, ?string $channel = null)
    {
        return $query
            ->where('debit_orders.approved', 1)
            ->where('debit_orders.deleted', 0)
            ->where('debit_orders.contract_status', 'OPEN')
            ->where('debit_orders.lifecycle_status', self::LIFECYCLE_ACTIVE)
            ->when($channel !== null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            });
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

    public function getRemarksMessageAttribute()
    {
        $decoded = json_decode($this->remarks, true);

        return is_array($decoded)
            ? reset($decoded)
            : $this->remarks;
    }

    public function getResolvedLifecycleStatusAttribute(): string
    {
        if (!empty($this->lifecycle_status)) {
            return $this->lifecycle_status;
        }

        if ((int) $this->archived === 1) {
            return self::LIFECYCLE_ARCHIVED;
        }

        if ((int) $this->closed === 1 || strtoupper((string) $this->contract_status) === 'CLOSED') {
            return self::LIFECYCLE_CLOSED;
        }

        if ((int) $this->cancelled === 1) {
            return self::LIFECYCLE_CANCELLED;
        }

        if ($this->debitOrderRequests()->where('request_type', 'CANCELLATION')->where('status', 'PENDING')->exists()) {
            return self::LIFECYCLE_CANCELLATION_REQUESTED;
        }

        return match ((int) $this->status) {
            240 => self::LIFECYCLE_ACTIVE,
            202 => self::LIFECYCLE_VERIFIED,
            201 => self::LIFECYCLE_AWAITING_OTP,
            200, 249 => self::LIFECYCLE_SUBMITTED,
            403, 404, 405, 430, 500, 501 => self::LIFECYCLE_FAILED,
            100 => self::LIFECYCLE_DRAFT,
            default => self::LIFECYCLE_DRAFT,
        };
    }

    public static function lifecycleSummaryMap(): array
    {
        return [
            self::SUMMARY_PENDING => [
                self::LIFECYCLE_DRAFT,
                self::LIFECYCLE_SUBMITTED,
                self::LIFECYCLE_AWAITING_OTP,
                self::LIFECYCLE_VERIFIED,
            ],
            self::SUMMARY_APPROVED => [
                self::LIFECYCLE_ACTIVE,
                self::LIFECYCLE_CANCELLATION_REQUESTED,
                self::LIFECYCLE_CANCELLED,
                self::LIFECYCLE_CLOSED,
                self::LIFECYCLE_ARCHIVED,
            ],
            self::SUMMARY_FAILED => [
                self::LIFECYCLE_FAILED,
            ],
        ];
    }

    public static function lifecycleStatusesForSummary(string $summary): array
    {
        return static::lifecycleSummaryMap()[$summary] ?? [];
    }

    public static function lifecycleStatusLabels(): array
    {
        return [
            self::SUMMARY_PENDING => 'Pending (All)',
            self::LIFECYCLE_DRAFT => 'Draft',
            self::LIFECYCLE_SUBMITTED => 'Submitted',
            self::LIFECYCLE_AWAITING_OTP => 'Awaiting OTP',
            self::LIFECYCLE_VERIFIED => 'Verified',
            self::LIFECYCLE_ACTIVE => 'Active',
            self::LIFECYCLE_CANCELLATION_REQUESTED => 'Cancellation Requested',
            self::LIFECYCLE_CANCELLED => 'Cancelled',
            self::LIFECYCLE_CLOSED => 'Closed',
            self::LIFECYCLE_ARCHIVED => 'Archived',
            self::LIFECYCLE_FAILED => 'Failed',
        ];
    }

    public static function lifecycleStatusLabel(?string $status): string
    {
        $normalized = static::normalizeLifecycleStatus($status);

        return static::lifecycleStatusLabels()[$normalized]
            ?? ucwords(str_replace('_', ' ', (string) $normalized));
    }

    public static function normalizeLifecycleStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return match ($normalized) {
            'awaitingotp' => self::LIFECYCLE_AWAITING_OTP,
            'cancellationrequested' => self::LIFECYCLE_CANCELLATION_REQUESTED,
            default => $normalized,
        };
    }

    public static function lifecycleStatusFilterOptions(): array
    {
        return collect(static::lifecycleStatusLabels())
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    public function getSummaryStatusAttribute(): string
    {
        $resolved = $this->resolved_lifecycle_status;

        foreach (static::lifecycleSummaryMap() as $summary => $statuses) {
            if (in_array($resolved, $statuses, true)) {
                return $summary;
            }
        }

        return self::SUMMARY_PENDING;
    }

    public function getSummaryStatusLabelAttribute(): string
    {
        return strtoupper($this->summary_status);
    }

    public function setLifecycleStatus(string $status): self
    {
        $this->lifecycle_status = $status;

        return $this;
    }

    public function getPartnerAccountAttribute()
    {
        return PartnerAccount::toBase()
            ->where("partner_id", $this->partner->id)
            ->where('account_id', $this->customerAccount->account_id)
            ->first();
    }

    public static function mandateApprovalStats($channel = null, $year = null)
    {
        return self::filterCondition(includeDeleted: true)
            ->select([
                'debit_orders.approved',
                DB::raw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount'),
                DB::raw('COUNT(id) as number_of_mandate'),
            ])
            ->whereIn('debit_orders.approved', [0, 1, 2])
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', 1);
            })
            ->when($channel != null, function ($q) use ($channel) {
                $q->where('debit_orders.channel', $channel);
            })
            ->when($year != null, function ($q) use ($year) {
                $q->whereYear('debit_orders.created_at', $year);
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
                $query->where('debit_orders.lifecycle_status', $status);
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
                $query->where(function ($q) use ($keyword) {
                    $lowered = strtolower($keyword);

                    // Match reference OR customer account name/number
                    $q->whereRaw('LOWER(reference) LIKE ?', ["%{$lowered}%"])
                        ->orWhereHas('customerAccount', function ($q2) use ($lowered) {
                            $q2->where(function ($q3) use ($lowered) {
                                $q3->whereRaw('LOWER(account_name) LIKE ?', ["%{$lowered}%"])
                                    ->orWhereRaw('LOWER(account_number) LIKE ?', ["%{$lowered}%"]);
                            });
                        });
                });
            });
    }
}
