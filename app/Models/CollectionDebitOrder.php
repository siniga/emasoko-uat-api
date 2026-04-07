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

    public function scopeForCycle($query, int $installmentId, $paymentAt)
    {
        $month = Carbon::parse($paymentAt)->format('m');
        $year = Carbon::parse($paymentAt)->format('Y');

        return $query->where('installment_id', $installmentId)
            ->whereMonth('payment_at', $month)
            ->whereYear('payment_at', $year);
    }

    public static function createForCycle(array $attributes): self
    {
        return static::firstOrCreate(
            [
                'installment_id' => $attributes['installment_id'],
                'payment_at' => Carbon::parse($attributes['payment_at'])->toDateString(),
            ],
            $attributes
        );
    }
}
