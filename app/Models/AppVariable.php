<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVariable extends Model
{
    use HasFactory;
    public $table = "app_variables";
    protected $guarded = [];
    public $timestamps = false;
}
