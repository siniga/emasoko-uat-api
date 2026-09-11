<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionIntegrityIssue extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'event_date' => 'date',
        'context' => 'array',
        'detected_at' => 'datetime',
        'last_detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }
}
