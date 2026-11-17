<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sets the app locale from the route group: ->middleware('locale:uz'). */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = Locales::DEFAULT): Response
    {
        app()->setLocale(Locales::isSupported($locale) ? $locale : Locales::DEFAULT);

        return $next($request);
    }
}
