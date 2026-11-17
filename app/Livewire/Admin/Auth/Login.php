<?php

namespace App\Livewire\Admin\Auth;

use App\Livewire\Admin\Concerns\RussianValidation;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Staff login: email + password, active accounts only. 5 tries per minute per email and IP,
 * and 20 failed tries per ten minutes from one IP whatever the emails.
 */
class Login extends Component
{
    use RussianValidation;

    public const MAX_ATTEMPTS = 5;

    public const IP_MAX_ATTEMPTS = 20;

    public const IP_DECAY_SECONDS = 600;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->is_active) {
            return redirect()->route('admin.dashboard');
        }
    }

    public function login()
    {
        $this->validate(
            ['email' => ['required', 'string', 'email', 'max:255'], 'password' => ['required', 'string', 'max:255']],
            [],
            ['email' => 'Эл. почта', 'password' => 'Пароль'],
        );

        $key = $this->throttleKey();

        $ipKey = 'admin-login-ip|'.request()->ip();

        foreach ([[$ipKey, self::IP_MAX_ATTEMPTS], [$key, self::MAX_ATTEMPTS]] as [$limiter, $max]) {
            if (RateLimiter::tooManyAttempts($limiter, $max)) {
                $this->reset('password');

                throw ValidationException::withMessages([
                    'email' => 'Слишком много попыток входа. Повторите через '.RateLimiter::availableIn($limiter).' с.',
                ]);
            }
        }

        // "is_active" is part of the credentials: a deactivated account fails like a wrong password.
        $ok = Auth::guard('web')->attempt(
            ['email' => $this->email, 'password' => $this->password, 'is_active' => true],
            $this->remember,
        );

        if (! $ok) {
            RateLimiter::hit($key, 60);
            RateLimiter::hit($ipKey, self::IP_DECAY_SECONDS);
            $this->reset('password');

            throw ValidationException::withMessages([
                'email' => 'Неверная эл. почта или пароль, либо учётная запись отключена.',
            ]);
        }

        // The per-address count is not cleared: one valid account must not reset it.
        RateLimiter::clear($key);
        session()->regenerate();
        ActivityLog::record('login', Auth::guard('web')->user());

        return redirect()->intended(route('admin.dashboard'));
    }

    protected function throttleKey(): string
    {
        return 'admin-login|'.Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function render(): View
    {
        return view('livewire.admin.auth.login')
            ->layout('components.layouts.admin', ['title' => 'Вход', 'bare' => true]);
    }
}
