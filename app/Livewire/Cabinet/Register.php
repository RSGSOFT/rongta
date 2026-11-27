<?php

namespace App\Livewire\Cabinet;

use App\Livewire\Pages\Partners;
use App\Models\CabinetAccount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Partner cabinet registration. The account is saved as "pending": it cannot log in until an
 * admin approves it and links it to a partner (admin screen "Кабинеты партнёров").
 */
class Register extends Component
{
    /** How many registrations one address may send per hour. */
    public const REGISTRATIONS_PER_HOUR = 5;

    public string $company = '';

    public string $contact_name = '';

    public string $phone = '+998 ';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $sales = false;

    public bool $service = false;

    /** Honeypot: hidden from people, so only a bot fills it in. */
    public string $website = '';

    /** The email of the registration just sent; empty while the form is shown. */
    public string $sentEmail = '';

    public function register(): void
    {
        // A filled honeypot means a bot: answer as if it worked, save nothing.
        if (trim($this->website) !== '') {
            $this->sentEmail = 'ok';

            return;
        }

        $data = [
            'company' => trim($this->company),
            'contact_name' => trim($this->contact_name),
            'phone' => Partners::cleanPhone($this->phone),
            'email' => Str::lower(trim($this->email)),
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'roles' => $this->sales || $this->service,
        ];

        $validator = Validator::make($data, [
            'company' => ['required', 'string', 'min:2', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255', 'regex:'.Partners::NAME_PATTERN],
            'phone' => ['required', 'regex:/^\+998\d{9}$/'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', 'unique:cabinet_accounts,email'],
            'password' => ['bail', 'required', 'string', 'min:8', 'max:255', 'confirmed'],
            'roles' => ['accepted'],
        ], [
            'company.*' => ui('err.company'),
            'contact_name.*' => ui('err.name'),
            'phone.*' => ui('err.phone'),
            'email.unique' => ui('cab.reg.err.taken'),
            'email.*' => ui('cab.reg.err.email'),
            'password.confirmed' => ui('cab.reg.err.confirm'),
            'password.*' => ui('cab.reg.err.password'),
            'roles.*' => ui('cab.reg.err.roles'),
        ]);

        $this->resetErrorBag();

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            $this->reset('password', 'password_confirmation');
            $this->focusOn('#cab-register [aria-invalid="true"]');

            return;
        }

        $limiterKey = 'cabinet-register:'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::REGISTRATIONS_PER_HOUR)) {
            $this->addError('form', ui('cab.reg.err.throttle'));
            $this->focusOn('#cab-form-error');

            return;
        }
        RateLimiter::hit($limiterKey, 3600);

        CabinetAccount::create([
            'company' => $data['company'],
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => $data['password'],
            'wants_sales' => $this->sales,
            'wants_service' => $this->service,
            'locale' => app()->getLocale(),
            'status' => 'pending',
        ]);

        $this->reset();
        $this->sentEmail = $data['email'];
        $this->focusOn('#cab-done');
    }

    /** Move keyboard focus once the DOM has been updated (first invalid field, confirmation). */
    protected function focusOn(string $selector): void
    {
        $this->js('setTimeout(() => document.querySelector('.json_encode($selector).')?.focus(), 60)');
    }

    public function render(): View
    {
        return view('livewire.cabinet.register')->layout('components.layouts.site', [
            'title' => ui('cab.reg.h'),
            'description' => ui('cab.reg.lead'),
            'noindex' => true,
        ]);
    }
}
