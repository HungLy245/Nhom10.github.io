<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'start_date',
        'end_date',
        'amount_paid',
        'payment_method',
        'payment_status',
        'transaction_id',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    protected $with = ['package'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
} 