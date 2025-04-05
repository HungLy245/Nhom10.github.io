<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookReservation;
use Carbon\Carbon;

class CancelExpiredReservations extends Command
{
    protected $signature = 'reservations:cancel-expired';
    protected $description = 'Cancel expired book reservations';

    public function handle()
    {
        BookReservation::where('status', 'available')
            ->where('available_until', '<', now())
            ->update(['status' => 'cancelled']);
    }
} 