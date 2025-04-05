<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });
    }

    public function boot()
    {
        Schema::defaultStringLength(191);
    }
} 