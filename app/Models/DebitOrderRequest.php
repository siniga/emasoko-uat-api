<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebitOrderRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, "debit_order_id");
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
