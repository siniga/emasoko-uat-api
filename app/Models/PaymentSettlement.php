<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSettlement extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_PARTIAL = 'PARTIAL';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_FUNDSCHECK = 'FUNDSCHECK';
    public const STATUS_UNALLOCATED = 'UNALLOCATED';

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
