<?php

namespace App\Livewire\Cabinet\Concerns;

use App\Models\CabinetAccount;
use App\Models\Partner;
use Illuminate\Support\Facades\Auth;

/**
 * Re-checks the cabinet account on EVERY request of a cabinet component, including Livewire
 * updates (which do not pass through the route's "cabinet" middleware). A blocked or unlinked
 * account is stopped at its next click.
 *
 *   use RequiresCabinet;
 *   protected string $needs = 'sales';   // or 'service': the partner offer this section needs
 */
trait RequiresCabinet
{
    public function bootRequiresCabinet(): void
    {
        $account = Auth::guard('cabinet')->user();

        abort_unless($account instanceof CabinetAccount && $account->canEnter(), 403);

        match ($this->needs ?? null) {
            'sales' => abort_unless($account->sells(), 403),
            'service' => abort_unless($account->services(), 403),
            default => null,
        };
    }

    protected function account(): CabinetAccount
    {
        return Auth::guard('cabinet')->user();
    }

    protected function partner(): Partner
    {
        return $this->account()->partner;
    }
}
