<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CollectionDebitOrder extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, "debit_order_id");
    }
}
