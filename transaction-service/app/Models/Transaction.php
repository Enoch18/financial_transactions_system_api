<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'type',
        'amount',
        'status'
    ];

    public function transfer()
    {
        return $this->hasOne(Transfer::class, 'transaction_id');
    }
}
