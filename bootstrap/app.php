<?php

use App\Http\Middleware\DevAutoLogin;
use App\Http\Middleware\EnsureStaffActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
            'staff' => EnsureStaffActive::class,
        ]);

        // Only the admin panel needs a login: guests go to the admin login page.
        $middleware->redirectGuestsTo('/admin/login');

        // The local passwordless admin login must run before "auth" looks for a user.
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: DevAutoLogin::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
