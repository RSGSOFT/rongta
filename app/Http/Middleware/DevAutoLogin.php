<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Developer convenience: on a local machine, a guest opening /admin is signed in as the first
 * active admin, so no password is needed. It is a real session login, so roles, the per-click
 * staff check and the activity log behave as normal.
 *
 * Runs only when BOTH hold: APP_ENV=local and ADMIN_AUTO_LOGIN=true. On any other environment
 * (production, testing) it does nothing, even if the flag is copied there by mistake.
 */
class DevAutoLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (self::isEnabled() && Auth::guard('web')->guest()) {
            $admin = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            if ($admin) {
                Auth::guard('web')->login($admin);
                $request->session()->regenerate();
            }
        }

        return $next($request);
    }

    public static function isEnabled(): bool
    {
        return app()->environment('local') && (bool) config('admin.auto_login');
    }
}
