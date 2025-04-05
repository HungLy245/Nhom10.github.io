<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;

class User extends Authenticatable implements HasApiTokensContract
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('end_date', '>', now())
            ->where('status', 'active')
            ->latest();
    }

    public function borrows()
    {
        return $this->hasMany(Borrow::class);
    }

    public function reservations()
    {
        return $this->hasMany(BookReservation::class);
    }
} 