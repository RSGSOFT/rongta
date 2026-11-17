<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Partner area gate, alias "partner.active". Runs after "auth:partner". */
class EnsurePartnerActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $partnerUser = Auth::guard('partner')->user();

        if (! $partnerUser || ! $partnerUser->is_active) {
            Auth::guard('partner')->logout();

            return redirect(lroute('partner.login'));
        }

        return $next($request);
    }
}
