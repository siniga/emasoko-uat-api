<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerAccount extends Model
{
    use HasFactory;

    public const ONBOARDING_PENDING_REVIEW = 'pending_emakato_review';
    public const ONBOARDING_NOT_REQUIRED = 'bank_approval_not_required';
    public const ONBOARDING_SUBMITTED = 'submitted_to_bank';
    public const ONBOARDING_PENDING_ACTIVATION = 'pending_bank_activation';
    public const ONBOARDING_ACTIVE = 'active';
    public const ONBOARDING_REJECTED = 'rejected';
    public const ONBOARDING_TECHNICAL_ERROR = 'technical_error';
    public const ONBOARDING_REVIEW_REQUIRED = 'review_required';

    protected $guarded = [];

    protected $casts = [
        'bank_response_payload' => 'array',
        'bank_submitted_at' => 'datetime',
        'bank_status_checked_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public static function onboardingOptions(): array
    {
        return [
            self::ONBOARDING_PENDING_REVIEW => 'Pending eMakato Review',
            self::ONBOARDING_NOT_REQUIRED => 'Bank Approval Not Required',
            self::ONBOARDING_SUBMITTED => 'Submitted to Bank',
            self::ONBOARDING_PENDING_ACTIVATION => 'Pending Bank Activation',
            self::ONBOARDING_ACTIVE => 'Active',
            self::ONBOARDING_REJECTED => 'Rejected',
            self::ONBOARDING_TECHNICAL_ERROR => 'Technical Error',
            self::ONBOARDING_REVIEW_REQUIRED => 'Review Required',
        ];
    }

    public function getOnboardingStatusLabelAttribute(): string
    {
        return static::onboardingOptions()[$this->onboarding_status]
            ?? ucwords(str_replace('_', ' ', (string) $this->onboarding_status));
    }

    public function isBankApproved(): bool
    {
        return str_contains(strtoupper((string) $this->channel), 'NMB')
            && $this->onboarding_status === self::ONBOARDING_ACTIVE;
    }

    public function canBeDeleted(): bool
    {
        return (int) $this->is_verified !== 1 && !$this->isOperational();
    }

    public function isOperational(): bool
    {
        return in_array($this->onboarding_status, [
            self::ONBOARDING_ACTIVE,
            self::ONBOARDING_NOT_REQUIRED,
        ], true);
    }

    public static function inferOnboardingStatus(
        ?string $channel,
        bool $isVerified,
        ?string $remarks = null,
        bool $isRejected = false,
        int $sendVerification = 0
    ): string {
        $channel = strtoupper((string) $channel);
        $remarks = strtolower((string) $remarks);

        if (str_contains($channel, 'CRDB')) {
            return $isVerified
                ? self::ONBOARDING_NOT_REQUIRED
                : self::ONBOARDING_PENDING_REVIEW;
        }

        if (!str_contains($channel, 'NMB')) {
            return self::ONBOARDING_REVIEW_REQUIRED;
        }

        if ($isRejected) {
            return self::ONBOARDING_REJECTED;
        }

        if ($isVerified && str_contains($remarks, 'active') && !str_contains($remarks, 'not active')) {
            return self::ONBOARDING_ACTIVE;
        }

        if ($sendVerification === 2 && !$isVerified) {
            return self::ONBOARDING_SUBMITTED;
        }

        return $isVerified
            ? self::ONBOARDING_REVIEW_REQUIRED
            : self::ONBOARDING_PENDING_REVIEW;
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, "currency_id");
    }
}
