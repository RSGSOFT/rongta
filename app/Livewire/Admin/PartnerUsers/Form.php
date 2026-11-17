<?php

namespace App\Livewire\Admin\PartnerUsers;

use App\Livewire\Admin\Concerns\LogsActivity;
use App\Livewire\Admin\Concerns\RequiresStaff;
use App\Livewire\Admin\Concerns\RussianValidation;
use App\Livewire\Admin\Partners\Options;
use App\Models\Partner;
use App\Models\PartnerUser;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create (/admin/partner-users/create) and edit (/admin/partner-users/{partnerUser}/edit) a partner-area login.
 * Admins only. The password is typed by the admin and handed over personally; it is never shown again.
 */
class Form extends Component
{
    use LogsActivity, RequiresStaff, RussianValidation;

    protected bool $adminOnly = true;

    #[Locked]
    public ?int $partnerUserId = null;

    public string $name = '';

    public string $login = '';

    public string $type = 'reseller';

    public string $partner_id = '';

    public bool $is_active = true;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(?PartnerUser $partnerUser = null): void
    {
        if ($partnerUser?->exists) {
            $this->partnerUserId = $partnerUser->id;
            $this->name = $partnerUser->name;
            $this->login = $partnerUser->login;
            $this->type = $partnerUser->type;
            $this->partner_id = (string) ($partnerUser->partner_id ?? '');
            $this->is_active = (bool) $partnerUser->is_active;
        }
    }

    /** A partner of the other kind cannot stay linked. */
    public function updatedType(): void
    {
        $partner = $this->partner_id !== '' ? Partner::find($this->partner_id) : null;
        if ($partner && $partner->kind !== $this->type) {
            $this->partner_id = '';
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'login' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9][a-z0-9._\-]*$/', Rule::unique('partner_users', 'login')->ignore($this->partnerUserId)],
            'type' => ['required', Rule::in(PartnerUser::TYPES)],
            'partner_id' => ['nullable', 'integer', Rule::exists('partners', 'id'), function ($attribute, $value, $fail) {
                $partner = Partner::find($value);
                if ($partner && $partner->kind !== $this->type) {
                    $fail('Тип партнёра не совпадает с типом доступа: выберите партнёра того же типа.');
                }
            }],
            'is_active' => ['boolean'],
            'password' => [$this->partnerUserId ? 'nullable' : 'required', 'string', 'confirmed', Password::min(10)->letters()->numbers(), 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            ...$this->russianMessages(),
            'login.regex' => 'Логин: строчные латинские буквы, цифры, точка, дефис и подчёркивание; начинается с буквы или цифры.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'Имя',
            'login' => 'Логин',
            'type' => 'Тип доступа',
            'partner_id' => 'Партнёр',
            'password' => 'Пароль',
        ];
    }

    public function save()
    {
        $this->login = mb_strtolower(trim($this->login));
        $this->validate();

        $user = $this->partnerUserId ? PartnerUser::findOrFail($this->partnerUserId) : new PartnerUser;
        $user->fill([
            'name' => trim($this->name),
            'login' => $this->login,
            'type' => $this->type,
            'partner_id' => $this->partner_id !== '' ? (int) $this->partner_id : null,
            'is_active' => $this->is_active,
        ]);

        $passwordChanged = $this->password !== '';
        if ($passwordChanged) {
            $user->password = $this->password; // hashed by the model cast
        }
        if ($passwordChanged || ! $this->is_active) {
            $user->remember_token = null;
        }
        $user->save();

        // The typed password does not stay in the component after saving.
        $this->reset('password', 'password_confirmation');

        $this->logSaved($user, $passwordChanged && ! $user->wasRecentlyCreated ? ['password_changed' => true] : []);
        session()->flash('status', "Доступ «{$user->login}» сохранён.");

        return redirect()->route('admin.partner-users.index');
    }

    public function render(): View
    {
        $title = $this->partnerUserId ? 'Доступ: '.$this->login : 'Новый доступ партнёра';

        return view('livewire.admin.partner-users.form', [
            'title' => $title,
            'partners' => Partner::where('kind', $this->type)->orderBy('sort')->get()
                ->mapWithKeys(fn (Partner $p) => [$p->id => $p->tr('name', 'ru').($p->is_placeholder ? ' (образец)' : '')]),
            'types' => Options::KINDS,
        ])->layout('components.layouts.admin', ['title' => $title]);
    }
}
