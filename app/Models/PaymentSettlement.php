<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentSettlement extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_PARTIAL = 'PARTIAL';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_FUNDSCHECK = 'FUNDSCHECK';
    public const STATUS_UNALLOCATED = 'UNALLOCATED';

    public const STATUS_REVERSED = 'REVERSED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_PENDING = 'PENDING';

    protected $casts = [
        'applied_to_balances' => 'boolean',
        'expected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'opening_installment_balance' => 'decimal:2',
        'closing_installment_balance' => 'decimal:2',
        'opening_debit_order_balance' => 'decimal:2',
        'closing_debit_order_balance' => 'decimal:2',
        'effective_payment_date' => 'date',
        'transaction_at' => 'datetime',
    ];

    public static function reportableStatuses(): array
    {
        return [self::STATUS_SUCCESS, self::STATUS_PARTIAL, self::STATUS_REVERSED];
    }

    public static function constrainReportable(
        $query,
        string $statusColumn = 'normalized_status',
        string $amountColumn = 'paid_amount'
    ) {
        return $query->where(function ($reportable) use ($statusColumn, $amountColumn) {
            $reportable
                ->where(function ($positive) use ($statusColumn, $amountColumn) {
                    $positive->whereIn($statusColumn, [self::STATUS_SUCCESS, self::STATUS_PARTIAL])
                        ->where($amountColumn, '>', 0);
                })
                ->orWhere(function ($reversal) use ($statusColumn, $amountColumn) {
                    $reversal->where($statusColumn, self::STATUS_REVERSED)
                        ->where($amountColumn, '<', 0);
                });
        });
    }

    public function scopeReportable($query, string $table = '')
    {
        $prefix = $table === '' ? '' : rtrim($table, '.') . '.';

        return self::constrainReportable(
            $query->where($prefix . 'applied_to_balances', 1)
                ->where($prefix . 'reconciliation_status', 'POSTED')
                ->whereNotNull($prefix . 'effective_payment_date'),
            $prefix . 'normalized_status',
            $prefix . 'paid_amount'
        );
    }

    public function reverse(string $reason): self
    {
        return DB::transaction(function () use ($reason) {
            $settlement = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if (!in_array($settlement->normalized_status, [self::STATUS_SUCCESS, self::STATUS_PARTIAL], true)
                || (float) $settlement->paid_amount <= 0
                || !$settlement->applied_to_balances
                || $settlement->reconciliation_status !== 'POSTED') {
                throw ValidationException::withMessages([
                    'settlement' => 'Only a posted successful or partial settlement can be reversed.',
                ]);
            }

            if (static::query()->where('reverses_settlement_id', $settlement->id)->exists()) {
                throw ValidationException::withMessages([
                    'settlement' => 'This settlement has already been reversed.',
                ]);
            }

            return static::create([
                'channel' => $settlement->channel,
                'collection_batch_id' => $settlement->collection_batch_id,
                'collection_debit_order_id' => $settlement->collection_debit_order_id,
                'debit_order_id' => $settlement->debit_order_id,
                'installment_id' => $settlement->installment_id,
                'mandate_reference' => $settlement->mandate_reference,
                'payment_reference' => 'REV-' . $settlement->id . '-' . now()->format('YmdHis'),
                'external_reference' => $settlement->payment_reference,
                'raw_status' => self::STATUS_REVERSED,
                'normalized_status' => self::STATUS_REVERSED,
                'settlement_type' => 'REVERSAL',
                'applied_to_balances' => true,
                'reconciliation_status' => 'POSTED',
                'reverses_settlement_id' => $settlement->id,
                'expected_amount' => 0,
                'paid_amount' => number_format(-abs((float) $settlement->paid_amount), 2, '.', ''),
                'effective_payment_date' => now()->toDateString(),
                'transaction_at' => now(),
                'remarks' => $reason,
            ]);
        });
    }

    public static function filterCondition()
    {
        $user = User::findOrFail(Auth::id());
        $settlements = static::query()->whereHas('debitOrder');

        if ($user->hasRole('Partner')) {
            $companyId = CompanyUser::where('user_id', $user->id)->value('company_id');

            return $companyId
                ? $settlements->whereHas('debitOrder.partner', fn ($query) => $query->where('partners.company_id', $companyId))
                : $settlements->whereRaw('1 = 0');
        }

        if ($user->hasRole('Company_Approver')) {
            $partnerIds = $user->partners
                ->where('authorize', 1)
                ->pluck('partner_id')
                ->values()
                ->all();

            return $partnerIds
                ? $settlements->whereHas('debitOrder', fn ($query) => $query->whereIn('debit_orders.partner_id', $partnerIds))
                : $settlements->whereRaw('1 = 0');
        }

        if ($user->hasRole('Company_User')) {
            return $settlements->whereHas('debitOrder', fn ($query) => $query->where('debit_orders.created_by', $user->id));
        }

        return $settlements;
    }

    public function batch()
    {
        return $this->belongsTo(CollectionBatch::class, 'collection_batch_id');
    }

    public function collectionLine()
    {
        return $this->belongsTo(CollectionDebitOrder::class, 'collection_debit_order_id');
    }

    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, 'debit_order_id');
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class, 'installment_id');
    }
}
