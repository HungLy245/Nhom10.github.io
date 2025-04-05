<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'description',
        'duration',
        'price',
        'max_borrows',
        'borrow_duration',
        'can_reserve',
        'priority_support'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'can_reserve' => 'boolean',
        'priority_support' => 'boolean'
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
} 