<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = true;

    function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
