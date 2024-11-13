<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function userCreated()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }
}
