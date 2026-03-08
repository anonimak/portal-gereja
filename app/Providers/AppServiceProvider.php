<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Church;
use App\Observers\ChurchObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Church observer to auto-seed default funds and categories
        Church::observe(ChurchObserver::class);
    }
}
