<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CollectionBatchPushAttempt extends Model
{
    protected $guarded = [];

    public $incrementing = false;

    protected $keyType = "string";

    protected static function booted(): void
    {
        static::creating(function (self $attempt) {
            $attempt->id ??= (string) Str::uuid();
        });
    }

    public function batch()
    {
        return $this->belongsTo(CollectionBatch::class, "collection_batch_id");
    }
}
