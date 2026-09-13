<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectionBatch extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $incrementing = false;

    public const LIFECYCLE_DRAFT = 'draft';
    public const LIFECYCLE_READY = 'ready';
    public const LIFECYCLE_QUEUED_FOR_PUSH = 'queued_for_push';
    public const LIFECYCLE_PUSHED = 'pushed';
    public const LIFECYCLE_STATUS_PENDING = 'status_pending';
    public const LIFECYCLE_PARTIALLY_PROCESSED = 'partially_processed';
    public const LIFECYCLE_PROCESSED_SUCCESS = 'processed_success';
    public const LIFECYCLE_PROCESSED_FAILED = 'processed_failed';
    public const LIFECYCLE_RECREATED = 'recreated';
    public const LIFECYCLE_ARCHIVED = 'archived';

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
        $user = Auth::user();

        $batches = static::orderBy('collection_batches.created_at', 'DESC');

        if ($user->hasRole('Partner')) {
            $company_user = CompanyUser::where(['user_id' => $user->id])->first();

            if ($company_user) {
                $batches = $batches->whereHas('partner', function ($query) use ($company_user) {
                    $query->where('partners.company_id', '=', $company_user->company_id);
                });
            } else {
                $batches->whereRaw('1 = 0');
            }
        } else if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $arr_partners = [];

            $partners = $user->partners->where('authorize', '=', 1);
            foreach ($partners as $val)
                array_push($arr_partners, $val->partner_id);

            if ($arr_partners) {
                $batches = $batches->whereIn('collection_batches.partner_id', $arr_partners);
            } else {
                $batches->whereRaw('1 = 0');
            }
        }

        //filter where deleted = 0
        $batches = $batches->where('collection_batches.deleted', '=', 0);

        return $batches;
    }

    public function debits()
    {
        return $this->hasMany(CollectionDebitOrder::class, "collection_batch_id");
    }

