<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public static function createForCycle(array $attributes): self
    {
        $paymentAt = Carbon::parse($attributes['payment_at'])->toDateString();

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

        $line = static::firstOrNew([
            'installment_id' => $attributes['installment_id'],
            'payment_at' => $paymentAt,
        ]);

        $line->fill($attributes);
        $line->save();

        return $line;
    }
}
