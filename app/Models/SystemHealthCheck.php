<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthCheck extends Model
{
    use HasFactory;

    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DOWN = 'down';
    public const STATUS_UNKNOWN = 'unknown';

    protected $guarded = [];

    protected $casts = [
        'checked_at' => 'datetime',
        'response_time_ms' => 'integer',
        'port' => 'integer',
    ];
}
