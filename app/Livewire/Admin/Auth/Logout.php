<?php

namespace App\Livewire\Admin\Auth;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** POST /admin/logout (plain invokable action, kept next to the login component). */
class Logout
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($user = Auth::guard('web')->user()) {
            ActivityLog::record('logout', $user);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
