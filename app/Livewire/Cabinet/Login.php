<?php

namespace App\Livewire\Cabinet;

use App\Models\CabinetAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Partner cabinet login: email + password. Only an account that an admin has approved and
 * linked to a partner gets in. 5 tries per minute per email and address, and 20 failed tries
 * per ten minutes from one address whatever the emails.
 */
class Login extends Component
{
    public const MAX_ATTEMPTS = 5;

    public const IP_MAX_ATTEMPTS = 20;

    public const IP_DECAY_SECONDS = 600;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        if (Auth::guard('cabinet')->user()?->canEnter()) {
            return redirect(lroute('cabinet'));
        }
    }

    public function login()
    {
        $this->resetErrorBag();
        $email = Str::lower(trim($this->email));

        if ($email === '' || $this->password === '' || mb_strlen($email) > 255 || mb_strlen($this->password) > 255) {
            return $this->refuse(ui('cab.login.err'));
        }

        $key = 'cabinet-login|'.Str::transliterate($email).'|'.request()->ip();
        $ipKey = 'cabinet-login-ip|'.request()->ip();

        foreach ([[$ipKey, self::IP_MAX_ATTEMPTS], [$key, self::MAX_ATTEMPTS]] as [$limiter, $max]) {
            if (RateLimiter::tooManyAttempts($limiter, $max)) {
                return $this->refuse(ui('cab.login.throttle', ['s' => RateLimiter::availableIn($limiter)]));
            }
        }

        $account = CabinetAccount::where('email', $email)->first();

        if (! $account || ! Hash::check($this->password, $account->password)) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, self::IP_DECAY_SECONDS);

            return $this->refuse(ui('cab.login.err'));
        }

        // The status is told only to someone who knows the password.
        if (! $account->canEnter()) {
            return $this->refuse(ui($account->status === 'blocked' ? 'cab.login.blocked' : 'cab.login.pending'));
        }

        RateLimiter::clear($key);
        Auth::guard('cabinet')->login($account, $this->remember);
        session()->regenerate();

        return redirect(lroute('cabinet'));
    }

    private function refuse(string $message): void
    {
        $this->reset('password');
        $this->addError('email', $message);
        $this->js("document.querySelector('#cab-email')?.focus()");
    }

    public function render(): View
    {
        return view('livewire.cabinet.login')->layout('components.layouts.site', [
            'title' => ui('cab.login.h'),
            'description' => ui('cab.login.lead'),
            'noindex' => true,
        ]);
    }
}
