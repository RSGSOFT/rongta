<?php

namespace App\Livewire\Pages\Partner;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Partner login: guard "partner", "login" + password, active accounts only.
 * Five failed attempts per login and address lock the form for a minute; twenty failed
 * attempts from one address, whatever the logins, lock that address for ten minutes.
 */
class Login extends Component
{
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public const IP_MAX_ATTEMPTS = 20;

    public const IP_DECAY_SECONDS = 600;

    public string $login = '';

    public string $password = '';

    public function mount(): void
    {
        $user = Auth::guard('partner')->user();
        if ($user && $user->is_active) {
            $this->redirect(lroute('partner'));
        }
    }

    public function authenticate(): void
    {
        $this->resetErrorBag();

        $login = trim($this->login);
        $key = 'partner-login:'.Str::transliterate(Str::lower($login)).'|'.request()->ip();

        $ipKey = 'partner-login-ip:'.request()->ip();

        foreach ([[$ipKey, self::IP_MAX_ATTEMPTS], [$key, self::MAX_ATTEMPTS]] as [$limiter, $max]) {
            if (RateLimiter::tooManyAttempts($limiter, $max)) {
                $this->password = '';
                $this->addError('login', ui('pa.err.throttle', ['s' => RateLimiter::availableIn($limiter)]));

                return;
            }
        }

        // One message for every failure: unknown login, wrong password and switched-off account alike.
        $ok = $login !== '' && $this->password !== '' && Auth::guard('partner')->attempt([
            'login' => $login,
            'password' => $this->password,
            'is_active' => true,
        ]);

        if (! $ok) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            RateLimiter::hit($ipKey, self::IP_DECAY_SECONDS);
            $this->password = '';
            $this->addError('login', ui('pa.err.login'));

            return;
        }

        // The per-address count is not cleared: one valid account must not reset it.
        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirect(lroute('partner'));
    }

    public function render(): View
    {
        return view('livewire.pages.partner.login')
            ->layout('components.layouts.site', ['title' => ui('pa.h')]);
    }
}
