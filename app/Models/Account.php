<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'allowed_periodicities' => 'array',
    ];

    public const PERIODICITY_DAILY = 'DAILY';
    public const PERIODICITY_WEEKLY = 'WEEKLY';
    public const PERIODICITY_MONTHLY = 'MONTHLY';
    public const PERIODICITY_QUARTERLY = 'QUARTERLY';
    public const PERIODICITY_YEARLY = 'YEARLY';

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->slug = Str::slug($model->name);
        });
    }

    function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    public static function supportedPeriodicities(): array
    {
        return [
            self::PERIODICITY_DAILY,
            self::PERIODICITY_WEEKLY,
            self::PERIODICITY_MONTHLY,
            self::PERIODICITY_QUARTERLY,
            self::PERIODICITY_YEARLY,
        ];
    }

    public static function periodicityOptions(): array
    {
        return array_combine(self::supportedPeriodicities(), self::supportedPeriodicities());
    }

    public function normalizedAllowedPeriodicities(): array
    {
        $values = $this->allowed_periodicities;

        if (is_string($values) && $values !== '') {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : explode(',', $values);
        }

        if (!is_array($values) || empty($values)) {
            return [self::PERIODICITY_MONTHLY];
        }

        return collect($values)
            ->map(function ($value) {
                return match (strtoupper(trim((string) $value))) {
                    'DAILY' => self::PERIODICITY_DAILY,
                    'WEEKLY' => self::PERIODICITY_WEEKLY,
                    'MONTHLY' => self::PERIODICITY_MONTHLY,
                    'QUARTELY', 'QUARTERLY' => self::PERIODICITY_QUARTERLY,
                    'ANNUALLY', 'YEARLY' => self::PERIODICITY_YEARLY,
                    default => null,
                };
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function supportsPeriodicity(?string $periodicity): bool
    {
        if (!$periodicity) {
            return false;
        }

        $normalized = match (strtoupper(trim((string) $periodicity))) {
            'DAILY' => self::PERIODICITY_DAILY,
            'WEEKLY' => self::PERIODICITY_WEEKLY,
            'MONTHLY' => self::PERIODICITY_MONTHLY,
            'QUARTELY', 'QUARTERLY' => self::PERIODICITY_QUARTERLY,
            'ANNUALLY', 'YEARLY' => self::PERIODICITY_YEARLY,
            default => null,
        };

        return $normalized !== null && in_array($normalized, $this->normalizedAllowedPeriodicities(), true);
    }
}
