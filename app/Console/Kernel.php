<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('subscriptions:check-expiring')->daily();
        $schedule->command('reservations:cancel-expired')->hourly();
        $schedule->command('books:process-quantity-changes')
                 ->everyFiveSeconds()
                 ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
} 