<?php

namespace App\Livewire\Pages;

use App\Models\SerialNumber;
use App\Support\Trust;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Serial number check: a visitor types the serial number of a scale and sees its model and
 * warranty period. Nothing about the dealer, the batch or the service history is shown.
 */
class SerialCheck extends Component
{
    /** How many checks one address may make per hour (the numbers must not be guessable in bulk). */
    public const CHECKS_PER_HOUR = 20;

    public string $serial = '';

    /** The number that was looked up, normalised; empty before the first check. */
    #[Locked]
    public string $checked = '';

    #[Locked]
    public ?int $foundId = null;

    public function check(): void
    {
        $this->resetErrorBag();
        $this->checked = '';
        $this->foundId = null;

        $serial = SerialNumber::normalize(mb_substr($this->serial, 0, 64));
        if ($serial === '') {
            $this->addError('serial', ui('sn.err.required'));

            return;
        }

        $limiterKey = 'serial-check:'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::CHECKS_PER_HOUR)) {
            $this->addError('serial', ui('sn.err.throttle'));

            return;
        }
        RateLimiter::hit($limiterKey, 3600);

        $this->checked = $serial;
        $this->foundId = SerialNumber::where('serial', $serial)->value('id');
        $this->js("document.querySelector('#sn-result')?.focus()");
    }

    public function render(): View
    {
        $found = $this->foundId ? SerialNumber::with(['scaleModel', 'movements'])->find($this->foundId) : null;
        $trust = Trust::all();

        return view('livewire.pages.serial-check', [
            'found' => $found,
            'months' => SerialNumber::warrantyMonths(),
            'terms' => trv($trust['service']['text']),
        ])->layout('components.layouts.site', [
            'title' => ui('sn.h'),
            'description' => ui('sn.lead'),
        ]);
    }
}
