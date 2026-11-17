<?php

namespace App\Providers;

use App\Http\Middleware\EnsureStaffActive;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        // Livewire update requests re-run these route middleware, so a deactivated or demoted
        // account is stopped on its next click on every admin screen.
        Livewire::addPersistentMiddleware([
            EnsureStaffActive::class,
        ]);
    }
}
