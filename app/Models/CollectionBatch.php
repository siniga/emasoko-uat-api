<?php

namespace App\Models;

use Carbon\Carbon;
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
            self::LIFECYCLE_QUEUED_FOR_PUSH,
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

    public function scopeWithFilters($query, $request)
    {
        $start_date        = $request['start_at'] ?? null;
        $end_date          = $request['end_at'] ?? null;
        $partner_id        = $request['partner_id'] ?? null;
        $status            = $request['cl_status'] ?? $request['status'] ?? null;
        $mandate_reference = $request['mandate_reference'] ?? null;

        return $query->when($start_date, function ($query) use ($start_date, $end_date) {
            if ($start_date != null || $end_date != null) {
                //format dates
                $start_date = Carbon::createFromFormat('Y-m-d', $start_date)->startOfDay();
                $end_date = Carbon::createFromFormat('Y-m-d', $end_date)->endOfDay();

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
            })
            ->when($status, function ($query) use ($status) {
                if ($status === 'uploaded') {
                    $query->where('collection_batches.pushed', 1);
                } elseif ($status === 'processed') {
                    $query->where('collection_batches.processed', 1);
                } elseif ($status === 'failed') {
                    $query->where('collection_batches.failed', 1);
                }
            })
            ->when($mandate_reference, function ($query) use ($mandate_reference) {
                $ids = \App\Models\Installment::query()
                    ->join('debit_orders as d', 'd.id', '=', 'installments.debit_order_id')
                    ->where('d.reference', $mandate_reference)
                    ->pluck('installments.id')
                    ->all();

                // If none, return no results quickly
                if (empty($ids)) {
                    $query->whereRaw('1=0');
                    return;
                }

                // Build: FIND_IN_SET(1, installment_ids) OR FIND_IN_SET(2, installment_ids) ...
                $query->where(function ($q) use ($ids) {
                    foreach ($ids as $id) {
                        $q->orWhereRaw('FIND_IN_SET(?, collection_batches.installment_ids) > 0', [$id]);
                    }
                });
            });
    }
}
