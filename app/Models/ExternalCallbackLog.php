<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalCallbackLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'response_body' => 'array',
        'last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function debitOrder()
    {
        return $this->belongsTo(DebitOrder::class, 'debit_order_id');
    }
}
