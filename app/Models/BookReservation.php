<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReservation extends Model
{
    protected $table = 'book_reservations';

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'fulfilled_at',
        'available_until'
    ];

    protected $dates = [
        'fulfilled_at',
        'available_until'
    ];

    protected $casts = [
        'fulfilled_at' => 'datetime',
        'available_until' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
} 