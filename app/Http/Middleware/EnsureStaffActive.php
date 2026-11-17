<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin panel gate, alias "staff". Runs after "auth".
 *   ->middleware('staff')        any active staff user
 *   ->middleware('staff:admin')  active staff with one of the listed roles
 */
class EnsureStaffActive
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login');
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
