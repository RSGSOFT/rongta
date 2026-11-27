<?php

namespace App\Http\Middleware;

use App\Models\CabinetAccount;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Partner cabinet gate, alias "cabinet". A guest, or an account that is no longer approved and
 * linked to a partner, goes to the cabinet login page in the current language.
 */
class EnsureCabinetAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = Auth::guard('cabinet')->user();

        if (! $account instanceof CabinetAccount || ! $account->canEnter()) {
            // Only the cabinet login is dropped: a staff member signed in to /admin in the same browser stays.
            Auth::guard('cabinet')->logout();

            return redirect(lroute('cabinet.login'));
        }

        return $next($request);
    }
}
