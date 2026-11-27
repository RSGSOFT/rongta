<?php

namespace App\Livewire\Cabinet;

use App\Livewire\Cabinet\Concerns\RequiresCabinet;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * /cabinet: the partner's cabinet. Shows the dealer section when the partner sells scales and
 * the service section when it services them (the partner's "sales" and "service" offers).
 */
class Home extends Component
{
    use RequiresCabinet;

    public function render(): View
    {
        $account = $this->account();

        return view('livewire.cabinet.home', [
            'account' => $account,
            'company' => $account->partner->tr('name') ?: $account->company,
            'sells' => $account->sells(),
            'services' => $account->services(),
        ])->layout('components.layouts.site', ['title' => ui('cab.h'), 'noindex' => true]);
    }
}
