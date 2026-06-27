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
    protected $hidden = ['callback_secret'];

    public const LIFECYCLE_DRAFT = 'draft';
    public const LIFECYCLE_SUBMITTED = 'submitted';
    public const LIFECYCLE_UNDER_REVIEW = 'under_review';
    public const LIFECYCLE_REJECTED = 'rejected';
    public const LIFECYCLE_APPROVED = 'approved';
    public const LIFECYCLE_PENDING_ACCOUNT_ACTIVATION = 'pending_account_activation';
    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_FAILED = 'failed';
    public const LIFECYCLE_ARCHIVED = 'archived';

    public const REGISTRATION_SOURCE_SELF_MOBILE = 'self-mobile';
    public const REGISTRATION_SOURCE_SELF_WEB = 'self-web';
    public const REGISTRATION_SOURCE_OFFICER = 'officer';

    public static function registrationSourceOptions(): array
    {
        return [
            self::REGISTRATION_SOURCE_SELF_MOBILE => 'Self Registration (Mobile)',
            self::REGISTRATION_SOURCE_SELF_WEB => 'Self Registration (Web)',
            self::REGISTRATION_SOURCE_OFFICER => 'Officer',
        ];
    }

    public function getRegistrationSourceLabelAttribute(): string
    {
        return static::registrationSourceOptions()[$this->registration_source]
            ?? ucwords(str_replace('-', ' ', (string) $this->registration_source));
    }

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            //create short code and client code
            do {
                $shortCode = strtoupper(Str::random(3));
                $clientCode = substr($shortCode, 0, 2);

                $exists = self::where('short_code', $shortCode)
                    ->orWhere('client_code', $clientCode)
                    ->exists();
            } while ($exists);

            $model->reference = Partner::document_number("reference", "CO", 4, true, false);
            $model->short_code = $shortCode;
            $model->client_code = $clientCode;
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
        $lifecycle_status  = $request['lifecycle_status'] ?? null;
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
            ->when(!is_null($lifecycle_status) && $lifecycle_status !== '', function ($q) use ($lifecycle_status) {
                $q->where(function ($statusQuery) use ($lifecycle_status) {
                    $statusQuery->where('lifecycle_status', $lifecycle_status);

                    if ($lifecycle_status === self::LIFECYCLE_REJECTED) {
                        $statusQuery->orWhere('approved', 99);
                    }

                    if ($lifecycle_status === self::LIFECYCLE_ACTIVE) {
                        $statusQuery->orWhere(function ($legacy) {
                            $legacy->where('approved', 1)
                                ->whereDoesntHave('partnerAccounts', function ($accountQuery) {
                                    $accountQuery->where('channel', 'NMB')
                                        ->where('is_verified', 0);
                                });
                        });
                    }
                });
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

    public function getResolvedLifecycleStatusAttribute(): string
    {
        if (!empty($this->lifecycle_status)) {
            return $this->lifecycle_status;
        }

        if ((int) $this->approved === 99) {
            return self::LIFECYCLE_REJECTED;
        }

        if ((int) $this->approved === 1) {
            return $this->resolveApprovedLifecycleStatus();
        }

        if ((int) $this->complete === 1) {
            return self::LIFECYCLE_SUBMITTED;
        }

        return self::LIFECYCLE_DRAFT;
    }

    public function resolveApprovedLifecycleStatus(): string
    {
        if ($this->hasFailedNmbAccount()) {
            return self::LIFECYCLE_FAILED;
        }

        if ($this->hasPendingNmbAccount()) {
            return self::LIFECYCLE_PENDING_ACCOUNT_ACTIVATION;
        }

        return self::LIFECYCLE_ACTIVE;
    }

    public function hasPendingNmbAccount(): bool
    {
        return $this->partnerAccounts()
            ->where('channel', 'NMB')
            ->where('is_verified', 0)
            ->whereNull('rejected_at')
            ->exists();
    }

    public function hasFailedNmbAccount(): bool
    {
        return $this->partnerAccounts()
            ->where('channel', 'NMB')
            ->where('is_verified', 0)
            ->whereNotNull('rejected_at')
            ->exists();
    }

    public function refreshLifecycleStatus(): self
    {
        $this->lifecycle_status = match (true) {
            (int) $this->approved === 99 => self::LIFECYCLE_REJECTED,
            (int) $this->approved === 1 => $this->resolveApprovedLifecycleStatus(),
            (int) $this->complete === 1 => self::LIFECYCLE_SUBMITTED,
            default => self::LIFECYCLE_DRAFT,
        };

        return $this;
    }

    public static function lifecycleOptions(): array
    {
        return [
            self::LIFECYCLE_DRAFT => 'Draft',
            self::LIFECYCLE_SUBMITTED => 'Submitted',
            self::LIFECYCLE_UNDER_REVIEW => 'Under Review',
            self::LIFECYCLE_REJECTED => 'Rejected',
            self::LIFECYCLE_APPROVED => 'Approved',
            self::LIFECYCLE_PENDING_ACCOUNT_ACTIVATION => 'Pending Account Activation',
            self::LIFECYCLE_ACTIVE => 'Active',
            self::LIFECYCLE_FAILED => 'Failed',
            self::LIFECYCLE_ARCHIVED => 'Archived',
        ];
    }
}
