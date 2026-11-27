<?php

namespace App\Livewire\Cabinet;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** POST /cabinet/logout (plain invokable action, kept next to the login component). */
class Logout
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Only the cabinet login is dropped: a staff member signed in to /admin in the same browser stays.
        Auth::guard('cabinet')->logout();
        $request->session()->regenerateToken();

        return redirect(lroute('cabinet.login'));
    }
}