public function pushAttempts()
{
    return $this->hasMany(CollectionBatchPushAttempt::class, "collection_batch_id");
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

    public function installments()
    {
        return $this->hasManyThrough(
            Installment::class,
            CollectionDebitOrder::class,
            'collection_batch_id',
            'id',
            'id',
            'installment_id'
        );
    }

    public function getInstallmentIdListAttribute(): array
    {
        $lineIds = $this->debits()
            ->whereNotNull('installment_id')
            ->pluck('installment_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (!empty($lineIds)) {
            return $lineIds;
        }

        if (empty($this->installment_ids)) {
            return [];
        }

        return collect(explode(',', (string) $this->installment_ids))
            ->filter()
            ->map(fn ($id) => (int) trim($id))
            ->values()
            ->all();
    }

    public function getResolvedLifecycleStatusAttribute(): string
    {
        if (!empty($this->lifecycle_status)) {
            return $this->lifecycle_status;
        }

        if ((int) $this->deleted === 1) {
            return self::LIFECYCLE_ARCHIVED;
        }

        if ((int) $this->recreated === 1) {
            return self::LIFECYCLE_RECREATED;
        }

        if ((int) $this->failed === 1) {
            return self::LIFECYCLE_PROCESSED_FAILED;
        }

        if ((int) $this->processed === 1) {
            return self::LIFECYCLE_PROCESSED_SUCCESS;
        }

        if ((int) $this->pushed === 1) {
            return self::LIFECYCLE_PUSHED;
        }

        return self::LIFECYCLE_READY;
    }

    public function setLifecycleStatus(string $status): self
    {
        $this->lifecycle_status = $status;

        return $this;
    }

    public function canBePushed(): bool
    {
        return in_array($this->resolved_lifecycle_status, [
            self::LIFECYCLE_READY,
        ], true) && !empty($this->request_csv);
    }

    public function canFetchStatus(): bool
    {
        return in_array($this->resolved_lifecycle_status, [
            self::LIFECYCLE_PUSHED,
            self::LIFECYCLE_STATUS_PENDING,
            self::LIFECYCLE_PARTIALLY_PROCESSED,
        ], true);
    }

    public function getLifecycleLabelAttribute(): string
    {
        return match ($this->resolved_lifecycle_status) {
            self::LIFECYCLE_READY => 'Ready',
            self::LIFECYCLE_QUEUED_FOR_PUSH => 'Queued for Push',
            self::LIFECYCLE_PUSHED => 'Uploaded',
            self::LIFECYCLE_STATUS_PENDING => 'Awaiting Status',
            self::LIFECYCLE_PARTIALLY_PROCESSED => 'Partially Processed',
            self::LIFECYCLE_PROCESSED_SUCCESS => 'Processed Success',
            self::LIFECYCLE_PROCESSED_FAILED => 'Processed Failed',
            self::LIFECYCLE_RECREATED => 'Recreated',
            self::LIFECYCLE_ARCHIVED => 'Archived',
            default => 'Draft',
        };
    }

    public function getLifecycleBadgeClassAttribute(): string
    {
        return match ($this->resolved_lifecycle_status) {
            self::LIFECYCLE_READY => 'badge-soft-primary',
            self::LIFECYCLE_QUEUED_FOR_PUSH => 'badge-soft-info',
            self::LIFECYCLE_PUSHED => 'badge-soft-info',
            self::LIFECYCLE_STATUS_PENDING => 'badge-soft-warning',
            self::LIFECYCLE_PARTIALLY_PROCESSED => 'badge-soft-warning',
            self::LIFECYCLE_PROCESSED_SUCCESS => 'badge-soft-success',
            self::LIFECYCLE_PROCESSED_FAILED => 'badge-soft-danger',
            self::LIFECYCLE_RECREATED => 'badge-soft-secondary',
            self::LIFECYCLE_ARCHIVED => 'badge-soft-dark',
            default => 'badge-soft-secondary',
        };
    }

    public function getUploadStateLabelAttribute(): string
    {
        return match ($this->resolved_lifecycle_status) {
            self::LIFECYCLE_QUEUED_FOR_PUSH => 'Queued',
            self::LIFECYCLE_PUSHED,
            self::LIFECYCLE_STATUS_PENDING,
            self::LIFECYCLE_PARTIALLY_PROCESSED,
            self::LIFECYCLE_PROCESSED_SUCCESS,
            self::LIFECYCLE_PROCESSED_FAILED,
            self::LIFECYCLE_RECREATED => 'Uploaded',
            default => 'Pending',
        };
    }

    public function getProcessingStateLabelAttribute(): string
    {
        return match ($this->resolved_lifecycle_status) {
            self::LIFECYCLE_STATUS_PENDING => 'Awaiting Status',
            self::LIFECYCLE_PARTIALLY_PROCESSED => 'In Progress',
            self::LIFECYCLE_PROCESSED_SUCCESS,
            self::LIFECYCLE_PROCESSED_FAILED => 'Completed',
            default => 'Pending',
        };
    }

    public function isWorkflowComplete(): bool
    {
        return in_array($this->resolved_lifecycle_status, [
            self::LIFECYCLE_PARTIALLY_PROCESSED,
            self::LIFECYCLE_PROCESSED_SUCCESS,
            self::LIFECYCLE_PROCESSED_FAILED,
            self::LIFECYCLE_RECREATED,
            self::LIFECYCLE_ARCHIVED,
        ], true);
    }

    public function scopeWithFilters($query, array $filters)
    {
        $startDate = $filters['start_at'] ?? null;
        $endDate = $filters['end_at'] ?? null;
        $partnerId = $filters['partner_id'] ?? null;
        $status = $filters['cl_status'] ?? $filters['status'] ?? null;
        $collectionReference = trim((string) ($filters['collection_reference'] ?? ''));
        $search = trim((string) ($filters['mandate_reference'] ?? ''));
        $lifecycleExpression = static::lifecycleSqlExpression();

        return $query
            ->when($startDate, function ($query, $value) {
                $query->where('collection_batches.created_at', '>=', Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
            })
            ->when($endDate, function ($query, $value) {
                $query->where('collection_batches.created_at', '<=', Carbon::createFromFormat('Y-m-d', $value)->endOfDay());
            })
            ->when($partnerId, function ($query, $value) {
                $query->where('collection_batches.partner_id', $value);
            })
            ->when($collectionReference !== '', function ($query) use ($collectionReference) {
                $query->where('collection_batches.collectionReference', 'like', "%{$collectionReference}%");
            })
            ->when($status, function ($query, $value) use ($lifecycleExpression) {
                $statuses = match ($value) {
                    'ready' => [self::LIFECYCLE_DRAFT, self::LIFECYCLE_READY],
                    'in_progress' => [
                        self::LIFECYCLE_QUEUED_FOR_PUSH,
                        self::LIFECYCLE_PUSHED,
                        self::LIFECYCLE_STATUS_PENDING,
                        self::LIFECYCLE_PARTIALLY_PROCESSED,
                    ],
                    'successful' => [self::LIFECYCLE_PROCESSED_SUCCESS],
                    'failed' => [self::LIFECYCLE_PROCESSED_FAILED],
                    default => [$value],
                };

                $query->whereIn(DB::raw($lifecycleExpression), $statuses);
            })
            ->when($search !== '', function ($query) use ($search) {
                $normalizedSearch = CollectionDebitOrder::normalizeAccountNumber($search);

                $query->where(function ($searchQuery) use ($search, $normalizedSearch) {
                    $searchQuery->whereHas('debits', function ($lineQuery) use ($search, $normalizedSearch) {
                            $lineQuery->where('account_number', $normalizedSearch)
                                ->orWhereHas('debitOrder', function ($mandateQuery) use ($search) {
                                    $mandateQuery->where('reference', $search);
                                });
                        })
                        ->orWhere(function ($legacyQuery) use ($search, $normalizedSearch) {
                            $legacyQuery->whereDoesntHave('debits')
                                ->whereExists(function ($installmentQuery) use ($search, $normalizedSearch) {
                                    $installmentQuery->selectRaw('1')
                                        ->from('installments')
                                        ->join('debit_orders as legacy_debit_orders', 'legacy_debit_orders.id', '=', 'installments.debit_order_id')
                                        ->leftJoin('customer_accounts as legacy_accounts', 'legacy_accounts.id', '=', 'legacy_debit_orders.customer_account_id')
                                        ->whereRaw('FIND_IN_SET(installments.id, collection_batches.installment_ids) > 0')
                                        ->where(function ($matchQuery) use ($search, $normalizedSearch) {
                                            $matchQuery->where('legacy_debit_orders.reference', $search)
                                                ->orWhereRaw("REPLACE(legacy_accounts.account_number, ' ', '') = ?", [$normalizedSearch]);
                                        });
                                });
                        });
                });
            });
    }

    public static function lifecycleSqlExpression(): string
    {
        return "COALESCE(collection_batches.lifecycle_status, CASE
            WHEN collection_batches.deleted = 1 THEN 'archived'
            WHEN collection_batches.recreated = 1 THEN 'recreated'
            WHEN collection_batches.failed = 1 THEN 'processed_failed'
            WHEN collection_batches.processed = 1 THEN 'processed_success'
            WHEN collection_batches.pushed = 1 THEN 'pushed'
            ELSE 'ready'
        END)";
    }
}
