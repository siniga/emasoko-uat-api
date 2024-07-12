<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CollectionBatch extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $incrementing = false;
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function getCsvsAttribute()
    {
        $debits = $this->debits;
        $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO; AMOUNT\n";
        foreach ($debits as $debit) {
            $debitOrder = $debit->debitOrder;
            $customerAccount = $debitOrder->customerAccount;
            $csv .= "{$debit->id};16;NMB;{$customerAccount->account_name};{$customerAccount->account_name};{$customerAccount->account_number};{$debitOrder->installment_amount}\n";
        }
        return $csv;
    }

    public function debits()
    {
        return $this->hasMany(CollectionDebitOrder::class, "collection_batch_id");
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, "createdBy");
    }

    public function verifiedUser()
    {
        return $this->belongsTo(User::class, "verifiedBy");
    }
}
