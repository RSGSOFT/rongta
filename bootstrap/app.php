<?php

use App\Http\Middleware\EnsurePartnerActive;
use App\Http\Middleware\EnsureStaffActive;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
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
            'partner.active' => EnsurePartnerActive::class,
        ]);

        // Staff go to the admin login; everyone else is a partner-area visitor.
        // The locale is read from the URL here because "auth" runs before the locale middleware.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return '/admin/login';
            }
            $locale = in_array($request->segment(1), Locales::prefixed(), true) ? $request->segment(1) : Locales::DEFAULT;

            return lroute('partner.login', [], $locale);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
