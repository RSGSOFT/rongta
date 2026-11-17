<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Re-checks the staff account on EVERY request of an admin component, including Livewire
 * updates (which do not pass through the route's "staff" middleware). A deactivated account
 * or, on admin-only screens, a demoted one is stopped at its next click.
 *
 *   use RequiresStaff;
 *   protected bool $adminOnly = true;   // only on screens behind ->middleware('staff:admin')
 */
trait RequiresStaff
{
    public function bootRequiresStaff(): void
    {
        $user = Auth::guard('web')->user();

        abort_unless($user && $user->is_active, 403);

        if (($this->adminOnly ?? false) && ! $user->isAdmin()) {
            abort(403);
        }
    }
}
