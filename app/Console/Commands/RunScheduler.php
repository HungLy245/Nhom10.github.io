<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduler extends Command
{
    protected $signature = 'schedule:daemon';
    protected $description = 'Run the scheduler daemon';

    public function handle()
    {
        $this->info('Starting scheduler daemon...');
        
        while (true) {
            try {
                $this->info('Processing book quantity changes...');
                $this->call('books:process-quantity-changes');
                $this->info('Waiting 5 seconds...');
                sleep(5);
            } catch (\Exception $e) {
                Log::error('Error in scheduler daemon: ' . $e->getMessage());
                $this->error('Error occurred: ' . $e->getMessage());
            }
        }
    }
} 