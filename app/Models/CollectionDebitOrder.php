<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class CollectionDebitOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function batch()
    {
        return $this->belongsTo(CollectionBatch::class, "collection_batch_id");
    }

    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, "debit_order_id");
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class, "installment_id");
    }

    public function scopeForCycle($query, int $debitOrderId, $paymentAt)
    {
        $month = Carbon::parse($paymentAt)->format('m');
        $year = Carbon::parse($paymentAt)->format('Y');

        return $query->where('debit_order_id', $debitOrderId)
            ->whereMonth('payment_at', $month)
            ->whereYear('payment_at', $year);
    }

    public function scopeForAccountMonth($query, string $accountMonthUniqueKey)
    {
        return $query->where('account_month_unique_key', $accountMonthUniqueKey);
    }

    public static function createForCycle(array $attributes): self
    {
        $paymentAt = Carbon::parse($attributes['payment_at'])->toDateString();
        $paymentMonth = Carbon::parse($paymentAt)->format('Y-m');

        if (!empty($attributes['account_number'])) {
            $attributes['account_number'] = static::normalizeAccountNumber($attributes['account_number']);
            $attributes['payment_month'] = $attributes['payment_month'] ?? $paymentMonth;
            $attributes['account_month_unique_key'] = $attributes['account_month_unique_key']
                ?? static::accountMonthUniqueKey($attributes['account_number'], $paymentAt);

            $activeAccountMonth = static::query()
                ->forAccountMonth($attributes['account_month_unique_key'])
                ->first();

            if ($activeAccountMonth) {
                return $activeAccountMonth;
            }
        }

        $activeCycle = static::query()
            ->forCycle($attributes['debit_order_id'], $paymentAt)
            ->whereHas('batch', function ($query) {
                $query->where('deleted', 0);
            })
            ->first();

        if ($activeCycle) {
            return $activeCycle;
        }

        $attributes['payment_at'] = $paymentAt;
        $attributes['payment_month'] = $attributes['payment_month'] ?? $paymentMonth;

        $line = static::firstOrNew([
            'installment_id' => $attributes['installment_id'],
            'payment_at' => $paymentAt,
        ]);

        $line->fill($attributes);
        try {
            $line->save();
        } catch (QueryException $exception) {
            if (!empty($attributes['account_month_unique_key'])) {
                $existingAccountMonth = static::query()
                    ->forAccountMonth($attributes['account_month_unique_key'])
                    ->first();

                if ($existingAccountMonth) {
                    return $existingAccountMonth;
                }
            }

            throw $exception;
        }

        return $line;
    }

    public static function accountMonthUniqueKey(string $accountNumber, $paymentAt): string
    {
        return static::normalizeAccountNumber($accountNumber) . '|' . Carbon::parse($paymentAt)->format('Y-m');
    }

    public static function normalizeAccountNumber(?string $accountNumber): string
    {
        return preg_replace('/\s+/', '', trim((string) $accountNumber)) ?? '';
    }
}
