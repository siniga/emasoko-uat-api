<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Wallet extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'partner_id', 'currency', 'status', 'created_at', 'updated_at'];

    protected $casts = ['partner_id' => 'integer'];

    public static function forPartner(int $partnerId): self
    {
        $timestamp = now('UTC')->format('Y-m-d H:i:s');

        // The unique partner_id constraint protects simultaneous provisioning requests.
        return static::firstOrCreate(['partner_id' => $partnerId], [
            'id' => (string) Str::uuid(),
            'currency' => 'TZS',
            'status' => 'ACTIVE',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
