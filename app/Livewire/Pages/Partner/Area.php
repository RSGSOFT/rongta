<?php

namespace App\Livewire\Pages\Partner;

use App\Models\PartnerUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Partner area. The view follows the account type: a reseller sees the reseller view,
 * an integrator the integrator view, and there is no way to switch. Commercial terms
 * are "on request" for both; no prices are shown anywhere.
 */
class Area extends Component
{
    /** The signed-in partner login (guard "partner"); route middleware guarantees it exists and is active. */
    public function partnerUser(): PartnerUser
    {
        return Auth::guard('partner')->user();
    }

    public function logout(): void
    {
        Auth::guard('partner')->logout();
        session()->regenerate();
        session()->regenerateToken();

        $this->redirect(lroute('partner.login'));
    }

    public function render(): View
    {
        $user = $this->partnerUser();

        return view('livewire.pages.partner.area', [
            'user' => $user,
            'isIntegrator' => $user->isIntegrator(),
        ])->layout('components.layouts.site', ['title' => ui('pa.h')]);
    }
}
